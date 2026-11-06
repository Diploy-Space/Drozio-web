<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\OtpPurpose;
use App\Events\EmailVerificationEvent;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Auth\OtpSendRequest;
use App\Http\Requests\Customer\Auth\OtpVerifyRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OtpVerificationController extends BaseApiController
{
    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly CustomerAuthService $customerAuthService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Send a login or verification OTP',
        description: 'This is the OTP login send step: post purpose=login to sign in with a code instead of a password, which requires OTP login to be enabled in the panel. Omit purpose (or send verification) to confirm a phone or email on an existing account.',
        tag: 'Auth (Customer)',
        status: [403, 404, 422, 429, 503],
        auth: false,
        guard: 'api',
    )]
    public function sendOtp(OtpSendRequest $request): JsonResponse
    {
        $blocked = $this->loginOptionRefusal(purpose: $request['purpose']);

        if ($blocked !== null) {
            return $blocked;
        }

        $identity = $request['identity'];
        $isRegistration = $request['purpose'] === OtpPurpose::REGISTRATION;
        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($isRegistration && $customer !== null) {
            return $this->identityReservedError(identity: $identity);
        }

        if (! $isRegistration && $customer === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $resendWait = $this->verificationService->secondsUntilResendAllowed(
            identity: $identity,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            return $this->authThrottled(
                config: config('response.otp_resend_wait'),
                errorCode: AuthErrorCode::OTP_RESEND_WAIT,
                retryAfter: $resendWait,
            );
        }

        $channel = $this->channelFor(identity: $identity);
        $otp = $this->verificationService->issueOtp(identity: $identity);

        if (! $this->dispatchOtp(channel: $channel, customer: $customer, identity: $identity, otp: $otp)) {
            $this->verificationService->deleteByIdentity(identity: $identity);

            return $this->authError(
                config: config('response.otp_send_failed'),
                errorCode: AuthErrorCode::NOTIFICATION_CHANNEL_UNAVAILABLE,
            );
        }

        return $this->responseFormatter(
            config: config('response.otp_sent'),
            content: [
                'channel' => $channel,
                'resend_after' => $this->resendSeconds(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Verify a login or verification OTP',
        description: 'This is the OTP login verify step: a correct code returns the access token, so purpose=login completes a passwordless sign-in. It also marks the phone or email verified.',
        tag: 'Auth (Customer)',
        status: [403, 404, 422, 429],
        auth: false,
        guard: 'api',
    )]
    public function verifyOtp(OtpVerifyRequest $request): JsonResponse
    {
        $blocked = $this->loginOptionRefusal(purpose: $request['purpose']);

        if ($blocked !== null) {
            return $blocked;
        }

        $identity = $request['identity'];

        $blocked = $this->verificationService->secondsUntilUnblocked(
            identity: $identity,
            blockSeconds: $this->blockSeconds(),
        );

        if ($blocked > 0) {
            return $this->authThrottled(
                config: config('response.otp_attempts_blocked'),
                errorCode: AuthErrorCode::OTP_BLOCKED,
                retryAfter: $blocked,
            );
        }

        $reason = $this->verificationService->verifyOtp(
            identity: $identity,
            token: $request['otp'],
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        if ($reason !== null) {
            $isExpired = $reason === VerificationService::REASON_OTP_EXPIRED;

            return $this->authError(
                errorCode: $isExpired ? AuthErrorCode::OTP_EXPIRED : AuthErrorCode::OTP_INVALID,
                config: config($isExpired ? 'response.otp_expired' : 'response.otp_invalid'),
            );
        }

        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($customer === null) {
            if ($request['purpose'] !== OtpPurpose::REGISTRATION) {
                return $this->authError(
                    config: config('response.account_not_found'),
                    errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
                );
            }

            return $this->responseFormatter(
                config: config('response.registration_required'),
                content: [
                    'temporary_token' => $this->verificationService->issueRegistrationToken(identity: $identity),
                    'registration_required' => true,
                ],
            );
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        $this->customerAuthService->markVerified(id: $customer->id, channel: $this->channelFor(identity: $identity));
        $this->verificationService->deleteByIdentity(identity: $identity);

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer->refresh()), 'token_type' => 'Bearer'],
        );
    }

    private function loginOptionRefusal(?string $purpose): ?JsonResponse
    {
        if ($purpose !== OtpPurpose::LOGIN) {
            return null;
        }

        if ($this->loginSetupService->isLoginOptionEnabled(option: LoginSetupService::LOGIN_OPTION_OTP)) {
            return null;
        }

        return $this->authError(
            config: config('response.otp_login_disabled'),
            errorCode: AuthErrorCode::LOGIN_METHOD_DISABLED,
        );
    }

    private function identityReservedError(string $identity): JsonResponse
    {
        $isEmail = $this->channelFor(identity: $identity) === LoginSetupService::CHANNEL_EMAIL;

        return $this->authError(
            errorCode: $isEmail ? AuthErrorCode::ACCOUNT_EMAIL_RESERVED : AuthErrorCode::ACCOUNT_PHONE_RESERVED,
            config: config($isEmail ? 'response.email_already_registered' : 'response.phone_already_registered'),
        );
    }

    private function channelFor(string $identity): string
    {
        return filter_var($identity, FILTER_VALIDATE_EMAIL) !== false
            ? LoginSetupService::CHANNEL_EMAIL
            : LoginSetupService::CHANNEL_PHONE;
    }

    private function dispatchOtp(string $channel, mixed $customer, string $identity, string $otp): bool
    {
        if ($channel === LoginSetupService::CHANNEL_PHONE) {
            event(new OtpSmsEvent(phone: $identity, otp: $otp));

            return true;
        }

        if (! $this->isMailConfigured()) {
            return false;
        }

        event(new EmailVerificationEvent(email: $identity, data: [
            'userType' => 'customer',
            'templateName' => 'registration-verification',
            'userName' => $customer?->f_name ?? '',
            'subject' => translate('Email verification'),
            'title' => translate('Email verification'),
            'verificationCode' => $otp,
        ]));

        return true;
    }

    private function isMailConfigured(): bool
    {
        if (($this->businessSettingService->getValueByType(type: 'mail_config')['status'] ?? 0) == 1) {
            return true;
        }

        return ($this->businessSettingService->getValueByType(type: 'mail_config_sendgrid')['status'] ?? 0) == 1;
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_otp_hit', default: self::DEFAULT_MAX_OTP_HIT);
    }

    private function resendSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'otp_resend_time', default: self::DEFAULT_RESEND_SECONDS);
    }

    private function blockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'temporary_block_time', default: self::DEFAULT_BLOCK_SECONDS);
    }
}
