<?php

namespace App\Http\Middleware;

class DeliveryPartnerConfigMiddleware extends DeliveryPartnerServiceMiddleware
{
    protected function isAvailable(string $businessModule): bool
    {
        return deliveryPartnerConfigAvailable();
    }

    protected function refusalMessage(): string
    {
        return translate('The delivery partner addon is not active');
    }
}
