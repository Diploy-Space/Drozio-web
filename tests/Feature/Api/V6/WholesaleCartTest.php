<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Shop;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCartTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CART_URI = '/api/v6/wholesale/customer/cart';

    private const CART_ITEMS_URI = '/api/v6/wholesale/customer/cart/items';

    private const CART_MERGE_GUEST_URI = '/api/v6/wholesale/customer/cart/merge-guest';

    private const RETAIL_CART_MERGE_GUEST_URI = '/api/v6/customer/cart/merge-guest';

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

    public function test_cart_list_prices_every_row_from_the_quote(): void
    {
        $customer = $this->customer();
        $tieredProduct = $this->createTieredProduct();
        [$variationProduct, $sizeAxis] = $this->createVariationTierProduct();

        $this->addItem(customer: $customer, productId: $tieredProduct->id, quantity: 60);
        $this->addItem(customer: $customer, productId: $variationProduct->id, quantity: 20, variation: [$sizeAxis => 'M']);

        $response = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk();
        $items = $this->flatItems(response: $response->json('content.data'));

        $this->assertCount(2, $items);

        foreach ([$tieredProduct->id => [60, []], $variationProduct->id => [20, [$sizeAxis => 'M']]] as $productId => [$quantity, $selection]) {
            $item = $items->firstWhere('product_id', $productId);
            $quote = $this->service()->getPriceQuote($this->reload(Product::find($productId)), $selection, $quantity);

            $this->assertNotNull($item);
            $this->assertTrue($item['is_available']);
            $this->assertNull($item['message']);
            $this->assertEqualsWithDelta($quote['unit_price'], $item['unit_price'], 0.0001);
            $this->assertEqualsWithDelta($quote['discount_per_unit'], $item['discount_per_unit'], 0.0001);
            $this->assertEqualsWithDelta($quote['total'], $item['line_total'], 0.0001);
            $this->assertSame($quote['minimum_quantity'], $item['minimum_quantity']);
            $this->assertSame($quote['available_stock'], $item['available_stock']);
            $this->assertNotSame('', $item['line_total_label']);
        }

        $this->assertSame([['title' => 'Size', 'value' => 'M']], $items->firstWhere('product_id', $variationProduct->id)['selection_labels']);

        $productPageQuote = $this->postJson('/api/v6/wholesale/products/'.$tieredProduct->id.'/price-quote', ['quantity' => 60])
            ->assertOk()
            ->json('content.data.total');

        $this->assertEqualsWithDelta($productPageQuote, $items->firstWhere('product_id', $tieredProduct->id)['line_total'], 0.0001);
    }

    public function test_cart_list_groups_rows_by_shop(): void
    {
        $customer = $this->customer();
        $shop = $this->approvedSellerShop();
        $inHouseProduct = $this->createTieredProduct();
        $sellerProduct = $this->createTieredProduct(
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        );

        $this->addItem(customer: $customer, productId: $inHouseProduct->id, quantity: 10);
        $this->addItem(customer: $customer, productId: $sellerProduct->id, quantity: 10);

        $cart = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');

        $this->assertCount(2, $cart['cart_groups']);
        $this->assertCount(2, collect($cart['cart_groups'])->pluck('cart_group_id')->unique());
        $this->assertSame(
            [CartService::SELLER_IS_ADMIN => 1, 'seller' => 1],
            [
                CartService::SELLER_IS_ADMIN => collect($cart['cart_groups'])->filter(fn (array $group): bool => $group['shop']['name'] === getInHouseShopConfig(key: 'name'))->count(),
                'seller' => collect($cart['cart_groups'])->filter(fn (array $group): bool => $group['shop']['name'] === $shop->name)->count(),
            ],
        );
        $this->assertEqualsWithDelta(
            collect($cart['cart_groups'])->sum('subtotal'),
            $cart['summary']['subtotal'],
            0.0001,
        );
    }

    public function test_cart_list_excludes_retail_rows(): void
    {
        $customer = $this->customer();
        $wholesaleProduct = $this->createTieredProduct();
        $retailProduct = $this->retailProduct();

        $this->addItem(customer: $customer, productId: $wholesaleProduct->id, quantity: 10);
        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $retailProduct->minimum_order_qty])
            ->assertOk();

        $items = $this->flatItems(response: $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data'));

        $this->assertSame([$wholesaleProduct->id], $items->pluck('product_id')->all());
        $this->assertNotNull(Cart::query()->where([
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'product_id' => $retailProduct->id,
            'business_module' => BusinessModule::RETAIL,
        ])->first());
    }

    public function test_cart_list_reprices_a_row_after_the_price_setup_changes(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);
        $this->assertEquals(40, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->price);

        $this->raiseSecondTierPrice(product: $product, unitPrice: 70);

        $items = $this->flatItems(response: $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data'));
        $quote = $this->service()->getPriceQuote($this->reload(Product::find($product->id)), [], 60);

        $this->assertEqualsWithDelta(70, $items->first()['unit_price'], 0.0001);
        $this->assertEqualsWithDelta($quote['total'], $items->first()['line_total'], 0.0001);
        $this->assertEquals(70, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->price);
    }

    public function test_cart_list_flags_a_deactivated_row_and_blocks_checkout(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);

        Product::query()->whereKey($product->id)->update(['status' => 0]);

        $cart = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');
        $items = $this->flatItems(response: $cart);

        $this->assertCount(1, $items);
        $this->assertFalse($items->first()['is_available']);
        $this->assertSame('This product is no longer available', $items->first()['message']);
        $this->assertEqualsWithDelta(0, $cart['summary']['total'], 0.0001);
        $this->assertContains('Please remove this unavailable product to continue', $cart['checkout_blockers']);
    }

    public function test_an_unchecked_row_is_excluded_from_the_summary(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);

        $checked = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');
        $this->assertGreaterThan(0, $checked['summary']['subtotal']);

        Cart::query()->whereKey($this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id)->update(['is_checked' => 0]);

        $unchecked = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');

        $this->assertFalse($this->flatItems(response: $unchecked)->first()['is_checked']);
        $this->assertEqualsWithDelta(0, $unchecked['summary']['subtotal'], 0.0001);
        $this->assertEqualsWithDelta(0, $unchecked['summary']['total'], 0.0001);
        $this->assertContains('Please add or check items before proceeding to checkout', $unchecked['checkout_blockers']);
    }

    public function test_delete_item_and_clear_empty_the_cart(): void
    {
        $customer = $this->customer();
        $first = $this->createTieredProduct();
        $second = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $first->id, quantity: 10);
        $this->addItem(customer: $customer, productId: $second->id, quantity: 10);

        $removed = $this->deleteJson(
            self::CART_ITEMS_URI.'/'.$this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $first->id)->id,
            [],
            $this->actingAsCustomer($customer),
        )->assertOk();

        $this->assertSame('cart_item_removed_200', $removed->json('identical_code'));
        $this->assertFalse($removed->json('content.data.is_empty'));
        $this->assertSame([$second->id], $this->flatItems(response: $removed->json('content.data'))->pluck('product_id')->all());

        $cleared = $this->deleteJson(self::CART_URI, [], $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame('cart_cleared_200', $cleared->json('identical_code'));
        $this->assertTrue($cleared->json('content.data.is_empty'));
        $this->assertSame(0, $cleared->json('content.data.cart_count'));
        $this->assertSame([], $cleared->json('content.data.cart_groups'));
    }

    public function test_another_customers_row_is_not_found(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $cartId = $this->addRowForCustomer(customer: $customer, product: $product, quantity: 10)->id;

        $this->deleteJson(self::CART_ITEMS_URI.'/'.$cartId, $this->guestParams(), [])
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'cart_item_not_found_404');

        $this->assertNotNull(Cart::query()->find($cartId));
    }

    public function test_a_guest_reads_and_clears_only_its_own_cart(): void
    {
        $customer = $this->customer();
        $customerProduct = $this->createTieredProduct();
        $guestProduct = $this->createTieredProduct();

        $this->addRowForCustomer(customer: $customer, product: $customerProduct, quantity: 10);
        $this->postJson(self::CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $guestProduct->id, 'quantity' => 10])->assertOk();

        $guestItems = $this->flatItems(response: $this->getJson(self::CART_URI.'?'.http_build_query($this->guestParams()))->assertOk()->json('content.data'));
        $this->assertSame([$guestProduct->id], $guestItems->pluck('product_id')->all());

        $this->deleteJson(self::CART_URI, $this->guestParams())->assertOk();

        $this->assertNull($this->guestRow(productId: (int) $guestProduct->id));
        $this->assertSame(
            [$customerProduct->id],
            $this->flatItems(response: $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data'))->pluck('product_id')->all(),
        );
    }

    public function test_merge_guest_moves_the_guest_cart_into_the_customer_cart(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->postJson(self::CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 10])->assertOk();

        $this->postJson(self::CART_MERGE_GUEST_URI, ['cart_guest_id' => $this->guestId()], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'cart_guest_merged_200');

        $this->assertNull($this->guestRow(productId: (int) $product->id));
        $this->assertContains(
            $product->id,
            $this->flatItems(response: $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data'))->pluck('product_id')->all(),
        );
    }

    public function test_merge_guest_leaves_retail_guest_rows_to_the_retail_endpoint(): void
    {
        $customer = $this->customer();
        $retailProduct = $this->retailProduct();

        $this->postJson('/api/v6/customer/cart/items', [
            ...$this->guestParams(),
            'id' => $retailProduct->id,
            'quantity' => $retailProduct->minimum_order_qty,
        ])->assertOk();

        $this->postJson(self::CART_MERGE_GUEST_URI, ['cart_guest_id' => $this->guestId()], $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame(1, $this->retailRowCount(ownerId: $this->guestId(), isGuest: CartService::GUEST, productId: (int) $retailProduct->id));

        $this->postJson(self::RETAIL_CART_MERGE_GUEST_URI, ['cart_guest_id' => $this->guestId()], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'cart_guest_merged_200');

        $this->assertSame(0, $this->retailRowCount(ownerId: $this->guestId(), isGuest: CartService::GUEST, productId: (int) $retailProduct->id));
        $this->assertSame(1, $this->retailRowCount(ownerId: (int) $customer->id, isGuest: CartService::CUSTOMER, productId: (int) $retailProduct->id));
    }

    public function test_merge_guest_without_the_guest_cart_id_is_refused(): void
    {
        $customer = $this->customer();

        foreach ([self::CART_MERGE_GUEST_URI, self::RETAIL_CART_MERGE_GUEST_URI] as $uri) {
            $this->postJson($uri, $this->guestParams(), $this->actingAsCustomer($customer))
                ->assertUnprocessable()
                ->assertJsonPath('errors.0.error_code', 'cart_guest_id');
        }
    }

    public function test_merge_guest_keeps_the_row_when_the_guest_id_equals_the_customer_id(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        app(CartService::class)->addWholesaleItem(
            product: $product,
            quote: app(ProductWholesaleService::class)->getPriceQuote($product, [], 10),
            customerId: null,
            guestId: (string) $customer->id,
            selection: [],
            shippingType: '',
            shippingCost: 0,
        );

        $this->postJson(self::CART_MERGE_GUEST_URI, ['cart_guest_id' => $customer->id], $this->actingAsCustomer($customer))->assertOk();

        $this->assertNotNull($this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id));
    }

    public function test_an_unknown_identity_is_refused(): void
    {
        $this->getJson(self::CART_URI)
            ->assertUnauthorized()
            ->assertJsonPath('identical_code', 'guest_identity_required_401');
    }

    public function test_a_coupon_code_discounts_the_summary_and_remove_coupon_drops_it(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $coupon = $this->wholesaleCoupon();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);

        $withoutCoupon = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data');
        $withCoupon = $this->getJson(self::CART_URI.'?coupon_code='.$coupon->code, $this->actingAsCustomer($customer))->assertOk()->json('content.data');

        $this->assertNull($withoutCoupon['coupon']);
        $this->assertEqualsWithDelta(0, $withoutCoupon['summary']['coupon_discount'], 0.0001);
        $this->assertSame($coupon->code, $withCoupon['coupon']['code']);
        $this->assertEqualsWithDelta($coupon->discount, $withCoupon['summary']['coupon_discount'], 0.0001);
        $this->assertEqualsWithDelta(
            $withoutCoupon['summary']['total'] - $coupon->discount,
            $withCoupon['summary']['total'],
            0.0001,
        );

        $removed = $this->deleteJson(self::CART_URI.'/coupon', [], $this->actingAsCustomer($customer))->assertOk()->json('content.data');

        $this->assertNull($removed['coupon']);
        $this->assertEqualsWithDelta($withoutCoupon['summary']['total'], $removed['summary']['total'], 0.0001);
    }

    public function test_update_quantity_reprices_across_a_tier(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $cartId = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id;

        $response = $this->putJson(self::CART_ITEMS_URI.'/'.$cartId, [
            'cart_id' => $cartId,
            'quantity' => 60,
        ], $this->actingAsCustomer($customer))->assertOk();

        $quote = $this->service()->getPriceQuote($this->reload(Product::find($product->id)), [], 60);
        $item = $this->flatItems(response: $response->json('content.data'))->first();

        $this->assertSame('cart_quantity_updated_200', $response->json('identical_code'));
        $this->assertSame(60, $item['quantity']);
        $this->assertEqualsWithDelta(40, $item['unit_price'], 0.0001);
        $this->assertEqualsWithDelta($quote['total'], $item['line_total'], 0.0001);
        $this->assertEquals(40, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->price);
    }

    public function test_update_quantity_below_the_minimum_is_refused_and_restores_the_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $cartId = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id;

        $this->putJson(self::CART_ITEMS_URI.'/'.$cartId, ['cart_id' => $cartId, 'quantity' => 5], $this->actingAsCustomer($customer))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_below_minimum_order_quantity_422')
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER)
            ->assertJsonPath('errors.0.message', 'Minimum order quantity is 10')
            ->assertJsonPath('content.data.qty', 10);

        $this->assertSame(10, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);
    }

    public function test_update_quantity_above_the_last_tier_is_refused(): void
    {
        $customer = $this->customer();
        $product = $this->createBoundedTierProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $cartId = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id;

        $this->putJson(self::CART_ITEMS_URI.'/'.$cartId, ['cart_id' => $cartId, 'quantity' => 200], $this->actingAsCustomer($customer))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'unprocessable_entity_422')
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY)
            ->assertJsonPath('content.data.qty', 10);

        $this->assertSame(10, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);
    }

    public function test_add_accepts_exactly_the_minimum_and_the_maximum_order_quantity(): void
    {
        $customer = $this->customer();
        $product = $this->createBoundedTierProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $this->assertSame(10, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);

        $this->addItem(customer: $customer, productId: $product->id, quantity: 100);
        $this->assertSame(100, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);

        $this->addItem(customer: $customer, productId: $product->id, quantity: 100);
        $this->assertSame(100, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);
    }

    public function test_add_above_the_maximum_order_quantity_is_refused(): void
    {
        $customer = $this->customer();
        $product = $this->createBoundedTierProduct();

        $this->postJson(self::CART_ITEMS_URI, ['product_id' => $product->id, 'quantity' => 101], $this->actingAsCustomer($customer))
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY)
            ->assertJsonPath('errors.0.message', 'Maximum order quantity is 100');

        $this->assertNull($this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id));
    }

    public function test_add_without_replacing_merges_with_the_cart_and_names_the_quantity_already_there(): void
    {
        $customer = $this->customer();
        $product = $this->createBoundedTierProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);

        $this->postJson(
            self::CART_ITEMS_URI,
            ['product_id' => $product->id, 'quantity' => 40, 'replace_quantity' => 0],
            $this->actingAsCustomer($customer),
        )->assertOk();
        $this->assertSame(100, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);

        $this->postJson(
            self::CART_ITEMS_URI,
            ['product_id' => $product->id, 'quantity' => 10, 'replace_quantity' => 0],
            $this->actingAsCustomer($customer),
        )
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY)
            ->assertJsonPath('errors.0.message', 'You already have 100 in your cart; maximum order quantity is 100');

        $this->assertSame(100, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);
    }

    public function test_update_quantity_above_the_variant_stock_is_refused(): void
    {
        $customer = $this->customer();
        [$product, $sizeAxis] = $this->createVariationTierProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 20, variation: [$sizeAxis => 'M']);
        $cartId = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id;

        $this->putJson(self::CART_ITEMS_URI.'/'.$cartId, ['cart_id' => $cartId, 'quantity' => 101], $this->actingAsCustomer($customer))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_out_of_stock_422')
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_EXCEEDS_STOCK)
            ->assertJsonPath('errors.0.message', 'Only 100 items are available in stock');

        $this->assertSame(20, $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->quantity);
    }

    public function test_selection_update_changes_the_summary(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);
        $cartId = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->id;

        $unchecked = $this->putJson(self::CART_URI.'/selection', [
            'cart_id' => $cartId,
            'is_checked' => false,
        ], $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame('cart_selection_updated_200', $unchecked->json('identical_code'));
        $this->assertEqualsWithDelta(0, $unchecked->json('content.data.summary.subtotal'), 0.0001);

        $rechecked = $this->putJson(self::CART_URI.'/selection', [
            'cart_group_id' => $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id)->cart_group_id,
            'is_checked' => true,
        ], $this->actingAsCustomer($customer))->assertOk();

        $this->assertEqualsWithDelta(2400, $rechecked->json('content.data.summary.subtotal'), 0.0001);
    }

    public function test_selection_update_of_another_owners_row_is_not_found(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $cartId = $this->addRowForCustomer(customer: $customer, product: $product, quantity: 10)->id;

        $this->putJson(self::CART_URI.'/selection', [...$this->guestParams(), 'cart_id' => $cartId, 'is_checked' => false])
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'cart_item_not_found_404');

        $this->assertSame(1, (int) Cart::query()->find($cartId)->is_checked);
    }

    public function test_shipping_method_selection_is_refused_when_the_group_is_not_order_wise(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $row = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id);

        Cart::query()->whereKey($row->id)->update(['shipping_type' => 'category_wise']);

        $this->putJson(self::CART_URI.'/shipping-method', [
            'cart_group_id' => $row->cart_group_id,
            'shipping_method_id' => 1,
        ], $this->actingAsCustomer($customer))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_shipping_unavailable_422');
    }

    public function test_shipping_method_selection_changes_the_shipping_line(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $row = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id);
        $shippingMethod = $this->wholesaleShippingMethod();

        Cart::query()->whereKey($row->id)->update(['shipping_type' => 'order_wise', 'shipping_cost' => 0]);

        $response = $this->putJson(self::CART_URI.'/shipping-method', [
            'cart_group_id' => $row->cart_group_id,
            'shipping_method_id' => $shippingMethod->id,
        ], $this->actingAsCustomer($customer))->assertOk();

        $cartGroup = collect($response->json('content.data.cart_groups'))->firstWhere('cart_group_id', $row->cart_group_id);

        $this->assertSame($shippingMethod->id, $cartGroup['selected_shipping_method_id']);
        $this->assertEqualsWithDelta($shippingMethod->cost, $cartGroup['shipping_cost'], 0.0001);
        $this->assertEqualsWithDelta($shippingMethod->cost, $response->json('content.data.summary.shipping'), 0.0001);
    }

    public function test_shipping_method_selection_rejects_an_unknown_method(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 10);
        $row = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id);

        Cart::query()->whereKey($row->id)->update(['shipping_type' => 'order_wise']);

        $this->putJson(self::CART_URI.'/shipping-method', [
            'cart_group_id' => $row->cart_group_id,
            'shipping_method_id' => 99999999,
        ], $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'cart_shipping_method_not_found_404');
    }

    public function test_apply_coupon_returns_the_discounted_cart_and_rejects_an_unknown_code(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $coupon = $this->wholesaleCoupon();

        $this->addItem(customer: $customer, productId: $product->id, quantity: 60);

        $applied = $this->postJson(self::CART_URI.'/coupon', ['code' => $coupon->code], $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame('coupon_discount_calculated_200', $applied->json('identical_code'));
        $this->assertSame($coupon->code, $applied->json('content.data.coupon.code'));
        $this->assertEqualsWithDelta($coupon->discount, $applied->json('content.data.summary.coupon_discount'), 0.0001);

        $this->postJson(self::CART_URI.'/coupon', ['code' => 'NOSUCHCODE'], $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'coupon_code_invalid_404');
    }

    public function test_retail_v6_cart_endpoints_are_unchanged(): void
    {
        $customer = $this->customer();
        $retailProduct = $this->retailProduct();

        $this->postJson('/api/v6/customer/cart/items', [
            'id' => $retailProduct->id,
            'quantity' => $retailProduct->minimum_order_qty,
        ], $this->actingAsCustomer($customer))->assertOk();

        $this->getJson('/api/v6/customer/cart', $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200');

        $this->assertNotNull(Cart::query()->where([
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'product_id' => $retailProduct->id,
            'business_module' => BusinessModule::RETAIL,
        ])->first());
    }

    public function test_api_and_web_summary_amounts_are_identical(): void
    {
        $customer = $this->customer();
        $shop = $this->approvedSellerShop();
        $tieredProduct = $this->createTieredProduct();
        [$variationProduct, $sizeAxis] = $this->createVariationTierProduct();
        $sellerProduct = $this->createTieredProduct(
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        );

        $this->addItem(customer: $customer, productId: $tieredProduct->id, quantity: 60);
        $this->addItem(customer: $customer, productId: $variationProduct->id, quantity: 20, variation: [$sizeAxis => 'S']);
        $this->addItem(customer: $customer, productId: $variationProduct->id, quantity: 5, variation: [$sizeAxis => 'M']);
        $this->addItem(customer: $customer, productId: $sellerProduct->id, quantity: 12);

        $apiSummary = $this->getJson(self::CART_URI, $this->actingAsCustomer($customer))->assertOk()->json('content.data.summary');
        $firstRow = $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $tieredProduct->id);
        $webSummary = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.select'), ['cart_id' => $firstRow->id, 'is_checked' => 1])
            ->assertOk()
            ->json('summary');

        foreach (['subtotal', 'discount', 'coupon_discount', 'shipping', 'tax', 'total'] as $amountKey) {
            $this->assertSame($apiSummary[$amountKey], $webSummary[$amountKey], $amountKey);
        }

        $this->assertGreaterThan(0, $apiSummary['subtotal']);
    }

    private function addItem(User $customer, int $productId, int $quantity, array $variation = []): void
    {
        $this->postJson(
            self::CART_ITEMS_URI,
            ['product_id' => $productId, 'quantity' => $quantity, 'variation' => $variation],
            $this->actingAsCustomer($customer),
        )->assertOk();
    }

    private function addRowForCustomer(User $customer, Model $product, int $quantity): Cart
    {
        app(CartService::class)->addWholesaleItem(
            product: $product,
            quote: app(ProductWholesaleService::class)->getPriceQuote($product, [], $quantity),
            customerId: (int) $customer->id,
            guestId: null,
            selection: [],
            shippingType: '',
            shippingCost: 0,
        );

        return $this->wholesaleRow(customerId: (int) $customer->id, productId: (int) $product->id);
    }

    private function guestRow(int $productId): ?Cart
    {
        return Cart::query()
            ->where(['customer_id' => $this->guestId(), 'is_guest' => CartService::GUEST, 'product_id' => $productId])
            ->where('business_module', BusinessModule::WHOLESALE)
            ->first();
    }

    private function retailRowCount(int $ownerId, int $isGuest, int $productId): int
    {
        return Cart::query()
            ->where(['customer_id' => $ownerId, 'is_guest' => $isGuest, 'product_id' => $productId])
            ->where('business_module', BusinessModule::RETAIL)
            ->count();
    }

    private function flatItems(array $response): Collection
    {
        return collect($response['cart_groups'])->flatMap(static fn (array $group): array => $group['items']);
    }

    private function wholesaleRow(int $customerId, int $productId): ?Cart
    {
        return Cart::query()
            ->where(['customer_id' => $customerId, 'is_guest' => CartService::CUSTOMER, 'product_id' => $productId])
            ->where('business_module', BusinessModule::WHOLESALE)
            ->first();
    }

    private function raiseSecondTierPrice(Model $product, float $unitPrice): void
    {
        $detail = ProductWholesale::query()->where('product_id', $product->id)->firstOrFail();
        $priceSetup = $detail->price_setup;
        $priceSetup['rows'][0]['tiers'][1]['unit_price'] = $unitPrice;

        ProductWholesale::query()->whereKey($detail->id)->update(['price_setup' => json_encode($priceSetup)]);
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

    private function wholesaleShippingMethod(): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'creator_id' => 1,
            'creator_type' => 'admin',
            'title' => 'Wholesale cart api fixture method',
            'cost' => 25,
            'duration' => '3 days',
            'status' => 1,
            'context' => BusinessModule::WHOLESALE,
        ]);
    }

    private function wholesaleCoupon(): Coupon
    {
        return Coupon::query()->create([
            'added_by' => ProductWholesaleService::ADDED_BY_ADMIN,
            'business_module' => BusinessModule::WHOLESALE,
            'status' => 1,
            'coupon_type' => 'discount_on_purchase',
            'coupon_bearer' => 'inhouse',
            'customer_id' => 0,
            'seller_id' => 0,
            'title' => 'Wholesale cart fixture coupon',
            'code' => 'WSCPN'.random_int(10000, 99999),
            'start_date' => now()->subDay(),
            'expire_date' => now()->addDay(),
            'min_purchase' => 1,
            'max_discount' => 500,
            'discount' => 50,
            'discount_type' => 'amount',
            'limit' => 100,
        ]);
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

    /** @return array{0: Model, 1: string} */
    private function createVariationTierProduct(): array
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        $attributeId = (int) $ids[0];

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
            'variation_tier_discount' => ['M' => [1 => 10]],
        ]);

        return [$product, 'choice_'.$attributeId];
    }

    private function createProduct(array $overrides, string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN, ?int $userId = null, int $shopId = 0): Model
    {
        $product = $this->service()->create(addedBy: $addedBy, userId: $userId, shopId: $shopId, data: [
            'lang' => ['en'],
            'name' => ['Wholesale cart api fixture'],
            'description' => ['Wholesale cart api fixture'],
            'code' => 'WSAPI'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'shipping_cost' => 0,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
            ...$overrides,
        ]);

        $this->service()->updateStatus($product->id, true);
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        return $this->reload($product);
    }

    private function reload(Model $product): Model
    {
        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
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

    private function retailProduct(): Product
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

    private function approvedSellerShop(): Shop
    {
        return $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->first(),
            'approved seller with a shop',
        );
    }
}
