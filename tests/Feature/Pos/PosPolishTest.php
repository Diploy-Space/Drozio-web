<?php

namespace Tests\Feature\Pos;

use App\Enums\SessionKey;
use App\Events\CustomerRegistrationEvent;
use App\Models\Admin;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class PosPolishTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const SIMPLE_PRODUCT = 1;

    private const VARIATION_PRODUCT = 434;

    private const ROBERT = 2;

    private const WALK_IN_CART = 'walk-in-customer-polish0001';

    private const ROBERT_CART = 'saved-customer-2';

    private const TOAST_SESSION_KEY = 'laravel-toaster-magic::messages';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        DB::table('products')->where('id', self::SIMPLE_PRODUCT)->update(['current_stock' => 11]);
        $variations = collect(json_decode(DB::table('products_retail')->where('product_id', self::VARIATION_PRODUCT)->value('variation'), true))
            ->map(function (array $variation): array {
                $variation['qty'] = match ($variation['type']) {
                    'White-S' => 0,
                    'Red-M' => 99,
                    'Gray-S' => 1,
                    default => 50,
                };

                return $variation;
            })->all();
        DB::table('products_retail')->where('product_id', self::VARIATION_PRODUCT)->update(['variation' => json_encode($variations)]);

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

    public function test_resuming_a_customer_order_toasts_the_customer_name_instead_of_the_cart_key(): void
    {
        $this->holdCart(cartId: self::ROBERT_CART, customerId: self::ROBERT);

        $this->post(route('admin.retail.pos.change-cart'), ['cart_id' => self::ROBERT_CART])->assertRedirect();

        $toast = json_encode(session(self::TOAST_SESSION_KEY));
        $this->assertStringContainsString('Order for Robert Downey resumed', $toast);
        $this->assertStringNotContainsString(self::ROBERT_CART, $toast);
    }

    public function test_resuming_a_walk_in_order_toasts_walk_in_customer(): void
    {
        $heldWalkInCart = 'walk-in-customer-polishheld';
        $this->holdCart(cartId: $heldWalkInCart, customerId: 0);

        $this->post(route('admin.retail.pos.change-cart'), ['cart_id' => $heldWalkInCart])->assertRedirect();

        $toast = json_encode(session(self::TOAST_SESSION_KEY));
        $this->assertStringContainsString('Order for Walk-in customer resumed', $toast);
        $this->assertStringNotContainsString($heldWalkInCart, $toast);
    }

    public function test_cancelling_a_held_order_returns_the_new_hold_count_without_the_cart_key(): void
    {
        $this->holdCart(cartId: self::ROBERT_CART, customerId: self::ROBERT);

        $response = $this->post(route('admin.retail.pos.cancel-order'), ['cart_id' => self::ROBERT_CART])->assertOk();

        $response->assertJsonPath('totalHoldOrders', 0)->assertJsonPath('status', 'success');
        $this->assertStringNotContainsString(self::ROBERT_CART, $response->json('message'));
        $this->assertFalse(session()->has(self::ROBERT_CART));
    }

    public function test_cart_line_quantity_max_is_the_available_stock_of_the_line(): void
    {
        $this->post(route('admin.retail.pos.add-to-cart'), ['id' => self::SIMPLE_PRODUCT, 'quantity' => 2])->assertOk();
        $view = $this->post(route('admin.retail.pos.add-to-cart'), [
            'id' => self::VARIATION_PRODUCT,
            'color' => '#FF0000',
            'choice_1' => 'M',
            'quantity' => 1,
        ])->assertOk()->json('view');

        $this->assertSame('11', $this->cartLineMax(view: $view, productId: self::SIMPLE_PRODUCT));
        $this->assertSame('99', $this->cartLineMax(view: $view, productId: self::VARIATION_PRODUCT));
    }

    public function test_digital_cart_line_quantity_max_is_the_digital_limit(): void
    {
        $digitalProductId = (int) DB::table('products')->where('product_type', 'digital')->where('added_by', 'admin')->value('id');
        if ($digitalProductId === 0) {
            $this->markTestSkipped('No in-house digital product in the test database.');
        }
        DB::table('products')->where('id', $digitalProductId)->update(['current_stock' => 0]);

        $view = $this->post(route('admin.retail.pos.add-to-cart'), ['id' => $digitalProductId, 'quantity' => 1])->assertOk()->json('view');

        $this->assertSame('9999', $this->cartLineMax(view: $view, productId: $digitalProductId));
    }

    public function test_extra_discount_modal_values_are_rendered_from_the_cart_in_the_default_currency(): void
    {
        $this->post(route('admin.retail.pos.add-to-cart'), ['id' => self::SIMPLE_PRODUCT, 'quantity' => 2])->assertOk();

        $amountView = $this->post(route('admin.retail.pos.update-discount'), ['discount' => 5, 'type' => 'amount'])
            ->assertOk()->assertJsonPath('extraDiscount', 'success')->json('view');
        $this->assertSame(['amount', (string) round(usdToDefaultCurrency(amount: currencyConverter(amount: 5)), 2)], $this->extraDiscountAttributes(view: $amountView));

        $percentView = $this->post(route('admin.retail.pos.update-discount'), ['discount' => 10, 'type' => 'percent'])
            ->assertOk()->json('view');
        $this->assertSame(['percent', '10'], $this->extraDiscountAttributes(view: $percentView));

        $removedView = $this->post(route('admin.retail.pos.remove-discount'))->assertOk()->json('view');
        $this->assertSame(['amount', '0'], $this->extraDiscountAttributes(view: $removedView));
    }

    public function test_coupon_below_min_purchase_reports_the_minimum(): void
    {
        $this->post(route('admin.retail.pos.add-to-cart'), ['id' => self::SIMPLE_PRODUCT, 'quantity' => 1])->assertOk();
        $this->createCoupon(code: 'POSMINMSG', minPurchase: 100);

        $response = $this->post(route('admin.retail.pos.coupon-discount'), ['coupon_code' => 'POSMINMSG'])->assertOk();

        $response->assertJsonPath('coupon', 'min_purchase');
        $expectedMinimum = setCurrencySymbol(amount: usdToDefaultCurrency(amount: 100), currencyCode: getCurrencyCode());
        $this->assertStringContainsString($expectedMinimum, $response->json('message'));
        $this->assertArrayNotHasKey('coupon_code', session(self::WALK_IN_CART));
    }

    public function test_quick_view_stepper_max_and_plus_state_come_from_the_selection(): void
    {
        $inStock = $this->quickView(productId: self::VARIATION_PRODUCT);
        $this->assertSame('50', $this->xpath($inStock)->query('//input[@name="quantity"]')->item(0)->getAttribute('max'));
        $this->assertFalse($this->quantityPlusButton($inStock)->hasAttribute('disabled'));

        DB::table('products')->where('id', self::SIMPLE_PRODUCT)->update(['current_stock' => 1]);
        $lastUnit = $this->quickView(productId: self::SIMPLE_PRODUCT);
        $this->assertSame('1', $this->xpath($lastUnit)->query('//input[@name="quantity"]')->item(0)->getAttribute('max'));
        $this->assertTrue($this->quantityPlusButton($lastUnit)->hasAttribute('disabled'));
    }

    public function test_quick_view_out_of_stock_chip_uses_the_shared_wording(): void
    {
        DB::table('products')->where('id', self::SIMPLE_PRODUCT)->update(['current_stock' => 0]);

        $view = $this->quickView(productId: self::SIMPLE_PRODUCT);

        $chip = $this->xpath($view)->query('//*[contains(@class,"stock-status-in-quick-view")]')->item(0);
        $this->assertSame('Out of Stock', trim($chip->textContent));
    }

    public function test_adding_a_customer_returns_the_new_customer_id(): void
    {
        Event::fake([CustomerRegistrationEvent::class]);
        $email = 'pos.polish.'.Str::lower(Str::random(8)).'@example.com';

        $response = $this->post(route('admin.people.customer.add'), [
            'f_name' => 'Pos',
            'l_name' => 'Polish',
            'email' => $email,
            'phone' => '+1555'.random_int(1000000, 9999999),
            'country' => 'Bangladesh',
        ])->assertOk();

        $response->assertJsonPath('success', true);
        $this->assertSame((int) DB::table('users')->where('email', $email)->value('id'), $response->json('customerId'));
    }

    private function holdCart(string $cartId, int $customerId): void
    {
        session()->push(SessionKey::CART_NAME, $cartId);
        session()->put($cartId, [
            [
                'id' => self::SIMPLE_PRODUCT,
                'customerId' => $customerId,
                'customerOnHold' => true,
                'quantity' => 1,
                'price' => 20.0,
                'name' => 'Pos test product',
                'productType' => 'physical',
                'image' => null,
                'discount' => 0,
                'variant' => '',
                'variations' => [],
            ],
        ]);
    }

    private function createCoupon(string $code, float $minPurchase): void
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
            'max_discount' => 0,
            'discount' => 15,
            'discount_type' => 'amount',
            'status' => 1,
            'limit' => 100,
        ]);
    }

    private function quickView(int $productId): string
    {
        return $this->get(route('admin.retail.pos.quick-view', ['product_id' => $productId]))->assertOk()->json('view');
    }

    private function quantityPlusButton(string $view): DOMElement
    {
        return $this->xpath($view)->query('//button[contains(@class,"cart-qty-field-plus")]')->item(0);
    }

    private function cartLineMax(string $view, int $productId): ?string
    {
        $input = $this->xpath($view)->query('//input[contains(@class,"product-qty")][@data-product-key="'.$productId.'"]')->item(0);

        return $input?->getAttribute('max');
    }

    private function extraDiscountAttributes(string $view): array
    {
        $button = $this->xpath($view)->query('//button[@id="extra_discount"]')->item(0);

        return [$button->getAttribute('data-extra-discount-type'), $button->getAttribute('data-extra-discount-value')];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>');
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
