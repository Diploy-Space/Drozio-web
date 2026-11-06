<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman\Auth;

use App\Enums\AuthErrorCode;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Auth\LoginRequest;
use App\Services\New\Auth\DeliverymanAuthService;
use App\Services\New\BusinessSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AuthenticationController extends BaseApiController
{
    private const DEFAULT_MAX_LOGIN_HIT = 5;

    private const DEFAULT_BLOCK_SECONDS = 600;

    public function __construct(
        private readonly DeliverymanAuthService $deliverymanAuthService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    #[ApiDoc(
        summary: 'Delivery-man login',
        description: 'Issues the bearer token the delivery-man app sends on every later call. Only one token is live per delivery-man, so logging in on a second device signs the first one out.',
        tag: 'Auth (Delivery-man)',
        status: [401, 403, 422, 429],
        auth: false,
        guard: 'deliveryman',
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $identity = $request['phone'];
        $maxAttempts = $this->maxLoginHit();
        $blockSeconds = $this->loginBlockSeconds();

        $deliveryMan = $this->deliverymanAuthService->findByPhone(phone: $identity);

        $retryAfter = $this->deliverymanAuthService->secondsUntilLoginAllowed(
            identity: $identity,
            account: $deliveryMan,
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

        if ($deliveryMan === null || ! $this->deliverymanAuthService->verifyPassword(account: $deliveryMan, password: $request['password'])) {
            $this->deliverymanAuthService->registerLoginFailure(
                identity: $identity,
                account: $deliveryMan,
                maxAttempts: $maxAttempts,
                blockSeconds: $blockSeconds,
            );

            return $this->authError(
                config: config('response.credentials_mismatch'),
                errorCode: AuthErrorCode::INVALID_CREDENTIALS,
            );
        }

        if ($this->deliverymanAuthService->accountStatusReason(account: $deliveryMan) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        $this->deliverymanAuthService->clearLoginFailures(identity: $identity, account: $deliveryMan);

        return $this->responseFormatter(
            config: config('response.login_success'),
            content: ['token' => $this->deliverymanAuthService->issueAccessToken(account: $deliveryMan), 'token_type' => 'Bearer'],
        );
    }

    #[ApiDoc(
        summary: 'Delivery-man logout',
        tag: 'Auth (Delivery-man)',
        status: [401],
        guard: 'deliveryman',
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->deliverymanAuthService->revokeAccessToken(account: $request->attributes->get('delivery_man'));

        return $this->responseFormatter(config: config('response.logged_out'));
    }

    private function maxLoginHit(): int
    {
        return (int) $this->businessSettingService->getValueByType(
            type: 'maximum_login_hit',
            default: self::DEFAULT_MAX_LOGIN_HIT,
        );
    }

    private function loginBlockSeconds(): int
    {
        return (int) $this->businessSettingService->getValueByType(
            type: 'temporary_login_block_time',
            default: self::DEFAULT_BLOCK_SECONDS,
        );
    }
}
