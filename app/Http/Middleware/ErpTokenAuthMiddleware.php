<?php

namespace App\Http\Middleware;

use App\Models\ErpApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErpTokenAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $authHeader = $request->header('Authorization', '');
        $apiKey = $request->header('X-API-KEY')
            ?? (str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : null);
        $apiSecret = $request->header('X-API-SECRET');

        if (! $apiKey || ! $apiSecret) {
            return $this->unauthorized(translate('API key and secret are required'));
        }

        $token = ErpApiToken::active()->where('api_key', $apiKey)->first();
        $storedSecret = $token?->decryptedSecret();

        if (! $token || ! $storedSecret || ! hash_equals($storedSecret, $apiSecret)) {
            return $this->unauthorized(translate('Invalid or revoked API credentials'));
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('erp_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'errors' => [
                ['code' => 'erp-auth-401', 'message' => $message],
            ],
        ], 401);
    }
}
