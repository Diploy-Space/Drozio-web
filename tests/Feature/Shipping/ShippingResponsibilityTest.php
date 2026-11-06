<?php

namespace Tests\Feature\Shipping;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Seller;
use App\Models\ShippingMethod;
use App\Services\New\ShippingMethodService as RetailShippingMethodService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use ReflectionFunction;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class ShippingResponsibilityTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const ADMIN_METHOD_TITLE = 'Responsibility admin method';

    private const SELLER_METHOD_TITLE = 'Responsibility seller method';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_wholesale_in_house_shipping_offers_admin_methods_for_a_vendor_shop(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::WHOLESALE, title: self::ADMIN_METHOD_TITLE);
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: self::SELLER_METHOD_TITLE);

        $titles = $this->wholesaleService(responsibility: RetailShippingMethodService::SHIPPING_METHOD_INHOUSE)
            ->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)
            ->pluck('title');

        $this->assertContains(self::ADMIN_METHOD_TITLE, $titles);
        $this->assertNotContains(self::SELLER_METHOD_TITLE, $titles);
    }

    public function test_wholesale_vendor_shipping_offers_vendor_methods_for_a_vendor_shop(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::WHOLESALE, title: self::ADMIN_METHOD_TITLE);
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: self::SELLER_METHOD_TITLE);

        $titles = $this->wholesaleService(responsibility: RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE)
            ->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)
            ->pluck('title');

        $this->assertContains(self::SELLER_METHOD_TITLE, $titles);
        $this->assertNotContains(self::ADMIN_METHOD_TITLE, $titles);
    }

    public function test_retail_in_house_shipping_offers_admin_methods_for_a_vendor_shop(): void
    {
        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::RETAIL, title: self::ADMIN_METHOD_TITLE);
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::RETAIL, title: self::SELLER_METHOD_TITLE);

        $titles = $this->retailService(responsibility: RetailShippingMethodService::SHIPPING_METHOD_INHOUSE)
            ->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)
            ->pluck('title');

        $this->assertContains(self::ADMIN_METHOD_TITLE, $titles);
        $this->assertNotContains(self::SELLER_METHOD_TITLE, $titles);
    }

    public function test_retail_vendor_shipping_offers_vendor_methods_for_a_vendor_shop(): void
    {
        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::RETAIL, title: self::ADMIN_METHOD_TITLE);
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::RETAIL, title: self::SELLER_METHOD_TITLE);

        $titles = $this->retailService(responsibility: RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE)
            ->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)
            ->pluck('title');

        $this->assertContains(self::SELLER_METHOD_TITLE, $titles);
        $this->assertNotContains(self::ADMIN_METHOD_TITLE, $titles);
    }

    public function test_wholesale_in_house_shipping_never_offers_a_retail_method(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::RETAIL, title: self::ADMIN_METHOD_TITLE);

        $titles = $this->wholesaleService(responsibility: RetailShippingMethodService::SHIPPING_METHOD_INHOUSE)
            ->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)
            ->pluck('title');

        $this->assertNotContains(self::ADMIN_METHOD_TITLE, $titles);
    }

    public function test_offered_methods_match_the_cart_shipping_options_of_the_same_shop(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $this->createMethod(creatorId: 1, creatorType: 'admin', context: BusinessModule::WHOLESALE, title: self::ADMIN_METHOD_TITLE);
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: self::SELLER_METHOD_TITLE);

        foreach ([RetailShippingMethodService::SHIPPING_METHOD_INHOUSE, RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE] as $responsibility) {
            $service = $this->wholesaleService(responsibility: $responsibility);
            $shop = ['added_by' => RetailShippingMethodService::CREATOR_SELLER, 'user_id' => $seller->id];

            $this->assertSame(
                $service->getCartShippingOptions(product: $shop, shippingType: RetailShippingMethodService::TYPE_ORDER_WISE)?->pluck('id')->all(),
                $service->getActiveMethodsForSeller(sellerId: $seller->id, sellerIs: RetailShippingMethodService::CREATOR_SELLER)->pluck('id')->all(),
            );
        }
    }

    private function wholesaleService(string $responsibility): ShippingMethodService
    {
        $service = new class(app(ShippingMethod::class)) extends ShippingMethodService
        {
            public string $settingName = '';

            protected function responsibilitySetting(): string
            {
                return $this->settingName;
            }
        };

        $service->settingName = $this->useSetting(value: $responsibility);

        return $service;
    }

    private function retailService(string $responsibility): RetailShippingMethodService
    {
        $service = new class(app(ShippingMethod::class)) extends RetailShippingMethodService
        {
            public string $settingName = '';

            protected function responsibilitySetting(): string
            {
                return $this->settingName;
            }
        };

        $service->settingName = $this->useSetting(value: $responsibility);

        return $service;
    }

    private function useSetting(string $value): string
    {
        $name = 'shipping_responsibility_fixture_'.Str::lower(Str::random(16));
        $setting = BusinessSetting::query()->create(['type' => $name, 'value' => $value]);

        getWebConfig(name: 'company_name');
        (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']?->push($setting);

        return $name;
    }

    private function createMethod(int $creatorId, string $creatorType, string $context, string $title): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'creator_id' => $creatorId,
            'creator_type' => $creatorType,
            'title' => $title,
            'duration' => '3 days',
            'cost' => 10,
            'status' => 1,
            'context' => $context,
        ]);
    }

    private function seller(): Seller
    {
        return $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'approved seller',
        );
    }
}
