<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\CategoryShippingCost;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Services\New\CategoryShippingCostService as RetailCategoryShippingCostService;
use App\Services\New\ShippingMethodService as RetailShippingMethodService;
use App\Services\New\ShippingTypeService as RetailShippingTypeService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Services\Shipping\CategoryShippingCostService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class ShippingHooksTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const FIXTURE_SELLER_ID = 990201;

    private const FIXTURE_CATEGORY_ID = 990202;

    private const PRODUCT_SHIPPING_COST = 9;

    private const RETAIL_CATEGORY_COST = 11;

    private const WHOLESALE_CATEGORY_COST = 77;

    private const RETAIL_METHOD_COSTS = [20, 30];

    private const WHOLESALE_METHOD_COSTS = [70, 90];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_retail_shipping_type_reads_shipping_method_and_retail_rows(): void
    {
        $this->assertSame('shipping_method', $this->responsibilitySettingOf(app(RetailShippingTypeService::class)));
        $this->assertSame(RetailShippingTypeService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: 'shipping_method'));

        $this->seedShippingType(sellerId: RetailShippingTypeService::ADMIN_SELLER_ID, context: BusinessModule::WHOLESALE, type: RetailShippingTypeService::TYPE_CATEGORY_WISE);
        $this->seedShippingType(sellerId: RetailShippingTypeService::ADMIN_SELLER_ID, context: BusinessModule::RETAIL, type: RetailShippingTypeService::TYPE_PRODUCT_WISE);
        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::WHOLESALE, type: RetailShippingTypeService::TYPE_ORDER_WISE);
        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::RETAIL, type: RetailShippingTypeService::TYPE_CATEGORY_WISE);
        $this->seedCategoryShippingCosts(contexts: [BusinessModule::WHOLESALE, BusinessModule::RETAIL]);

        $service = app(RetailShippingTypeService::class);
        $orderWiseCostRange = ['min' => 1, 'max' => 2];

        $inHouseProduct = $this->product(addedBy: RetailShippingTypeService::ADDED_BY_ADMIN);
        $this->assertSame(RetailShippingTypeService::TYPE_PRODUCT_WISE, $service->resolveTypeForProduct(product: $inHouseProduct));
        $this->assertSame((float) self::PRODUCT_SHIPPING_COST, $service->resolveProductShippingCost(product: $inHouseProduct, quantity: 3));
        $this->assertEquals([
            'delivery_cost' => self::PRODUCT_SHIPPING_COST,
            'delivery_cost_max' => 0,
            'delivery_cost_min' => 0,
            'shipping_type' => RetailShippingTypeService::TYPE_PRODUCT_WISE,
        ], $service->getProductDeliveryCharge(product: $inHouseProduct, quantity: 3, orderWiseCostRange: $orderWiseCostRange));

        $sellerProduct = $this->product(addedBy: 'seller');
        $this->assertSame(RetailShippingTypeService::TYPE_CATEGORY_WISE, $service->resolveTypeForProduct(product: $sellerProduct));
        $this->assertSame((float) self::RETAIL_CATEGORY_COST, $service->resolveProductShippingCost(product: $sellerProduct, quantity: 3));
        $this->assertEquals([
            'delivery_cost' => self::RETAIL_CATEGORY_COST,
            'delivery_cost_max' => 0,
            'delivery_cost_min' => 0,
            'shipping_type' => RetailShippingTypeService::TYPE_CATEGORY_WISE,
        ], $service->getProductDeliveryCharge(product: $sellerProduct, quantity: 3, orderWiseCostRange: $orderWiseCostRange));
    }

    public function test_retail_order_wise_cost_range_reads_shipping_method_and_retail_rows(): void
    {
        $this->assertSame('shipping_method', $this->responsibilitySettingOf(app(RetailShippingMethodService::class)));
        $this->assertSame(RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: 'shipping_method'));

        $this->seedShippingMethods(contexts: [BusinessModule::WHOLESALE, BusinessModule::RETAIL]);

        $this->assertEquals(
            ['min' => self::RETAIL_METHOD_COSTS[0], 'max' => self::RETAIL_METHOD_COSTS[1]],
            app(RetailShippingMethodService::class)->getOrderWiseCostRange(product: $this->product(addedBy: RetailShippingMethodService::CREATOR_SELLER)),
        );
    }

    public function test_wholesale_shipping_type_reads_wholesale_shipping_method(): void
    {
        $this->assertSame(ShippingMethodService::RESPONSIBILITY_SETTING, $this->responsibilitySettingOf(app(ShippingTypeService::class)));
        $this->assertSame(RetailShippingTypeService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: ShippingMethodService::RESPONSIBILITY_SETTING));

        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::RETAIL, type: RetailShippingTypeService::TYPE_ORDER_WISE);
        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::WHOLESALE, type: RetailShippingTypeService::TYPE_PRODUCT_WISE);

        $sellerProduct = $this->product(addedBy: 'seller');
        $service = app(ShippingTypeService::class);

        $this->assertSame(RetailShippingTypeService::TYPE_PRODUCT_WISE, $service->resolveTypeForProduct(product: $sellerProduct));
        $this->assertSame((float) self::PRODUCT_SHIPPING_COST, $service->resolveProductShippingCost(product: $sellerProduct, quantity: 3));
        $this->assertSame(RetailShippingTypeService::TYPE_ORDER_WISE, app(RetailShippingTypeService::class)->resolveTypeForProduct(product: $sellerProduct));
    }

    public function test_wholesale_category_wise_cost_reads_the_wholesale_context_row(): void
    {
        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::RETAIL, type: RetailShippingTypeService::TYPE_CATEGORY_WISE);
        $this->seedShippingType(sellerId: self::FIXTURE_SELLER_ID, context: BusinessModule::WHOLESALE, type: RetailShippingTypeService::TYPE_CATEGORY_WISE);
        $this->seedCategoryShippingCosts(contexts: [BusinessModule::RETAIL, BusinessModule::WHOLESALE]);

        $sellerProduct = $this->product(addedBy: 'seller');
        $service = app(ShippingTypeService::class);

        $this->assertSame((float) self::WHOLESALE_CATEGORY_COST, $service->resolveProductShippingCost(product: $sellerProduct, quantity: 3));
        $this->assertEquals([
            'delivery_cost' => self::WHOLESALE_CATEGORY_COST,
            'delivery_cost_max' => 0,
            'delivery_cost_min' => 0,
            'shipping_type' => RetailShippingTypeService::TYPE_CATEGORY_WISE,
        ], $service->getProductDeliveryCharge(product: $sellerProduct, quantity: 3, orderWiseCostRange: ['min' => 1, 'max' => 2]));
        $this->assertSame((float) self::RETAIL_CATEGORY_COST, app(RetailShippingTypeService::class)->resolveProductShippingCost(product: $sellerProduct, quantity: 3));
    }

    public function test_wholesale_order_wise_cost_range_reads_the_wholesale_setting_and_context(): void
    {
        $this->assertSame(ShippingMethodService::RESPONSIBILITY_SETTING, $this->responsibilitySettingOf(app(ShippingMethodService::class)));
        $this->assertSame(RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: ShippingMethodService::RESPONSIBILITY_SETTING));

        $this->seedShippingMethods(contexts: [BusinessModule::RETAIL, BusinessModule::WHOLESALE]);

        $this->assertEquals(
            ['min' => self::WHOLESALE_METHOD_COSTS[0], 'max' => self::WHOLESALE_METHOD_COSTS[1]],
            app(ShippingMethodService::class)->getOrderWiseCostRange(product: $this->product(addedBy: RetailShippingMethodService::CREATOR_SELLER)),
        );
    }

    public function test_category_shipping_cost_create_writes_the_service_context(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $retailRow = app(RetailCategoryShippingCostService::class)->create(data: [
            'seller_id' => self::FIXTURE_SELLER_ID,
            'category_id' => self::FIXTURE_CATEGORY_ID,
            'context' => BusinessModule::WHOLESALE,
        ]);
        $wholesaleRow = app(CategoryShippingCostService::class)->create(data: [
            'seller_id' => self::FIXTURE_SELLER_ID,
            'category_id' => self::FIXTURE_CATEGORY_ID,
        ]);

        $this->assertSame(BusinessModule::RETAIL, $retailRow->context);
        $this->assertSame(BusinessModule::WHOLESALE, $wholesaleRow->context);
        $this->assertNull(app(RetailCategoryShippingCostService::class)->find(id: $wholesaleRow->id));
        $this->assertNull(app(CategoryShippingCostService::class)->find(id: $retailRow->id));
    }

    /** @return array<string, mixed> */
    private function product(string $addedBy): array
    {
        return [
            'added_by' => $addedBy,
            'user_id' => self::FIXTURE_SELLER_ID,
            'category_id' => self::FIXTURE_CATEGORY_ID,
            'category_ids' => json_encode([['id' => (string) self::FIXTURE_CATEGORY_ID, 'position' => 1]]),
            'multiply_qty' => 0,
            'shipping_cost' => self::PRODUCT_SHIPPING_COST,
        ];
    }

    private function responsibilitySettingOf(object $service): string
    {
        $hook = new ReflectionMethod($service, 'responsibilitySetting');
        $hook->setAccessible(true);

        return $hook->invoke($service);
    }

    private function seedShippingType(int $sellerId, string $context, string $type): void
    {
        ShippingType::query()->updateOrCreate(
            ['seller_id' => $sellerId, 'context' => $context],
            ['shipping_type' => $type],
        );
    }

    private function seedCategoryShippingCosts(array $contexts): void
    {
        $costByContext = [
            BusinessModule::RETAIL => self::RETAIL_CATEGORY_COST,
            BusinessModule::WHOLESALE => self::WHOLESALE_CATEGORY_COST,
        ];

        foreach ($contexts as $context) {
            CategoryShippingCost::query()->updateOrCreate(
                ['seller_id' => self::FIXTURE_SELLER_ID, 'category_id' => self::FIXTURE_CATEGORY_ID, 'context' => $context],
                ['cost' => $costByContext[$context], 'multiply_qty' => 0],
            );
        }
    }

    private function seedShippingMethods(array $contexts): void
    {
        $costsByContext = [
            BusinessModule::RETAIL => self::RETAIL_METHOD_COSTS,
            BusinessModule::WHOLESALE => self::WHOLESALE_METHOD_COSTS,
        ];

        foreach ($contexts as $context) {
            foreach ($costsByContext[$context] as $cost) {
                ShippingMethod::query()->create([
                    'creator_id' => self::FIXTURE_SELLER_ID,
                    'creator_type' => RetailShippingMethodService::CREATOR_SELLER,
                    'title' => 'Shipping hooks fixture',
                    'cost' => $cost,
                    'duration' => '3 days',
                    'status' => 1,
                    'context' => $context,
                ]);
            }
        }
    }
}
