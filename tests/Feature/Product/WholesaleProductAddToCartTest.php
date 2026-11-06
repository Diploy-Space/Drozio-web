<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductAddToCartTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const API_CART_ITEMS_URI = '/api/v6/wholesale/customer/cart/items';

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

    public function test_cart_row_uses_the_tier_price_for_the_quantity(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $countBefore = app(CartService::class)->getItemCount(customerId: $customer->id, guestId: null);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 20, 'variation' => [$sizeAxis => 'M']])
            ->assertOk()
            ->assertJsonPath('cart_count', $countBefore + 1)
            ->assertJsonPath('redirect_to', null);

        $quote = $this->service()->getPriceQuote($this->reload($product), [$sizeAxis => 'M'], 20);
        $row = $this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id);

        $this->assertSame(1, $quote['tier_index']);
        $this->assertSame(BusinessModule::WHOLESALE, $row->business_module);
        $this->assertSame('M', $row->variant);
        $this->assertSame(20, $row->quantity);
        $this->assertEquals($quote['unit_price'], $row->price);
        $this->assertEquals($quote['discount_per_unit'], $row->discount);
        $this->assertSame($quote['tax_model'], $row->tax_model);
        $this->assertEqualsWithDelta($quote['total'], ($row->price - $row->discount + $row->tax) * $row->quantity, 0.0001);
    }

    public function test_quantity_below_minimum_is_rejected_with_the_reason(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 5])
            ->assertUnprocessable()
            ->assertJson([
                'reason' => ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER,
                'message' => 'Minimum order quantity is 10',
            ]);

        $this->assertNull($this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id));

        $this->postJson(self::API_CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_below_minimum_order_quantity_422')
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER)
            ->assertJsonPath('errors.0.message', 'Minimum order quantity is 10');
    }

    public function test_quantity_above_combination_stock_is_rejected(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 101, 'variation' => [$sizeAxis => 'M']])
            ->assertUnprocessable()
            ->assertJson([
                'reason' => ProductWholesaleService::REASON_EXCEEDS_STOCK,
                'message' => 'Only 100 items are available in stock',
            ]);

        $this->assertNull($this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id));

        $this->postJson(self::API_CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 101, 'variation' => [$sizeAxis => 'M']])
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_out_of_stock_422')
            ->assertJsonPath('errors.0.error_code', ProductWholesaleService::REASON_EXCEEDS_STOCK);
    }

    public function test_adding_the_same_variant_again_merges_and_reprices(): void
    {
        [$product, $sizeAxis] = $this->createVariationTierProduct();
        $customer = $this->customer();
        $payload = ['product_id' => $product->id, 'variation' => [$sizeAxis => 'M']];

        $this->actingAs($customer, 'customer')->postJson(route('wholesale.cart.add'), [...$payload, 'quantity' => 5])->assertOk();

        $firstRow = $this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id);
        $this->assertEquals(110, $firstRow->price);
        $this->assertEquals(0, $firstRow->discount);

        $this->actingAs($customer, 'customer')->postJson(route('wholesale.cart.add'), [...$payload, 'quantity' => 10])->assertOk();

        $rows = Cart::query()->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER, 'product_id' => $product->id])->get();
        $quote = $this->service()->getPriceQuote($this->reload($product), [$sizeAxis => 'M'], 15);

        $this->assertCount(1, $rows);
        $this->assertSame($firstRow->id, $rows->first()->id);
        $this->assertSame(15, $rows->first()->quantity);
        $this->assertEquals(95, $rows->first()->price);
        $this->assertEquals(9.5, $rows->first()->discount);
        $this->assertEqualsWithDelta($quote['total'], ($rows->first()->price - $rows->first()->discount + $rows->first()->tax) * 15, 0.0001);
    }

    public function test_buy_now_returns_the_checkout_redirect(): void
    {
        $product = $this->createTieredProduct();
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 10, 'buy_now' => 1])
            ->assertOk()
            ->assertJsonPath('redirect_to', route('wholesale.orders.checkout'));

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 60, 'buy_now' => 1])
            ->assertOk();

        $row = $this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id);
        $this->assertSame(60, $row->quantity);
        $this->assertEquals(40, $row->price);

        $this->postJson(self::API_CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 10, 'buy_now' => 1])
            ->assertOk()
            ->assertJsonPath('identical_code', 'cart_item_added_for_checkout_200')
            ->assertJsonPath('content.redirect_to', CartService::REDIRECT_TO_CHECKOUT)
            ->assertJsonPath('content.data.product_id', $product->id)
            ->assertJsonPath('content.data.quantity', 10);

        $this->postJson(self::API_CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 10])
            ->assertOk()
            ->assertJsonPath('identical_code', 'cart_item_added_200')
            ->assertJsonPath('content.redirect_to', null)
            ->assertJsonPath('content.data.quantity', 20);
    }

    public function test_closed_shop_cannot_be_added(): void
    {
        $shop = $this->closedWholesaleSellerShop();
        $product = $this->createTieredProduct(addedBy: ProductWholesaleService::ADDED_BY_SELLER, userId: (int) $shop->seller_id, shopId: (int) $shop->id);
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 10])
            ->assertUnprocessable()
            ->assertJson([
                'reason' => 'shop_closed',
                'message' => 'This shop is temporarily closed or on vacation',
            ]);

        $this->postJson(self::API_CART_ITEMS_URI, [...$this->guestParams(), 'product_id' => $product->id, 'quantity' => 10])
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'cart_shop_unavailable_422')
            ->assertJsonPath('errors.0.error_code', 'shop_closed');

        $this->postJson(route('wholesale.products.price-quote', ['id' => $product->id]), ['quantity' => 10])
            ->assertOk()
            ->assertJson([
                'reason' => null,
                'message' => 'This shop is temporarily closed or on vacation',
                'is_purchasable' => false,
            ]);

        $this->postJson('/api/v6/wholesale/products/'.$product->id.'/price-quote', ['quantity' => 10])
            ->assertOk()
            ->assertJsonPath('content.data.message', 'This shop is temporarily closed or on vacation')
            ->assertJsonPath('content.data.is_purchasable', false);

        $this->assertNull($this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id));
    }

    public function test_retail_cart_add_is_unchanged(): void
    {
        $product = $this->requireFixture(
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
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $product->id, 'quantity' => $product->minimum_order_qty])
            ->assertOk()
            ->assertJsonPath('status', CartService::STATUS_SUCCESS);

        $row = $this->cartRow(ownerId: $customer->id, isGuest: CartService::CUSTOMER, productId: $product->id);

        $this->assertSame(BusinessModule::RETAIL, $row->business_module);
        $this->assertEquals($product->unit_price, $row->price);
        $this->assertSame((int) $product->minimum_order_qty, $row->quantity);
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
            'name' => ['Wholesale cart fixture'],
            'description' => ['Wholesale cart fixture'],
            'code' => 'WSCRT'.random_int(10000, 99999),
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

    private function cartRow(int|string $ownerId, int $isGuest, int $productId): ?Cart
    {
        return Cart::query()->where(['customer_id' => $ownerId, 'is_guest' => $isGuest, 'product_id' => $productId])->first();
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

    private function closedWholesaleSellerShop(): Shop
    {
        $shop = $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->first(),
            'approved seller with a shop',
        );

        $shop->forceFill(['wholesale_temporary_close' => 1, 'temporary_close' => 0, 'vacation_status' => 0])->save();

        return $shop;
    }
}
