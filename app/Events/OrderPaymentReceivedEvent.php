<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrderPaymentReceivedEvent
{
    use Dispatchable;

    public function __construct(public readonly mixed $paymentRequest) {}
}
