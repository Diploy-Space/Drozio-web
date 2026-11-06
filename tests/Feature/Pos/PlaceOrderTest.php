<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Models\Admin;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Services\New\BusinessSettingService;
use App\Services\New\ProductRetailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PRODUCT_TWENTY = 1;

    private const VARIATION_PRODUCT = 434;

    private const WALK_IN_CART = 'walk-in-customer-placetest1';

    private const CUSTOMER = 2;

    private const OTHER_CUSTOMER = 4;

    private const CUSTOMER_CART = 'saved-customer-2';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        DB::table('products')->where('id', self::PRODUCT_TWENTY)->update(['current_stock' => 50]);

        $this->actingAs(Admin::query()->findOrFail(1), 'admin');
        $this->withSession([
            SessionKey::CURRENT_USER => self::WALK_IN_CART,
            SessionKey::CART_NAME => [self::WALK_IN_CART],
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_tampered_amount_is_rejected_and_nothing_is_written(): void
    {
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 3));
        $cartBefore = $this->cart();
        $ordersBefore = $this->orderCount();

        $response = $this->placeOrder(amount: $payable - 5);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('The cart changed', $response->json('message'));
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame(50, $this->stock(self::PRODUCT_TWENTY));
        $this->assertEquals($cartBefore, $this->cart());
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
    }

    public function test_placing_an_order_reduces_stock_by_exactly_the_ordered_quantity(): void
    {
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 3));
        $ordersBefore = $this->orderCount();

        $this->placeOrder(amount: $payable)->assertOk()->assertJsonMissingPath('status');

        $this->assertSame($ordersBefore + 1, $this->orderCount());
        $this->assertSame(47, $this->stock(self::PRODUCT_TWENTY));
        $orderId = session('last_order');
        $this->assertNotNull($orderId);
        $this->assertSame(1, DB::table('order_details')->where('order_id', $orderId)->count());
        $this->assertSame(3, (int) DB::table('order_details')->where('order_id', $orderId)->value('qty'));
        $this->assertNull(session(self::WALK_IN_CART));
        $this->assertNotSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
    }

    public function test_ordering_more_than_the_stock_left_is_rejected_and_the_cart_is_restored(): void
    {
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 3));
        DB::table('products')->where('id', self::PRODUCT_TWENTY)->update(['current_stock' => 2]);
        $cartBefore = $this->cart();
        $ordersBefore = $this->orderCount();
        $detailsBefore = DB::table('order_details')->count();

        $response = $this->placeOrder(amount: $payable);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('Only 2 left of', $response->json('message'));
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame($detailsBefore, DB::table('order_details')->count());
        $this->assertSame(2, $this->stock(self::PRODUCT_TWENTY));
        $this->assertEquals($cartBefore, $this->cart());
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
    }

    public function test_repeating_the_request_after_success_creates_no_second_order(): void
    {
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $ordersBefore = $this->orderCount();

        $this->placeOrder(amount: $payable)->assertOk();
        $repeat = $this->placeOrder(amount: $payable);

        $repeat->assertOk()->assertJsonPath('status', 'error');
        $this->assertSame($ordersBefore + 1, $this->orderCount());
        $this->assertSame(48, $this->stock(self::PRODUCT_TWENTY));
    }

    public function test_the_place_order_route_blocks_concurrent_requests_of_one_session(): void
    {
        $route = Route::getRoutes()->getByName('admin.retail.pos.place-order');

        $this->assertNotNull($route->locksFor());
        $this->assertNotNull($route->waitsFor());
    }

    public function test_wallet_payment_for_a_walk_in_customer_is_rejected(): void
    {
        $this->setWalletStatus(isEnabled: true);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1));
        $ordersBefore = $this->orderCount();

        $response = $this->post(route('admin.retail.pos.place-order'), ['type' => 'wallet', 'amount' => $payable]);

        $response->assertOk()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Select a customer to pay by wallet');
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame(50, $this->stock(self::PRODUCT_TWENTY));
        $this->assertCount(1, array_filter($this->cart(), 'is_array'));
    }

    public function test_the_order_total_equals_the_cart_total_with_coupon_and_extra_discount(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6);
        $this->post(route('admin.retail.pos.coupon-discount'), ['coupon_code' => $this->createCoupon('POSPLACE15')])
            ->assertOk()->assertJsonPath('coupon', 'success');
        $discountResponse = $this->post(route('admin.retail.pos.update-discount'), ['discount' => 5, 'type' => 'amount'])
            ->assertOk()->assertJsonPath('extraDiscount', 'success');
        $payable = $this->payableAmount($discountResponse);

        $this->placeOrder(amount: $payable)->assertOk();

        $order = DB::table('orders')->where('id', session('last_order'))->first();
        $this->assertNotNull($order);
        $this->assertEqualsWithDelta(currencyConverter(amount: $payable), (float) $order->order_amount, 0.01);
        $this->assertSame('POSPLACE15', $order->coupon_code);
        $this->assertEqualsWithDelta(15, (float) $order->discount_amount, 0.001);
        $this->assertEqualsWithDelta(5, (float) $order->extra_discount, 0.001);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('cash', $order->payment_method);
    }

    public function test_a_variation_order_decrements_that_variation_row(): void
    {
        $stockBefore = $this->stock(self::VARIATION_PRODUCT);
        $payable = $this->putVariationLine(variant: 'Red-M', quantity: 2);

        $this->placeOrder(amount: $payable)->assertOk()->assertJsonMissingPath('status');

        $variations = collect($this->variations(self::VARIATION_PRODUCT))->keyBy('type');
        $this->assertSame(98, (int) $variations['Red-M']['qty']);
        $this->assertSame(100, (int) $variations['Red-S']['qty']);
        $this->assertSame(100, (int) $variations['White-M']['qty']);
        $this->assertSame($stockBefore - 2, $this->stock(self::VARIATION_PRODUCT));
    }

    public function test_an_emptied_variation_row_is_rejected_even_when_the_product_has_stock(): void
    {
        $stockBefore = $this->stock(self::VARIATION_PRODUCT);
        $payable = $this->putVariationLine(variant: 'Red-M', quantity: 2);
        $this->setVariationQuantity(variant: 'Red-M', quantity: 0);
        $ordersBefore = $this->orderCount();

        $response = $this->placeOrder(amount: $payable);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('Only 0 left of', $response->json('message'));
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame($stockBefore, $this->stock(self::VARIATION_PRODUCT));
        $this->assertSame(0, (int) collect($this->variations(self::VARIATION_PRODUCT))->firstWhere('type', 'Red-M')['qty']);
        $this->assertSame(2, (int) array_filter($this->cart(), 'is_array')[0]['quantity']);
    }

    public function test_the_unconditional_stock_deduction_still_goes_negative_when_stock_is_short(): void
    {
        $productService = app(ProductRetailService::class);
        $this->setVariationQuantity(variant: 'Red-M', quantity: 1);
        $variationStockBefore = $this->stock(self::VARIATION_PRODUCT);

        $productService->deductPosStock(product: Product::query()->findOrFail(self::PRODUCT_TWENTY), variant: '', quantity: 60);
        $productService->deductPosStock(product: Product::query()->findOrFail(self::VARIATION_PRODUCT), variant: 'Red-M', quantity: 3);

        $this->assertSame(-10, $this->stock(self::PRODUCT_TWENTY));
        $this->assertSame(-2, (int) collect($this->variations(self::VARIATION_PRODUCT))->firstWhere('type', 'Red-M')['qty']);
        $this->assertSame($variationStockBefore - 3, $this->stock(self::VARIATION_PRODUCT));
    }

    public function test_a_wallet_order_debits_exactly_the_payable_amount(): void
    {
        $this->setWalletStatus(isEnabled: true);
        $this->openCustomerCart(walletBalance: 100000);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $ordersBefore = $this->orderCount();

        $this->placeWalletOrder(amount: $payable)->assertOk()->assertJsonMissingPath('status');

        $this->assertSame($ordersBefore + 1, $this->orderCount());
        $order = DB::table('orders')->where('id', session('last_order'))->first();
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEqualsWithDelta(100000 - currencyConverter(amount: $payable), $this->walletBalance(), 0.01);
        $this->assertSame(1, DB::table('wallet_transactions')->where('user_id', self::CUSTOMER)->where('reference', 'order_place_in_pos')->where('created_at', '>=', now()->subMinute())->count());
        $this->assertSame(48, $this->stock(self::PRODUCT_TWENTY));
    }

    public function test_a_wallet_order_is_rejected_when_the_wallet_is_turned_off(): void
    {
        $this->setWalletStatus(isEnabled: false);
        $this->openCustomerCart(walletBalance: 100000);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $cartBefore = session(self::CUSTOMER_CART);
        $ordersBefore = $this->orderCount();

        $response = $this->placeWalletOrder(amount: $payable);

        $response->assertOk()->assertJsonPath('status', 'error')->assertJsonPath('message', 'Wallet payment is turned off');
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame(50, $this->stock(self::PRODUCT_TWENTY));
        $this->assertEqualsWithDelta(100000, $this->walletBalance(), 0.001);
        $this->assertEquals($cartBefore, session(self::CUSTOMER_CART));
        $this->assertSame(self::CUSTOMER_CART, session(SessionKey::CURRENT_USER));
    }

    public function test_a_wallet_order_is_rejected_when_the_balance_is_short(): void
    {
        $this->setWalletStatus(isEnabled: true);
        $this->openCustomerCart(walletBalance: 1);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $cartBefore = session(self::CUSTOMER_CART);
        $ordersBefore = $this->orderCount();

        $response = $this->placeWalletOrder(amount: $payable);

        $response->assertOk()->assertJsonPath('status', 'error')->assertJsonPath('message', 'Insufficient wallet balance');
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame(50, $this->stock(self::PRODUCT_TWENTY));
        $this->assertEqualsWithDelta(1, $this->walletBalance(), 0.001);
        $this->assertEquals($cartBefore, session(self::CUSTOMER_CART));
    }

    public function test_a_failed_wallet_debit_rolls_the_order_back_and_answers_with_an_error(): void
    {
        $this->setWalletStatus(isEnabled: false);
        $this->app->instance(BusinessSettingService::class, new class(new BusinessSetting) extends BusinessSettingService
        {
            public function getValueByType(string $type, mixed $default = null): mixed
            {
                return $type === self::WALLET_STATUS ? 1 : parent::getValueByType(type: $type, default: $default);
            }
        });
        $this->openCustomerCart(walletBalance: 100000);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $cartBefore = session(self::CUSTOMER_CART);
        $ordersBefore = $this->orderCount();
        $detailsBefore = DB::table('order_details')->count();

        $response = $this->placeWalletOrder(amount: $payable);

        $response->assertOk()->assertJsonPath('status', 'error')->assertJsonPath('message', 'The wallet payment could not be completed. Please try again.');
        $this->assertSame($ordersBefore, $this->orderCount());
        $this->assertSame($detailsBefore, DB::table('order_details')->count());
        $this->assertSame(50, $this->stock(self::PRODUCT_TWENTY));
        $this->assertEqualsWithDelta(100000, $this->walletBalance(), 0.001);
        $this->assertEquals($cartBefore, session(self::CUSTOMER_CART));
        $this->assertSame(self::CUSTOMER_CART, session(SessionKey::CURRENT_USER));
    }

    public function test_a_card_order_with_an_empty_paid_amount_is_placed(): void
    {
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1));
        $ordersBefore = $this->orderCount();

        $response = $this->post(route('admin.retail.pos.place-order'), ['type' => 'card', 'amount' => $payable, 'paid_amount' => ''], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJsonMissingPath('status');
        $this->assertSame($ordersBefore + 1, $this->orderCount());
        $this->assertSame('card', DB::table('orders')->where('id', session('last_order'))->value('payment_method'));
    }

    public function test_order_details_hold_only_the_lines_of_the_cart_customer(): void
    {
        $this->openCustomerCart(walletBalance: 0);
        $payable = $this->payableAmount($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2));
        $cart = session(self::CUSTOMER_CART);
        $strayLine = [...array_filter($cart, 'is_array')[0], 'id' => self::VARIATION_PRODUCT, 'customerId' => self::OTHER_CUSTOMER, 'variant' => 'Red-M'];
        session()->put(self::CUSTOMER_CART, [...array_values(array_filter($cart, 'is_array')), $strayLine, 'add_to_cart_time' => $cart['add_to_cart_time']]);
        $variationStockBefore = $this->stock(self::VARIATION_PRODUCT);

        $this->placeOrder(amount: $payable)->assertOk()->assertJsonMissingPath('status');

        $orderId = session('last_order');
        $this->assertSame([self::PRODUCT_TWENTY], DB::table('order_details')->where('order_id', $orderId)->pluck('product_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(48, $this->stock(self::PRODUCT_TWENTY));
        $this->assertSame($variationStockBefore, $this->stock(self::VARIATION_PRODUCT));
    }

    private function openCustomerCart(float $walletBalance): void
    {
        DB::table('users')->where('id', self::CUSTOMER)->update(['wallet_balance' => $walletBalance]);
        session()->put(SessionKey::CURRENT_USER, self::CUSTOMER_CART);
        session()->put(SessionKey::CART_NAME, [self::CUSTOMER_CART]);
    }

    private function setWalletStatus(bool $isEnabled): void
    {
        DB::table('business_settings')->updateOrInsert(['type' => 'wallet_status'], ['value' => $isEnabled ? 1 : 0]);
    }

    private function placeWalletOrder(float $amount): TestResponse
    {
        return $this->post(route('admin.retail.pos.place-order'), ['type' => 'wallet', 'amount' => $amount, 'paid_amount' => '']);
    }

    private function walletBalance(): float
    {
        return (float) DB::table('users')->where('id', self::CUSTOMER)->value('wallet_balance');
    }

    private function addToCart(int $productId, int $quantity): TestResponse
    {
        return $this->post(route('admin.retail.pos.add-to-cart'), ['id' => $productId, 'quantity' => $quantity])->assertOk();
    }

    private function placeOrder(float $amount): TestResponse
    {
        return $this->post(route('admin.retail.pos.place-order'), [
            'type' => 'cash',
            'amount' => $amount,
            'paid_amount' => $amount,
        ]);
    }

    private function putVariationLine(string $variant, int $quantity): float
    {
        session()->put(self::WALK_IN_CART, [[
            'id' => self::VARIATION_PRODUCT,
            'customerId' => 0,
            'customerOnHold' => false,
            'quantity' => 1,
            'price' => 20.0,
            'name' => 'Pos test variation',
            'productType' => 'physical',
            'image' => null,
            'discount' => 0,
            'variant' => $variant,
            'variations' => [],
        ]]);

        $response = $this->post(route('admin.retail.pos.update-quantity'), ['key' => self::VARIATION_PRODUCT, 'quantity' => $quantity, 'variant' => $variant])
            ->assertOk();
        $this->assertSame($quantity, (int) array_filter($this->cart(), 'is_array')[0]['quantity']);

        return $this->payableAmount($response);
    }

    private function setVariationQuantity(string $variant, int $quantity): void
    {
        $variations = collect($this->variations(self::VARIATION_PRODUCT))->map(function (array $variation) use ($variant, $quantity): array {
            if ($variation['type'] === $variant) {
                $variation['qty'] = $quantity;
            }

            return $variation;
        })->all();

        DB::table('products_retail')->where('product_id', self::VARIATION_PRODUCT)->update(['variation' => json_encode($variations)]);
    }

    private function createCoupon(string $code): string
    {
        DB::table('coupons')->insert([
            'added_by' => 'admin',
            'business_module' => 'retail',
            'coupon_type' => 'discount_on_purchase',
            'coupon_bearer' => 'inhouse',
            'customer_id' => 0,
            'title' => 'Pos test '.$code,
            'code' => $code,
            'start_date' => now()->subDay()->toDateString(),
            'expire_date' => now()->addYear()->toDateString(),
            'min_purchase' => 100,
            'max_discount' => 0,
            'discount' => 15,
            'discount_type' => 'amount',
            'status' => 1,
            'limit' => 100,
        ]);

        return $code;
    }

    private function cart(): array
    {
        return session(self::WALK_IN_CART) ?? [];
    }

    private function orderCount(): int
    {
        return DB::table('orders')->count();
    }

    private function stock(int $productId): int
    {
        return (int) DB::table('products')->where('id', $productId)->value('current_stock');
    }

    private function variations(int $productId): array
    {
        return json_decode(DB::table('products_retail')->where('product_id', $productId)->value('variation'), true);
    }

    private function payableAmount(TestResponse $response): float
    {
        preg_match('/name="amount"[^>]*value="([^"]*)"/', $response->json('view'), $matches);

        return (float) ($matches[1] ?? -1);
    }
}
