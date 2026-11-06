<?php

namespace App\Http\Middleware;

use App\Enums\SessionKey;
use Closure;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    private const LOGIN_PAGE_THEME = 'default';

    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::guard('customer')->check() && auth('customer')->user()->is_active) {
            return $next($request);
        } elseif (Auth::guard('customer')->check()) {
            auth()->guard('customer')->logout();
            ToastMagic::warning(translate('The account is suspended'));
        } else {
            ToastMagic::info(translate('Login first for next steps'));
            $this->keepIntendedUrl(request: $request);
        }

        return redirect()->route('customer.auth.login');
    }

    private function keepIntendedUrl(Request $request): void
    {
        if (! $this->hasLoginPage() || ! $request->isMethod('get') || $request->ajax()) {
            return;
        }

        session()->put(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL, $request->fullUrl());
    }

    private function hasLoginPage(): bool
    {
        return config('app.theme') === self::LOGIN_PAGE_THEME;
    }
}
