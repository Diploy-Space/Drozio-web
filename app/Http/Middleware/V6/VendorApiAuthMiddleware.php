<?php

namespace App\Http\Middleware\V6;

use App\Enums\AuthErrorCode;
use App\Services\New\Auth\VendorAuthService;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorApiAuthMiddleware
{
    use ApiResponseTrait;

    private const MIN_TOKEN_LENGTH = 30;

    public function __construct(private readonly VendorAuthService $vendorAuthService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            return $this->unauthenticated();
        }

        $seller = $this->vendorAuthService->findByAccessToken(token: $token, relations: ['shop']);

        if ($seller === null) {
            return $this->unauthenticated();
        }

        $reason = $this->vendorAuthService->accountStatusReason(account: $seller);

        if ($reason !== null) {
            return $this->authError(
                errorCode: $reason === VendorAuthService::REASON_PENDING_APPROVAL
                    ? AuthErrorCode::VENDOR_PENDING_APPROVAL
                    : AuthErrorCode::VENDOR_NOT_APPROVED,
                config: config($reason === VendorAuthService::REASON_PENDING_APPROVAL ? 'response.vendor_approval_pending' : 'response.vendor_approval_revoked'),
            );
        }

        $request->attributes->set('seller', $seller);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $segments = explode(' ', (string) $request->header('authorization'));

        if (count($segments) !== 2 || strtolower($segments[0]) !== 'bearer') {
            return null;
        }

        $token = $segments[1];

        return strlen($token) >= self::MIN_TOKEN_LENGTH && ctype_alnum($token) ? $token : null;
    }

    private function unauthenticated(): Response
    {
        return $this->authError(
            config: config('response.token_expired'),
            errorCode: AuthErrorCode::UNAUTHENTICATED,
        )->header('WWW-Authenticate', 'Bearer');
    }
}
