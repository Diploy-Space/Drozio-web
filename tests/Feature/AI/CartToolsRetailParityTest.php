<?php

namespace Tests\Feature\AI;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Color;
use App\Models\DigitalProductVariation;
use App\Models\Product;
use App\Models\ProductRetail;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\New\CartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AI\app\Services\ShoppingAssistant\CartActionCollector;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\AI\app\Tools\AddToCartTool;
use Modules\AI\app\Tools\CheckoutTool;
use Modules\AI\app\Tools\UpdateCartQuantityTool;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionFunction;
use Tests\TestCase;

class CartToolsRetailParityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_guest_adds_a_single_variant_product(): void
    {
        $product = $this->product();
        $this->context(customer: null, guestId: 'guest-single');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame('added', $result['status']);
        $this->assertSame(2, $result['quantity']);
        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'customer_id' => 'guest-single',
            'is_guest' => CartService::GUEST,
            'business_module' => BusinessModule::RETAIL,
            'quantity' => 2,
        ]);
        $this->assertSame('added', $this->cartActions()[0]['type']);
    }

    public function test_customer_adds_a_color_and_option_variant(): void
    {
        Color::query()->create(['name' => 'Red', 'code' => '#FF0000']);
        $product = $this->productWithVariant();
        $customer = $this->customer();
        $this->context(customer: $customer, guestId: null);

        $result = $this->addToCart([
            'product_id' => $product->id,
            'color' => 'Red',
            'options' => ['Size' => 'M'],
            'quantity' => 1,
        ]);

        $this->assertSame('added', $result['status']);
        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'variant' => 'Red-M',
            'business_module' => BusinessModule::RETAIL,
        ]);
    }

    public function test_add_multiple_variations_in_one_call(): void
    {
        Color::query()->create(['name' => 'Red', 'code' => '#FF0000']);
        Color::query()->create(['name' => 'Blue', 'code' => '#0000FF']);
        $product = $this->productWithVariant();
        $this->context(customer: null, guestId: 'guest-multi');

        $result = $this->addToCart([
            'product_id' => $product->id,
            'variations' => [
                ['color' => 'Red', 'options' => ['Size' => 'M'], 'quantity' => 1],
                ['color' => 'Blue', 'options' => ['Size' => 'M'], 'quantity' => 2],
            ],
        ]);

        $this->assertSame('added', $result['status']);
        $this->assertCount(2, $result['added']);
        $this->assertSame(2, Cart::query()->where('product_id', $product->id)->count());
    }

    public function test_digital_variant_is_added_by_variant_key(): void
    {
        $product = $this->digitalProduct();
        DigitalProductVariation::query()->create([
            'product_id' => $product->id,
            'variant_key' => 'pdf_standard',
            'price' => 9.99,
        ]);
        $this->context(customer: null, guestId: 'guest-digital');

        $result = $this->addToCart([
            'product_id' => $product->id,
            'options' => ['Format' => 'pdf_standard'],
            'quantity' => 1,
        ]);

        $this->assertSame('added', $result['status']);
        $this->assertDatabaseHas('carts', [
            'product_id' => $product->id,
            'variant' => 'pdf_standard',
            'product_type' => 'digital',
        ]);
    }

    public function test_out_of_stock_blocks_add(): void
    {
        $product = $this->product(['current_stock' => 1]);
        $this->context(customer: null, guestId: 'guest-oos');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 5]);

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertSame(1, $result['available']);
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }

    public function test_quantity_below_minimum_order_is_auto_raised(): void
    {
        $product = $this->product(['minimum_order_qty' => 5, 'current_stock' => 50]);
        $this->context(customer: null, guestId: 'guest-moq');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $this->assertSame('added', $result['status']);
        $this->assertSame(5, $result['quantity']);
        $this->assertSame(1, $result['requested_quantity']);
        $this->assertSame(5, $result['minimum_order_qty']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 5]);
    }

    public function test_buy_now_needs_shipping_selection_when_order_wise(): void
    {
        $this->ensureAdminShippingMethod();
        $product = $this->product();
        $this->context(customer: null, guestId: 'guest-shipping');

        $result = $this->addToCart(['product_id' => $product->id, 'quantity' => 1, 'buy_now' => true]);

        $this->assertSame('buy_now', $result['status']);
        $checkoutAction = $this->cartActions()[0];
        $this->assertSame('checkout', $checkoutAction['type']);
        $this->assertTrue($checkoutAction['needs_shipping_selection']);
    }

    #[RunInSeparateProcess]
    public function test_buy_now_blocked_when_shop_minimum_not_met(): void
    {
        $this->setBusinessSetting('minimum_order_amount_status', 1);
        $this->setBusinessSetting('minimum_order_amount', 999999);
        $this->setBusinessSetting('minimum_order_amount_by_seller', 0);
        $product = $this->product(['unit_price' => 10]);
        $this->context(customer: null, guestId: 'guest-minimum');
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->checkout();

        $this->assertSame('needs_minimum', $result['status']);
        $this->assertNotEmpty($result['shops']);
        $this->assertArrayHasKey('shortfall', $result['shops'][0]);
    }

    public function test_update_cart_quantity_increases_an_existing_line(): void
    {
        $product = $this->product();
        $this->context(customer: null, guestId: 'guest-update');
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 4]);

        $this->assertSame('updated', $result['status']);
        $this->assertSame(4, $result['quantity']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 4]);
    }

    public function test_update_cart_quantity_reports_not_in_cart(): void
    {
        $product = $this->product();
        $this->context(customer: null, guestId: 'guest-not-in-cart');

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame('not_in_cart', $result['status']);
    }

    public function test_update_cart_quantity_caps_at_available_stock(): void
    {
        $product = $this->product(['current_stock' => 3]);
        $this->context(customer: null, guestId: 'guest-cap');
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->updateQuantity(['product_id' => $product->id, 'quantity' => 10]);

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertSame(3, $result['available']);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_checkout_reports_empty_cart(): void
    {
        $this->context(customer: null, guestId: 'guest-empty');

        $result = $this->checkout();

        $this->assertSame('empty_cart', $result['status']);
    }

    public function test_checkout_succeeds_for_a_checked_cart(): void
    {
        $product = $this->product();
        $customer = $this->customer();
        $this->context(customer: $customer, guestId: null);
        $this->addToCart(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->checkout();

        $this->assertSame('checkout', $result['status']);
        $this->assertContains('checkout', array_column($this->cartActions(), 'type'));
    }

    public function test_wholesale_cart_lines_are_invisible_to_retail_update_and_checkout(): void
    {
        $wholesaleProduct = $this->product(['business_module' => BusinessModule::WHOLESALE]);
        $customer = $this->customer();
        $wholesaleCart = Cart::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => CartService::CUSTOMER,
            'cart_group_id' => $customer->id.'-wholesale-fixture',
            'product_id' => $wholesaleProduct->id,
            'product_type' => CartService::PRODUCT_TYPE_PHYSICAL,
            'variant' => '',
            'variations' => json_encode([]),
            'choices' => json_encode([]),
            'quantity' => 10,
            'price' => $wholesaleProduct->unit_price,
            'discount' => 0,
            'is_checked' => 1,
            'slug' => $wholesaleProduct->slug,
            'name' => $wholesaleProduct->name,
            'thumbnail' => $wholesaleProduct->thumbnail,
            'seller_id' => CartService::IN_HOUSE_CART_SELLER_ID,
            'seller_is' => 'admin',
            'shipping_cost' => 0,
            'shipping_type' => 'order_wise',
        ]);
        $wholesaleCart->forceFill(['business_module' => BusinessModule::WHOLESALE])->save();
        $this->context(customer: $customer, guestId: null);

        $checkoutResult = $this->checkout();
        $this->assertSame('empty_cart', $checkoutResult['status']);

        $updateResult = $this->updateQuantity(['product_id' => $wholesaleProduct->id, 'quantity' => 20]);
        $this->assertSame('not_found', $updateResult['status']);
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

    private function product(array $overrides = []): Product
    {
        static $sequence = 0;
        $sequence++;

        return Product::query()->create(array_merge([
            'name' => 'Parity Product '.$sequence,
            'slug' => 'parity-product-'.$sequence.'-'.uniqid(),
            'added_by' => 'admin',
            'user_id' => 1,
            'category_id' => null,
            'category_ids' => json_encode([]),
            'brand_id' => null,
            'unit_price' => 20.0,
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
        ], $overrides));
    }

    private function productWithVariant(): Product
    {
        $product = $this->product([
            'colors' => json_encode(['#FF0000', '#0000FF']),
            'choice_options' => json_encode([
                ['title' => 'Size', 'name' => 'Size', 'options' => ['S', 'M', 'L']],
            ]),
        ]);

        ProductRetail::query()->create([
            'product_id' => $product->id,
            'variation' => json_encode([
                ['type' => 'Red-M', 'price' => 21.0, 'sku' => 'RED-M', 'qty' => 50],
                ['type' => 'Blue-M', 'price' => 22.0, 'sku' => 'BLUE-M', 'qty' => 50],
            ]),
        ]);

        return $product->fresh();
    }

    private function digitalProduct(): Product
    {
        $product = $this->product(['product_type' => 'digital']);

        ProductRetail::query()->create([
            'product_id' => $product->id,
            'digital_product_type' => 'ready_product',
        ]);

        return $product->fresh();
    }

    private function customer(): User
    {
        static $sequence = 0;
        $sequence++;

        return User::query()->create([
            'name' => 'Parity Customer '.$sequence,
            'email' => 'parity-customer-'.$sequence.'-'.uniqid().'@example.test',
            'phone' => '1000000'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);
    }

    private function ensureAdminShippingMethod(): void
    {
        if (ShippingMethod::query()->where(['status' => 1, 'creator_type' => 'admin', 'context' => BusinessModule::RETAIL])->exists()) {
            return;
        }

        ShippingMethod::query()->create([
            'creator_id' => 0,
            'creator_type' => 'admin',
            'title' => 'Parity Shipping',
            'cost' => 5,
            'duration' => '2-3 days',
            'status' => 1,
            'context' => BusinessModule::RETAIL,
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
