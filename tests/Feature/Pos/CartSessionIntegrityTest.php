<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CartSessionIntegrityTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PRODUCT_TWENTY = 1;

    private const PRODUCT_THIRTY_TWO = 21;

    private const CUSTOMER_WITH_HELD_CART = 2;

    private const OTHER_CUSTOMER = 4;

    private const WALK_IN_CART = 'walk-in-customer-testcart01';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        DB::table('products')->whereIn('id', [self::PRODUCT_TWENTY, self::PRODUCT_THIRTY_TWO])->update(['current_stock' => 500]);

        $this->actingAs(Admin::query()->findOrFail(1), 'admin');
        $this->withSession([
            SessionKey::CURRENT_USER => self::WALK_IN_CART,
            SessionKey::CART_NAME => [self::WALK_IN_CART],
        ]);
    }

    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_coupon_and_extra_discount_survive_quantity_update_add_and_remove(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6);
        $this->applyCoupon($this->createCoupon(code: 'POSKEEPAMT', type: 'amount', discount: 15, minPurchase: 100));
        $this->applyExtraDiscount(5);

        $couponBefore = $this->cart()['coupon_discount'];
        $extraBefore = $this->cart()['ext_discount'];

        $responses = [
            $this->updateQuantity(productId: self::PRODUCT_TWENTY, quantity: 7),
            $this->addToCart(productId: self::PRODUCT_THIRTY_TWO, quantity: 1),
            $this->removeLine(productId: self::PRODUCT_THIRTY_TWO),
        ];

        foreach ($responses as $response) {
            $response->assertOk();
            $this->assertSame('POSKEEPAMT', $this->cart()['coupon_code'] ?? null);
            $this->assertEquals($couponBefore, $this->cart()['coupon_discount']);
            $this->assertEquals($extraBefore, $this->cart()['ext_discount'] ?? null);
            $this->assertSame('amount', $this->cart()['ext_discount_type']);
            $response->assertJsonPath('adjustmentNotices', []);
        }
    }

    public function test_coupon_is_removed_with_a_notice_when_quick_view_drops_below_min_purchase(): void
    {
        $taxMultiplier = $this->taxMultiplier($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6), usdSubtotal: 120);
        $this->applyCoupon($this->createCoupon(code: 'POSMINAMT', type: 'amount', discount: 15, minPurchase: 100));
        $this->applyExtraDiscount(5);
        $extraDiscount = (float) $this->cart()['ext_discount'];

        $response = $this->post(route('admin.retail.pos.add-to-cart'), [
            'id' => self::PRODUCT_TWENTY,
            'quantity' => 1,
            'quantity_in_cart' => 1,
        ]);

        $response->assertOk()->assertJsonPath('data', 1);
        $this->assertCount(1, $response->json('adjustmentNotices'));
        $this->assertStringContainsString('minimum purchase', $response->json('adjustmentNotices.0'));
        $this->assertArrayNotHasKey('coupon_code', $this->cart());
        $this->assertArrayNotHasKey('coupon_discount', $this->cart());
        $this->assertEquals($extraDiscount, $this->cart()['ext_discount']);

        $expectedPayable = usdToDefaultCurrency(amount: (20 - $extraDiscount) * $taxMultiplier);
        $this->assertEqualsWithDelta($expectedPayable, $this->payableAmount($response), 0.01);
    }

    public function test_percentage_coupon_is_recomputed_after_a_quantity_change(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6);
        $this->applyCoupon($this->createCoupon(code: 'POSPCT10', type: 'percentage', discount: 10, minPurchase: 50, maxDiscount: 1000));

        $this->assertEqualsWithDelta(12, $this->cart()['coupon_discount'], 0.001);

        $response = $this->updateQuantity(productId: self::PRODUCT_TWENTY, quantity: 10)->assertOk();

        $this->assertEqualsWithDelta(20, $this->cart()['coupon_discount'] ?? 0, 0.001);
        $response->assertJsonPath('adjustmentNotices', []);
    }

    public function test_switching_to_a_customer_with_a_held_cart_parks_the_walk_in_cart_and_resumes_theirs(): void
    {
        $heldCartId = 'saved-customer-'.self::CUSTOMER_WITH_HELD_CART;
        $heldLine = $this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 2, customerId: self::CUSTOMER_WITH_HELD_CART, onHold: true);
        session()->put($heldCartId, [$heldLine]);
        session()->push(SessionKey::CART_NAME, $heldCartId);

        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 3);
        $walkInLines = array_filter($this->cart(), 'is_array');

        $response = $this->post(route('admin.retail.pos.change-customer'), ['user_id' => self::CUSTOMER_WITH_HELD_CART]);

        $response->assertOk()->assertJsonPath('totalHoldOrders', 1)->assertJsonMissingPath('customerChange');
        $this->assertCount(1, $response->json('holdNotices'));
        $this->assertSame($heldCartId, session(SessionKey::CURRENT_USER));
        $this->assertEquals([array_merge($heldLine, ['customerOnHold' => false])], session($heldCartId));
        $this->assertContains(self::WALK_IN_CART, session(SessionKey::CART_NAME));
        $parkedLines = array_filter($this->cart(), 'is_array');
        $this->assertCount(count($walkInLines), $parkedLines);
        foreach ($parkedLines as $index => $parkedLine) {
            $this->assertTrue($parkedLine['customerOnHold']);
            $this->assertEquals(array_merge($walkInLines[$index], ['customerOnHold' => true]), $parkedLine);
        }
    }

    public function test_switching_customer_removes_the_coupon_with_a_notice(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6);
        $this->applyCoupon($this->createCoupon(code: 'POSSWITCH', type: 'amount', discount: 15, minPurchase: 100));

        $response = $this->post(route('admin.retail.pos.change-customer'), ['user_id' => self::OTHER_CUSTOMER]);

        $targetCartId = 'saved-customer-'.self::OTHER_CUSTOMER;
        $response->assertOk();
        $this->assertCount(1, $response->json('adjustmentNotices'));
        $this->assertSame($targetCartId, session(SessionKey::CURRENT_USER));
        $targetCart = session($targetCartId);
        $this->assertCount(1, array_filter($targetCart, 'is_array'));
        foreach (['coupon_code', 'coupon_discount', 'coupon_title', 'coupon_bearer', 'coupon_min_purchase', 'coupon_discount_type', 'coupon_amount', 'coupon_max_discount'] as $couponKey) {
            $this->assertArrayNotHasKey($couponKey, $targetCart);
        }
    }

    public function test_new_orders_never_reuse_an_existing_cart_id(): void
    {
        $heldCartId = 'walk-in-customer-aaaaaaaaaa';
        session()->put($heldCartId, [$this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 1, customerId: 0, onHold: true)]);
        session()->push(SessionKey::CART_NAME, $heldCartId);

        $tokens = ['aaaaaaaaaa', 'bbbbbbbbbb', 'bbbbbbbbbb', 'cccccccccc'];
        Str::createRandomStringsUsing(function (int $length) use (&$tokens): string {
            return $length === 10 && $tokens !== [] ? array_shift($tokens) : substr(bin2hex(random_bytes($length)), 0, $length);
        });

        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $this->post(route('admin.retail.pos.new-cart-id'))->assertRedirect();
        $firstNewCartId = session(SessionKey::CURRENT_USER);

        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $this->post(route('admin.retail.pos.new-cart-id'))->assertRedirect();
        $secondNewCartId = session(SessionKey::CURRENT_USER);

        $this->assertSame('walk-in-customer-bbbbbbbbbb', $firstNewCartId);
        $this->assertSame('walk-in-customer-cccccccccc', $secondNewCartId);
        $this->assertMatchesRegularExpression('/^walk-in-customer-[a-z0-9]{10}$/', $secondNewCartId);
        $this->assertCount(1, array_filter(session($heldCartId), 'is_array'));
    }

    public function test_held_carts_with_one_line_are_counted_and_listed_with_the_coupon_deducted(): void
    {
        $heldCartId = 'walk-in-customer-heldone001';
        session()->put($heldCartId, [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 6, customerId: 0, onHold: true),
            'coupon_code' => 'HELDCOUPON',
            'coupon_discount' => 15,
        ]);
        session()->push(SessionKey::CART_NAME, $heldCartId);
        $bareHeldCartId = 'walk-in-customer-heldbare01';
        session()->put($bareHeldCartId, [$this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 1, customerId: 0, onHold: true)]);
        session()->push(SessionKey::CART_NAME, $bareHeldCartId);
        $taxMultiplier = $this->taxMultiplier($this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 6), usdSubtotal: 120);

        $response = $this->post(route('admin.retail.pos.view-hold-orders'));

        $response->assertOk()->assertJsonPath('totalHoldOrders', 2);
        $this->assertStringContainsString($bareHeldCartId, $response->json('view'));
        $expectedAmount = setCurrencySymbol(amount: usdToDefaultCurrency(amount: round((120 - 15) * $taxMultiplier, 2)), currencyCode: getCurrencyCode());
        $this->assertStringContainsString($expectedAmount, $response->json('view'));
        $this->assertStringContainsString($heldCartId, $response->json('view'));
    }

    public function test_a_coupon_worth_more_than_the_cart_is_removed_with_a_notice(): void
    {
        session()->put(self::WALK_IN_CART, [
            [...$this->line(productId: self::PRODUCT_TWENTY, quantity: 1, customerId: 0, onHold: false), 'price' => 10.0],
            'coupon_code' => 'POSOVER',
            'coupon_discount' => 9,
            'coupon_min_purchase' => 0.0,
            'coupon_discount_type' => 'amount',
            'coupon_amount' => 15.0,
            'coupon_max_discount' => 0.0,
        ]);

        $response = $this->updateQuantity(productId: self::PRODUCT_TWENTY, quantity: 1)->assertOk();

        $response->assertJsonPath('adjustmentNotices', ['Coupon removed because its discount is more than the cart total']);
        $this->assertArrayNotHasKey('coupon_code', $this->cart());
        $this->assertArrayNotHasKey('coupon_discount', $this->cart());
    }

    public function test_an_extra_discount_worth_more_than_the_cart_is_removed_with_a_notice(): void
    {
        session()->put(self::WALK_IN_CART, [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 1, customerId: 0, onHold: false),
            'ext_discount' => 100000,
            'ext_discount_type' => 'amount',
        ]);

        $response = $this->updateQuantity(productId: self::PRODUCT_TWENTY, quantity: 1)->assertOk();

        $response->assertJsonPath('adjustmentNotices', ['Extra discount removed because it is more than the cart total']);
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
        $this->assertArrayNotHasKey('ext_discount_type', $this->cart());
    }

    public function test_removing_the_last_line_drops_the_coupon_and_extra_discount_silently(): void
    {
        session()->put(self::WALK_IN_CART, [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 6, customerId: 0, onHold: false),
            'coupon_code' => 'POSLAST',
            'coupon_discount' => 15,
            'coupon_discount_type' => 'amount',
            'coupon_amount' => 15.0,
            'ext_discount' => 5,
            'ext_discount_type' => 'amount',
        ]);

        $response = $this->removeLine(productId: self::PRODUCT_TWENTY)->assertOk();

        $response->assertJsonPath('adjustmentNotices', []);
        $this->assertSame([], array_filter($this->cart(), 'is_array'));
        foreach (['coupon_code', 'coupon_discount', 'coupon_discount_type', 'coupon_amount', 'ext_discount', 'ext_discount_type'] as $key) {
            $this->assertArrayNotHasKey($key, $this->cart());
        }
    }

    private function addToCart(int $productId, int $quantity): TestResponse
    {
        return $this->post(route('admin.retail.pos.add-to-cart'), ['id' => $productId, 'quantity' => $quantity])->assertOk();
    }

    private function updateQuantity(int $productId, int $quantity): TestResponse
    {
        return $this->post(route('admin.retail.pos.update-quantity'), ['key' => $productId, 'quantity' => $quantity, 'variant' => '']);
    }

    private function removeLine(int $productId): TestResponse
    {
        return $this->post(route('admin.retail.pos.remove-cart'), ['id' => $productId, 'variant' => '']);
    }

    private function applyCoupon(string $code): void
    {
        $this->post(route('admin.retail.pos.coupon-discount'), ['coupon_code' => $code])
            ->assertOk()
            ->assertJsonPath('coupon', 'success');
    }

    private function applyExtraDiscount(float $amount): void
    {
        $this->post(route('admin.retail.pos.update-discount'), ['discount' => $amount, 'type' => 'amount'])
            ->assertOk()
            ->assertJsonPath('extraDiscount', 'success');
    }

    private function createCoupon(string $code, string $type, float $discount, float $minPurchase, float $maxDiscount = 0): string
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
            'min_purchase' => $minPurchase,
            'max_discount' => $maxDiscount,
            'discount' => $discount,
            'discount_type' => $type,
            'status' => 1,
            'limit' => 100,
        ]);

        return $code;
    }

    private function line(int $productId, int $quantity, int $customerId, bool $onHold): array
    {
        $price = (float) DB::table('products')->where('id', $productId)->value('unit_price');

        return [
            'id' => $productId,
            'customerId' => $customerId,
            'customerOnHold' => $onHold,
            'quantity' => $quantity,
            'price' => $price,
            'name' => 'Pos test product '.$productId,
            'productType' => 'physical',
            'image' => null,
            'discount' => 0,
            'variant' => '',
            'variations' => [],
        ];
    }

    private function cart(): array
    {
        return session(self::WALK_IN_CART) ?? [];
    }

    private function payableAmount(TestResponse $response): float
    {
        preg_match('/name="amount"[^>]*value="([^"]*)"/', $response->json('view'), $matches);

        return (float) ($matches[1] ?? -1);
    }

    private function taxMultiplier(TestResponse $response, float $usdSubtotal): float
    {
        return $this->payableAmount($response) / usdToDefaultCurrency(amount: $usdSubtotal);
    }
}
