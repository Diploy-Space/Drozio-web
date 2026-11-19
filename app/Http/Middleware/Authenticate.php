<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            $adminLoginUrl = getWebConfig(name: 'admin_login_url');
            return $adminLoginUrl ? url('login/' . $adminLoginUrl) : url('/');
        }

        if ($request->is('vendor') || $request->is('vendor/*')) {
            return route('vendor.auth.login');
        }

        if ($request->is('delivery-man') || $request->is('delivery-man/*')) {
            return \Illuminate\Support\Facades\Route::has('delivery-man.auth.login')
                ? route('delivery-man.auth.login')
                : url('/');
        }

        return \Illuminate\Support\Facades\Route::has('customer.auth.login')
            ? route('customer.auth.login')
            : route('authentication-failed');
    }
}
