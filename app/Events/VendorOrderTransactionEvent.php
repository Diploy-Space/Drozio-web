<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class VendorOrderTransactionEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Model $order,
        public readonly float $adminCommission,
        public readonly string $paymentMethod,
        public readonly string $transactionId,
    ) {}
}
