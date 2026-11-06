<?php

namespace App\Traits;

use App\Enums\SessionKey;
use Illuminate\Http\Request;

trait CustomerAuthRedirectTrait
{
    protected function keepCustomerAuthReturnUrl(mixed $requestedUrl = null): void
    {
        if ($requestedUrl) {
            session()->put(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL, $requestedUrl);

            return;
        }

        if (session()->has(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL)) {
            return;
        }

        $historyUrls = session(SessionKey::RECENT_USER_ROUTES_HISTORY, []);

        if ($historyUrls === []) {
            return;
        }

        session()->put(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL, end($historyUrls));
    }

    protected function getCustomerAuthReturnUrl(): string
    {
        return session()->has(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL)
            ? session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL)
            : route('home');
    }

    protected function getCustomerLogoutReturnUrl(Request $request): string
    {
        $historyUrls = session(SessionKey::RECENT_USER_ROUTES_HISTORY, []);

        return storefrontHomeUrl(
            $request->headers->get('referer'),
            session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL),
            $historyUrls === [] ? null : end($historyUrls),
        );
    }
}
