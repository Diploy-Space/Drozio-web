<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AI\app\Services\ShoppingAssistant\CartActionCollector;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Tools\AddToCartTool;
use Modules\Wholesale\app\Tools\CheckoutTool;
use Modules\Wholesale\app\Tools\UpdateCartQuantityTool;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionFunction;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class AiShoppingCartToolsTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresWholesaleAddon;

    private Seller $seller;

    private Shop $shop;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $user = User::create([
            'name' => 'Cart Tools Seller',
            'email' => 'cart-tools-seller@example.test',
            'phone' => '10000000099',
            'password' => bcrypt('password'),
            'user_type' => 'seller',
        ]);

        $this->seller = Seller::create([
            'id' => $user->id,
            'email' => 'cart-tools-seller@example.test',
            'f_name' => 'Cart',
            'l_name' => 'Seller',
            'phone' => '10000000099',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $this->shop = Shop::create([
            'seller_id' => $this->seller->id,
            'name' => 'Cart Tools Shop',
            'address' => 'Cart Tools Address',
            'contact' => '10000000099',
            'banner' => 'banner.png',
            'image' => 'shop.png',
        ]);

        $this->category = Category::create([
            'name' => 'Cart Tools Chairs',
            'slug' => 'cart-tools-chairs-'.uniqid(),
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);
    }

    public function test_guest_adds_a_simple_product(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Simple Chair', tiers: [[1, null, 100.0]], minimumOrderQty: 1);
        $this->context(customer: null, guestId: 'guest-simple');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame('added', $result['status']);
        $this->assertSame(2, $result['quantity']);
        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'customer_id' => 'guest-simple',
            'is_guest' => CartService::GUEST,
            'business_module' => BusinessModule::WHOLESALE,
            'quantity' => 2,
            'price' => 100.0,
            'variant' => '',
        ]);
        $this->assertSame('added', $this->cartActions()[0]['type']);
    }

    public function test_customer_adds_a_color_variant(): void
    {
        Color::query()->create(['name' => 'Red', 'code' => '#FF0000']);
        $product = $this->colorVariantProduct(name: 'Isolation Color Chair');
        $customer = $this->customer();
        $this->context(customer: $customer, guestId: null);

        $result = $this->addToCart(['product_id' => $product->id, 'color' => 'Red', 'quantity' => 1]);

        $this->assertSame('added', $result['status']);
        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'variant' => 'Red',
            'business_module' => BusinessModule::WHOLESALE,
        ]);
    }

    public function test_quantity_below_minimum_order_is_auto_raised(): void
    {
        $product = $this->tieredProduct(name: 'Isolation MOQ Chair', tiers: [[5, null, 90.0]], minimumOrderQty: 5);
        $this->context(customer: null, guestId: 'guest-moq');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $this->assertSame('added', $result['status']);
        $this->assertSame(5, $result['quantity']);
        $this->assertSame(1, $result['requested_quantity']);
        $this->assertSame(5, $result['minimum_order_qty']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 5]);
    }

    public function test_over_stock_is_reported_as_out_of_stock(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Stock Chair', tiers: [[1, null, 100.0]], minimumOrderQty: 1, stock: 3);
        $this->context(customer: null, guestId: 'guest-stock');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 10]);

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertSame(3, $result['available']);
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }

    public function test_closed_shop_blocks_add(): void
    {
        $this->shop->forceFill(['wholesale_temporary_close' => 1, 'temporary_close' => 0, 'vacation_status' => 0])->save();
        $product = $this->tieredProduct(name: 'Isolation Closed Chair');
        $this->context(customer: null, guestId: 'guest-closed');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $this->assertSame('failed', $result['status']);
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }

    public function test_buy_now_returns_checkout_action_with_the_wholesale_checkout_url(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Buy Now Chair');
        $this->context(customer: null, guestId: 'guest-buy-now');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1, 'buy_now' => true]);

        $this->assertSame('buy_now', $result['status']);
        $checkoutAction = $this->cartActions()[0];
        $this->assertSame('checkout', $checkoutAction['type']);
        $this->assertSame(route('wholesale.orders.checkout'), $checkoutAction['url']);
    }

    #[RunInSeparateProcess]
    public function test_buy_now_blocked_when_seller_minimum_not_met(): void
    {
        $this->setBusinessSetting('wholesale_vendor_can_set_minimum_order_amount', 1);
        $this->setBusinessSetting('wholesale_vendor_minimum_order_amount_status', 1);
        $this->seller->forceFill(['wholesale_minimum_order_amount' => 999999])->save();
        $product = $this->tieredProduct(name: 'Isolation Minimum Chair', tiers: [[1, null, 10.0]]);
        $this->context(customer: null, guestId: 'guest-minimum');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1, 'buy_now' => true]);

        $this->assertSame('needs_minimum', $result['status']);
        $this->assertNotEmpty($result['shops']);
        $this->assertArrayHasKey('shortfall', $result['shops'][0]);
        $this->assertSame('minimum_not_met', $this->cartActions()[0]['type']);
    }

    public function test_retail_product_is_not_found(): void
    {
        $retail = $this->retailProduct(name: 'Isolation Retail Chair');
        $this->context(customer: null, guestId: 'guest-retail');

        $result = $this->addToCart(['product_id' => $retail->id, 'quantity' => 1]);

        $this->assertSame('not_found', $result['status']);
    }

    public function test_added_row_matches_what_the_web_cart_add_endpoint_writes(): void
    {
        $productForHttp = $this->tieredProduct(name: 'Isolation Parity Chair Http');
        $this->post(route('wholesale.cart.add'), ['product_id' => $productForHttp->id, 'quantity' => 3])->assertOk();
        $httpRow = Cart::query()->where('product_id', $productForHttp->id)->firstOrFail();

        $productForTool = $this->tieredProduct(name: 'Isolation Parity Chair Tool');
        $this->context(customer: null, guestId: 'guest-parity');
        $this->addToCart(['product_id' => $productForTool->id, 'quantity' => 3]);
        $toolRow = Cart::query()->where('product_id', $productForTool->id)->firstOrFail();

        $this->assertSame($httpRow->variant, $toolRow->variant);
        $this->assertEqualsWithDelta((float) $httpRow->price, (float) $toolRow->price, 0.0001);
        $this->assertEqualsWithDelta((float) $httpRow->discount, (float) $toolRow->discount, 0.0001);
        $this->assertEqualsWithDelta((float) $httpRow->shipping_cost, (float) $toolRow->shipping_cost, 0.0001);
        $this->assertSame($httpRow->shipping_type, $toolRow->shipping_type);
        $this->assertSame($httpRow->business_module, $toolRow->business_module);
        $this->assertSame($httpRow->quantity, $toolRow->quantity);
    }

    public function test_update_cart_quantity_increases_an_existing_line(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Update Chair');
        $this->context(customer: null, guestId: 'guest-update');
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 4]);

        $this->assertSame('updated', $result['status']);
        $this->assertSame(4, $result['quantity']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 4]);
    }

    public function test_update_cart_quantity_reports_not_in_cart(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Not In Cart Chair');
        $this->context(customer: null, guestId: 'guest-not-in-cart');

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame('not_in_cart', $result['status']);
    }

    public function test_update_cart_quantity_caps_at_available_stock(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Cap Chair', stock: 3);
        $this->context(customer: null, guestId: 'guest-cap');
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 10]);

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertSame(3, $result['available']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_update_cart_quantity_never_sees_a_retail_line(): void
    {
        $retail = $this->retailProduct(name: 'Isolation Retail Update Chair');
        $this->context(customer: null, guestId: 'guest-retail-update');

        $result = $this->updateQuantity(['product_id' => $retail->id, 'quantity' => 2]);

        $this->assertSame('not_found', $result['status']);
    }

    public function test_checkout_reports_empty_cart(): void
    {
        $this->context(customer: null, guestId: 'guest-empty');

        $result = $this->checkout();

        $this->assertSame('empty_cart', $result['status']);
    }

    public function test_checkout_succeeds_for_a_checked_cart(): void
    {
        $product = $this->tieredProduct(name: 'Isolation Checkout Chair');
        $customer = $this->customer();
        $this->context(customer: $customer, guestId: null);
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->checkout();

        $this->assertSame('checkout', $result['status']);
        $checkoutAction = $this->cartActions()[array_key_last($this->cartActions())];
        $this->assertSame('checkout', $checkoutAction['type']);
        $this->assertSame(route('wholesale.orders.checkout'), $checkoutAction['url']);
    }

    #[RunInSeparateProcess]
    public function test_checkout_reports_needs_minimum_when_a_seller_minimum_is_not_met(): void
    {
        $this->setBusinessSetting('wholesale_vendor_can_set_minimum_order_amount', 1);
        $this->setBusinessSetting('wholesale_vendor_minimum_order_amount_status', 1);
        $this->seller->forceFill(['wholesale_minimum_order_amount' => 999999])->save();
        $product = $this->tieredProduct(name: 'Isolation Checkout Minimum Chair', tiers: [[1, null, 10.0]]);
        $customer = $this->customer();
        $this->context(customer: $customer, guestId: null);
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->checkout();

        $this->assertSame('needs_minimum', $result['status']);
        $this->assertNotEmpty($result['shops']);
        $this->assertArrayHasKey('shortfall', $result['shops'][0]);
    }

    private function addToCart(array $payload): array
    {
        return json_decode($this->app->make(AddToCartTool::class)->handle(new ToolRequest($payload)), associative: true);
    }

    private function updateQuantity(array $payload): array
    {
        return json_decode($this->app->make(UpdateCartQuantityTool::class)->handle(new ToolRequest($payload)), associative: true);
    }

    private function checkout(): array
    {
        return json_decode($this->app->make(CheckoutTool::class)->handle(new ToolRequest([])), associative: true);
    }

    private function cartActions(): array
    {
        return $this->app->make(CartActionCollector::class)->all();
    }

    private function context(?User $customer, ?string $guestId): void
    {
        $this->app->make(CartSelectionContext::class)->set($customer, $guestId, null);
    }

    private function tieredProduct(string $name, array $tiers = [[1, null, 100.0]], int $minimumOrderQty = 1, int $stock = 500): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'category_ids' => json_encode([['id' => (string) $this->category->id, 'position' => 1]]),
            'brand_id' => null,
            'unit_price' => $tiers[0][2],
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'current_stock' => $stock,
            'minimum_order_qty' => $minimumOrderQty,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);

        $product->wholesaleDetail()->create([
            'variation_based_price' => false,
            'quantity_based_price' => true,
            'variation_type' => null,
            'price_setup' => [
                'rows' => [[
                    'variation_value' => null,
                    'unit_price' => null,
                    'discount' => null,
                    'tiers' => array_map(fn (array $tier): array => [
                        'min_qty' => (string) $tier[0],
                        'max_qty' => $tier[1] === null ? '' : (string) $tier[1],
                        'unit_price' => $tier[2],
                        'discount' => $tier[3] ?? 0,
                    ], $tiers),
                ]],
            ],
        ]);

        return $product->fresh();
    }

    private function colorVariantProduct(string $name): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'category_ids' => json_encode([['id' => (string) $this->category->id, 'position' => 1]]),
            'brand_id' => null,
            'unit_price' => 100.0,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'current_stock' => 500,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'colors' => json_encode(['#FF0000', '#0000FF']),
            'choice_options' => json_encode([]),
        ]);

        $product->wholesaleDetail()->create([
            'variation_based_price' => false,
            'quantity_based_price' => true,
            'variation_type' => null,
            'price_setup' => [
                'rows' => [[
                    'variation_value' => null,
                    'unit_price' => null,
                    'discount' => null,
                    'tiers' => [['min_qty' => '1', 'max_qty' => '', 'unit_price' => 100.0, 'discount' => 0]],
                ]],
            ],
        ]);

        $product->stocks()->createMany([
            ['variant' => 'Red', 'sku' => 'RED', 'price' => 0, 'qty' => 100],
            ['variant' => 'Blue', 'sku' => 'BLUE', 'price' => 0, 'qty' => 100],
        ]);

        return $product->fresh();
    }

    private function retailProduct(string $name): Product
    {
        $categoryRetail = Category::create([
            'name' => $this->category->name,
            'slug' => Str::slug($name).'-retail-category-'.uniqid(),
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $categoryRetail->id,
            'category_ids' => json_encode([['id' => (string) $categoryRetail->id, 'position' => 1]]),
            'brand_id' => null,
            'unit_price' => 25.0,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 100,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::RETAIL,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);
    }

    private function customer(): User
    {
        static $sequence = 0;
        $sequence++;

        return User::query()->create([
            'name' => 'Cart Tools Customer '.$sequence,
            'email' => 'cart-tools-customer-'.$sequence.'-'.uniqid().'@example.test',
            'phone' => '1000000'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);
    }

    private function setBusinessSetting(string $type, mixed $value): void
    {
        $setting = BusinessSetting::query()->updateOrCreate(['type' => $type], ['value' => $value]);

        getWebConfig(name: 'company_name');

        $collection = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings'];
        $existing = $collection?->firstWhere('type', $type);
        if ($existing) {
            $existing->value = $value;
        } else {
            $collection?->push($setting);
        }
    }
}
