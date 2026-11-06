<?php

namespace App\Traits;

use App\Services\New\OrderPlacementService;

trait OrderPlacementSessionTrait
{
    /** @return array<string, mixed> */
    private function getCheckoutSession(): array
    {
        $checkoutSession = [];

        foreach (OrderPlacementService::CHECKOUT_SESSION_KEYS as $sessionKey) {
            $checkoutSession[$sessionKey] = session($sessionKey);
        }

        return $checkoutSession;
    }

    /** @param  array<string, mixed>|null  $couponSession */
    private function putCouponSession(?array $couponSession): void
    {
        foreach ($couponSession ?? [] as $sessionKey => $value) {
            session()->put($sessionKey, $value);
        }
    }

    /** @param  array{forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null}  $placement */
    private function applyPlacementSession(array $placement): void
    {
        $this->putCouponSession(couponSession: $placement['coupon_session']);
        session()->forget($placement['forget_session_keys']);
    }
}
