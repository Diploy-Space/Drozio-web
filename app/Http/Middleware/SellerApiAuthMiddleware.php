<?php

namespace App\Http\Middleware;

use App\Enums\VendorStatus;
use App\Models\Seller;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SellerApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = explode(' ', (string) $request->header('authorization'));
        if (count($token) > 1 && strlen($token[1]) > 30) {
            $seller = Seller::where(['auth_token' => $token[1]])->first();
            if (isset($seller) && $seller->status === VendorStatus::APPROVED) {
                $request['seller'] = $seller;

                return $next($request);
            }
        }

        return response()->json([
            'auth-001' => translate('Your existing session token does not authorize you anymore'),
        ], 401);
    }
}
