<?php

namespace App\Http\Middleware;

class VendorDeliveryPartnerSetupMiddleware extends DeliveryPartnerServiceMiddleware
{
    protected function isAvailable(string $businessModule): bool
    {
        return vendorDeliveryPartnerSetupAvailable(businessModule: $businessModule);
    }

    protected function refusalMessage(): string
    {
        return translate('Delivery partner setup is managed by the admin for your store');
    }
}
