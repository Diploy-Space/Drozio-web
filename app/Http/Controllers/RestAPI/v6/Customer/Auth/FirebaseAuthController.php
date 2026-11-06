<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Enums\UserType;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Auth\FirebaseSessionRequest;
use App\Http\Requests\Shared\Auth\FirebaseVerifyRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\PasswordResetService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\FirebaseOtpService;
use App\Services\New\LoginSetupService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class FirebaseAuthController extends BaseApiController
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly PasswordResetService $passwordResetService,
        private readonly VerificationService $verificationService,
        private readonly FirebaseOtpService $firebaseOtpService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Register a Firebase phone verification session',
        description: 'Records the sessionInfo Firebase returned when the SMS was sent, so the code submitted next must belong to the same session for the same identity. The value stored here is never accepted as a password reset token.',
        tag: 'Auth (Customer)',
        status: [422, 429],
        auth: false,
        guard: 'api',
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
        summary: 'Verify a phone number with a Firebase OTP',
        description: 'Firebase itself confirms the code, so nothing is trusted from the client. A verified number belonging to a customer signs them in; an unknown number comes back with a temporary_token to register with. With is_reset_token=1 nobody is signed in: the verified code is issued server-side as the password reset OTP that password/reset accepts.',
        tag: 'Auth (Customer)',
        status: [403, 404, 422, 503],
        auth: false,
        guard: 'api',
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

        $this->firebaseOtpService->forgetSession(identity: $request['phone_number']);

        $phone = $verification['phone_number'] ?? $request['phone_number'];
        $customer = $this->customerAuthService->findByPhone(phone: $phone);

        if ((bool) $request['is_reset_token']) {
            return $this->issuePasswordResetOtp(customerExists: $customer !== null, phone: $phone, code: $request['code']);
        }

        if ($customer === null) {
            return $this->responseFormatter(
                config: config('response.registration_required'),
                content: [
                    'phone' => $phone,
                    'temporary_token' => $this->verificationService->issueRegistrationToken(identity: $phone),
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

        $this->customerAuthService->markVerified(id: $customer->id, channel: LoginSetupService::CHANNEL_PHONE);

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer->refresh()), 'token_type' => 'Bearer'],
        );
    }

    private function issuePasswordResetOtp(bool $customerExists, string $phone, string $code): JsonResponse
    {
        if (! $customerExists) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        $this->passwordResetService->issueOtp(identity: $phone, userType: UserType::CUSTOMER, token: $code);

        return $this->responseFormatter(
            config: config('response.otp_verified'),
            content: ['identity' => $phone],
        );
    }

    private function firebaseApiKey(): string
    {
        $config = $this->businessSettingService->getValueByType(type: 'firebase_otp_verification', default: []);

        return is_array($config) ? (string) ($config['web_api_key'] ?? '') : '';
    }
}
