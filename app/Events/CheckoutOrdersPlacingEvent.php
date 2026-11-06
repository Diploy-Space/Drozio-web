<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class CheckoutOrdersPlacingEvent
{
    use Dispatchable;

    /** @var array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null} */
    public array $placement = ['order_ids' => [], 'forget_session_keys' => [], 'coupon_session' => null];

    /**
     * @param  array{id: int|string|null, is_guest: int}  $orderCustomer
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $checkoutSession
     * @param  array<int, int|string>|null  $quotedCartGroupIds
     */
    public function __construct(
        public readonly ?Model $customer,
        public readonly int|string|null $requestGuestId,
        public readonly array $orderCustomer,
        public readonly array $orderData,
        public readonly array $checkoutSession,
        public readonly ?float $walletDebitAmount = null,
        public readonly ?float $quotedAmount = null,
        public readonly ?array $quotedCartGroupIds = null,
    ) {}
}
