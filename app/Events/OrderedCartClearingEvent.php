<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrderedCartClearingEvent
{
    use Dispatchable;

    /** @var array<int, string> */
    public array $forgetSessionKeys = [];

    /** @param  array<int, int|string>  $cartGroupIds */
    public function __construct(public readonly array $cartGroupIds) {}
}
