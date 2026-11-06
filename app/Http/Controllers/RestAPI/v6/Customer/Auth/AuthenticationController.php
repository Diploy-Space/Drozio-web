<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Events\CustomerRegisteredViaReferralEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Auth\CheckEmailRequest;
use App\Http\Requests\Customer\Auth\CheckPhoneRequest;
use App\Http\Requests\Customer\Auth\LoginRequest;
use App\Http\Requests\Customer\Auth\OtpRegistrationRequest;
use App\Http\Requests\Customer\Auth\RegistrationRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\Auth\VerificationService;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use App\Services\New\ReferralCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AuthenticationController extends BaseApiController
{
    private const DEFAULT_MAX_LOGIN_HIT = 5;

    private const DEFAULT_BLOCK_SECONDS = 600;

    private const DEFAULT_MAX_OTP_HIT = 5;

    private const REGISTRATION_TOKEN_LIFETIME = 1800;

    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly VerificationService $verificationService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Customer registration',
        description: 'Returns an access token, or a temporary_token when the panel requires phone or email verification first.',
        tag: 'Auth (Customer)',
        status: [201, 403, 422],
        auth: false,
        guard: 'api',
    )]
    public function register(RegistrationRequest $request): JsonResponse
    {
        if (! $this->loginSetupService->isLoginOptionEnabled(option: LoginSetupService::LOGIN_OPTION_MANUAL)) {
            return $this->loginOptionDisabled();
        }

        $referrer = $request['referral_code'] === null
            ? null
            : $this->customerAuthService->findByReferralCode(code: $request['referral_code']);

        $customer = $this->customerAuthService->create(
            data: $request->validated() + ['referred_by' => $referrer?->id],
        );

        $this->rewardReferrer(customerId: $customer->id, referrer: $referrer);

        return $this->identityResponse(customer: $customer, status: 201);
    }

    #[ApiDoc(
        summary: 'Customer registration after phone OTP',
        description: 'Finishes the passwordless signup that otp/send and otp/verify start with purpose=registration. Pass back the temporary_token otp/verify returned; the account opens with the phone already verified and no password.',
        tag: 'Auth (Customer)',
        status: [201, 422],
        auth: false,
        guard: 'api',
    )]
    public function registerWithOtp(OtpRegistrationRequest $request): JsonResponse
    {
        $phone = $request['phone'];

        $reason = $this->verificationService->verifyOtp(
            identity: $phone,
            token: $request['temporary_token'],
            maxAttempts: $this->maxOtpHit(),
            expirySeconds: self::REGISTRATION_TOKEN_LIFETIME,
        );

        if ($reason !== null) {
            return $this->authError(
                config: config('response.phone_reverification_required'),
                errorCode: $reason === VerificationService::REASON_OTP_EXPIRED ? AuthErrorCode::OTP_EXPIRED : AuthErrorCode::OTP_INVALID,
            );
        }

        $referrer = $request['referral_code'] === null
            ? null
            : $this->customerAuthService->findByReferralCode(code: $request['referral_code']);

        $customer = $this->customerAuthService->create(
            data: $request->validated() + [
                'referred_by' => $referrer?->id,
                'login_medium' => CustomerAuthService::MEDIUM_OTP,
                'is_phone_verified' => 1,
            ],
        );

        $this->verificationService->deleteByIdentity(identity: $phone);
        $this->rewardReferrer(customerId: $customer->id, referrer: $referrer);

        return $this->responseFormatter(
            config: config('response.registration_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Customer login',
        description: 'Answers 200 with a temporary_token instead of a token when the account still needs phone or email verification.',
        tag: 'Auth (Customer)',
        status: [401, 403, 422, 429],
        auth: false,
        guard: 'api',
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->loginSetupService->isLoginOptionEnabled(option: LoginSetupService::LOGIN_OPTION_MANUAL)) {
            return $this->loginOptionDisabled();
        }

        $identity = $request['email_or_phone'];
        $maxAttempts = $this->maxLoginHit();
        $blockSeconds = $this->loginBlockSeconds();

        $customer = $this->customerAuthService->findByIdentity(identity: $identity);

        $retryAfter = $this->customerAuthService->secondsUntilLoginAllowed(
            identity: $identity,
            account: $customer,
            maxAttempts: $maxAttempts,
            blockSeconds: $blockSeconds,
        );

        if ($retryAfter > 0) {
            return $this->authThrottled(
                config: config('response.login_attempts_blocked'),
                errorCode: AuthErrorCode::LOGIN_BLOCKED,
                retryAfter: $retryAfter,
            );
        }

        if ($customer === null || ! $this->customerAuthService->verifyPassword(account: $customer, password: $request['password'])) {
            $this->customerAuthService->registerLoginFailure(
                identity: $identity,
                account: $customer,
                maxAttempts: $maxAttempts,
                blockSeconds: $blockSeconds,
            );

            return $this->authError(
                config: config('response.credentials_mismatch'),
                errorCode: AuthErrorCode::INVALID_CREDENTIALS,
            );
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        $this->customerAuthService->clearLoginFailures(identity: $identity, account: $customer);

        return $this->identityResponse(customer: $customer);
    }

    #[ApiDoc(
        summary: 'Customer logout',
        tag: 'Auth (Customer)',
        status: [401],
        guard: 'api',
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->customerAuthService->revokeAccessToken(account: $request->user());

        return $this->responseFormatter(config: config('response.logged_out'));
    }

    #[ApiDoc(
        summary: 'Check whether an email is already registered',
        tag: 'Auth (Customer)',
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function checkEmail(CheckEmailRequest $request): JsonResponse
    {
        return $this->availabilityResponse(identity: $request['email']);
    }

    #[ApiDoc(
        summary: 'Check whether a phone number is already registered',
        tag: 'Auth (Customer)',
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function checkPhone(CheckPhoneRequest $request): JsonResponse
    {
        return $this->availabilityResponse(identity: $request['phone']);
    }

    private function availabilityResponse(string $identity): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'available' => ! $this->customerAuthService->identityExists(identity: $identity),
                'verification_required' => $this->loginSetupService->requiredVerificationChannel(),
            ],
        );
    }

    private function identityResponse(mixed $customer, int $status = 200): JsonResponse
    {
        $pendingChannel = $this->customerAuthService->pendingVerificationChannel(
            account: $customer,
            requiredChannel: $this->loginSetupService->requiredVerificationChannel(),
        );

        if ($pendingChannel !== null) {
            return $this->responseFormatter(
                config: config($status === 201 ? 'response.registration_verification_required' : 'response.verification_required'),
                content: [
                    'temporary_token' => $this->customerAuthService->issueTemporaryToken(id: $customer->id),
                    'verification_required' => $pendingChannel,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'is_phone_verified' => (int) $customer->is_phone_verified,
                    'is_email_verified' => (int) $customer->is_email_verified,
                ],
            );
        }

        return $this->responseFormatter(
            config: config($status === 201 ? 'response.registration_success' : 'response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer), 'token_type' => 'Bearer'],
        );
    }

    private function rewardReferrer(int $customerId, mixed $referrer): void
    {
        $referralConfig = $this->businessSettingService->getValueByType(type: 'ref_earning_customer', default: []);

        if ($referrer === null || (int) ($referralConfig['ref_earning_discount_status'] ?? 0) !== 1) {
            return;
        }

        $referral = $this->referralCustomerService->create(data: $referralConfig + [
            'user_id' => $customerId,
            'refer_by' => $referrer->id,
            'ref_by_earning_amount' => $this->businessSettingService->getValueByType(type: 'ref_earning_exchange_rate', default: 0),
        ]);

        event(new CustomerRegisteredViaReferralEvent($referral, $referrer));
    }

    private function loginOptionDisabled(): JsonResponse
    {
        return $this->authError(
            config: config('response.login_method_disabled'),
            errorCode: AuthErrorCode::LOGIN_METHOD_DISABLED,
        );
    }

    private function maxLoginHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_login_hit', default: self::DEFAULT_MAX_LOGIN_HIT);
    }

    private function maxOtpHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'maximum_otp_hit', default: self::DEFAULT_MAX_OTP_HIT);
    }

    private function loginBlockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(type: 'temporary_login_block_time', default: self::DEFAULT_BLOCK_SECONDS);
    }
}
