<?php

namespace App\Http\Middleware\V6;

use App\Enums\AuthErrorCode;
use App\Services\New\Auth\DeliverymanAuthService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryManApiAuthMiddleware
{
    use ApiResponseTrait;

    private const MIN_TOKEN_LENGTH = 30;

    public function __construct(private readonly DeliverymanAuthService $deliverymanAuthService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            return $this->unauthenticated(AuthErrorCode::UNAUTHENTICATED);
        }

        $deliveryMan = $this->deliverymanAuthService->findByAccessToken(token: $token);

        if ($deliveryMan === null) {
            return $this->unauthenticated(AuthErrorCode::UNAUTHENTICATED);
        }

        if ($this->deliverymanAuthService->accountStatusReason(account: $deliveryMan) !== null) {
            return $this->authError(
                config: config('response.account_inactive'),
                errorCode: AuthErrorCode::ACCOUNT_INACTIVE,
            );
        }

        $request->attributes->set('delivery_man', $deliveryMan);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = (string) $request->header('authorization');
        $segments = explode(' ', $header);

        if (count($segments) !== 2 || strtolower($segments[0]) !== 'bearer') {
            return null;
        }

        $token = $segments[1];

        return strlen($token) >= self::MIN_TOKEN_LENGTH && ctype_alnum($token) ? $token : null;
    }

    private function unauthenticated(string $errorCode): Response
    {
        return $this->authError(config: config('response.token_expired'), errorCode: $errorCode)
            ->header('WWW-Authenticate', 'Bearer');
    }
}
