<?php

namespace App\Listeners;

use App\Events\VendorOrderPlacedEvent;
use App\Services\New\OrderStatusHistoryService;

class VendorOrderRecordHistoryListener
{
    public function __construct(private readonly OrderStatusHistoryService $orderStatusHistoryService) {}

    public function handle(VendorOrderPlacedEvent $event): void
    {
        $this->orderStatusHistoryService->create(data: [
            'order_id' => $event->orderId,
            'user_id' => $event->customerId,
            'user_type' => OrderStatusHistoryService::USER_TYPE_CUSTOMER,
            'status' => $event->initialStatus,
        ]);
    }
}
