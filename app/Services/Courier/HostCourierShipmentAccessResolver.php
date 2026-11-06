<?php

namespace App\Services\Courier;

use App\Models\Order;
use Modules\Courier\app\Contracts\CourierShipmentAccessResolver;
use Modules\Courier\app\ValueObjects\CourierOwner;

class HostCourierShipmentAccessResolver implements CourierShipmentAccessResolver
{
    public function allowsWrite(CourierOwner $actor, ?string $hostOrderReference): bool
    {
        if ($actor->isPlatform()) {
            return true;
        }

        return $hostOrderReference !== null && $this->ownsOrder($actor, $hostOrderReference);
    }

    public function allowsRead(CourierOwner $viewer, CourierOwner $bookedBy, ?string $hostOrderReference): bool
    {
        if ($viewer->equals($bookedBy) || $viewer->isPlatform()) {
            return true;
        }

        if ($hostOrderReference === null) {
            return false;
        }

        return $this->ownsOrder($viewer, $hostOrderReference);
    }

    private function ownsOrder(CourierOwner $owner, string $hostOrderReference): bool
    {
        return Order::where('id', $hostOrderReference)
            ->where('seller_id', $owner->id)
            ->where('seller_is', 'seller')
            ->exists();
    }
}
