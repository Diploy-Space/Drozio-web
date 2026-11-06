<?php

namespace Tests\Feature\Order;

use Modules\Wholesale\app\Services\OrderService;

class DeliveryVerificationOrderService extends OrderService
{
    public bool $isDeliveryVerificationEnabled = false;

    public function isDeliveryVerificationEnabled(): bool
    {
        return $this->isDeliveryVerificationEnabled;
    }
}
