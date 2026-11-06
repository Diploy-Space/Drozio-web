<?php

namespace Tests\Feature\Cart;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CategoryShippingCost;
use App\Models\Color;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Models\User;
use App\Services\New\ColorService;
use App\Services\New\ShippingMethodService as RetailShippingMethodService;
use App\Services\New\ShippingTypeService as RetailShippingTypeService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\CategoryShippingCostService;
use Modules\Wholesale\app\Services\Shipping\ShippingMethodService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Modules\Wholesale\app\Utils\ProductViewSections;
use ReflectionMethod;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCartDomainTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const FIXTURE_SELLER_ID = 990001;

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

    public function test_refresh_prices_does_not_change_a_wholesale_row(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $this->assertEquals(50, $row->price);
        $this->assertEquals(40, $this->reload($product)->unit_price);

        $this->cartService()->refreshPrices(customerId: $customer->id, guestId: null);

        $this->assertEquals(50, $row->fresh()->price);
    }

    public function test_apply_quoted_prices_writes_the_quote_onto_the_row(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $quote = $this->quoteFor(product: $product, selection: [], quantity: 60);

        $changedRowCount = $this->cartService()->applyQuotedPrices(quotes: [
            $row->id => [...$quote, 'shipping_type' => ShippingTypeService::TYPE_ORDER_WISE, 'shipping_cost' => 0.0],
        ]);

        $row = $row->fresh();

        $this->assertSame(1, $changedRowCount);
        $this->assertSame(60, $row->quantity);
        $this->assertEquals(40, $row->price);
        $this->assertEqualsWithDelta($quote['total'], ($row->price - $row->discount + $row->tax) * $row->quantity, 0.0001);
    }

    public function test_apply_quoted_prices_skips_an_unchanged_row(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $quotes = [
            $row->id => [
                ...$this->quoteFor(product: $product, selection: [], quantity: 10),
                'shipping_type' => $row->shipping_type,
                'shipping_cost' => (float) $row->shipping_cost,
            ],
        ];

        $this->assertSame(0, $this->cartService()->applyQuotedPrices(quotes: $quotes));
    }

    public function test_cart_item_list_keeps_the_stored_variation_discount(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 20, selection: [$sizeAxis => 'M']);

        $this->assertEquals(9.5, $row->discount);
        $this->assertEquals(0, $this->reload($product)->discount);

        $listedRow = $this->cartService()
            ->getCartItemList(customerId: $customer->id)
            ->firstWhere('id', $row->id);

        $this->assertEquals(9.5, $listedRow->discount);
        $this->assertTrue($listedRow->product->relationLoaded('wholesaleDetail'));
        $this->assertTrue($listedRow->product->relationLoaded('stocks'));
    }

    public function test_discounted_subtotal_uses_the_stored_discount(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $this->addRow(product: $product, customer: $customer, quantity: 20, selection: [$sizeAxis => 'M']);

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);

        $this->assertEqualsWithDelta((95 - 9.5) * 20, $this->cartService()->getDiscountedSubtotal(cartItems: $cartItems), 0.0001);
    }

    public function test_row_selection_decodes_the_wholesale_shape(): void
    {
        [$product, $sizeAxis, $colorCode] = $this->createColorAndAttributeProduct();
        $customer = $this->customer();
        $selection = ['color' => $colorCode, $sizeAxis => 'L'];
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10, selection: $selection);

        $this->assertSame($selection, $this->cartService()->getRowSelection(cartItem: $row));
    }

    public function test_selection_labels_resolve_colour_and_attribute_titles(): void
    {
        [$product, $sizeAxis, $colorCode] = $this->createColorAndAttributeProduct();
        $colorNames = app(ColorService::class)->getNameMap();

        $labels = ProductViewSections::formatSelectionLabels(
            selection: ['color' => $colorCode, $sizeAxis => 'L', 'choice_999999' => 'Unmapped'],
            colorNames: $colorNames,
            choiceOptions: json_decode((string) $this->reload($product)->choice_options, true) ?: [],
        );

        $this->assertSame('Color', $labels[0]['title']);
        $this->assertSame($colorNames[$colorCode], $labels[0]['value']);
        $this->assertSame('Size', $labels[1]['title']);
        $this->assertSame('L', $labels[1]['value']);
        $this->assertSame('choice_999999', $labels[2]['title']);
        $this->assertSame('Unmapped', $labels[2]['value']);
    }

    public function test_shipping_type_resolves_the_wholesale_context_row(): void
    {
        $this->seedShippingTypeRows();

        $this->assertSame(
            RetailShippingTypeService::TYPE_CATEGORY_WISE,
            app(ShippingTypeService::class)->resolveTypeForSeller(self::FIXTURE_SELLER_ID),
        );
        $this->assertSame(
            RetailShippingTypeService::TYPE_ORDER_WISE,
            app(RetailShippingTypeService::class)->resolveTypeForSeller(self::FIXTURE_SELLER_ID),
        );
    }

    public function test_category_shipping_cost_resolves_the_wholesale_context_row(): void
    {
        $categoryId = $this->categoryId();
        $this->seedCategoryShippingCostRows(categoryId: $categoryId);

        $params = ['seller_id' => self::FIXTURE_SELLER_ID, 'category_id' => $categoryId];

        $this->assertEquals(77, app(CategoryShippingCostService::class)->findBy(params: $params)?->cost);
        $this->assertEquals(11, app(\App\Services\New\CategoryShippingCostService::class)->findBy(params: $params)?->cost);
    }

    public function test_order_wise_cost_range_reads_the_wholesale_responsibility_setting(): void
    {
        $this->seedShippingMethodRows();

        $product = ['user_id' => self::FIXTURE_SELLER_ID, 'added_by' => ProductWholesaleService::ADDED_BY_SELLER];

        $this->assertSame('wholesale_shipping_method', ShippingMethodService::RESPONSIBILITY_SETTING);
        $this->assertSame('wholesale_shipping_method', $this->wholesaleShippingResponsibilitySetting());
        $this->assertSame(RetailShippingMethodService::SHIPPING_METHOD_SELLERWISE, getWebConfig(name: ShippingMethodService::RESPONSIBILITY_SETTING));

        $this->assertEquals(70, app(ShippingMethodService::class)->getOrderWiseCostRange(product: $product)['min']);
        $this->assertEquals(20, app(RetailShippingMethodService::class)->getOrderWiseCostRange(product: $product)['min']);
    }

    public function test_quantity_update_is_refused_below_the_effective_minimum(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $response = $this->cartService()->updateItemQuantity(
            cartItem: $row,
            product: $this->reload($product),
            quantity: 5,
            isBuyNow: false,
            customerId: $customer->id,
            guestId: null,
            quote: $this->shippedQuote(product: $product, selection: [], quantity: 5),
        );

        $this->assertSame(CartService::STATUS_FAILED, $response['status']);
        $this->assertSame(ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER, $response['reason']);
        $this->assertSame(10, $response['qty']);
        $this->assertSame(10, $row->fresh()->quantity);
    }

    public function test_quantity_update_is_refused_above_the_last_tier(): void
    {
        $product = $this->createBoundedTierProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $response = $this->cartService()->updateItemQuantity(
            cartItem: $row,
            product: $this->reload($product),
            quantity: 200,
            isBuyNow: false,
            customerId: $customer->id,
            guestId: null,
            quote: $this->shippedQuote(product: $product, selection: [], quantity: 200),
        );

        $this->assertSame(CartService::STATUS_FAILED, $response['status']);
        $this->assertSame(ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY, $response['reason']);
        $this->assertSame(10, $row->fresh()->quantity);
    }

    public function test_quantity_update_reprices_across_a_tier_boundary(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);
        $quote = $this->shippedQuote(product: $product, selection: [], quantity: 60);

        $response = $this->cartService()->updateItemQuantity(
            cartItem: $row,
            product: $this->reload($product),
            quantity: 60,
            isBuyNow: false,
            customerId: $customer->id,
            guestId: null,
            quote: $quote,
        );

        $row = $row->fresh();

        $this->assertSame(CartService::STATUS_SUCCESS, $response['status']);
        $this->assertSame(60, $row->quantity);
        $this->assertEquals(40, $row->price);
        $this->assertEqualsWithDelta($quote['total'], ($row->price - $row->discount + $row->tax) * $row->quantity, 0.0001);
    }

    public function test_has_enough_stock_reads_the_variant_stock_row(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 20, selection: [$sizeAxis => 'M']);

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);

        $this->assertTrue($this->cartService()->hasEnoughStock(cartItems: $cartItems));

        Cart::query()->whereKey($row->id)->update(['quantity' => 101]);

        $this->assertFalse($this->cartService()->hasEnoughStock(
            cartItems: $this->cartService()->getCartItemList(customerId: $customer->id),
        ));
    }

    public function test_unavailable_items_lists_a_deactivated_product(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $this->assertCount(0, $this->cartService()->getUnavailableItems(cartItems: $this->ownerRows(customer: $customer)));

        Product::query()->whereKey($product->id)->update(['status' => 0]);

        $unavailableItems = $this->cartService()->getUnavailableItems(cartItems: $this->ownerRows(customer: $customer));

        $this->assertCount(1, $unavailableItems);
        $this->assertSame($row->id, $unavailableItems->first()->id);
        $this->assertCount(0, $this->cartService()->getCartItemList(customerId: $customer->id));
    }

    public function test_unavailable_items_lists_a_row_with_no_stock(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $this->addRow(product: $product, customer: $customer, quantity: 10);

        Product::query()->whereKey($product->id)->update(['current_stock' => 0]);

        $this->assertCount(1, $this->cartService()->getUnavailableItems(cartItems: $this->ownerRows(customer: $customer)));
    }

    public function test_a_wholesale_cart_and_a_retail_cart_do_not_see_each_other(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $wholesaleRow = $this->addRow(product: $product, customer: $customer, quantity: 10);
        $retailRow = Cart::query()->create([
            'business_module' => BusinessModule::RETAIL,
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'cart_group_id' => $customer->id.'-retail-'.time(),
            'product_id' => $product->id,
            'product_type' => CartService::PRODUCT_TYPE_PHYSICAL,
            'variant' => '',
            'quantity' => 1,
            'price' => 1,
            'discount' => 0,
            'tax' => 0,
            'is_checked' => 1,
            'seller_id' => CartService::IN_HOUSE_CART_SELLER_ID,
            'seller_is' => ProductWholesaleService::ADDED_BY_ADMIN,
        ]);

        $wholesaleIds = $this->cartService()->getCartItemList(customerId: $customer->id)->pluck('id');
        $retailIds = app(\App\Services\New\CartService::class)->getCartItemList(customerId: $customer->id)->pluck('id');

        $this->assertTrue($wholesaleIds->contains($wholesaleRow->id));
        $this->assertFalse($wholesaleIds->contains($retailRow->id));
        $this->assertTrue($retailIds->contains($retailRow->id));
        $this->assertFalse($retailIds->contains($wholesaleRow->id));
    }

    public function test_a_row_prices_identically_through_a_list_and_subtotal_round_trip(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $selection = [$sizeAxis => 'M'];
        $quote = $this->quoteFor(product: $product, selection: $selection, quantity: 20);
        $this->addRow(product: $product, customer: $customer, quantity: 20, selection: $selection);

        $cartItems = $this->cartService()->getCartItemList(customerId: $customer->id);

        $this->assertEqualsWithDelta(
            $quote['subtotal'] - $quote['discount_amount'],
            $this->cartService()->getDiscountedSubtotal(cartItems: $cartItems),
            0.0001,
        );
    }

    public function test_clear_forgets_only_the_wholesale_session_keys(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);

        $clearedKeys = $this->cartService()->clearOrderedCartGroups(cartGroupIds: [$row->cart_group_id]);

        $this->assertSame(CheckoutSessionKey::ALL, $clearedKeys);
        $this->assertNotContains('coupon_code', $clearedKeys);
        $this->assertNull(Cart::query()->whereKey($row->id)->first());
    }

    public function test_summary_amounts_sum_the_vendor_carts(): void
    {
        $vendorCarts = [
            [
                'cart_list' => [['price' => 50, 'discount' => 2, 'quantity' => 10], ['price' => 90, 'discount' => 0, 'quantity' => 20]],
                'coupon_discount' => 25,
                'shipping_cost' => 30,
                'free_delivery_discount' => 10,
                'total_tax_amount' => 12.5,
                'order_amount_with_tax' => 2297.5,
                'refer_and_earn_discount' => 0,
            ],
            [
                'cart_list' => [['price' => 40, 'discount' => 1.5, 'quantity' => 60]],
                'coupon_discount' => 0,
                'shipping_cost' => 15,
                'free_delivery_discount' => 0,
                'total_tax_amount' => 0,
                'order_amount_with_tax' => 2325,
                'refer_and_earn_discount' => 0,
            ],
        ];

        $this->assertSame(
            ['subtotal' => 4700.0, 'discount' => 110.0, 'discounted_subtotal' => 4590.0, 'coupon_discount' => 25.0, 'referral_discount' => 0.0, 'shipping' => 35.0, 'tax' => 12.5, 'total' => 4622.5],
            $this->cartService()->getSummaryAmounts(vendorCarts: $vendorCarts),
        );
        $this->assertSame(
            ['subtotal' => 0.0, 'discount' => 0.0, 'discounted_subtotal' => 0.0, 'coupon_discount' => 0.0, 'referral_discount' => 0.0, 'shipping' => 0.0, 'tax' => 0.0, 'total' => 0.0],
            $this->cartService()->getSummaryAmounts(vendorCarts: []),
        );
    }

    public function test_summary_and_payable_amounts_deduct_the_referral_discount(): void
    {
        $vendorCarts = [
            [
                'cart_list' => [['price' => 100, 'discount' => 0, 'quantity' => 10]],
                'coupon_discount' => 0,
                'shipping_cost' => 20,
                'free_delivery_discount' => 0,
                'total_tax_amount' => 0,
                'order_amount_with_tax' => 1020,
                'refer_and_earn_discount' => 100,
            ],
            [
                'cart_list' => [['price' => 50, 'discount' => 0, 'quantity' => 10]],
                'coupon_discount' => 0,
                'shipping_cost' => 0,
                'free_delivery_discount' => 0,
                'total_tax_amount' => 0,
                'order_amount_with_tax' => 500,
                'refer_and_earn_discount' => 50,
            ],
        ];

        $summary = $this->cartService()->getSummaryAmounts(vendorCarts: $vendorCarts);

        $this->assertSame(150.0, $summary['referral_discount']);
        $this->assertSame(1370.0, $summary['total']);
        $this->assertSame(1370.0, $this->cartService()->getPayableAmount(vendorWiseCartList: $vendorCarts));
    }

    public function test_group_amounts_include_free_delivery_and_minimum(): void
    {
        $checkedAvailable = (new Cart)->forceFill(['id' => 1, 'price' => 50, 'discount' => 5, 'quantity' => 10, 'is_checked' => 1]);
        $checkedUnavailable = (new Cart)->forceFill(['id' => 2, 'price' => 20, 'discount' => 0, 'quantity' => 5, 'is_checked' => 1]);
        $unchecked = (new Cart)->forceFill(['id' => 3, 'price' => 99, 'discount' => 0, 'quantity' => 99, 'is_checked' => 0]);
        $detailedItem = (new Cart)->forceFill([
            'minimum_order_amount_info' => 500,
            'free_delivery_order_amount' => ['status' => 1, 'amount' => 1000, 'percentage' => 55, 'amount_need' => 450],
        ]);
        $vendorCart = ['shipping_cost' => 40, 'free_delivery_discount' => 15, 'grand_total' => 540];

        $groupAmounts = $this->cartService()->getGroupAmounts(
            groupItems: [$checkedAvailable, $checkedUnavailable, $unchecked],
            detailedItem: $detailedItem,
            vendorCart: $vendorCart,
        );

        $this->assertSame(550.0, $groupAmounts['subtotal']);
        $this->assertSame(25.0, $groupAmounts['shipping_cost']);
        $this->assertSame(500.0, $groupAmounts['minimum_order_amount']);
        $this->assertTrue($groupAmounts['is_minimum_met']);
        $this->assertSame(['status' => 1, 'amount' => 1000.0, 'percentage' => 55.0, 'remaining' => 450.0], $groupAmounts['free_delivery']);

        $belowMinimum = $this->cartService()->getGroupAmounts(
            groupItems: [$unchecked],
            detailedItem: $detailedItem,
            vendorCart: null,
        );

        $this->assertSame(0.0, $belowMinimum['subtotal']);
        $this->assertSame(0.0, $belowMinimum['shipping_cost']);
        $this->assertFalse($belowMinimum['is_minimum_met']);

        $withoutDetails = $this->cartService()->getGroupAmounts(groupItems: [], detailedItem: null, vendorCart: null);

        $this->assertSame(0.0, $withoutDetails['minimum_order_amount']);
        $this->assertTrue($withoutDetails['is_minimum_met']);
        $this->assertSame(['status' => 0, 'amount' => 0.0, 'percentage' => 0.0, 'remaining' => 0.0], $withoutDetails['free_delivery']);
    }

    public function test_item_figures_match_the_quote(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $selection = [$sizeAxis => 'M'];
        $row = $this->addRow(product: $product, customer: $customer, quantity: 20, selection: $selection);
        $quote = $this->quoteFor(product: $product, selection: $selection, quantity: 20);

        $figures = $this->cartService()->getItemFigures(cartItem: $row->fresh(), quote: $quote);

        $this->assertEqualsWithDelta($quote['unit_price'], $figures['unit_price'], 0.0001);
        $this->assertEqualsWithDelta($quote['discount_per_unit'], $figures['discount_per_unit'], 0.0001);
        $this->assertEqualsWithDelta($quote['unit_price'] - $quote['discount_per_unit'], $figures['discounted_unit_price'], 0.0001);
        $this->assertEqualsWithDelta($quote['total'], $figures['line_total'], 0.0001);
        $this->assertEqualsWithDelta($quote['subtotal'] - $quote['discount_amount'], $figures['line_subtotal'], 0.0001);
        $this->assertSame(20, $figures['quantity']);
        $this->assertSame((int) $quote['minimum_quantity'], $figures['minimum_quantity']);
        $this->assertNull($figures['maximum_quantity']);
        $this->assertSame((int) $quote['available_stock'], $figures['available_stock']);

        $withoutQuote = $this->cartService()->getItemFigures(cartItem: $row->fresh(), quote: null);

        $this->assertNull($withoutQuote['minimum_quantity']);
        $this->assertNull($withoutQuote['maximum_quantity']);
        $this->assertSame(0, $withoutQuote['available_stock']);
    }

    public function test_apply_row_quotes_resolves_wholesale_shipping(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);
        $refusedRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 10);
        Cart::query()->whereKey([$row->id, $refusedRow->id])->update(['shipping_type' => 'stale', 'shipping_cost' => 999]);

        $cartItems = $this->cartService()->getOwnerItems(customerId: $customer->id, guestId: null, relations: ['product.wholesaleDetail', 'product.stocks'])
            ->whereIn('id', [$row->id, $refusedRow->id]);

        $changedRowCount = $this->cartService()->applyRowQuotes(cartItems: $cartItems, quotes: [
            $row->id => $this->quoteFor(product: $product, selection: [], quantity: 60),
            $refusedRow->id => [
                ...$this->quoteFor(product: $product, selection: [], quantity: 2),
                'reason' => ProductWholesaleService::REASON_VARIATION_UNAVAILABLE,
            ],
        ]);

        $wholesaleShippingTypeService = app(ShippingTypeService::class);
        $pricedRow = $row->fresh();

        $this->assertSame(1, $changedRowCount);
        $this->assertSame(60, $pricedRow->quantity);
        $this->assertSame($wholesaleShippingTypeService->resolveTypeForProduct(product: $this->reload($product)), $pricedRow->shipping_type);
        $this->assertEqualsWithDelta($wholesaleShippingTypeService->resolveProductShippingCost(product: $this->reload($product), quantity: 60), $pricedRow->shipping_cost, 0.0001);
        $this->assertSame('stale', $refusedRow->fresh()->shipping_type);
        $this->assertEquals(999, $refusedRow->fresh()->shipping_cost);
    }

    public function test_apply_row_quotes_reprices_a_row_whose_quantity_exceeds_stock(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 10);
        Cart::query()->whereKey($row->id)->update(['price' => 1]);
        Product::query()->whereKey($product->id)->update(['current_stock' => 5]);

        $quote = $this->quoteFor(product: $product, selection: [], quantity: 10);
        $cartItems = $this->cartService()->getOwnerItems(customerId: $customer->id, guestId: null, relations: ['product.wholesaleDetail', 'product.stocks'])
            ->whereIn('id', [$row->id]);

        $changedRowCount = $this->cartService()->applyRowQuotes(cartItems: $cartItems, quotes: [$row->id => $quote]);

        $this->assertSame(ProductWholesaleService::REASON_EXCEEDS_STOCK, $quote['reason']);
        $this->assertSame(1, $changedRowCount);
        $this->assertEqualsWithDelta(50, (float) $row->fresh()->price, 0.0001);
    }

    public function test_a_quote_off_only_by_quantity_keeps_the_row_available(): void
    {
        $product = $this->createTieredProduct();
        Product::query()->whereKey($product->id)->update(['current_stock' => 5]);

        $exceedsStock = $this->quoteFor(product: $product, selection: [], quantity: 10);
        $belowMinimum = $this->quoteFor(product: $product, selection: [], quantity: 2);

        $this->assertSame(ProductWholesaleService::REASON_EXCEEDS_STOCK, $exceedsStock['reason']);
        $this->assertTrue($this->cartService()->isQuoteAvailable(quote: $exceedsStock));
        $this->assertTrue($this->cartService()->isQuoteAvailable(quote: $belowMinimum));
        $this->assertFalse($this->cartService()->isQuoteAvailable(quote: [...$exceedsStock, 'reason' => ProductWholesaleService::REASON_OUT_OF_STOCK]));
        $this->assertFalse($this->cartService()->isQuoteAvailable(quote: [...$exceedsStock, 'reason' => ProductWholesaleService::REASON_VARIATION_UNAVAILABLE]));
        $this->assertFalse($this->cartService()->isQuoteAvailable(quote: null));
    }

    private function ownerRows(User $customer): Collection
    {
        return $this->cartService()->getList(
            dataLimit: 'all',
            relations: ['product.stocks'],
            filters: ['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER],
        );
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function wholesaleShippingResponsibilitySetting(): string
    {
        $seam = new ReflectionMethod(CartService::class, 'shippingResponsibilitySetting');
        $seam->setAccessible(true);

        return $seam->invoke($this->cartService());
    }

    /** @return array<string, mixed> */
    private function quoteFor(Model $product, array $selection, int $quantity): array
    {
        return $this->service()->getPriceQuote($this->reload($product), $selection, $quantity, app(ColorService::class)->getNameMap());
    }

    /** @return array<string, mixed> */
    private function shippedQuote(Model $product, array $selection, int $quantity): array
    {
        return [
            ...$this->quoteFor(product: $product, selection: $selection, quantity: $quantity),
            'shipping_type' => ShippingTypeService::TYPE_ORDER_WISE,
            'shipping_cost' => 0.0,
        ];
    }

    private function addRow(Model $product, User $customer, int $quantity, array $selection = []): Cart
    {
        $quote = $this->quoteFor(product: $product, selection: $selection, quantity: $quantity);

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

    private function createTieredProduct(): Model
    {
        return $this->createProduct(overrides: [
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
            'variation_tier_discount' => ['M' => [1 => 10]],
        ]);

        return [$product, 'choice_'.$attributeId];
    }

    /** @return array{0: Model, 1: string, 2: string} */
    private function createColorAndAttributeProduct(): array
    {
        $attributeId = $this->attributeId();
        $colorCode = (string) $this->requireFixture(Color::query()->value('code'), 'colour');

        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 200,
            'colors_active' => 1,
            'colors' => [$colorCode],
            'choice_attributes' => [$attributeId],
            'choice_no' => [$attributeId],
            'choice' => ['Size'],
            'choice_options_'.$attributeId => ['L'],
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [25],
        ], colorNames: app(ColorService::class)->getNameMap());

        Product::query()->whereKey($product->id)->update(['current_stock' => 200]);
        $product->stocks()->update(['qty' => 200]);

        return [$this->reload($product), 'choice_'.$attributeId, $colorCode];
    }

    private function createProduct(array $overrides, array $colorNames = []): Model
    {
        $product = $this->service()->create(
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale cart domain fixture'],
                'description' => ['Wholesale cart domain fixture'],
                'code' => 'WSDOM'.random_int(10000, 99999),
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

    private function seedShippingTypeRows(): void
    {
        ShippingType::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'shipping_type' => RetailShippingTypeService::TYPE_ORDER_WISE,
            'context' => BusinessModule::RETAIL,
        ]);
        ShippingType::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'shipping_type' => RetailShippingTypeService::TYPE_CATEGORY_WISE,
            'context' => BusinessModule::WHOLESALE,
        ]);
    }

    private function seedCategoryShippingCostRows(int $categoryId): void
    {
        CategoryShippingCost::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'category_id' => $categoryId,
            'cost' => 11,
            'context' => BusinessModule::RETAIL,
        ]);
        CategoryShippingCost::query()->create([
            'seller_id' => self::FIXTURE_SELLER_ID,
            'category_id' => $categoryId,
            'cost' => 77,
            'context' => BusinessModule::WHOLESALE,
        ]);
    }

    private function seedShippingMethodRows(): void
    {
        foreach ([[20, BusinessModule::RETAIL], [70, BusinessModule::WHOLESALE]] as [$cost, $context]) {
            ShippingMethod::query()->create([
                'creator_id' => self::FIXTURE_SELLER_ID,
                'creator_type' => ProductWholesaleService::ADDED_BY_SELLER,
                'title' => 'Wholesale cart domain fixture',
                'cost' => $cost,
                'duration' => '3 days',
                'status' => 1,
                'context' => $context,
            ]);
        }
    }

    private function attributeId(): int
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        return (int) $ids[0];
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
}
