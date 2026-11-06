<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class CheckoutCartStateRequestedEvent
{
    use Dispatchable;

    /** @var array{vendor_carts: array<int, array<string, mixed>>, coupon_session: array<string, mixed>|null} */
    public array $cartState = ['vendor_carts' => [], 'coupon_session' => null];

    /** @param  array<string, mixed>  $checkoutSession */
    public function __construct(
        public readonly ?Model $customer,
        public readonly int|string|null $requestGuestId,
        public readonly array $checkoutSession,
        public readonly ?string $couponCode,
        public readonly mixed $shippingAddressId,
        public readonly mixed $billingAddressId,
    ) {}
}
