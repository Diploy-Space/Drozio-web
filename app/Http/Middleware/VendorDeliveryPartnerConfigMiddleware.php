<?php

namespace App\Http\Middleware;

class VendorDeliveryPartnerConfigMiddleware extends DeliveryPartnerConfigMiddleware
{
    protected function isAvailable(string $businessModule): bool
    {
        return vendorDeliveryPartnerConfigAvailable(businessModule: $businessModule);
    }

    protected function refusalMessage(): string
    {
        return translate('Delivery partner setup is managed by the admin for your store');
    }
}
