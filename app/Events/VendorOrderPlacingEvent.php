<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class VendorOrderPlacingEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $vendorCart */
    public function __construct(
        public readonly int $orderId,
        public readonly array $vendorCart,
    ) {}
}
