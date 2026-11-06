<?php

namespace App\Http\Middleware;

use App\Enums\SessionKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Symfony\Component\HttpFoundation\Response;

class LogUserBrowsingNavigationMiddleware
{
    private const HISTORY_LIMIT = 10;

    private const CUSTOMER_MIDDLEWARE_ALIAS = 'customer';

    private const SCHEMA_CACHE_KEY = '_schema_has_business_settings';

    private const SCHEMA_CACHE_SECONDS = 21600;

    private const EXCLUDED_PATTERNS = [
        'admin/*',
        'vendor/*',
        'vendor/auth/*',
        'login/*',
        'customer',
        'customer/*',
        'chat*',
        'g-recaptcha-session-store*',
        'quick-view',
        'web-payment*',
        'payment*',
        'payment-success*',
        'payment-fail*',
        'authentication-failed*',
        'coupon*',
        'wholesale/checkout/*',
        'wholesale/orders/checkout*',
    ];

    private const GUEST_CHECKOUT_EXCLUDED_PATTERNS = [
        'shop-cart*',
        'checkout-details*',
        'checkout-payment*',
        'wholesale/orders/cart*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasBusinessSettings() || ! $this->isTrackable(request: $request)) {
            return $next($request);
        }

        $currentUrl = $request->fullUrl();
        $urls = array_values(array_filter(
            session(SessionKey::RECENT_USER_ROUTES_HISTORY, []),
            fn ($url): bool => $url !== $currentUrl,
        ));

        if (! $this->isExcluded(request: $request)) {
            $urls[] = $currentUrl;
            session()->put(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL, $currentUrl);
        }

        session()->put(SessionKey::RECENT_USER_ROUTES_HISTORY, array_slice($urls, -self::HISTORY_LIMIT));

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

    private function isTrackable(Request $request): bool
    {
        return ! $request->ajax()
            && $request->isMethod('get')
            && $request->getHost() === parse_url(url('/'), PHP_URL_HOST);
    }

    private function isExcluded(Request $request): bool
    {
        if ($this->requiresCustomerLogin(request: $request)) {
            return true;
        }

        return $request->is(...$this->excludedPatterns());
    }

    private function requiresCustomerLogin(Request $request): bool
    {
        $route = $request->route();

        return $route instanceof Route
            && in_array(self::CUSTOMER_MIDDLEWARE_ALIAS, $route->gatherMiddleware(), true);
    }

    /**
     * @return string[]
     */
    private function excludedPatterns(): array
    {
        if (! getWebConfig('guest_checkout')) {
            return array_merge(self::EXCLUDED_PATTERNS, self::GUEST_CHECKOUT_EXCLUDED_PATTERNS);
        }

        return self::EXCLUDED_PATTERNS;
    }
}
