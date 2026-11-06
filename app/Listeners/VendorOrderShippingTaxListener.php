<?php

namespace App\Listeners;

use App\Events\VendorOrderPlacingEvent;
use App\Services\New\OrderTaxService;

class VendorOrderShippingTaxListener
{
    public function __construct(private readonly OrderTaxService $orderTaxService) {}

    public function handle(VendorOrderPlacingEvent $event): void
    {
        $this->orderTaxService->createShippingTaxes(orderId: $event->orderId, vendorCart: $event->vendorCart);
    }
}
