<?php

namespace App\Http\Middleware;

use App\Models\DeliveryMan;
use Closure;
use Illuminate\Http\Request;

class DeliveryManAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = explode(' ', (string) $request->header('authorization'));
        if (count($token) > 1 && strlen($token[1]) > 30) {
            $d_man = DeliveryMan::where(['auth_token' => $token[1]])->first();
            if (isset($d_man) && (int) $d_man->is_active === 1) {
                $request['delivery_man'] = $d_man;

                return $next($request);
            }
        }

        return response()->json([
            'auth-001' => translate('Your existing session token does not authorize you anymore'),
        ], 401);
    }
}
