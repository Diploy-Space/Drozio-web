<?php

namespace Modules\AI\app\Utils;

use Illuminate\Support\Facades\Auth;

class CurrentAuthUser
{
    private const PANEL_GUARDS = ['seller', 'customer', 'admin'];

    public static function id(): ?int
    {
        return self::model()?->id;
    }

    public static function model(): ?object
    {
        $seller = self::sellerFromRequest();

        if ($seller) {
            return $seller;
        }

        $panelGuard = self::panelGuard();

        if ($panelGuard) {
            return Auth::guard($panelGuard)->user();
        }

        if (request()->user()) {
            return request()->user();
        }

        if (Auth::guard('seller')->check()) {
            return Auth::guard('seller')->user();
        }

        if (Auth::guard('customer')->check()) {
            return Auth::guard('customer')->user();
        }

        if (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }

        return null;
    }

    public static function isAdmin(): bool
    {
        $panelGuard = self::panelGuard();

        return $panelGuard ? $panelGuard === 'admin' : Auth::guard('admin')->check();
    }

    public static function vendor(): object
    {
        $seller = self::sellerFromRequest();

        if ($seller) {
            return $seller;
        }

        if (Auth::guard('seller')->check()) {
            return Auth::guard('seller')->user();
        }

        return (object) [];
    }

    public static function isCustomer(): bool
    {
        $panelGuard = self::panelGuard();

        if ($panelGuard) {
            return $panelGuard === 'customer';
        }

        return Auth::guard('customer')->check()
            || (self::sellerFromRequest() === null && (bool) request()->user());
    }

    private static function panelGuard(): ?string
    {
        $middleware = request()->route()?->gatherMiddleware() ?? [];

        foreach (self::PANEL_GUARDS as $guard) {
            if (in_array($guard, $middleware, true)) {
                return $guard;
            }
        }

        return null;
    }

    private static function sellerFromRequest(): ?object
    {
        $seller = request()->attributes->get('seller');

        if ($seller) {
            return $seller;
        }

        return request()->has('seller') && request()->seller ? request()->seller : null;
    }
}
