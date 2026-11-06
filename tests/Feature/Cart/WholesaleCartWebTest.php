<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\ColorService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Enums\CheckoutStep;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\CheckoutService;
use Modules\Wholesale\app\Services\CouponService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCartWebTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const VIEW_CONTRACT = [
        'cartGroups',
        'summary',
        'coupon',
        'orderNote',
        'isEmpty',
        'isCustomer',
        'checkoutUrl',
        'cartUrls',
        'checkoutBlockers',
    ];

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-cart-views';

    private const CART_VIEW_STUB = '{{ count($cartGroups) }}';

    private const SIDEBAR_VIEW_STUB = '{{ $summary["total_label"] }}';

    private array $stubbedViewPaths = [];

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
        foreach ($this->stubbedViewPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_cart_page_renders_every_product_case(): void
    {
        $customer = $this->customer();
        $tiered = $this->createTieredProduct();
        [$variationTier, $sizeAxis] = $this->createVariationTierProduct();
        [$colored, $colorAxis, $colorCode] = $this->createColorPricedProduct();

        $this->addRow(product: $tiered, customer: $customer, quantity: 60);
        $this->addRow(product: $variationTier, customer: $customer, quantity: 20, selection: [$sizeAxis => 'M']);
        $this->addRow(product: $colored, customer: $customer, quantity: 10, selection: [$colorAxis => $colorCode]);

        $response = $this->openCartPage(customer: $customer)->assertViewHasAll(self::VIEW_CONTRACT);
        $items = $this->flattenItems(cartGroups: $response->viewData('cartGroups'));

        $this->assertCount(3, $items);
        $this->assertFalse($response->viewData('isEmpty'));
        $this->assertTrue($response->viewData('isCustomer'));
        $this->assertSame(route('wholesale.orders.checkout'), $response->viewData('checkoutUrl'));

        foreach ([$tiered, $variationTier, $colored] as $product) {
            $this->assertRowMatchesItsQuote(product: $product, customer: $customer);
        }

        $tieredItem = $this->findItemByProduct(items: $items, productId: $tiered->id);
        $this->assertSame(40.0, $tieredItem['unit_price']);
        $this->assertSame(webCurrencyConverter(amount: 40.0).' (≥ 50 pc)', $tieredItem['tier_label']);
        $this->assertTrue($tieredItem['is_available']);
        $this->assertNull($tieredItem['message']);

        $coloredItem = $this->findItemByProduct(items: $items, productId: $colored->id);
        $this->assertSame([['title' => 'Color', 'value' => app(ColorService::class)->getNameMap()[$colorCode]]], $coloredItem['selection_labels']);
    }

    public function test_two_variants_of_one_product_are_two_rows(): void
    {
        $customer = $this->customer();
        [$product, $sizeAxis] = $this->createVariationTierProduct();

        $this->addRow(product: $product, customer: $customer, quantity: 20, selection: [$sizeAxis => 'S']);
        $this->addRow(product: $product, customer: $customer, quantity: 5, selection: [$sizeAxis => 'M']);

        $items = $this->flattenItems(cartGroups: $this->openCartPage(customer: $customer)->viewData('cartGroups'));

        $this->assertCount(2, $items);
        $this->assertSame([90.0, 110.0], collect($items)->pluck('unit_price')->sort()->values()->all());
        $this->assertSame(['M', 'S'], collect($items)->pluck('variant')->sort()->values()->all());
    }

    public function test_rows_from_two_shops_are_two_groups(): void
    {
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $adminProduct = $this->createTieredProduct();
        $sellerProduct = $this->createTieredProduct(
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        );

        $this->addRow(product: $adminProduct, customer: $customer, quantity: 60);
        $this->addRow(product: $sellerProduct, customer: $customer, quantity: 60);

        $cartGroups = $this->openCartPage(customer: $customer)->viewData('cartGroups');

        $this->assertCount(2, $cartGroups);
        $this->assertCount(2, collect($cartGroups)->pluck('cart_group_id')->unique());
        $this->assertSame(
            [$adminProduct->id, $sellerProduct->id],
            collect($cartGroups)->flatMap(static fn (array $group): array => collect($group['items'])->pluck('product_id')->all())->sort()->values()->all(),
        );
        $this->assertSame([2400.0, 2400.0], collect($cartGroups)->pluck('subtotal')->all());
        $this->assertNotSame($cartGroups[0]['shop']['name'], $cartGroups[1]['shop']['name']);
    }

    public function test_retail_rows_are_not_listed(): void
    {
        $customer = $this->customer();
        $wholesaleProduct = $this->createTieredProduct();
        $retailProduct = $this->retailProduct();

        $this->addRow(product: $wholesaleProduct, customer: $customer, quantity: 60);

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $retailProduct->minimum_order_qty])
            ->assertOk();

        $items = $this->flattenItems(cartGroups: $this->openCartPage(customer: $customer)->viewData('cartGroups'));

        $this->assertSame([$wholesaleProduct->id], collect($items)->pluck('product_id')->all());
        $this->assertTrue(
            Cart::query()
                ->where(['customer_id' => $customer->id, 'product_id' => $retailProduct->id, 'business_module' => BusinessModule::RETAIL])
                ->exists(),
        );
    }

    public function test_empty_cart_renders_the_empty_state(): void
    {
        $customer = $this->customerWithoutCart();

        $response = $this->openCartPage(customer: $customer);

        $this->assertTrue($response->viewData('isEmpty'));
        $this->assertSame([], $response->viewData('cartGroups'));
        $this->assertSame(0.0, $response->viewData('summary')['total']);
    }

    public function test_cart_page_marks_the_cart_step_current(): void
    {
        foreach ([$this->customerWithoutCart(), $this->customer()] as $customer) {
            $steps = $this->openCartPage(customer: $customer)->viewData('steps');

            $this->assertSame(
                [CheckoutStep::CART, CheckoutStep::ADDRESS, CheckoutStep::PAYMENT],
                array_column($steps, 'key'),
            );
            $this->assertSame([true, false, false], array_column($steps, 'is_current'));
            $this->assertSame([false, false, false], array_column($steps, 'is_complete'));
        }

        $this->flushSession();
        $this->assertSame([true, false, false], array_column($this->get(route('wholesale.shop-cart'))->viewData('steps'), 'is_current'));
    }

    public function test_quantity_crossing_a_tier_reprices_the_row(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $this->assertEquals(50, $row->price);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 60])
            ->assertOk();

        $this->assertEquals(40.0, $response->json('item.unit_price'));
        $this->assertSame(60, $response->json('item.quantity'));
        $this->assertEquals(40, $row->fresh()->price);
        $this->assertRowMatchesItsQuote(product: $product, customer: $customer);
        $this->assertEquals(40.0 * 60, $response->json('summary.subtotal'));
    }

    public function test_quantity_below_the_minimum_is_refused(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 5])
            ->assertUnprocessable()
            ->assertJson([
                'reason' => ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER,
                'message' => 'Minimum order quantity is 10',
                'qty' => 10,
            ]);

        $this->assertSame(10, $row->fresh()->quantity);
    }

    public function test_quantity_above_the_last_tier_is_refused(): void
    {
        $customer = $this->customer();
        $product = $this->createBoundedTierProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 200])
            ->assertUnprocessable()
            ->assertJsonPath('reason', ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY);

        $this->assertSame(10, $row->fresh()->quantity);
    }

    public function test_quantity_above_the_variant_stock_is_refused(): void
    {
        $customer = $this->customer();
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 20, selection: [$sizeAxis => 'M']);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 101])
            ->assertUnprocessable()
            ->assertJsonPath('reason', ProductWholesaleService::REASON_EXCEEDS_STOCK);

        $this->assertSame(20, $row->fresh()->quantity);
    }

    public function test_a_deactivated_product_flags_the_row_and_blocks_checkout(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $this->addRow(product: $product, customer: $customer, quantity: 60);

        app(ProductWholesaleService::class)->updateStatus($product->id, false);

        $response = $this->openCartPage(customer: $customer);
        $items = $this->flattenItems(cartGroups: $response->viewData('cartGroups'));

        $this->assertCount(1, $items);
        $this->assertFalse($items[0]['is_available']);
        $this->assertSame('This product is no longer available', $items[0]['message']);
        $this->assertFalse($items[0]['is_checked']);
        $this->assertNotEmpty($response->viewData('checkoutBlockers'));
        $this->assertSame(
            ['The items in your cart are no longer available. Remove them and add other products to continue'],
            $response->viewData('cartSummaryBlockers'),
        );
        $this->assertSame(1, $response->viewData('unavailableItemCount'));
        $this->assertSame(0.0, $response->viewData('summary')['subtotal']);
    }

    public function test_unchecked_rows_leave_the_totals(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 60);

        $this->assertSame(2400.0, $this->openCartPage(customer: $customer)->viewData('summary')['subtotal']);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.select'), ['cart_id' => $row->id, 'is_checked' => false])
            ->assertOk()
            ->assertJsonPath('summary.subtotal', 0);

        $response = $this->openCartPage(customer: $customer);

        $this->assertSame(0.0, $response->viewData('summary')['subtotal']);
        $this->assertFalse($this->flattenItems(cartGroups: $response->viewData('cartGroups'))[0]['is_checked']);
    }

    public function test_coupon_applies_and_is_dropped_when_the_cart_no_longer_qualifies(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 60);
        $coupon = $this->createCoupon(discount: 100, minimumPurchase: 2000);

        $applied = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.coupon.apply'), ['code' => $coupon->code])
            ->assertOk();

        $this->assertSame($coupon->code, $applied->json('coupon.code'));
        $this->assertEquals(100.0, $applied->json('summary.coupon_discount'));
        $this->assertEquals(2400.0 - 100.0, $applied->json('summary.total'));

        $dropped = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 10])
            ->assertOk();

        $this->assertNotNull($dropped->json('summary.coupon_message'));
        $this->assertEquals(0, $dropped->json('summary.coupon_discount'));
        $this->assertNull(session(CheckoutSessionKey::COUPON_CODE));

        $response = $this->openCartPage(customer: $customer);

        $this->assertNull($response->viewData('coupon'));
        $this->assertSame(0.0, $response->viewData('summary')['coupon_discount']);
    }

    public function test_coupon_can_be_removed(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $this->addRow(product: $product, customer: $customer, quantity: 60);
        $coupon = $this->createCoupon(discount: 100, minimumPurchase: 2000);

        $this->actingAs($customer, 'customer')->postJson(route('wholesale.cart.coupon.apply'), ['code' => $coupon->code])->assertOk();

        $this->actingAs($customer, 'customer')
            ->deleteJson(route('wholesale.cart.coupon.remove'))
            ->assertOk()
            ->assertJsonPath('summary.coupon_discount', 0);

        $this->assertNull(session(CheckoutSessionKey::COUPON_CODE));
    }

    public function test_free_delivery_meter_updates_with_the_subtotal(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $firstGroup = $this->openCartPage(customer: $customer)->viewData('cartGroups')[0];

        $this->assertArrayHasKey('free_delivery', $firstGroup);
        $this->assertSame(['status', 'amount', 'percentage', 'remaining_label'], array_keys($firstGroup['free_delivery']));
        $this->assertSame(500.0, $firstGroup['subtotal']);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 60])
            ->assertOk();

        $this->assertSame(2400.0, $this->openCartPage(customer: $customer)->viewData('cartGroups')[0]['subtotal']);
    }

    public function test_another_customers_row_cannot_be_updated_or_removed(): void
    {
        $owner = $this->customer();
        $intruder = $this->customerWithoutCart();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $owner, quantity: 60);

        $this->actingAs($intruder, 'customer')
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 10])
            ->assertNotFound();

        $this->actingAs($intruder, 'customer')
            ->postJson(route('wholesale.cart.remove'), ['cart_id' => $row->id])
            ->assertNotFound();

        $this->actingAs($intruder, 'customer')
            ->postJson(route('wholesale.cart.select'), ['cart_id' => $row->id, 'is_checked' => false])
            ->assertNotFound();

        $this->assertSame(60, $row->fresh()->quantity);
        $this->assertSame(1, $row->fresh()->is_checked);
    }

    public function test_guest_can_edit_their_cart(): void
    {
        $product = $this->createTieredProduct();
        $guestId = $this->guestId();

        $this->withSession(['guest_id' => $guestId])
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 10])
            ->assertOk();

        $row = Cart::query()->where(['customer_id' => $guestId, 'is_guest' => CartService::GUEST, 'product_id' => $product->id])->firstOrFail();

        $this->withSession(['guest_id' => $guestId])
            ->postJson(route('wholesale.cart.quantity'), ['cart_id' => $row->id, 'quantity' => 60])
            ->assertOk()
            ->assertJsonPath('item.unit_price', 40);

        $this->withSession(['guest_id' => $guestId])
            ->postJson(route('wholesale.cart.remove'), ['cart_id' => $row->id])
            ->assertOk()
            ->assertJsonPath('is_empty', true);

        $this->assertNull($row->fresh());
    }

    public function test_guest_checkout_link_points_at_login(): void
    {
        $product = $this->createTieredProduct();
        $guestId = $this->guestId();

        $this->withSession(['guest_id' => $guestId])
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 10])
            ->assertOk();

        $this->stubThemeView(view: 'orders.cart', contents: self::CART_VIEW_STUB);

        $response = $this->withSession(['guest_id' => $guestId])
            ->get(route('wholesale.shop-cart'))
            ->assertOk();

        $this->assertFalse($response->viewData('isCustomer'));
        $this->assertSame(route('customer.auth.login'), $response->viewData('checkoutUrl'));
        $this->assertCount(1, $this->flattenItems(cartGroups: $response->viewData('cartGroups')));
    }

    public function test_summary_endpoint_renders_the_sidebar_and_the_same_totals(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $this->addRow(product: $product, customer: $customer, quantity: 60);
        $this->stubThemeView(view: 'orders.partials._cart-sidebars', contents: self::SIDEBAR_VIEW_STUB);

        $response = $this->actingAs($customer, 'customer')->getJson(route('wholesale.cart.summary'))->assertOk();

        $this->assertSame($response->json('summary.total_label'), trim($response->json('html')));
        $this->assertEquals(2400.0, $response->json('summary.subtotal'));
        $this->assertIsArray($response->json('checkout_blockers'));
    }

    public function test_shipping_method_is_rejected_when_it_is_not_offered_for_the_shop(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 60);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.shipping-method'), [
                'cart_group_id' => $row->cart_group_id,
                'shipping_method_id' => 99999999,
            ])
            ->assertUnprocessable();
    }

    public function test_inhouse_shipping_sets_one_method_on_every_cart_group(): void
    {
        if (! app(ShippingMethodService::class)->isInhouseShipping()) {
            $this->markTestSkipped('Wholesale shipping responsibility is not inhouse.');
        }

        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(
            product: $this->createTieredProduct(addedBy: ProductWholesaleService::ADDED_BY_SELLER, userId: (int) $shop->seller_id, shopId: (int) $shop->id),
            customer: $customer,
            quantity: 60,
        );
        $shippingMethod = ShippingMethod::query()->create([
            'creator_id' => ShippingMethodService::IN_HOUSE_CREATOR_ID,
            'creator_type' => ShippingMethodService::CREATOR_ADMIN,
            'title' => 'Wholesale shared shipping fixture',
            'duration' => '3 days',
            'cost' => 15,
            'status' => 1,
            'context' => BusinessModule::WHOLESALE,
        ]);

        $cartPage = $this->openCartPage(customer: $customer);
        $sharedShipping = $cartPage->viewData('sharedShipping');

        foreach ($cartPage->viewData('cartGroups') as $cartGroup) {
            $this->assertNotContains(
                translate('Please select a shipping method for :shop', ['shop' => $cartGroup['shop']['name']]),
                $cartGroup['blockers'],
            );
        }

        $this->assertNotNull($sharedShipping);
        $this->assertNull($sharedShipping['selected_shipping_method_id']);
        $this->assertContains($shippingMethod->id, array_column($sharedShipping['shipping_methods'], 'id'));

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.shipping-method'), [
                'cart_group_id' => CheckoutService::ALL_CART_GROUPS,
                'shipping_method_id' => $shippingMethod->id,
            ])
            ->assertOk();

        $cartGroupIds = $this->cartService()->getCartGroupIds(customerId: $customer->id, guestId: null);

        $this->assertCount(2, $cartGroupIds);
        $this->assertSame(
            [$shippingMethod->id, $shippingMethod->id],
            CartShipping::query()->whereIn('cart_group_id', $cartGroupIds)->pluck('shipping_method_id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );
        $this->assertSame($shippingMethod->id, $this->openCartPage(customer: $customer)->viewData('sharedShipping')['selected_shipping_method_id']);
    }

    private function assertRowMatchesItsQuote(Model $product, User $customer): void
    {
        $row = Cart::query()
            ->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER, 'product_id' => $product->id])
            ->firstOrFail();

        $quote = $this->service()->getPriceQuote(
            $this->reload($product),
            $this->cartService()->getRowSelection(cartItem: $row),
            (int) $row->quantity,
            app(ColorService::class)->getNameMap(),
        );

        $this->assertEqualsWithDelta($quote['total'], ($row->price - $row->discount + $row->tax) * $row->quantity, 0.0001);
        $this->assertEqualsWithDelta($quote['unit_price'], $row->price, 0.0001);
        $this->assertEqualsWithDelta($quote['discount_per_unit'], $row->discount, 0.0001);
    }

    /** @param  array<int, array<string, mixed>>  $cartGroups */
    private function flattenItems(array $cartGroups): array
    {
        return collect($cartGroups)->flatMap(static fn (array $group): array => $group['items'])->values()->all();
    }

    /** @param  array<int, array<string, mixed>>  $items */
    private function findItemByProduct(array $items, int $productId): array
    {
        return collect($items)->firstWhere('product_id', $productId);
    }

    private function openCartPage(User $customer): TestResponse
    {
        $this->stubThemeView(view: 'orders.cart', contents: self::CART_VIEW_STUB);

        return $this->actingAs($customer, 'customer')->get(route('wholesale.shop-cart'))->assertOk();
    }

    private function addRow(Model $product, User $customer, int $quantity, array $selection = []): Cart
    {
        $quote = $this->service()->getPriceQuote($this->reload($product), $selection, $quantity, app(ColorService::class)->getNameMap());

        return $this->cartService()->addWholesaleItem(
            product: $this->reload($product),
            quote: $quote,
            customerId: $customer->id,
            guestId: null,
            selection: $selection,
            shippingType: ShippingTypeService::TYPE_ORDER_WISE,
            shippingCost: 0.0,
        );
    }

    private function createCoupon(float $discount, float $minimumPurchase): Model
    {
        return app(CouponService::class)->create(data: [
            'coupon_type' => CouponService::TYPE_DISCOUNT_ON_PURCHASE,
            'title' => 'Wholesale cart web fixture',
            'code' => 'WSCARTWEB'.random_int(10000, 99999),
            'start_date' => now()->subDay()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'min_purchase' => $minimumPurchase,
            'coupon_bearer' => CouponService::BEARER_INHOUSE,
            'seller_id' => CouponService::VENDOR_ALL,
            'customer_id' => CouponService::CUSTOMER_ALL,
            'limit' => 100,
            'discount_type' => CouponService::DISCOUNT_TYPE_AMOUNT,
            'discount' => $discount,
            'max_discount' => $discount,
        ]);
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function createTieredProduct(string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN, ?int $userId = null, int $shopId = 0): Model
    {
        return $this->createProduct(addedBy: $addedBy, userId: $userId, shopId: $shopId, overrides: [
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 50],
            'tier_max_qty' => ['49', ''],
            'tier_unit_price' => [50, 40],
        ]);
    }

    private function createBoundedTierProduct(): Model
    {
        return $this->createProduct(overrides: [
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 50],
            'tier_max_qty' => ['49', '100'],
            'tier_unit_price' => [50, 40],
        ]);
    }

    /** @return array{0: Model, 1: string} */
    private function createVariationTierProduct(): array
    {
        $attributeId = $this->attributeId();

        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 200,
            'choice_attributes' => [$attributeId],
            'choice_no' => [$attributeId],
            'choice' => ['Size'],
            'choice_options_'.$attributeId => ['S,M'],
            'qty_S' => 100,
            'qty_M' => 100,
            'variation_based_price' => 1,
            'variation_type' => (string) $attributeId,
            'quantity_based_price' => 1,
            'tier_min_qty' => [1, 10],
            'tier_max_qty' => ['9', ''],
            'tier_unit_price' => [null, null],
            'variation_tier_price' => ['S' => [100, 90], 'M' => [110, 95]],
        ]);

        return [$product, 'choice_'.$attributeId];
    }

    /** @return array{0: Model, 1: string, 2: string} */
    private function createColorPricedProduct(): array
    {
        $colorCode = (string) $this->requireFixture(Color::query()->value('code'), 'colour');

        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 200,
            'colors_active' => 1,
            'colors' => [$colorCode],
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [25],
        ], colorNames: app(ColorService::class)->getNameMap());

        Product::query()->whereKey($product->id)->update(['current_stock' => 200]);
        $product->stocks()->update(['qty' => 200]);

        return [$this->reload($product), ProductWholesaleService::AXIS_COLOR, $colorCode];
    }

    private function createProduct(
        array $overrides,
        string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN,
        ?int $userId = null,
        int $shopId = 0,
        array $colorNames = [],
    ): Model {
        $product = $this->service()->create(
            addedBy: $addedBy,
            userId: $userId,
            shopId: $shopId,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale cart web fixture'],
                'description' => ['Wholesale cart web fixture'],
                'code' => 'WSWEB'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                ...$overrides,
            ],
            colorNames: $colorNames,
        );

        $this->service()->updateStatus($product->id, true);
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        return $this->reload($product);
    }

    private function reload(Model $product): Model
    {
        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }

    private function retailProduct(): Model
    {
        return $this->requireFixture(
            Product::query()
                ->where('business_module', BusinessModule::RETAIL)
                ->active()
                ->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)
                ->where(fn (Builder $query) => $query->whereNull('colors')->orWhere('colors', '[]'))
                ->where(fn (Builder $query) => $query->whereNull('choice_options')->orWhere('choice_options', '[]'))
                ->whereColumn('current_stock', '>=', 'minimum_order_qty')
                ->where('minimum_order_qty', '>', 0)
                ->first(),
            'active retail physical product without variations',
        );
    }

    private function sellerShop(): Shop
    {
        return $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->first(),
            'approved seller with a shop',
        );
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');
    }

    private function customerWithoutCart(): User
    {
        return $this->requireFixture(
            User::query()->where('id', '>', 0)->where('is_active', 1)->whereKeyNot($this->customer()->id)->first(),
            'second active customer',
        );
    }

    private function attributeId(): int
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        return (int) $ids[0];
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function stubThemeView(string $view, string $contents): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/'.str_replace('.', '/', $view).'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
