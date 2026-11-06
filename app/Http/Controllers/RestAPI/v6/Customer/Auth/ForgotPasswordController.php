<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\UserType;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Auth\ForgotPasswordRequest;
use App\Http\Requests\Customer\Auth\PasswordResetRequest;
use App\Http\Requests\Customer\Auth\ResetTokenVerificationRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ForgotPasswordController extends BaseApiController
{
    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Send a customer password-reset OTP',
        tag: 'Auth (Customer)',
        status: [404, 422, 429, 503],
        auth: false,
        guard: 'api',
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($customer === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $resendWait = $this->passwordResetService->secondsUntilResendAllowed(
            identity: $identity,
            userType: UserType::CUSTOMER,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            return $this->authThrottled(
                config: config('response.otp_resend_wait'),
                errorCode: AuthErrorCode::OTP_RESEND_WAIT,
                retryAfter: $resendWait,
            );
        }

        if ($customer->phone === null) {
            return $this->authError(
                config: config('response.account_phone_missing'),
                errorCode: AuthErrorCode::NOTIFICATION_CHANNEL_UNAVAILABLE,
            );
        }

        $otp = $this->passwordResetService->issueOtp(identity: $identity, userType: UserType::CUSTOMER);

        event(new OtpSmsEvent(phone: $customer->phone, otp: $otp));

        return $this->responseFormatter(
            config: config('response.otp_sent'),
            content: [
                'channel' => LoginSetupService::CHANNEL_PHONE,
                'resend_after' => $this->resendSeconds(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Verify a customer password-reset OTP',
        tag: 'Auth (Customer)',
        status: [422, 429],
        auth: false,
        guard: 'api',
    )]
    public function verifyToken(ResetTokenVerificationRequest $request): JsonResponse
    {
        $failure = $this->otpFailure(identity: $request['identity'], otp: $request['reset_token']);

        return $failure ?? $this->responseFormatter(
            config: config('response.otp_verified'),
        );
    }

    #[ApiDoc(
        summary: 'Set a new customer password',
        tag: 'Auth (Customer)',
        status: [404, 422, 429],
        auth: false,
        guard: 'api',
    )]
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $failure = $this->otpFailure(identity: $identity, otp: $request['otp']);

        if ($failure !== null) {
            return $failure;
        }

        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        if ($customer === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $this->customerAuthService->updatePassword(id: $customer->id, password: $request['password']);
        $this->passwordResetService->deleteByIdentity(identity: $identity, userType: UserType::CUSTOMER);

        return $this->responseFormatter(config: config('response.password_changed'));
    }

    private function otpFailure(string $identity, string $otp): ?JsonResponse
    {
        $blocked = $this->passwordResetService->secondsUntilUnblocked(
            identity: $identity,
            userType: UserType::CUSTOMER,
            blockSeconds: $this->blockSeconds(),
        );

        if ($blocked > 0) {
            return $this->authThrottled(
                config: config('response.otp_attempts_blocked'),
                errorCode: AuthErrorCode::OTP_BLOCKED,
                retryAfter: $blocked,
            );
        }

        $reason = $this->passwordResetService->verifyOtp(
            identity: $identity,
            userType: UserType::CUSTOMER,
            token: $otp,
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        if ($reason === null) {
            return null;
        }

        $isExpired = $reason === PasswordResetService::REASON_OTP_EXPIRED;

        return $this->authError(
            errorCode: $isExpired ? AuthErrorCode::OTP_EXPIRED : AuthErrorCode::OTP_INVALID,
            config: config($isExpired ? 'response.otp_expired' : 'response.otp_invalid'),
        );
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
