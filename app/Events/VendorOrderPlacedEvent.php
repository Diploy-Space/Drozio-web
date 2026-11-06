<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class VendorOrderPlacedEvent
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $vendorCart
     * @param  array<string, mixed>  $loyaltyPointSettings
     */
    public function __construct(
        public readonly int $orderId,
        public readonly array $vendorCart,
        public readonly int|string $customerId,
        public readonly string $initialStatus,
        public readonly ?Model $coupon,
        public readonly array $loyaltyPointSettings,
    ) {}
}
