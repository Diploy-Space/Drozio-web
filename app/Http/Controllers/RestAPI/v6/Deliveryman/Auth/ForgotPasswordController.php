<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\UserType;
use App\Events\DeliverymanPasswordResetEvent;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Auth\ForgotPasswordRequest;
use App\Http\Requests\Deliveryman\Auth\OtpVerificationRequest;
use App\Http\Requests\Deliveryman\Auth\PasswordResetRequest;
use App\Services\New\Auth\DeliverymanAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\BusinessSettingService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ForgotPasswordController extends BaseApiController
{
    private const CHANNEL_PHONE = 'phone';

    private const CHANNEL_EMAIL = 'email';

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly DeliverymanAuthService $deliverymanAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Send a delivery-man password-reset OTP',
        tag: 'Auth (Delivery-man)',
        status: [404, 422, 429, 503],
        auth: false,
        guard: 'deliveryman',
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $deliveryMan = $this->deliverymanAuthService->findByIdentity(identity: $identity);

        if ($deliveryMan === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $resendWait = $this->passwordResetService->secondsUntilResendAllowed(
            identity: $identity,
            userType: UserType::DELIVERY_MAN,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            return $this->authThrottled(
                config: config('response.otp_resend_wait'),
                errorCode: AuthErrorCode::OTP_RESEND_WAIT,
                retryAfter: $resendWait,
            );
        }

        $channel = $this->resetChannel();
        $otp = $this->passwordResetService->issueOtp(identity: $identity, userType: UserType::DELIVERY_MAN);

        if (! $this->dispatchOtp(channel: $channel, deliveryMan: $deliveryMan, otp: $otp)) {
            $this->passwordResetService->deleteByIdentity(identity: $identity, userType: UserType::DELIVERY_MAN);

            return $this->authError(
                config: config('response.otp_send_failed'),
                errorCode: AuthErrorCode::NOTIFICATION_CHANNEL_UNAVAILABLE,
            );
        }

        return $this->responseFormatter(
            config: config('response.otp_sent'),
            content: ['channel' => $channel,
                'resend_after' => $this->resendSeconds(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Verify a delivery-man password-reset OTP',
        tag: 'Auth (Delivery-man)',
        status: [422, 429],
        auth: false,
        guard: 'deliveryman',
    )]
    public function verifyOtp(OtpVerificationRequest $request): JsonResponse
    {
        $identity = $request['identity'];

        $blocked = $this->passwordResetService->secondsUntilUnblocked(
            identity: $identity,
            userType: UserType::DELIVERY_MAN,
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
            userType: UserType::DELIVERY_MAN,
            token: $request['otp'],
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        if ($reason !== null) {
            return $this->authError(
                errorCode: $reason === PasswordResetService::REASON_OTP_EXPIRED ? AuthErrorCode::OTP_EXPIRED : AuthErrorCode::OTP_INVALID,
                config: config($reason === PasswordResetService::REASON_OTP_EXPIRED ? 'response.otp_expired' : 'response.otp_invalid'),
            );
        }

        return $this->responseFormatter(config: config('response.otp_verified'));
    }

    #[ApiDoc(
        summary: 'Set a new delivery-man password',
        tag: 'Auth (Delivery-man)',
        status: [404, 422],
        auth: false,
        guard: 'deliveryman',
    )]
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $identity = $request['identity'];

        $reason = $this->passwordResetService->verifyOtp(
            identity: $identity,
            userType: UserType::DELIVERY_MAN,
            token: $request['otp'],
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        if ($reason !== null) {
            return $this->authError(
                errorCode: $reason === PasswordResetService::REASON_OTP_EXPIRED ? AuthErrorCode::OTP_EXPIRED : AuthErrorCode::OTP_INVALID,
                config: config($reason === PasswordResetService::REASON_OTP_EXPIRED ? 'response.otp_expired' : 'response.otp_invalid'),
            );
        }

        $deliveryMan = $this->deliverymanAuthService->findByIdentity(identity: $identity);

        if ($deliveryMan === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $this->deliverymanAuthService->updatePassword(id: $deliveryMan->id, password: $request['password']);
        $this->deliverymanAuthService->revokeAccessToken(account: $deliveryMan);
        $this->passwordResetService->deleteByIdentity(identity: $identity, userType: UserType::DELIVERY_MAN);

        return $this->responseFormatter(config: config('response.password_changed'));
    }

    private function dispatchOtp(string $channel, mixed $deliveryMan, string $otp): bool
    {
        if ($channel === self::CHANNEL_EMAIL) {
            if ($deliveryMan->email === null || ! $this->isMailConfigured()) {
                return false;
            }

            event(new DeliverymanPasswordResetEvent(email: $deliveryMan->email, data: [
                'userType' => 'delivery-man',
                'templateName' => 'reset-password-verification',
                'deliveryManName' => $deliveryMan->f_name,
                'subject' => translate('OTP Verification for password reset'),
                'title' => translate('OTP Verification'),
                'verificationCode' => $otp,
            ]));

            return true;
        }

        event(new OtpSmsEvent(
            phone: $deliveryMan->country_code ? '+'.$deliveryMan->country_code.$deliveryMan->phone : $deliveryMan->phone,
            otp: $otp,
        ));

        return true;
    }

    private function resetChannel(): string
    {
        return $this->businessSettingService->getValueByType(
            type: 'deliveryman_forgot_password_method',
            default: self::CHANNEL_PHONE,
        ) === self::CHANNEL_EMAIL ? self::CHANNEL_EMAIL : self::CHANNEL_PHONE;
    }

    private function isMailConfigured(): bool
    {
        $mailConfig = $this->businessSettingService->getValueByType(type: 'mail_config');

        if (($mailConfig['status'] ?? 0) == 1) {
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
