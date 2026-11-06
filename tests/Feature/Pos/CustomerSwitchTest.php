<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Models\Admin;
use App\View\Composers\WebConfigComposer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class CustomerSwitchTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PRODUCT_TWENTY = 1;

    private const PRODUCT_THIRTY_TWO = 21;

    private const ROBERT = 2;

    private const CHRIS = 4;

    private const WALK_IN_CART = 'walk-in-customer-switch0001';

    private const ROBERT_CART = 'saved-customer-2';

    private const CHRIS_CART = 'saved-customer-4';

    private const MISSING_CUSTOMER = 999999999;

    private const MISSING_CUSTOMER_CART = 'saved-customer-999999999';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        DB::table('products')->whereIn('id', [self::PRODUCT_TWENTY, self::PRODUCT_THIRTY_TWO])->update(['current_stock' => 500]);

        $this->actingAs(Admin::query()->findOrFail(1), 'admin');
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_walk_in_cart_is_carried_to_a_customer_without_a_cart_dropping_only_the_coupon(): void
    {
        $this->openCart(cartId: self::WALK_IN_CART, cart: [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 6, customerId: 0, onHold: false),
            $this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 1, customerId: 0, onHold: false),
            'coupon_code' => 'POSCARRY',
            'coupon_discount' => 15,
            'ext_discount' => 5,
            'ext_discount_type' => 'amount',
        ]);

        $response = $this->switchTo(self::CHRIS);

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('holdNotices', []);
        $this->assertCount(1, $response->json('adjustmentNotices'));
        $this->assertSame(self::CHRIS_CART, session(SessionKey::CURRENT_USER));
        $chrisCart = session(self::CHRIS_CART);
        $this->assertSame([self::PRODUCT_TWENTY, self::PRODUCT_THIRTY_TWO], $this->lineProductIds($chrisCart));
        foreach ($this->lines($chrisCart) as $line) {
            $this->assertEquals(self::CHRIS, $line['customerId']);
            $this->assertFalse($line['customerOnHold']);
        }
        $this->assertArrayNotHasKey('coupon_code', $chrisCart);
        $this->assertArrayNotHasKey('coupon_discount', $chrisCart);
        $this->assertEquals(5, $chrisCart['ext_discount']);
        $this->assertFalse(session()->has(self::WALK_IN_CART));
        $this->assertNotContains(self::WALK_IN_CART, session(SessionKey::CART_NAME));
    }

    public function test_customer_cart_is_parked_with_its_coupon_and_the_new_customer_starts_empty(): void
    {
        $robertCart = [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 6, customerId: self::ROBERT, onHold: false),
            'coupon_code' => 'POSPARK',
            'coupon_discount' => 15,
            'ext_discount' => 5,
            'ext_discount_type' => 'amount',
        ];
        $this->openCart(cartId: self::ROBERT_CART, cart: $robertCart);

        $response = $this->switchTo(self::CHRIS);

        $response->assertJsonPath('totalHoldOrders', 1)->assertJsonPath('adjustmentNotices', []);
        $this->assertCount(1, $response->json('holdNotices'));
        $this->assertStringContainsString('Robert Downey', $response->json('holdNotices.0'));
        $this->assertSame(self::CHRIS_CART, session(SessionKey::CURRENT_USER));
        $this->assertSame([], $this->lines(session(self::CHRIS_CART)));
        $parkedCart = session(self::ROBERT_CART);
        $this->assertContains(self::ROBERT_CART, session(SessionKey::CART_NAME));
        $this->assertSame([self::PRODUCT_TWENTY], $this->lineProductIds($parkedCart));
        $this->assertTrue($parkedCart[0]['customerOnHold']);
        $this->assertSame('POSPARK', $parkedCart['coupon_code']);
        $this->assertEquals(15, $parkedCart['coupon_discount']);
        $this->assertEquals(5, $parkedCart['ext_discount']);
    }

    public function test_switching_back_resumes_the_parked_customer_cart_and_drops_the_empty_one(): void
    {
        $this->openCart(cartId: self::ROBERT_CART, cart: [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 6, customerId: self::ROBERT, onHold: false),
            'coupon_code' => 'POSBACK',
            'coupon_discount' => 15,
        ]);

        $this->switchTo(self::CHRIS);
        $response = $this->switchTo(self::ROBERT);

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('holdNotices', [])->assertJsonPath('adjustmentNotices', []);
        $this->assertSame(self::ROBERT_CART, session(SessionKey::CURRENT_USER));
        $robertCart = session(self::ROBERT_CART);
        $this->assertSame([self::PRODUCT_TWENTY], $this->lineProductIds($robertCart));
        $this->assertFalse($robertCart[0]['customerOnHold']);
        $this->assertSame('POSBACK', $robertCart['coupon_code']);
        $this->assertFalse(session()->has(self::CHRIS_CART));
        $this->assertNotContains(self::CHRIS_CART, session(SessionKey::CART_NAME));
    }

    public function test_customer_cart_is_parked_and_the_target_customer_held_cart_is_resumed(): void
    {
        $this->openCart(cartId: self::ROBERT_CART, cart: [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 2, customerId: self::ROBERT, onHold: false),
        ]);
        $this->holdCart(cartId: self::CHRIS_CART, cart: [
            $this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 3, customerId: self::CHRIS, onHold: true),
        ]);

        $response = $this->switchTo(self::CHRIS);

        $response->assertJsonPath('totalHoldOrders', 1);
        $this->assertCount(1, $response->json('holdNotices'));
        $this->assertSame(self::CHRIS_CART, session(SessionKey::CURRENT_USER));
        $this->assertSame([self::PRODUCT_THIRTY_TWO], $this->lineProductIds(session(self::CHRIS_CART)));
        $this->assertFalse(session(self::CHRIS_CART)[0]['customerOnHold']);
        $this->assertSame([self::PRODUCT_TWENTY], $this->lineProductIds(session(self::ROBERT_CART)));
        $this->assertTrue(session(self::ROBERT_CART)[0]['customerOnHold']);
    }

    public function test_customer_cart_is_parked_when_walk_in_is_selected_and_a_fresh_walk_in_cart_opens(): void
    {
        $this->openCart(cartId: self::ROBERT_CART, cart: [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 2, customerId: self::ROBERT, onHold: false),
        ]);

        $response = $this->switchTo(0);

        $response->assertJsonPath('totalHoldOrders', 1);
        $this->assertStringContainsString('Robert Downey', $response->json('holdNotices.0'));
        $walkInCartId = session(SessionKey::CURRENT_USER);
        $this->assertMatchesRegularExpression('/^walk-in-customer-[a-z0-9]{10}$/', $walkInCartId);
        $this->assertSame([], $this->lines(session($walkInCartId)));
        $this->assertContains($walkInCartId, session(SessionKey::CART_NAME));
        $this->assertTrue(session(self::ROBERT_CART)[0]['customerOnHold']);
    }

    public function test_empty_customer_cart_is_discarded_when_switching_to_a_customer_with_a_held_cart(): void
    {
        $this->openCart(cartId: self::ROBERT_CART, cart: []);
        $this->holdCart(cartId: self::CHRIS_CART, cart: [
            $this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 1, customerId: self::CHRIS, onHold: true),
        ]);

        $response = $this->switchTo(self::CHRIS);

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('holdNotices', [])->assertJsonPath('adjustmentNotices', []);
        $this->assertSame(self::CHRIS_CART, session(SessionKey::CURRENT_USER));
        $this->assertFalse(session(self::CHRIS_CART)[0]['customerOnHold']);
        $this->assertFalse(session()->has(self::ROBERT_CART));
        $this->assertNotContains(self::ROBERT_CART, session(SessionKey::CART_NAME));
    }

    public function test_selecting_walk_in_on_an_empty_walk_in_cart_keeps_the_same_cart(): void
    {
        $this->openCart(cartId: self::WALK_IN_CART, cart: []);

        $response = $this->switchTo(0);

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('holdNotices', []);
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
        $this->assertSame([self::WALK_IN_CART], session(SessionKey::CART_NAME));
    }

    public function test_selecting_walk_in_on_a_walk_in_cart_with_lines_keeps_every_line(): void
    {
        $cart = [$this->line(productId: self::PRODUCT_TWENTY, quantity: 2, customerId: 0, onHold: false), 'coupon_code' => 'POSSTAY', 'coupon_discount' => 15];
        $this->openCart(cartId: self::WALK_IN_CART, cart: $cart);

        $response = $this->switchTo(0);

        $response->assertJsonPath('holdNotices', [])->assertJsonPath('adjustmentNotices', []);
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
        $this->assertEquals($cart, session(self::WALK_IN_CART));
    }

    public function test_selecting_the_customer_who_owns_the_cart_changes_nothing(): void
    {
        $cart = [$this->line(productId: self::PRODUCT_TWENTY, quantity: 2, customerId: self::ROBERT, onHold: false), 'coupon_code' => 'POSSAME', 'coupon_discount' => 15];
        $this->openCart(cartId: self::ROBERT_CART, cart: $cart);

        $response = $this->switchTo(self::ROBERT);

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('holdNotices', [])->assertJsonPath('adjustmentNotices', []);
        $this->assertSame(self::ROBERT_CART, session(SessionKey::CURRENT_USER));
        $this->assertEquals($cart, session(self::ROBERT_CART));
        $this->assertSame([self::ROBERT_CART], session(SessionKey::CART_NAME));
    }

    public function test_customer_change_returns_the_cart_and_customer_views(): void
    {
        $this->openCart(cartId: self::WALK_IN_CART, cart: [$this->line(productId: self::PRODUCT_TWENTY, quantity: 1, customerId: 0, onHold: false)]);

        $response = $this->switchTo(self::CHRIS);

        $this->assertNotEmpty($response->json('view'));
        $this->assertNotEmpty($response->json('cart_view'));
    }

    public function test_an_unknown_customer_is_rejected_and_the_cart_is_untouched(): void
    {
        $walkInCart = [$this->line(productId: self::PRODUCT_TWENTY, quantity: 2, customerId: 0, onHold: false)];
        $this->openCart(cartId: self::WALK_IN_CART, cart: $walkInCart);

        $response = $this->post(route('admin.retail.pos.change-customer'), ['user_id' => self::MISSING_CUSTOMER], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(422);
        $this->assertSame('user_id', $response->json('errors.0.error_code'));
        $this->assertSame('Selected customer could not be found', $response->json('errors.0.message'));
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
        $this->assertEquals($walkInCart, session(self::WALK_IN_CART));
        $this->assertSame([self::WALK_IN_CART], session(SessionKey::CART_NAME));
    }

    public function test_listing_hold_orders_with_a_deleted_customer_leaves_the_current_cart_alone(): void
    {
        $this->openCart(cartId: self::WALK_IN_CART, cart: [$this->line(productId: self::PRODUCT_TWENTY, quantity: 1, customerId: 0, onHold: false)]);
        $this->holdCart(cartId: self::MISSING_CUSTOMER_CART, cart: [$this->line(productId: self::PRODUCT_THIRTY_TWO, quantity: 1, customerId: self::MISSING_CUSTOMER, onHold: true)]);

        $response = $this->post(route('admin.retail.pos.view-hold-orders'));

        $response->assertOk()->assertJsonPath('totalHoldOrders', 1);
        $this->assertStringContainsString(self::MISSING_CUSTOMER_CART, $response->json('view'));
        $this->assertSame(self::WALK_IN_CART, session(SessionKey::CURRENT_USER));
        $this->assertSame([self::WALK_IN_CART, self::MISSING_CUSTOMER_CART], session(SessionKey::CART_NAME));
    }

    public function test_opening_pos_on_a_deleted_customer_cart_carries_its_lines_to_a_walk_in_cart(): void
    {
        $this->openCart(cartId: self::MISSING_CUSTOMER_CART, cart: [
            $this->line(productId: self::PRODUCT_TWENTY, quantity: 3, customerId: self::MISSING_CUSTOMER, onHold: false),
            'coupon_code' => 'POSGONE',
            'coupon_discount' => 15,
            'ext_discount' => 5,
            'ext_discount_type' => 'amount',
        ]);
        View::share('language', getWebConfig(name: 'language') ?? []);
        View::composer('*', WebConfigComposer::class);

        $this->get(route('admin.retail.pos.index'))->assertOk();

        $currentCartId = session(SessionKey::CURRENT_USER);
        $this->assertStringStartsWith('walk-in-customer-', $currentCartId);
        $walkInCart = session($currentCartId);
        $this->assertSame([self::PRODUCT_TWENTY], $this->lineProductIds($walkInCart));
        $this->assertEquals(0, $walkInCart[0]['customerId']);
        $this->assertSame(3, $walkInCart[0]['quantity']);
        $this->assertArrayNotHasKey('coupon_code', $walkInCart);
        $this->assertEquals(5, $walkInCart['ext_discount']);
        $this->assertFalse(session()->has(self::MISSING_CUSTOMER_CART));
        $this->assertNotContains(self::MISSING_CUSTOMER_CART, session(SessionKey::CART_NAME));
        $this->assertContains($currentCartId, session(SessionKey::CART_NAME));
    }

    private function switchTo(int $customerId): TestResponse
    {
        return $this->post(route('admin.retail.pos.change-customer'), ['user_id' => $customerId])->assertOk();
    }

    private function openCart(string $cartId, array $cart): void
    {
        session()->put(SessionKey::CURRENT_USER, $cartId);
        session()->put(SessionKey::CART_NAME, [$cartId]);
        session()->put($cartId, $cart);
    }

    private function holdCart(string $cartId, array $cart): void
    {
        session()->push(SessionKey::CART_NAME, $cartId);
        session()->put($cartId, $cart);
    }

    private function lines(mixed $cart): array
    {
        return array_values(array_filter(is_array($cart) ? $cart : [], 'is_array'));
    }

    private function lineProductIds(mixed $cart): array
    {
        return array_map(fn (array $line): int => (int) $line['id'], $this->lines($cart));
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
}
