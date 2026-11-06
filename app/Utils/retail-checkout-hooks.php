<?php

use App\Events\OrderPaymentReceivedEvent;

if (! function_exists('retail_payment_success')) {
    function retail_payment_success($paymentData): void
    {
        if (isset($paymentData)) {
            OrderPaymentReceivedEvent::dispatch($paymentData);
        }
    }
}

if (! function_exists('retail_payment_fail')) {
    function retail_payment_fail($paymentData): void {}
}
