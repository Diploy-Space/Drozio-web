<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class PosRequestValidationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PRODUCT_TWENTY = 1;

    private const WALK_IN_CART = 'walk-in-customer-reqtest01';

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

    public function test_negative_extra_discount_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2);

        $response = $this->postAjax(route('admin.retail.pos.update-discount'), ['discount' => -5, 'type' => 'amount']);

        $response->assertStatus(422);
        $this->assertSame('discount', $response->json('errors.0.error_code'));
        $this->assertStringContainsString('greater than 0', $response->json('errors.0.message'));
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
    }

    public function test_non_numeric_extra_discount_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2);

        $response = $this->postAjax(route('admin.retail.pos.update-discount'), ['discount' => 'abc', 'type' => 'amount']);

        $response->assertStatus(422);
        $this->assertSame('discount', $response->json('errors.0.error_code'));
        $this->assertStringContainsString('must be a number', $response->json('errors.0.message'));
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
    }

    public function test_percent_discount_over_100_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 2);

        $response = $this->postAjax(route('admin.retail.pos.update-discount'), ['discount' => 150, 'type' => 'percent']);

        $response->assertStatus(422);
        $this->assertSame('discount', $response->json('errors.0.error_code'));
        $this->assertStringContainsString('100', $response->json('errors.0.message'));
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
    }

    public function test_extra_discount_on_an_empty_cart_is_refused_without_touching_the_session(): void
    {
        $sessionBefore = session()->all();

        $response = $this->post(route('admin.retail.pos.update-discount'), ['discount' => 5, 'type' => 'amount']);

        $response->assertOk()->assertJsonPath('cart', 'empty');
        $this->assertNotEmpty($response->json('message'));
        $this->assertSame($sessionBefore[self::WALK_IN_CART] ?? null, session(self::WALK_IN_CART));
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
    }

    public function test_coupon_on_an_empty_cart_is_refused_without_touching_the_session(): void
    {
        $sessionBefore = session()->all();

        $response = $this->post(route('admin.retail.pos.coupon-discount'), ['coupon_code' => 'ANYCODE']);

        $response->assertOk()->assertJsonPath('cart', 'empty');
        $this->assertNotEmpty($response->json('message'));
        $this->assertSame($sessionBefore[self::WALK_IN_CART] ?? null, session(self::WALK_IN_CART));
        $this->assertArrayNotHasKey('coupon_code', $this->cart());
    }

    public function test_add_to_cart_with_zero_quantity_is_rejected(): void
    {
        $response = $this->postAjax(route('admin.retail.pos.add-to-cart'), ['id' => self::PRODUCT_TWENTY, 'quantity' => 0]);

        $response->assertStatus(422);
        $this->assertSame('quantity', $response->json('errors.0.error_code'));
        $this->assertEmpty(array_filter($this->cart(), 'is_array'));
    }

    public function test_add_to_cart_with_an_unknown_product_id_is_rejected(): void
    {
        $response = $this->postAjax(route('admin.retail.pos.add-to-cart'), ['id' => 999999999, 'quantity' => 1]);

        $response->assertStatus(422);
        $this->assertSame('id', $response->json('errors.0.error_code'));
        $this->assertSame('Selected product could not be found', $response->json('errors.0.message'));
        $this->assertEmpty(array_filter($this->cart(), 'is_array'));
    }

    public function test_an_invalid_payment_type_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);

        $response = $this->postAjax(route('admin.retail.pos.place-order'), ['type' => 'bank', 'amount' => 20]);

        $response->assertStatus(422);
        $this->assertSame('type', $response->json('errors.0.error_code'));
    }

    public function test_cancel_order_with_a_non_held_session_key_is_rejected_and_the_key_survives(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);

        $response = $this->postAjax(route('admin.retail.pos.cancel-order'), ['cart_id' => SessionKey::CURRENT_USER]);

        $response->assertStatus(422);
        $this->assertSame('cart_id', $response->json('errors.0.error_code'));
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
        $this->assertNotEmpty(array_filter($this->cart(), 'is_array'));
    }

    public function test_cancelling_the_current_cart_that_is_not_on_hold_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $cartBefore = $this->cart();

        $response = $this->postAjax(route('admin.retail.pos.cancel-order'), ['cart_id' => self::WALK_IN_CART]);

        $response->assertStatus(422);
        $this->assertSame('cart_id', $response->json('errors.0.error_code'));
        $this->assertEquals($cartBefore, $this->cart());
    }

    public function test_resuming_the_current_cart_that_is_not_on_hold_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $cartBefore = $this->cart();

        $response = $this->postAjax(route('admin.retail.pos.change-cart'), ['cart_id' => self::WALK_IN_CART]);

        $response->assertStatus(422);
        $this->assertSame('cart_id', $response->json('errors.0.error_code'));
        $this->assertEquals($cartBefore, $this->cart());
    }

    public function test_updating_the_quantity_of_an_unknown_product_is_rejected(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $cartBefore = $this->cart();

        $response = $this->postAjax(route('admin.retail.pos.update-quantity'), ['key' => 999999999, 'quantity' => 1, 'variant' => '']);

        $response->assertStatus(422);
        $this->assertSame('key', $response->json('errors.0.error_code'));
        $this->assertSame('Selected product could not be found', $response->json('errors.0.message'));
        $this->assertEquals($cartBefore, $this->cart());
    }

    public function test_get_is_rejected_on_the_post_only_routes(): void
    {
        $this->get(route('admin.retail.pos.change-customer'))->assertStatus(405);
        $this->get(route('admin.retail.pos.empty-cart'))->assertStatus(405);
        $this->get(route('admin.retail.pos.change-cart'))->assertStatus(405);
        $this->get(route('admin.retail.pos.cancel-order'))->assertStatus(405);
        $this->get(route('admin.retail.pos.clear-cart-ids'))->assertStatus(405);
        $this->get(route('admin.retail.pos.new-cart-id'))->assertStatus(405);
        $this->get(route('admin.retail.pos.view-hold-orders'))->assertStatus(405);
        $this->get(route('admin.retail.pos.remove-discount'))->assertStatus(405);
    }

    public function test_remove_discount_clears_the_extra_discount_and_restores_the_total(): void
    {
        $this->addToCart(productId: self::PRODUCT_TWENTY, quantity: 1);
        $discountResponse = $this->post(route('admin.retail.pos.update-discount'), ['discount' => 5, 'type' => 'amount'])
            ->assertOk()->assertJsonPath('extraDiscount', 'success');
        $this->assertArrayHasKey('ext_discount', $this->cart());
        $payableWithDiscount = $this->payableAmount($discountResponse);

        $response = $this->post(route('admin.retail.pos.remove-discount'));

        $response->assertOk()->assertJsonPath('extraDiscount', 'success')->assertJsonPath('extraDiscountAmount', 0);
        $this->assertArrayNotHasKey('ext_discount', $this->cart());
        $this->assertArrayNotHasKey('ext_discount_type', $this->cart());
        $this->assertGreaterThan($payableWithDiscount, $this->payableAmount($response));
    }

    private function addToCart(int $productId, int $quantity): TestResponse
    {
        return $this->post(route('admin.retail.pos.add-to-cart'), ['id' => $productId, 'quantity' => $quantity])->assertOk();
    }

    private function postAjax(string $url, array $data): TestResponse
    {
        return $this->post($url, $data, ['X-Requested-With' => 'XMLHttpRequest']);
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
}
