<?php

namespace Modules\Courier\app\Contracts;

use Modules\Courier\app\ValueObjects\CourierOwner;

interface CourierShipmentAccessResolver
{
    public function allowsRead(CourierOwner $viewer, CourierOwner $bookedBy, ?string $hostOrderReference): bool;

    /**
     * May this owner book or revise a shipment against the host's order? Asked before a driver is
     * called, so a caller cannot spend another owner's carrier account or attach a consignment to
     * an order that is not theirs.
     */
    public function allowsWrite(CourierOwner $actor, ?string $hostOrderReference): bool;
}
