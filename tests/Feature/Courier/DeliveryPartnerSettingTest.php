<?php

namespace Tests\Feature\Courier;

use App\Enums\BusinessModule;
use App\Enums\DeliveryPartnerSetting;
use Tests\TestCase;

class DeliveryPartnerSettingTest extends TestCase
{
    public function test_retail_keeps_its_original_keys(): void
    {
        $this->assertSame('third_party_delivery_service', DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: BusinessModule::RETAIL));
        $this->assertSame('vendor_delivery_partner_setup', DeliveryPartnerSetting::vendorSetupKey(businessModule: BusinessModule::RETAIL));
    }

    public function test_wholesale_has_its_own_keys(): void
    {
        $this->assertSame('wholesale_third_party_delivery_service', DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: BusinessModule::WHOLESALE));
        $this->assertSame('wholesale_vendor_delivery_partner_setup', DeliveryPartnerSetting::vendorSetupKey(businessModule: BusinessModule::WHOLESALE));
    }

    public function test_a_module_without_delivery_partners_falls_back_to_retail_keys(): void
    {
        $this->assertSame('third_party_delivery_service', DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: BusinessModule::AUCTION));
        $this->assertSame('third_party_delivery_service', DeliveryPartnerSetting::thirdPartyDeliveryKey(businessModule: 'unknown'));
    }

    public function test_the_order_business_module_defaults_to_retail(): void
    {
        $this->assertSame(BusinessModule::RETAIL, deliveryPartnerOrderBusinessModule(order: ['id' => 1]));
        $this->assertSame(BusinessModule::WHOLESALE, deliveryPartnerOrderBusinessModule(order: ['id' => 1, 'business_module' => BusinessModule::WHOLESALE]));
        $this->assertSame(BusinessModule::RETAIL, deliveryPartnerOrderBusinessModule(order: ['id' => 1, 'business_module' => BusinessModule::AUCTION]));
    }
}
