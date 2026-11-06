<?php

namespace App\Http\Controllers\RestAPI\v6\Customer\Auth;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Auth\ExistingAccountRequest;
use App\Http\Requests\Customer\Auth\SocialLoginRequest;
use App\Http\Requests\Customer\Auth\SocialRegistrationRequest;
use App\Http\Requests\Customer\Auth\UpdatePhoneRequest;
use App\Services\New\Auth\CustomerAuthService;
use App\Services\New\BusinessSettingService;
use App\Services\New\LoginSetupService;
use App\Services\New\SocialProfileService;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class SocialAuthController extends BaseApiController
{
    public function __construct(
        private readonly CustomerAuthService $customerAuthService,
        private readonly SocialProfileService $socialProfileService,
        private readonly LoginSetupService $loginSetupService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Sign in with Google, Facebook or Apple',
        description: 'Exchanges the provider token for a profile, then signs the matching customer in or creates one. A new account without a phone number gets a temporary_token to finish with social/phone.',
        tag: 'Auth (Customer)',
        status: [401, 403, 422],
        auth: false,
        guard: 'api',
    )]
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        $medium = $request['medium'];
        $refusal = $this->socialOptionRefusal(medium: $medium);

        if ($refusal !== null) {
            return $refusal;
        }

        $profile = $this->verifiedProfile(request: $request);

        if ($profile === null) {
            return $this->providerVerificationError();
        }

        $customer = $this->customerAuthService->findBySocialAccount(medium: $medium, socialId: $profile['social_id'])
            ?? ($profile['email'] === null ? null : $this->customerAuthService->findByIdentity(identity: $profile['email']));

        if ($customer === null) {
            $customer = $this->customerAuthService->createFromSocialProfile(profile: $profile, medium: $medium);
        } elseif ($customer->social_id === null) {
            $this->customerAuthService->linkSocialAccount(id: $customer->id, medium: $medium, socialId: $profile['social_id']);
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        if (empty($customer->phone)) {
            return $this->responseFormatter(
                config: config('response.phone_required'),
                content: [
                    'temporary_token' => $this->customerAuthService->issueTemporaryToken(id: $customer->id),
                    'phone_required' => true,
                ],
            );
        }

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer->refresh()), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Register with a social account and a phone number',
        description: 'Creates the account from the provider profile rather than from posted details, so the email is only trusted because the provider vouched for it. The phone number comes from the request and starts unverified. An account that social/login already started for the same provider profile and that still has no phone number is finished here instead of being refused; any other account holding the email answers email_already_registered.',
        tag: 'Auth (Customer)',
        status: [201, 401, 403, 422],
        auth: false,
        guard: 'api',
    )]
    public function registerWithSocialMedia(SocialRegistrationRequest $request): JsonResponse
    {
        $medium = $request['medium'];
        $refusal = $this->socialOptionRefusal(medium: $medium);

        if ($refusal !== null) {
            return $refusal;
        }

        $profile = $this->verifiedProfile(request: $request);

        if ($profile === null) {
            return $this->providerVerificationError();
        }

        $socialAccount = $this->customerAuthService->findBySocialAccount(medium: $medium, socialId: $profile['social_id']);
        $isAwaitingPhone = $socialAccount !== null && empty($socialAccount->phone);
        $isEmailTaken = $profile['email'] !== null && $this->customerAuthService->identityExists(identity: $profile['email']);

        if (! $isAwaitingPhone && ($socialAccount !== null || $isEmailTaken)) {
            return $this->authError(
                config: config('response.email_already_registered'),
                errorCode: AuthErrorCode::ACCOUNT_EMAIL_RESERVED,
            );
        }

        if ($isAwaitingPhone && $this->customerAuthService->accountStatusReason(account: $socialAccount) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        if ($isAwaitingPhone) {
            $this->customerAuthService->updatePhone(id: $socialAccount->id, phone: $request['phone']);
        }

        $customer = $isAwaitingPhone
            ? $socialAccount->refresh()
            : $this->customerAuthService->createFromSocialProfile(
                profile: $profile + ['phone' => $request['phone']],
                medium: $medium,
            );

        $requiredChannel = $this->loginSetupService->requiredVerificationChannel();

        if ($requiredChannel === LoginSetupService::CHANNEL_PHONE) {
            return $this->responseFormatter(
                config: config('response.registration_verification_required'),
                content: [
                    'temporary_token' => $this->customerAuthService->issueTemporaryToken(id: $customer->id),
                    'verification_required' => LoginSetupService::CHANNEL_PHONE,
                    'phone' => $request['phone'],
                ],
            );
        }

        return $this->responseFormatter(
            config: config('response.registration_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Claim or release an account found by social email',
        description: 'Answers the "we already have an account with this email" step. The provider token is re-verified first, so only the person who controls the email at the provider can claim the account or detach the email from it.',
        tag: 'Auth (Customer)',
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function existingAccount(ExistingAccountRequest $request): JsonResponse
    {
        $medium = $request['medium'];
        $refusal = $this->socialOptionRefusal(medium: $medium);

        if ($refusal !== null) {
            return $refusal;
        }

        $profile = $this->verifiedProfile(request: $request);

        if ($profile === null || $profile['email'] === null) {
            return $this->providerVerificationError();
        }

        $customer = $this->customerAuthService->findByIdentity(identity: $profile['email']);

        if ($customer === null) {
            return $this->authError(
                config: config('response.account_not_found'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        if ((int) $request['user_response'] !== 1) {
            $this->customerAuthService->detachEmail(id: $customer->id);

            return $this->responseFormatter(
                config: config('response.registration_required'),
                content: ['registration_required' => true],
            );
        }

        if ($this->customerAuthService->accountStatusReason(account: $customer) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        $this->customerAuthService->linkSocialAccount(id: $customer->id, medium: $medium, socialId: $profile['social_id']);
        $this->customerAuthService->markVerified(id: $customer->id, channel: LoginSetupService::CHANNEL_EMAIL);

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer->refresh()), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Add the phone number a social sign-in is missing',
        tag: 'Auth (Customer)',
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function updatePhone(UpdatePhoneRequest $request): JsonResponse
    {
        $customer = $this->customerAuthService->findByTemporaryToken(token: $request['temporary_token']);

        if ($customer === null) {
            return $this->authError(
                config: config('response.signin_session_expired'),
                errorCode: AuthErrorCode::ACCOUNT_NOT_FOUND,
            );
        }

        if ($this->customerAuthService->findByPhone(phone: $request['phone']) !== null) {
            return $this->authError(
                config: config('response.phone_already_registered'),
                errorCode: AuthErrorCode::ACCOUNT_PHONE_RESERVED,
            );
        }

        $this->customerAuthService->updatePhone(id: $customer->id, phone: $request['phone']);

        $requiredChannel = $this->loginSetupService->requiredVerificationChannel();

        if ($requiredChannel === LoginSetupService::CHANNEL_PHONE) {
            return $this->responseFormatter(
                config: config('response.verification_required'),
                content: [
                    'temporary_token' => $this->customerAuthService->issueTemporaryToken(id: $customer->id),
                    'verification_required' => LoginSetupService::CHANNEL_PHONE,
                    'phone' => $request['phone'],
                ],
            );
        }

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->customerAuthService->issueAccessToken(account: $customer->refresh()), 'token_type' => 'Bearer'],
        );
    }

    private function socialOptionRefusal(string $medium): ?JsonResponse
    {
        if (! $this->loginSetupService->isLoginOptionEnabled(option: LoginSetupService::LOGIN_OPTION_SOCIAL)) {
            return $this->authError(
                config: config('response.social_login_disabled'),
                errorCode: AuthErrorCode::LOGIN_METHOD_DISABLED,
            );
        }

        if (! $this->isMediumEnabled(medium: $medium)) {
            return $this->authError(
                config: config('response.social_medium_disabled'),
                errorCode: AuthErrorCode::SOCIAL_MEDIUM_DISABLED,
            );
        }

        return null;
    }

    private function verifiedProfile(mixed $request): ?array
    {
        return $this->socialProfileService->fetchProfile(
            medium: $request['medium'],
            token: $request['token'],
            uniqueId: $request['unique_id'],
            appleConfig: $this->appleConfig(),
        );
    }

    private function providerVerificationError(): JsonResponse
    {
        return $this->authError(
            config: config('response.provider_verification_failed'),
            errorCode: AuthErrorCode::PROVIDER_VERIFICATION_FAILED,
        );
    }

    private function isMediumEnabled(string $medium): bool
    {
        $media = $this->loginSetupService->getValueByKey(key: 'social_media_for_login', default: []);

        return is_array($media) ? (bool) ($media[$medium] ?? 0) : false;
    }

    private function appleConfig(): array
    {
        $config = $this->businessSettingService->getValueByType(type: 'apple_login', default: []);

        return is_array($config) ? ($config[0] ?? []) : [];
    }
}
