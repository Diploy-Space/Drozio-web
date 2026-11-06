<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleReorderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const ORDERS_URI = '/api/v6/wholesale/customer/orders';

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

    public function test_preview_reports_every_line_at_todays_price_without_touching_the_cart(): void
    {
        $customer = $this->customer();
        $first = $this->createTieredProduct();
        $second = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $first, 'quantity' => 60, 'price' => 50],
            ['product' => $second, 'quantity' => 60, 'price' => 50],
        ]);

        $preview = $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame('data_fetched_200', $preview->json('identical_code'));
        $this->assertSame($order->id, $preview->json('content.data.order_id'));
        $this->assertSame(2, $preview->json('content.data.line_count'));
        $this->assertSame(2, $preview->json('content.data.addable_line_count'));

        foreach ($preview->json('content.data.lines') as $line) {
            $quote = $this->service()->getPriceQuote($this->reload(Product::find($line['product_id'])), [], 60);

            $this->assertTrue($line['is_addable']);
            $this->assertNull($line['reason']);
            $this->assertNull($line['message']);
            $this->assertSame(60, $line['ordered_quantity']);
            $this->assertSame(0, $line['in_cart_quantity']);
            $this->assertSame(60, $line['cart_quantity']);
            $this->assertEqualsWithDelta(50, $line['ordered_unit_price'], 0.0001);
            $this->assertEqualsWithDelta($quote['unit_price'], $line['unit_price'], 0.0001);
            $this->assertEqualsWithDelta($quote['total'], $line['line_total'], 0.0001);
            $this->assertTrue($line['has_price_changed']);
        }

        $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame(0, $this->wholesaleRows(customerId: (int) $customer->id)->count());
    }

    public function test_reorder_adds_every_purchasable_line_and_agrees_with_the_preview(): void
    {
        $customer = $this->customer();
        $first = $this->createTieredProduct();
        $second = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $first, 'quantity' => 60, 'price' => 50],
            ['product' => $second, 'quantity' => 12, 'price' => 50],
        ]);

        $previewLines = $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertOk()
            ->json('content.data.lines');

        $reorder = $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))->assertOk();

        $this->assertSame('order_reorder_added_200', $reorder->json('identical_code'));
        $this->assertSame($previewLines, $reorder->json('content.data.lines'));

        $rows = $this->wholesaleRows(customerId: (int) $customer->id)->keyBy('product_id');

        $this->assertCount(2, $rows);
        $this->assertSame(60, (int) $rows[$first->id]->quantity);
        $this->assertEqualsWithDelta(40, (float) $rows[$first->id]->price, 0.0001);
        $this->assertSame(12, (int) $rows[$second->id]->quantity);
        $this->assertEqualsWithDelta(50, (float) $rows[$second->id]->price, 0.0001);

        foreach ($previewLines as $line) {
            $row = $rows[$line['product_id']];

            $this->assertEqualsWithDelta($line['unit_price'], (float) $row->price, 0.0001);
            $this->assertEqualsWithDelta($line['discount_per_unit'], (float) $row->discount, 0.0001);
            $this->assertSame($line['cart_quantity'], (int) $row->quantity);
        }
    }

    public function test_a_tier_price_raised_since_the_order_is_what_the_line_is_requoted_at(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $product, 'quantity' => 60, 'price' => 40],
        ]);

        $this->raiseSecondTierPrice(product: $product, unitPrice: 70);

        $line = $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertOk()
            ->json('content.data.lines.0');

        $this->assertEqualsWithDelta(40, $line['ordered_unit_price'], 0.0001);
        $this->assertEqualsWithDelta(70, $line['unit_price'], 0.0001);
        $this->assertTrue($line['has_price_changed']);

        $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))->assertOk();

        $this->assertEqualsWithDelta(
            70,
            (float) $this->wholesaleRows(customerId: (int) $customer->id)->firstWhere('product_id', $product->id)->price,
            0.0001,
        );
    }

    public function test_an_out_of_stock_line_is_reported_and_the_other_lines_are_added(): void
    {
        $customer = $this->customer();
        $available = $this->createTieredProduct();
        $soldOut = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $available, 'quantity' => 60, 'price' => 50],
            ['product' => $soldOut, 'quantity' => 60, 'price' => 50],
        ]);

        Product::query()->whereKey($soldOut->id)->update(['current_stock' => 0]);

        $reorder = $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))->assertOk();
        $lines = collect($reorder->json('content.data.lines'))->keyBy('product_id');

        $this->assertSame('order_reorder_partially_added_200', $reorder->json('identical_code'));
        $this->assertSame(1, $reorder->json('content.data.addable_line_count'));
        $this->assertFalse($lines[$soldOut->id]['is_addable']);
        $this->assertSame(ProductWholesaleService::REASON_OUT_OF_STOCK, $lines[$soldOut->id]['reason']);
        $this->assertSame('This variation is out of stock', $lines[$soldOut->id]['message']);
        $this->assertTrue($lines[$available->id]['is_addable']);

        $this->assertSame(
            [$available->id],
            $this->wholesaleRows(customerId: (int) $customer->id)->pluck('product_id')->all(),
        );
    }

    public function test_a_line_below_the_raised_minimum_order_quantity_is_reported_and_nothing_is_added(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $product, 'quantity' => 10, 'price' => 50],
        ]);

        Product::query()->whereKey($product->id)->update(['minimum_order_qty' => 50]);

        $reorder = $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))
            ->assertForbidden();
        $line = $reorder->json('content.data.lines.0');

        $this->assertSame('order_reorder_unavailable_403', $reorder->json('identical_code'));
        $this->assertSame(0, $reorder->json('content.data.addable_line_count'));
        $this->assertFalse($line['is_addable']);
        $this->assertSame(ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER, $line['reason']);
        $this->assertSame('Minimum order quantity is 50', $line['message']);
        $this->assertSame(50, $line['minimum_quantity']);

        $this->assertSame(0, $this->wholesaleRows(customerId: (int) $customer->id)->count());
    }

    public function test_a_deactivated_product_is_reported_as_unavailable(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $product, 'quantity' => 60, 'price' => 50],
        ]);

        Product::query()->whereKey($product->id)->update(['status' => 0]);

        $line = $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertOk()
            ->json('content.data.lines.0');

        $this->assertFalse($line['is_addable']);
        $this->assertSame(CartService::REASON_PRODUCT_UNAVAILABLE, $line['reason']);
        $this->assertSame('This product is no longer available', $line['message']);
        $this->assertSame($product->name, $line['product_name']);

        $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_reorder_unavailable_403');

        $this->assertSame(0, $this->wholesaleRows(customerId: (int) $customer->id)->count());
    }

    public function test_a_line_already_in_the_cart_combines_its_quantity_and_is_quoted_at_the_total(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $product, 'quantity' => 40, 'price' => 50],
        ]);

        $this->postJson(
            '/api/v6/wholesale/customer/cart/items',
            ['product_id' => $product->id, 'quantity' => 20, 'variation' => []],
            $this->actingAsCustomer($customer),
        )->assertOk();

        $reorder = $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))->assertOk();
        $line = $reorder->json('content.data.lines.0');
        $row = $this->wholesaleRows(customerId: (int) $customer->id)->firstWhere('product_id', $product->id);

        $this->assertSame(20, $line['in_cart_quantity']);
        $this->assertSame(40, $line['ordered_quantity']);
        $this->assertSame(60, $line['cart_quantity']);
        $this->assertEqualsWithDelta(40, $line['unit_price'], 0.0001);
        $this->assertSame(60, (int) $row->quantity);
        $this->assertEqualsWithDelta(40, (float) $row->price, 0.0001);
        $this->assertSame(1, $this->wholesaleRows(customerId: (int) $customer->id)->count());
    }

    public function test_a_variation_line_is_requoted_at_its_stored_selection(): void
    {
        $customer = $this->customer();
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $order = $this->createWholesaleOrder(customer: $customer, lines: [
            ['product' => $product, 'quantity' => 20, 'price' => 90, 'variation' => ['Size' => 'M'], 'variant' => 'M'],
        ]);

        $line = $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertOk()
            ->json('content.data.lines.0');
        $quote = $this->service()->getPriceQuote($this->reload(Product::find($product->id)), [$sizeAxis => 'M'], 20);

        $this->assertSame('M', $line['variant']);
        $this->assertTrue($line['is_addable']);
        $this->assertEqualsWithDelta($quote['unit_price'], $line['unit_price'], 0.0001);
        $this->assertEqualsWithDelta($quote['discount_per_unit'], $line['discount_per_unit'], 0.0001);

        $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))->assertOk();
        $row = $this->wholesaleRows(customerId: (int) $customer->id)->firstWhere('product_id', $product->id);

        $this->assertSame('M', $row->variant);
        $this->assertEqualsWithDelta($quote['unit_price'], (float) $row->price, 0.0001);
    }

    public function test_a_retail_order_is_not_found(): void
    {
        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::RETAIL);

        $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');

        $this->postJson(self::ORDERS_URI.'/'.$order->id.'/reorder', [], $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_another_customers_order_is_not_found(): void
    {
        $customer = $this->customer();
        $order = $this->createWholesaleOrder(customer: $this->otherCustomer(customer: $customer), lines: [
            ['product' => $this->createTieredProduct(), 'quantity' => 60, 'price' => 50],
        ]);

        $this->getJson(self::ORDERS_URI.'/'.$order->id.'/reorder-preview', $this->actingAsCustomer($customer))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
    }

    public function test_an_unauthenticated_caller_is_refused(): void
    {
        $this->getJson(self::ORDERS_URI.'/1/reorder-preview')->assertUnauthorized();
        $this->postJson(self::ORDERS_URI.'/1/reorder')->assertUnauthorized();
    }

    /** @param  array<int, array<string, mixed>>  $lines */
    private function createWholesaleOrder(User $customer, array $lines): Order
    {
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        foreach ($lines as $line) {
            OrderDetail::query()->create([
                'order_id' => $order->id,
                'product_id' => $line['product']->id,
                'seller_id' => (int) $line['product']->user_id,
                'product_details' => json_encode($line['product']->toArray()),
                'qty' => $line['quantity'],
                'price' => $line['price'],
                'discount' => 0,
                'discount_type' => 'discount_on_product',
                'tax' => 0,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                'delivery_status' => 'delivered',
                'payment_status' => 'paid',
                'variant' => $line['variant'] ?? '',
                'variation' => $line['variation'] ?? [],
            ]);
        }

        return $order;
    }

    private function createOrder(User $customer, string $businessModule): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => (int) Order::query()->max('id') + 1,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'business_module' => $businessModule,
            'order_status' => 'delivered',
            'payment_status' => 'paid',
        ])->save();

        return $order;
    }

    private function wholesaleRows(int $customerId): Collection
    {
        return Cart::query()
            ->where(['customer_id' => $customerId, 'is_guest' => CartService::CUSTOMER])
            ->where('business_module', BusinessModule::WHOLESALE)
            ->get();
    }

    private function raiseSecondTierPrice(Model $product, float $unitPrice): void
    {
        $detail = ProductWholesale::query()->where('product_id', $product->id)->firstOrFail();
        $priceSetup = $detail->price_setup;
        $priceSetup['rows'][0]['tiers'][1]['unit_price'] = $unitPrice;

        ProductWholesale::query()->whereKey($detail->id)->update(['price_setup' => json_encode($priceSetup)]);
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

    private function createProduct(array $overrides): Model
    {
        $product = $this->service()->create(addedBy: ProductWholesaleService::ADDED_BY_ADMIN, userId: null, shopId: 0, data: [
            'lang' => ['en'],
            'name' => ['Wholesale reorder api fixture'],
            'description' => ['Wholesale reorder api fixture'],
            'code' => 'WSREO'.random_int(10000, 99999),
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

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function customer(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()
            ->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER])
            ->where('business_module', BusinessModule::WHOLESALE)
            ->delete();

        return $customer;
    }

    private function otherCustomer(User $customer): User
    {
        return $this->requireFixture(
            User::query()->where('id', '>', 0)->whereKeyNot($customer->id)->where('is_active', 1)->first(),
            'second active customer',
        );
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }
}
