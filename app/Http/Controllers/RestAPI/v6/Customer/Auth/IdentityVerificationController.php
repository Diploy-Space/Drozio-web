<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Auth\IdentityVerificationRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class IdentityVerificationController extends BaseApiController
{
    private const DEFAULT_MAX_OTP_HIT = 5;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly VerificationService $verificationService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Attach a verified phone number or email to the signed-in account',
        description: 'Confirms an OTP sent to a new phone number or email and writes it onto the authenticated customer. The identity must not already belong to another account.',
        tag: 'Auth (Customer)',
        status: [401, 422, 429],
        guard: 'api',
    )]
    public function verifyIdentity(IdentityVerificationRequest $request): JsonResponse
    {
        $identity = $request['identity'];
        $customer = $request->user();
        $blockSeconds = $this->blockSeconds();

        $blocked = $this->verificationService->secondsUntilUnblocked(
            identity: $identity,
            blockSeconds: $blockSeconds,
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

        $isEmail = $request['type'] === LoginSetupService::CHANNEL_EMAIL;

        $isEmail
            ? $this->customerAuthService->updateEmail(id: $customer->id, email: $identity)
            : $this->customerAuthService->updatePhone(id: $customer->id, phone: $identity);

        $this->customerAuthService->markVerified(id: $customer->id, channel: $request['type']);
        $this->verificationService->deleteByIdentity(identity: $identity);

        return $this->responseFormatter(
            config: config($isEmail ? 'response.email_verified' : 'response.phone_verified'),
        );
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_otp_hit', default: self::DEFAULT_MAX_OTP_HIT);
    }

    private function blockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'temporary_block_time', default: self::DEFAULT_BLOCK_SECONDS);
    }
}
