<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Seller;
use App\Models\ShippingType;
use App\Models\User;
use App\Services\New\CartService;
use App\Services\New\CartShippingService;
use App\Services\New\ShippingMethodService;
use App\Services\New\ShippingTypeService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailCartUnchangedTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const FIXTURE_SELLER_ID = 990002;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_retail_refresh_still_reprices_from_unit_price(): void
    {
        $product = $this->retailProduct();
        $customer = $this->customer();
        $row = $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price + 123);

        $this->cartService()->refreshPrices(customerId: $customer->id, guestId: null);

        $this->assertEquals($product->unit_price, $row->fresh()->price);
    }

    public function test_retail_discounted_subtotal_is_unchanged(): void
    {
        $product = $this->retailProduct();
        $customer = $this->customer();
        $row = $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price);
        $row->forceFill(['discount' => 999])->save();

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);
        $listedItem = $cartItems->firstWhere('id', $row->id);
        $productDiscount = getProductPriceByType(
            product: $listedItem->product,
            type: 'discounted_amount',
            result: 'value',
            price: $listedItem->price,
        );
        $subtotal = $this->cartService()->getDiscountedSubtotal(cartItems: $cartItems);

        $this->assertEqualsWithDelta($productDiscount, $listedItem->discount, 0.0001);
        $this->assertEqualsWithDelta(($row->price - $productDiscount) * $row->quantity, $subtotal, 0.0001);
        $this->assertNotEqualsWithDelta(($row->price - 999) * $row->quantity, $subtotal, 0.0001);
    }

    public function test_retail_shipping_type_resolution_is_unchanged(): void
    {
        ShippingType::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'shipping_type' => ShippingTypeService::TYPE_ORDER_WISE,
            'context' => BusinessModule::RETAIL,
        ]);
        ShippingType::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'shipping_type' => ShippingTypeService::TYPE_CATEGORY_WISE,
            'context' => BusinessModule::WHOLESALE,
        ]);

        $this->assertSame(
            ShippingTypeService::TYPE_ORDER_WISE,
            app(ShippingTypeService::class)->resolveTypeForSeller(self::FIXTURE_SELLER_ID),
        );
    }

    public function test_retail_clear_forgets_the_retail_session_keys(): void
    {
        $product = $this->retailProduct();
        $customer = $this->customer();
        $row = $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price);

        $clearedKeys = $this->cartService()->clearOrderedCartGroups(cartGroupIds: [$row->cart_group_id]);

        $this->assertSame(CartService::ORDERED_CART_SESSION_KEYS, $clearedKeys);
        $this->assertNull(Cart::query()->whereKey($row->id)->first());
    }

    public function test_retail_checkout_blockers_read_the_retail_shipping_setting(): void
    {
        $this->assertSame('shipping_method', $this->retailShippingResponsibilitySetting());
        $this->assertSame(ShippingMethodService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: 'shipping_method'));

        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->whereHas('shop')->first(),
            'approved seller with a shop',
        );
        $product = $this->requireFixture(
            Product::query()
                ->where('business_module', BusinessModule::RETAIL)
                ->active()
                ->where(['product_type' => CartService::PRODUCT_TYPE_PHYSICAL, 'added_by' => 'seller', 'user_id' => $seller->id])
                ->first(),
            'active retail physical product owned by an approved seller',
        );
        $customer = $this->customer();

        ShippingType::query()->updateOrCreate(
            ['seller_id' => $seller->id, 'context' => BusinessModule::RETAIL],
            ['shipping_type' => ShippingTypeService::TYPE_ORDER_WISE],
        );

        $this->addRetailRow(
            product: $product,
            customer: $customer,
            price: (float) $product->unit_price,
            sellerId: (int) $seller->id,
            sellerIs: 'seller',
        );

        $blockers = $this->cartService()->getCheckoutBlockers(
            customerId: $customer->id,
            sessionGuestId: null,
            requestGuestId: null,
            activeShippingMethodCounts: [
                ShippingMethodService::CREATOR_ADMIN => 5,
                ShippingMethodService::CREATOR_SELLER => [(int) $seller->id => 0],
            ],
        );

        $this->assertSame(CartService::STATUS_FAILED, $blockers['status']);
        $this->assertContains(
            CartService::REASON_SHIPPING_NOT_AVAILABLE_FOR_SHOP,
            array_column($blockers['blockers'], 'reason'),
        );
    }

    public function test_retail_seller_minimum_reads_minimum_order_amount(): void
    {
        $this->assertSame('minimum_order_amount', $this->callSeam(method: 'sellerMinimumOrderAmountColumn'));

        [$seller, $product] = $this->sellerRetailProduct();
        $customer = $this->customer();
        Cart::query()->where('customer_id', $customer->id)->delete();
        Seller::query()->whereKey($seller->id)->update(['minimum_order_amount' => 999999999, 'wholesale_minimum_order_amount' => 0]);
        $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price, sellerId: (int) $seller->id, sellerIs: 'seller');
        $service = $this->cartServiceWithSellerMinimumOn();

        $singleProduct = $service->verifySingleProductMinimumOrderAmount(product: $product->fresh(['seller.shop']), totalAmount: 100);
        $cartList = $service->verifyCartListMinimumOrderAmount(customerId: $customer->id, sessionGuestId: null, requestGuestId: null);

        $this->assertSame(CartService::STATUS_FAILED, $singleProduct['status']);
        $this->assertEqualsWithDelta(999999999, (float) $singleProduct['minimum_order_amount'], 0.0001);
        $this->assertSame(CartService::STATUS_FAILED, $cartList['status']);
        $this->assertContains(CartService::REASON_BELOW_MINIMUM_ORDER_AMOUNT, array_column($cartList['reasons'], 'reason'));
    }

    public function test_retail_billing_input_reads_billing_input_by_customer(): void
    {
        $this->assertSame('billing_input_by_customer', $this->callSeam(method: 'billingInputSetting'));
        $this->assertSame((bool) getWebConfig(name: 'billing_input_by_customer'), $this->cartService()->isBillingInputByCustomer());
    }

    public function test_retail_cart_item_discount_is_the_product_discount(): void
    {
        $product = $this->retailProduct();
        $customer = $this->customer();
        $row = $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price);
        $row->forceFill(['discount' => 999])->save();

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);
        $detailedItem = $this->cartService()->applyCartListDetails(
            cartItems: $cartItems,
            orderGenerateCartList: [],
            customerId: $customer->id,
            guestId: null,
        )->firstWhere('id', $row->id);
        $productDiscount = getProductPriceByType(
            product: $detailedItem->product,
            type: 'discounted_amount',
            result: 'value',
            price: $detailedItem->price,
        );

        $this->assertSame($productDiscount, $detailedItem->discount);
        $this->assertNotEqualsWithDelta(999, $detailedItem->discount, 0.0001);
        $this->assertSame($product->current_stock, $detailedItem->product->total_current_stock);
    }

    public function test_retail_update_shipping_for_carts_reads_shipping_method(): void
    {
        $this->assertSame('shipping_method', $this->callSeam(method: 'shippingResponsibilitySetting'));

        [$seller, $product] = $this->sellerRetailProduct();
        $customer = $this->customer();
        $this->useShippingType(sellerId: CartService::ADMIN_SHIPPING_SELLER_ID, context: BusinessModule::RETAIL, shippingType: ShippingTypeService::TYPE_ORDER_WISE);
        $this->useShippingType(sellerId: (int) $seller->id, context: BusinessModule::RETAIL, shippingType: ShippingTypeService::TYPE_PRODUCT_WISE);
        $this->useShippingType(sellerId: (int) $seller->id, context: BusinessModule::WHOLESALE, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        $row = $this->addRetailRow(product: $product, customer: $customer, price: (float) $product->unit_price, sellerId: (int) $seller->id, sellerIs: 'seller');
        $expectedShippingType = match (getWebConfig(name: 'shipping_method')) {
            ShippingMethodService::SHIPPING_METHOD_SELLERWISE => ShippingTypeService::TYPE_PRODUCT_WISE,
            ShippingMethodService::SHIPPING_METHOD_INHOUSE => ShippingTypeService::TYPE_ORDER_WISE,
            default => '',
        };

        $this->cartService()->updateShippingForCarts(cartList: Cart::query()->with('product')->whereKey($row->id)->get());

        $this->assertSame($expectedShippingType, $row->fresh()->shipping_type);
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
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
        $name = 'retail_cart_hooks_fixture_'.Str::lower(Str::random(16));
        $setting = BusinessSetting::query()->create(['type' => $name, 'value' => $value]);

        getWebConfig(name: 'company_name');
        (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings']?->push($setting);

        return $name;
    }

    private function callSeam(string $method): mixed
    {
        $seam = new ReflectionMethod(CartService::class, $method);

        return $seam->invoke($this->cartService());
    }

    private function useShippingType(int $sellerId, string $context, string $shippingType): void
    {
        ShippingType::query()->where(['seller_id' => $sellerId, 'context' => $context])->delete();
        ShippingType::query()->create(['seller_id' => $sellerId, 'context' => $context, 'shipping_type' => $shippingType]);
    }

    /** @return array{0: Seller, 1: Model} */
    private function sellerRetailProduct(): array
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->whereHas('shop')->whereHas('product', fn (Builder $query) => $query
                ->where('business_module', BusinessModule::RETAIL)
                ->active()
                ->where(['product_type' => CartService::PRODUCT_TYPE_PHYSICAL, 'added_by' => 'seller']))->first(),
            'approved seller with a shop and an active retail physical product',
        );
        $product = Product::query()
            ->where('business_module', BusinessModule::RETAIL)
            ->active()
            ->where(['product_type' => CartService::PRODUCT_TYPE_PHYSICAL, 'added_by' => 'seller', 'user_id' => $seller->id])
            ->with('seller.shop')
            ->first();

        return [$seller, $product];
    }

    private function retailShippingResponsibilitySetting(): string
    {
        $seam = new ReflectionMethod(CartService::class, 'shippingResponsibilitySetting');
        $seam->setAccessible(true);

        return $seam->invoke($this->cartService());
    }

    private function addRetailRow(Model $product, User $customer, float $price, ?int $sellerId = null, string $sellerIs = 'admin'): Cart
    {
        return Cart::query()->create([
            'business_module' => BusinessModule::RETAIL,
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'cart_group_id' => $customer->id.'-'.Str::random(5).'-'.time(),
            'product_id' => $product->id,
            'product_type' => CartService::PRODUCT_TYPE_PHYSICAL,
            'variant' => '',
            'variations' => json_encode([]),
            'choices' => json_encode([]),
            'quantity' => max(1, (int) $product->minimum_order_qty),
            'price' => $price,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'is_checked' => 1,
            'slug' => $product->slug,
            'name' => $product->name,
            'thumbnail' => $product->thumbnail,
            'seller_id' => $sellerId ?? CartService::IN_HOUSE_CART_SELLER_ID,
            'seller_is' => $sellerIs,
            'shipping_cost' => 0,
            'shipping_type' => ShippingTypeService::TYPE_ORDER_WISE,
        ]);
    }

    private function retailProduct(): Model
    {
        return $this->requireFixture(
            Product::query()
                ->where('business_module', BusinessModule::RETAIL)
                ->active()
                ->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)
                ->where('added_by', 'admin')
                ->where(fn (Builder $query) => $query->whereNull('colors')->orWhere('colors', '[]'))
                ->where(fn (Builder $query) => $query->whereNull('choice_options')->orWhere('choice_options', '[]'))
                ->where('unit_price', '>', 0)
                ->first(),
            'active in-house retail physical product without variations',
        );
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');
    }
}
