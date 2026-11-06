<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\UserType;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Auth\FirebaseSessionRequest;
use App\Http\Requests\Shared\Auth\FirebaseVerifyRequest;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VendorAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\FirebaseOtpService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class FirebaseAuthController extends BaseApiController
{
    public function __construct(
        private readonly VendorAuthService $vendorAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Register a Firebase phone verification session for a vendor',
        description: 'Records the sessionInfo Firebase returned when the SMS was sent, so the code submitted next must belong to the same session for the same identity. The value stored here is never accepted as a password reset token.',
        tag: 'Auth (Seller)',
        status: [422, 429],
        auth: false,
        guard: 'seller',
    )]
    public function storeSession(FirebaseSessionRequest $request): JsonResponse
    {
        $this->firebaseOtpService->rememberSession(
            identity: $request['identity'],
            sessionInfo: $request['session_info'],
            ttlSeconds: $this->businessSettingService->getOtpExpirySeconds(),
        );

        return $this->responseFormatter(config: config('response.verification_session_saved'));
    }

    #[ApiDoc(
        summary: 'Verify a vendor phone number with a Firebase OTP',
        description: 'Firebase confirms the code, then the reset OTP is issued server-side. The client never supplies the token that a password reset will later accept.',
        tag: 'Auth (Seller)',
        status: [404, 422, 503],
        auth: false,
        guard: 'seller',
    )]
    public function verifyOtp(FirebaseVerifyRequest $request): JsonResponse
    {
        $apiKey = $this->firebaseApiKey();

        if ($apiKey === '') {
            return $this->authError(
                config: config('response.firebase_not_configured'),
                errorCode: AuthErrorCode::NOTIFICATION_CHANNEL_UNAVAILABLE,
            );
        }

        $registeredSession = $this->firebaseOtpService->rememberedSession(identity: $request['phone_number']);

        if ($registeredSession !== null && ! hash_equals($registeredSession, (string) $request['session_info'])) {
            return $this->authError(
                config: config('response.verification_session_mismatch'),
                errorCode: AuthErrorCode::PROVIDER_VERIFICATION_FAILED,
            );
        }

        $verification = $this->firebaseOtpService->verifyPhoneOtp(
            apiKey: $apiKey,
            sessionInfo: $request['session_info'],
            phoneNumber: $request['phone_number'],
            code: $request['code'],
        );

        if (! $verification['verified']) {
            return $this->authError(
                config: config('response.otp_invalid'),
                errorCode: AuthErrorCode::PROVIDER_VERIFICATION_FAILED,
            );
        }

        $phone = $verification['phone_number'] ?? $request['phone_number'];
        $seller = $this->vendorAuthService->findByIdentity(identity: $phone);

        if ($seller === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $this->firebaseOtpService->forgetSession(identity: $request['phone_number']);
        $this->passwordResetService->issueOtp(identity: $phone, userType: UserType::SELLER, token: $request['code']);

        return $this->responseFormatter(
            config: config('response.otp_verified'),
            content: ['identity' => $phone,
            ],
        );
    }

    private function firebaseApiKey(): string
    {
        $config = $this->businessSettingService->getValueByType(type: 'firebase_otp_verification', default: []);

        return is_array($config) ? (string) ($config['web_api_key'] ?? '') : '';
    }
}
