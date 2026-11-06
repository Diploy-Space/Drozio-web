<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CategoryShippingCost;
use App\Models\Product;
use App\Models\Seller;
use App\Models\ShippingAddress;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService as RetailCartService;
use App\Services\New\CartShippingService;
use App\Services\New\ColorService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\CategoryShippingCostService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Modules\Wholesale\app\Services\ShopService;
use ReflectionFunction;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCartSettingsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CART_ITEMS_URI = '/api/v6/wholesale/customer/cart/items';

    private const CHECKOUT_URI = '/api/v6/wholesale/customer/checkout';

    private const CHECKOUT_ADDRESS_URI = '/api/v6/wholesale/customer/checkout/address';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_minimum_order_amount_reads_the_wholesale_settings(): void
    {
        $this->assertSame([
            'status' => 'wholesale_vendor_can_set_minimum_order_amount',
            'by_seller' => 'wholesale_vendor_minimum_order_amount_status',
            'in_house_amount' => BusinessSettingService::MINIMUM_ORDER_AMOUNT,
        ], $this->callSeam(service: app(CartService::class), method: 'minimumOrderAmountSettings'));

        $this->assertSame([
            'status' => 'minimum_order_amount_status',
            'by_seller' => 'minimum_order_amount_by_seller',
            'in_house_amount' => 'minimum_order_amount',
        ], $this->callSeam(service: app(RetailCartService::class), method: 'minimumOrderAmountSettings'));
    }

    public function test_free_delivery_reads_the_wholesale_settings_and_seller_columns(): void
    {
        $this->assertSame([
            'status' => 'wholesale_free_delivery_status',
            'responsibility' => 'wholesale_free_delivery_responsibility',
            'in_house_status' => 'wholesale_inhouse_free_delivery_status',
            'in_house_amount' => 'wholesale_free_delivery_over_amount',
            'seller_amount' => 'wholesale_free_delivery_over_amount_seller',
            'seller_status_column' => 'wholesale_free_delivery_status',
            'seller_amount_column' => 'wholesale_free_delivery_over_amount',
        ], $this->callSeam(service: app(CartService::class), method: 'freeDeliverySettings'));

        $this->assertSame([
            'status' => 'free_delivery_status',
            'responsibility' => 'free_delivery_responsibility',
            'in_house_status' => 'inhouse_free_delivery_status',
            'in_house_amount' => 'free_delivery_over_amount',
            'seller_amount' => 'free_delivery_over_amount_seller',
            'seller_status_column' => 'free_delivery_status',
            'seller_amount_column' => 'free_delivery_over_amount',
        ], $this->callSeam(service: app(RetailCartService::class), method: 'freeDeliverySettings'));
    }

    public function test_free_delivery_for_a_seller_row_uses_the_wholesale_seller_threshold(): void
    {
        if ((int) getWebConfig(name: 'wholesale_free_delivery_status') !== 1
            || getWebConfig(name: 'wholesale_free_delivery_responsibility') !== 'seller') {
            $this->markTestSkipped('Test database does not enable seller-responsible wholesale free delivery.');
        }

        $shop = $this->sellerShop();
        $shop->seller->forceFill([
            'free_delivery_status' => 0,
            'free_delivery_over_amount' => 1,
            'wholesale_free_delivery_status' => 1,
            'wholesale_free_delivery_over_amount' => 12345,
        ])->save();
        $row = $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $this->customer(), quantity: 60);

        $freeDelivery = $this->cartService()->getFreeDeliveryOrderAmount(
            cartGroupId: (string) $row->cart_group_id,
            freeDeliveryCartItem: $row,
            seller: $shop->seller->fresh(),
        );

        $this->assertSame(1, (int) $freeDelivery['status']);
        $this->assertEqualsWithDelta(12345.0, (float) $freeDelivery['amount'], 0.0001);
    }

    public function test_checkout_blocks_a_shop_closed_for_wholesale_and_ignores_a_retail_only_closure(): void
    {
        $shop = $this->sellerShop();
        $customer = $this->customer();
        $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);

        $shop->forceFill(['temporary_close' => 1, 'wholesale_temporary_close' => 0, 'vacation_status' => 0])->save();
        $this->assertNotContains(RetailCartService::REASON_UNAVAILABLE_VENDORS, $this->blockerReasons(customer: $customer));

        $shop->forceFill(['temporary_close' => 0, 'wholesale_temporary_close' => 1])->save();
        $this->assertContains(RetailCartService::REASON_UNAVAILABLE_VENDORS, $this->blockerReasons(customer: $customer));
    }

    public function test_store_cards_get_the_wholesale_availability(): void
    {
        $shop = $this->sellerShop();
        $shop->forceFill(['temporary_close' => 1, 'wholesale_temporary_close' => 0, 'vacation_status' => 0])->save();

        $shops = new Collection([$shop->fresh()]);
        app(ShopService::class)->attachWholesaleAvailability(shops: $shops);

        $this->assertFalse($shops[0]->is_temporarily_closed);
        $this->assertFalse($shops[0]->is_on_vacation);

        $shop->forceFill(['temporary_close' => 0, 'wholesale_temporary_close' => 1])->save();
        $shops = new Collection([$shop->fresh()]);
        app(ShopService::class)->attachWholesaleAvailability(shops: $shops);

        $this->assertTrue($shops[0]->is_temporarily_closed);
    }

    public function test_the_payment_method_choice_is_forgotten_with_the_other_checkout_keys(): void
    {
        $this->assertContains(CheckoutSessionKey::PAYMENT_METHOD, CheckoutSessionKey::ALL);
        $this->assertContains(CheckoutSessionKey::PAYMENT_METHOD, $this->cartService()->clearOrderedCartGroups(cartGroupIds: []));
    }

    public function test_api_quantity_update_needs_no_cart_id_in_the_body(): void
    {
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $this->putJson(self::CART_ITEMS_URI.'/'.$row->id, ['quantity' => 70], $this->actingAsCustomer($customer))
            ->assertOk();

        $this->assertSame(70, (int) $row->fresh()->quantity);
    }

    public function test_api_cart_summary_matches_the_web_cart_summary(): void
    {
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $webSummary = $this->actingAs($customer, 'customer')->getJson(route('wholesale.cart.summary'))->assertOk()->json('summary');
        $apiSummary = $this->getJson('/api/v6/wholesale/customer/cart', $this->actingAsCustomer($customer))->assertOk()->json('content.data.summary');

        foreach (['subtotal', 'discount', 'coupon_discount', 'shipping', 'tax', 'total'] as $key) {
            $this->assertEqualsWithDelta((float) $webSummary[$key], (float) $apiSummary[$key], 0.000001, $key);
        }
    }

    public function test_a_created_category_shipping_cost_belongs_to_the_wholesale_context(): void
    {
        $service = app(CategoryShippingCostService::class);
        $created = $service->create(data: ['seller_id' => 0, 'category_id' => $this->categoryId(), 'cost' => 7, 'context' => BusinessModule::RETAIL]);

        $this->assertSame(BusinessModule::WHOLESALE, CategoryShippingCost::query()->whereKey($created->id)->value('context'));
        $this->assertNotNull($service->findBy(params: ['id' => $created->id]));
    }

    public function test_current_stock_is_deducted_atomically_and_never_below_zero(): void
    {
        $product = $this->createTieredProduct();
        Product::query()->whereKey($product->id)->update(['current_stock' => 50]);

        $this->assertTrue($this->productService()->deductCurrentStockForOrderItem(productId: $product->id, quantity: 30));
        $this->assertFalse($this->productService()->deductCurrentStockForOrderItem(productId: $product->id, quantity: 30));
        $this->assertSame(20, (int) Product::query()->whereKey($product->id)->value('current_stock'));
    }

    public function test_wholesale_seller_minimum_reads_the_wholesale_column(): void
    {
        $this->assertSame('wholesale_minimum_order_amount', $this->callSettingNameSeam(method: 'sellerMinimumOrderAmountColumn'));

        $shop = $this->sellerShop();
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $product = $this->createSellerTieredProduct(shop: $shop);
        $this->addRow(product: $product, customer: $customer, quantity: 10);
        $service = $this->cartServiceWithSellerMinimumOn();

        $this->setSellerMinimums(sellerId: (int) $shop->seller_id, retailMinimum: 999999999, wholesaleMinimum: 0);
        $this->assertSame(RetailCartService::STATUS_SUCCESS, $service->verifySingleProductMinimumOrderAmount(product: $this->reload(productId: $product->id), totalAmount: 100)['status']);
        $this->assertSame(RetailCartService::STATUS_SUCCESS, $service->verifyCartListMinimumOrderAmount(customerId: $customer->id, sessionGuestId: null, requestGuestId: null)['status']);

        $this->setSellerMinimums(sellerId: (int) $shop->seller_id, retailMinimum: 0, wholesaleMinimum: 999999999);
        $singleProduct = $service->verifySingleProductMinimumOrderAmount(product: $this->reload(productId: $product->id), totalAmount: 100);
        $cartList = $service->verifyCartListMinimumOrderAmount(customerId: $customer->id, sessionGuestId: null, requestGuestId: null);

        $this->assertSame(RetailCartService::STATUS_FAILED, $singleProduct['status']);
        $this->assertEqualsWithDelta(999999999, (float) $singleProduct['minimum_order_amount'], 0.0001);
        $this->assertSame(RetailCartService::STATUS_FAILED, $cartList['status']);
        $this->assertContains(RetailCartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT, array_column($cartList['reasons'], 'reason'));
    }

    public function test_wholesale_billing_input_reads_the_wholesale_setting(): void
    {
        $this->assertSame('wholesale_billing_address_status', $this->callSettingNameSeam(method: 'billingInputSetting'));

        if (! getWebConfig(name: 'billing_input_by_customer') || getWebConfig(name: 'wholesale_billing_address_status')) {
            $this->markTestSkipped('Test database does not have retail billing on and wholesale billing off.');
        }

        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);

        $this->assertTrue(app(RetailCartService::class)->isBillingInputByCustomer());
        $this->assertFalse($this->cartService()->isBillingInputByCustomer());

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.address'), ['address_id' => $address->id])
            ->assertOk()
            ->assertSessionHas(CheckoutSessionKey::BILLING_ADDRESS_ID, $address->id);

        $this->putJson(self::CHECKOUT_ADDRESS_URI, ['address_id' => $address->id], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('content.data.billing_address_id', $address->id);

        $this->getJson(self::CHECKOUT_URI, $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('content.data.billing_input_by_customer', false);
    }

    public function test_wholesale_cart_keeps_the_stored_variation_discount(): void
    {
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        Cart::query()->whereKey($row->id)->update(['discount' => 7]);

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);
        $listedItem = $cartItems->firstWhere('id', $row->id);
        $detailedItem = $this->cartService()->applyCartListDetails(
            cartItems: $this->cartService()->getCartItemList(customerId: $customer->id),
            orderGenerateCartList: [],
            customerId: $customer->id,
            guestId: null,
        )->firstWhere('id', $row->id);

        $this->assertTrue($listedItem->product->relationLoaded('wholesaleDetail'));
        $this->assertTrue($listedItem->product->relationLoaded('stocks'));
        $this->assertEqualsWithDelta(7, $listedItem->discount, 0.0001);
        $this->assertEqualsWithDelta(($row->price - 7) * $row->quantity, $this->cartService()->getDiscountedSubtotal(cartItems: $cartItems), 0.0001);
        $this->assertEqualsWithDelta(7, $detailedItem->discount, 0.0001);
        $this->assertSame(1000, $detailedItem->product->total_current_stock);
    }

    public function test_wholesale_update_shipping_for_carts_reads_the_wholesale_setting(): void
    {
        $this->assertSame(ShippingMethodService::RESPONSIBILITY_SETTING, $this->callSettingNameSeam(method: 'shippingResponsibilitySetting'));

        $shop = $this->sellerShop();
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        $this->useShippingType(sellerId: RetailCartService::ADMIN_SHIPPING_SELLER_ID, context: BusinessModule::WHOLESALE, shippingType: ShippingTypeService::TYPE_ORDER_WISE);
        $this->useShippingType(sellerId: (int) $shop->seller_id, context: BusinessModule::WHOLESALE, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        $this->useShippingType(sellerId: (int) $shop->seller_id, context: BusinessModule::RETAIL, shippingType: ShippingTypeService::TYPE_PRODUCT_WISE);
        $row = $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $expectedShippingType = match (getWebConfig(name: ShippingMethodService::RESPONSIBILITY_SETTING)) {
            RetailCartService::SHIPPING_METHOD_SELLERWISE => ShippingTypeService::TYPE_CATEGORY_WISE,
            RetailCartService::SHIPPING_METHOD_INHOUSE => ShippingTypeService::TYPE_ORDER_WISE,
            default => '',
        };

        $this->cartService()->updateShippingForCarts(cartList: $this->cartService()->getCartItemList(customerId: $customer->id));

        $this->assertSame($expectedShippingType, $row->fresh()->shipping_type);
    }

    /** @return array<int, string> */
    private function blockerReasons(User $customer): array
    {
        return array_column($this->cartService()->getCheckoutBlockers(
            customerId: $customer->id,
            sessionGuestId: null,
            requestGuestId: null,
            activeShippingMethodCounts: app(ShippingMethodService::class)->getActiveMethodCountsByCreator(),
        )['blockers'], 'reason');
    }

    /** @return array<string, string> */
    private function callSeam(object $service, string $method): array
    {
        $reflection = new ReflectionMethod($service, $method);

        return $reflection->invoke($service);
    }

    private function callSettingNameSeam(string $method): string
    {
        return (new ReflectionMethod(CartService::class, $method))->invoke($this->cartService());
    }

    private function cartServiceWithSellerMinimumOn(): CartService
    {
        $service = new class(app(Cart::class), app(ShippingTypeService::class), app(CartShippingService::class)) extends CartService
        {
            public array $settingNames = [];

            protected function minimumOrderAmountSettings(): array
            {
                return [...parent::minimumOrderAmountSettings(), ...$this->settingNames];
            }
        };

        $service->settingNames = [
            'status' => $this->useSetting(value: 1),
            'by_seller' => $this->useSetting(value: 1),
        ];

        return $service;
    }

    private function useSetting(int|float $value): string
    {
        $name = 'wholesale_cart_hooks_fixture_'.Str::lower(Str::random(16));
        $setting = BusinessSetting::query()->create(['type' => $name, 'value' => $value]);

        getWebConfig(name: 'company_name');
        (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']?->push($setting);

        return $name;
    }

    private function setSellerMinimums(int $sellerId, float $retailMinimum, float $wholesaleMinimum): void
    {
        Seller::query()->whereKey($sellerId)->update([
            'minimum_order_amount' => $retailMinimum,
            'wholesale_minimum_order_amount' => $wholesaleMinimum,
        ]);
    }

    private function useShippingType(int $sellerId, string $context, string $shippingType): void
    {
        ShippingType::query()->where(['seller_id' => $sellerId, 'context' => $context])->delete();
        ShippingType::query()->create(['seller_id' => $sellerId, 'context' => $context, 'shipping_type' => $shippingType]);
    }

    private function createAddress(User $customer): ShippingAddress
    {
        if (getWebConfig(name: 'delivery_country_restriction')) {
            $this->markTestSkipped('Test database restricts delivery countries.');
        }

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale cart settings fixture',
            'address_type' => 'office',
            'address' => 'Warehouse road 1',
            'city' => 'Dhaka',
            'zip' => '1200',
            'country' => 'Bangladesh',
            'phone' => '01700000000',
            'latitude' => '23.8',
            'longitude' => '90.4',
            'is_billing' => 0,
        ]);
    }

    private function addRow(Model $product, User $customer, int $quantity): Cart
    {
        $reloaded = $this->reload(productId: $product->id);

        return $this->cartService()->addWholesaleItem(
            product: $reloaded,
            quote: $this->productService()->getPriceQuote($reloaded, [], $quantity, app(ColorService::class)->getNameMap()),
            customerId: $customer->id,
            guestId: null,
            selection: [],
            shippingType: ShippingTypeService::TYPE_CATEGORY_WISE,
            shippingCost: 0.0,
        );
    }

    private function createTieredProduct(): Model
    {
        return $this->createProduct();
    }

    private function createSellerTieredProduct(Shop $shop): Model
    {
        return $this->createProduct(addedBy: ProductWholesaleService::ADDED_BY_SELLER, userId: (int) $shop->seller_id, shopId: (int) $shop->id);
    }

    private function createProduct(string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN, ?int $userId = null, int $shopId = 0): Model
    {
        $product = $this->productService()->create(
            addedBy: $addedBy,
            userId: $userId,
            shopId: $shopId,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale cart settings fixture'],
                'description' => ['Wholesale cart settings fixture'],
                'code' => 'WSSET'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                'minimum_order_qty' => 10,
                'current_stock' => 1000,
                'quantity_based_price' => 1,
                'tier_min_qty' => [10, 50],
                'tier_max_qty' => ['49', ''],
                'tier_unit_price' => [50, 40],
            ],
            colorNames: [],
        );

        $this->productService()->updateStatus($product->id, true);
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        return $this->reload(productId: $product->id);
    }

    private function reload(int $productId): Model
    {
        return $this->productService()->findUnscoped($productId, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }

    private function sellerShop(): Shop
    {
        return $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->with('seller')->first(),
            'approved seller with a shop',
        );
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function productService(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }
}
