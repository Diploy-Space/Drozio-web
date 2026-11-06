<?php

namespace App\Listeners;

use App\Services\New\OrderService;
use App\Services\New\OrderStatusHistoryService;
use Illuminate\Support\Facades\DB;
use Modules\Courier\app\Contracts\CourierOwnerResolver;
use Modules\Courier\app\Events\ShipmentDispatched;

class CourierShipmentDispatchedListener
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
    ) {}

    public function handle(ShipmentDispatched $event): void
    {
        $order = $this->orderService->updateDeliveryPartnerInAnyBusinessModule(
            id: $event->hostOrderReference,
            serviceName: $event->providerLabel,
            trackingId: $event->trackingCode ?: $event->consignmentId,
        );

        if ($order === null) {
            return;
        }

        $actor = $this->dispatchingActor();

        DB::transaction(function () use ($order, $actor, $event): void {
            if (! $this->orderService->advanceToOutForDeliveryInAnyBusinessModule(order: $order)) {
                return;
            }

            $this->orderStatusHistoryService->create(data: [
                'order_id' => $order->id,
                'user_id' => $actor['user_id'],
                'user_type' => $actor['user_type'],
                'status' => OrderService::STATUS_OUT_FOR_DELIVERY,
                'cause' => 'courier:'.$event->consignmentId,
            ]);
        });
    }

    private function dispatchingActor(): array
    {
        $owner = app(CourierOwnerResolver::class)->resolve();

        return $owner->isPlatform()
            ? ['user_type' => 'admin', 'user_id' => auth('admin')->id() ?? 0]
            : ['user_type' => 'seller', 'user_id' => $owner->id];
    }
}
