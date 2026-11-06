<?php

namespace App\Listeners;

use App\Events\OrderedCartClearingEvent;
use App\Services\New\CartService;

class OrderedCartClearListener
{
    public function __construct(private readonly CartService $cartService) {}

    public function handle(OrderedCartClearingEvent $event): void
    {
        $event->forgetSessionKeys = $this->cartService->clearOrderedCartGroups(cartGroupIds: $event->cartGroupIds);
    }
}
