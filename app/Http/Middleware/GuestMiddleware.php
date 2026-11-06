<?php

namespace App\Http\Middleware;

use App\Models\GuestUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PDOException;

class GuestMiddleware
{
    private const SCHEMA_CACHE_KEY = '_schema_has_business_settings';

    private const SCHEMA_CACHE_SECONDS = 21600;

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->hasBusinessSettings() || Auth::guard('customer')->check() || session('guest_id')) {
            return $next($request);
        }

        $guestUser = GuestUser::create([
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        session()->put('guest_id', $guestUser?->id);

        return $next($request);
    }

    private function hasBusinessSettings(): bool
    {
        try {
            return Cache::remember(
                self::SCHEMA_CACHE_KEY,
                self::SCHEMA_CACHE_SECONDS,
                fn (): bool => Schema::hasTable('business_settings'),
            );
        } catch (PDOException) {
            return false;
        }
    }
}
