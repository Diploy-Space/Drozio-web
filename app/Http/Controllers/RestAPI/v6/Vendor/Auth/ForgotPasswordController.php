<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\UserType;
use App\Events\OtpSmsEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Auth\OtpVerificationRequest;
use App\Http\Requests\Vendor\Auth\PasswordResetRequest;
use App\Http\Requests\Vendor\Auth\PasswordResetSubmitRequest;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ForgotPasswordController extends BaseApiController
{
    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_RESEND_SECONDS = 60;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Send a vendor password-reset OTP',
        description: 'The OTP is sent to the phone number on the vendor account. The panel setting vendor_forgot_password_method decides the channel, and only the phone channel carries an OTP.',
        tag: 'Auth (Seller)',
        status: [404, 422, 429, 503],
        auth: false,
        guard: 'seller',
    )]
    public function forgotPassword(PasswordResetRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $seller = $this->vendorAuthService->findByIdentity(identity: $identity);

        if ($seller === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        if ($seller->phone === null) {
            return $this->authError(
                config: config('response.account_phone_missing'),
                errorCode: AuthErrorCode::NOTIFICATION_CHANNEL_UNAVAILABLE,
            );
        }

        $resendWait = $this->passwordResetService->secondsUntilResendAllowed(
            identity: $identity,
            userType: UserType::SELLER,
            resendSeconds: $this->resendSeconds(),
        );

        if ($resendWait > 0) {
            return $this->authThrottled(
                config: config('response.otp_resend_wait'),
                errorCode: AuthErrorCode::OTP_RESEND_WAIT,
                retryAfter: $resendWait,
            );
        }

        $otp = $this->passwordResetService->issueOtp(identity: $identity, userType: UserType::SELLER);

        event(new OtpSmsEvent(phone: $seller->phone, otp: $otp));

        return $this->responseFormatter(
            config: config('response.otp_sent'),
            content: ['channel' => 'phone',
                'resend_after' => $this->resendSeconds(),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Verify a vendor password-reset OTP',
        tag: 'Auth (Seller)',
        status: [422, 429],
        auth: false,
        guard: 'seller',
    )]
    public function verifyOtp(OtpVerificationRequest $request): JsonResponse
    {
        $failure = $this->otpFailure(identity: $request['identity'], otp: $request['otp']);

        return $failure ?? $this->responseFormatter(config: config('response.otp_verified'));
    }

    #[ApiDoc(
        summary: 'Set a new vendor password',
        tag: 'Auth (Seller)',
        status: [404, 422, 429],
        auth: false,
        guard: 'seller',
    )]
    public function resetPassword(PasswordResetSubmitRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $failure = $this->otpFailure(identity: $identity, otp: $request['otp']);

        if ($failure !== null) {
            return $failure;
        }

        $seller = $this->vendorAuthService->findByIdentity(identity: $identity);

        if ($seller === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $this->vendorAuthService->updatePassword(id: $seller->id, password: $request['password']);
        $this->vendorAuthService->revokeAccessToken(account: $seller);
        $this->passwordResetService->deleteByIdentity(identity: $identity, userType: UserType::SELLER);

        return $this->responseFormatter(config: config('response.password_changed'));
    }

    private function otpFailure(string $identity, string $otp): ?JsonResponse
    {
        $blocked = $this->passwordResetService->secondsUntilUnblocked(
            identity: $identity,
            userType: UserType::SELLER,
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
            userType: UserType::SELLER,
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
