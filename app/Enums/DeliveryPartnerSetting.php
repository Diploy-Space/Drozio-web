<?php

namespace App\Enums;

enum DeliveryPartnerSetting
{
    const THIRD_PARTY_DELIVERY_KEYS = [
        BusinessModule::RETAIL => 'third_party_delivery_service',
        BusinessModule::WHOLESALE => 'wholesale_third_party_delivery_service',
    ];

    const VENDOR_SETUP_KEYS = [
        BusinessModule::RETAIL => 'vendor_delivery_partner_setup',
        BusinessModule::WHOLESALE => 'wholesale_vendor_delivery_partner_setup',
    ];

    public static function thirdPartyDeliveryKey(string $businessModule): string
    {
        return self::THIRD_PARTY_DELIVERY_KEYS[$businessModule] ?? self::THIRD_PARTY_DELIVERY_KEYS[BusinessModule::RETAIL];
    }

    public static function vendorSetupKey(string $businessModule): string
    {
        return self::VENDOR_SETUP_KEYS[$businessModule] ?? self::VENDOR_SETUP_KEYS[BusinessModule::RETAIL];
    }

    public static function supports(string $businessModule): bool
    {
        return array_key_exists($businessModule, self::THIRD_PARTY_DELIVERY_KEYS);
    }
}
