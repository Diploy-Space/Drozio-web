<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Enums\SessionKey;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Category;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Models\User;
use App\Services\New\ColorService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Modules\Wholesale\app\Enums\CheckoutPaymentMethod;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Enums\CheckoutStep;
use Modules\Wholesale\app\Http\Middleware\CustomerCheckoutMiddleware;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use ReflectionClass;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCheckoutWebTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const VIEW_CONTRACT = [
        'step',
        'steps',
        'shippingAddresses',
        'billingAddresses',
        'selectedShippingAddressId',
        'selectedBillingAddressId',
        'countries',
        'countriesName',
        'countriesCode',
        'zipCodes',
        'countryRestrictStatus',
        'zipRestrictStatus',
        'billingInputByCustomer',
        'defaultLocation',
        'summary',
        'cartGroups',
        'paymentMethods',
        'checkoutUrls',
        'checkoutBlockers',
        'isTaxIncluded',
    ];

    private const CHECKOUT_URL_KEYS = [
        'choose_address',
        'choose_billing_address',
        'shipping_method',
        'payment_method',
        'payment',
        'place_cash_on_delivery',
        'place_offline',
        'place_wallet',
        'digital_payment',
        'cart',
        'placed',
    ];

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-checkout-views';

    private const WEB_ORDERS_CONTROLLER_PATH = __DIR__.'/../../../Modules/Wholesale/app/Http/Controllers/Web/Orders';

    private const WEB_ORDERS_CONTROLLER_NAMESPACE = 'Modules\\Wholesale\\app\\Http\\Controllers\\Web\\Orders\\';

    private const STUB_VIEW_CONTENTS = '{{ $summary["total_label"] }}';

    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubThemeView(view: 'orders.checkout');
        $this->stubThemeView(view: 'orders.cart');
        $this->useInHouseWholesaleShippingType(shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
    }

    protected function tearDown(): void
    {
        foreach ($this->stubbedViewPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login_from_every_checkout_route(): void
    {
        $loginUrl = route('customer.auth.login');

        foreach (['wholesale.orders.checkout', 'wholesale.orders.checkout.payment'] as $routeName) {
            $this->flushSession();

            $this->get(route($routeName))->assertRedirect($loginUrl);
            $this->assertSame(route($routeName), session(SessionKey::CUSTOMER_LOGIN_REDIRECT_URL));
        }

        foreach (['wholesale.checkout.address', 'wholesale.checkout.billing-address', 'wholesale.checkout.shipping-method', 'wholesale.checkout.payment-method'] as $routeName) {
            $this->postJson(route($routeName), [])
                ->assertUnauthorized()
                ->assertJsonPath('reason', CustomerCheckoutMiddleware::REASON_LOGIN_REQUIRED)
                ->assertJsonPath('redirect_to', $loginUrl);
        }
    }

    public function test_guest_on_a_theme_without_a_login_page_is_sent_to_the_cart_login_prompt(): void
    {
        config(['app.theme' => 'theme_aster']);
        $cartUrl = route('wholesale.shop-cart');

        foreach (['wholesale.orders.checkout', 'wholesale.orders.checkout.payment', 'wholesale.orders.placed'] as $routeName) {
            $this->flushSession();

            $this->get(route($routeName))->assertRedirect($cartUrl);
            $this->assertTrue(session(CheckoutSessionKey::LOGIN_PROMPT));
        }

        foreach (['wholesale.checkout.address', 'wholesale.checkout.payment-method', 'wholesale.checkout.place.cash-on-delivery'] as $routeName) {
            $this->flushSession();

            $this->postJson(route($routeName), [])
                ->assertUnauthorized()
                ->assertJsonPath('reason', CustomerCheckoutMiddleware::REASON_LOGIN_REQUIRED)
                ->assertJsonPath('redirect_to', $cartUrl);
            $this->assertTrue(session(CheckoutSessionKey::LOGIN_PROMPT));
        }

        $this->assertTrue($this->get($cartUrl)->assertOk()->viewData('isLoginPromptOpen'));
        $this->assertFalse($this->get($cartUrl)->assertOk()->viewData('isLoginPromptOpen'));
    }

    public function test_login_prompt_stays_closed_for_a_customer_and_on_a_theme_with_a_login_page(): void
    {
        $customer = $this->customerWithoutWholesaleCart();

        $this->actingAs($customer, 'customer')
            ->withSession([CheckoutSessionKey::LOGIN_PROMPT => true])
            ->get(route('wholesale.shop-cart'))
            ->assertOk()
            ->assertViewHas('isLoginPromptOpen', false);
        $this->assertNull(session(CheckoutSessionKey::LOGIN_PROMPT));

        auth('customer')->logout();
        $this->flushSession();
        config(['app.theme' => CustomerCheckoutMiddleware::LOGIN_PAGE_THEME]);

        $this->get(route('wholesale.orders.checkout'))->assertRedirect(route('customer.auth.login'));
        $this->assertNull(session(CheckoutSessionKey::LOGIN_PROMPT));
        $this->assertFalse($this->get(route('wholesale.shop-cart'))->viewData('isLoginPromptOpen'));
    }

    public function test_guest_cart_merges_after_login(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $product = $this->createTieredProduct();
        $guestId = $this->guestId();

        $this->withSession(['guest_id' => $guestId])
            ->postJson(route('wholesale.cart.add'), ['product_id' => $product->id, 'quantity' => 60])
            ->assertOk();

        $this->cartService()->moveGuestCartToCustomer(guestId: $guestId, customerId: $customer->id);
        $this->cartService()->moveGuestCartToCustomer(guestId: $guestId, customerId: $customer->id);

        $this->assertSame(1, Cart::query()->where(['customer_id' => $customer->id, 'is_guest' => CartService::CUSTOMER, 'product_id' => $product->id])->count());
        $this->assertFalse(Cart::query()->where(['customer_id' => $guestId, 'is_guest' => CartService::GUEST, 'product_id' => $product->id])->exists());

        $cartGroups = $this->openCheckout(customer: $customer)->assertOk()->viewData('cartGroups');

        $this->assertSame([$product->id], $this->flattenItems(cartGroups: $cartGroups)->pluck('product_id')->all());
    }

    public function test_address_step_renders_the_contract(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $response = $this->openCheckout(customer: $customer)->assertOk()->assertViewHasAll(self::VIEW_CONTRACT);

        $this->assertSame(CheckoutStep::ADDRESS, $response->viewData('step'));
        $this->assertSame(
            [CheckoutStep::CART, CheckoutStep::ADDRESS, CheckoutStep::PAYMENT],
            array_column($response->viewData('steps'), 'key'),
        );
        $this->assertSame([false, true, false], array_column($response->viewData('steps'), 'is_current'));
        $this->assertSame(self::CHECKOUT_URL_KEYS, array_keys($response->viewData('checkoutUrls')));
        $this->assertSame([], $response->viewData('checkoutBlockers'));
        $this->assertNull($response->viewData('selectedShippingAddressId'));
        $this->assertSame(
            ['cash_on_delivery', 'digital', 'offline', 'wallet'],
            array_keys($response->viewData('paymentMethods')),
        );
        $this->assertEquals(2400.0, $response->viewData('summary')['subtotal']);
    }

    public function test_saved_addresses_render_as_an_autofill_payload_only(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);

        $response = $this->openCheckout(customer: $customer)->assertOk();
        $savedAddress = collect($response->viewData('shippingAddresses'))->firstWhere('id', (int) $address->id);

        $this->assertNotNull($savedAddress);
        $this->assertSame([
            'contact_person_name' => $address->contact_person_name,
            'phone' => $address->phone,
            'email' => (string) $address->email,
            'address_type' => $address->address_type,
            'country' => $address->country,
            'city' => $address->city,
            'zip' => $address->zip,
            'address' => $address->address,
            'latitude' => (string) $address->latitude,
            'longitude' => (string) $address->longitude,
        ], $savedAddress['fields']);

        foreach (['default', 'theme_aster'] as $theme) {
            $markup = View::make("wholesale::themes.{$theme}.orders.partials.checkout.address-book", [
                'kind' => 'shipping',
                'addresses' => [$savedAddress],
            ])->render();

            $this->assertStringContainsString('data-address-fields', $markup);
            $this->assertStringNotContainsString('data-address-id', $markup);
            $this->assertStringContainsString($address->city, $markup);
        }
    }

    public function test_choosing_a_saved_address_is_not_required_to_reach_payment(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->createAddress(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.address'), $this->newAddressInput())
            ->assertOk()
            ->assertJsonPath('payment_url', route('wholesale.orders.checkout.payment'));
    }

    public function test_checkout_view_does_not_carry_the_cart_page_actions(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $viewData = $this->openCheckout(customer: $customer)->assertOk()->original->getData();

        $this->assertArrayNotHasKey('cartUrls', $viewData);
        $this->assertArrayNotHasKey('checkoutUrl', $viewData);
        $this->assertArrayHasKey('summary', $viewData);
    }

    public function test_checkout_is_blocked_when_a_shop_minimum_is_not_met(): void
    {
        $minimumOrderAmount = (float) getWebConfig(name: 'minimum_order_amount');

        if (! getWebConfig(name: 'wholesale_vendor_can_set_minimum_order_amount') || $minimumOrderAmount <= 0) {
            $this->markTestSkipped('Test database has the in-house minimum order amount turned off.');
        }

        $customer = $this->customerWithoutWholesaleCart();
        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 1000,
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [1],
        ]);
        $this->addRow(product: $product, customer: $customer, quantity: 1);

        $this->openCheckout(customer: $customer)->assertRedirect(route('wholesale.shop-cart'));

        $blockers = $this->openCart(customer: $customer)->viewData('checkoutBlockers');

        $this->assertNotEmpty($blockers);
    }

    public function test_checkout_is_blocked_when_an_order_wise_group_has_no_shipping_method(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $product = $this->createTieredProduct();

        $this->useInHouseWholesaleShippingType(shippingType: ShippingTypeService::TYPE_ORDER_WISE);
        ShippingMethod::query()->create([
            'creator_id' => 1,
            'creator_type' => 'admin',
            'title' => 'Wholesale checkout fixture',
            'cost' => 15,
            'duration' => '2 days',
            'status' => 1,
            'context' => BusinessModule::WHOLESALE,
        ]);

        $row = $this->addRow(product: $product, customer: $customer, quantity: 60);

        $this->openCheckout(customer: $customer)->assertRedirect(route('wholesale.shop-cart'));
        $this->assertNotEmpty($this->openCart(customer: $customer)->viewData('checkoutBlockers'));

        $shippingMethod = ShippingMethod::query()->where(['title' => 'Wholesale checkout fixture', 'context' => BusinessModule::WHOLESALE])->firstOrFail();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.shipping-method'), ['cart_group_id' => $row->cart_group_id, 'shipping_method_id' => $shippingMethod->id])
            ->assertOk()
            ->assertJsonStructure(['summary', 'checkout_blockers']);

        $this->assertSame($shippingMethod->id, session(CheckoutSessionKey::SHIPPING_METHOD_ID));
        $this->openCheckout(customer: $customer)->assertOk();
    }

    public function test_checkout_is_blocked_when_a_row_became_unavailable(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $product = $this->createTieredProduct();
        $this->addRow(product: $product, customer: $customer, quantity: 60);

        $this->openCheckout(customer: $customer)->assertOk();

        $this->productService()->updateStatus($product->id, false);

        $this->openCheckout(customer: $customer)->assertRedirect(route('wholesale.shop-cart'));
    }

    public function test_checkout_is_blocked_with_an_empty_or_fully_unchecked_cart(): void
    {
        $customer = $this->customerWithoutWholesaleCart();

        $this->openCheckout(customer: $customer)->assertRedirect(route('wholesale.shop-cart'));

        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        Cart::query()->whereKey($row->id)->update(['is_checked' => 0]);

        $this->openCheckout(customer: $customer)->assertRedirect(route('wholesale.shop-cart'));
    }

    public function test_payment_step_requires_a_chosen_address(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.checkout.payment'))
            ->assertRedirect(route('wholesale.orders.checkout'));
    }

    public function test_another_customers_address_cannot_be_chosen(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $otherCustomer = $this->requireFixture(
            User::query()->where('id', '>', 0)->where('is_active', 1)->whereKeyNot($customer->id)->first(),
            'second active customer',
        );
        $otherAddress = $this->createAddress(customer: $otherCustomer);

        foreach (['wholesale.checkout.address', 'wholesale.checkout.billing-address'] as $routeName) {
            $this->actingAs($customer, 'customer')
                ->postJson(route($routeName), ['address_id' => $otherAddress->id])
                ->assertNotFound();
        }

        $this->assertNull(session(CheckoutSessionKey::ADDRESS_ID));
        $this->assertNull(session(CheckoutSessionKey::BILLING_ADDRESS_ID));
    }

    public function test_payment_step_totals_equal_the_cart_totals(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $this->createBoundedTierProduct(), customer: $customer, quantity: 10);

        $cartResponse = $this->openCart(customer: $customer);

        $this->chooseAddresses(customer: $customer);

        $paymentResponse = $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.checkout.payment'))
            ->assertOk()
            ->assertViewHasAll(self::VIEW_CONTRACT);

        $this->assertSame($cartResponse->viewData('summary'), $paymentResponse->viewData('summary'));
        $this->assertSame(
            $this->flattenItems(cartGroups: $cartResponse->viewData('cartGroups'))->where('is_checked', true)->values()->all(),
            $this->flattenItems(cartGroups: $paymentResponse->viewData('cartGroups'))->all(),
        );
        $this->assertSame(CheckoutStep::PAYMENT, $paymentResponse->viewData('step'));
        $this->assertSame([false, false, true], array_column($paymentResponse->viewData('steps'), 'is_current'));
        $this->assertNotNull($paymentResponse->viewData('selectedShippingAddressId'));

        $expectedSubtotal = 0.0;
        $expectedTotal = 0.0;

        foreach (Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::WHOLESALE, 'is_checked' => 1])->get() as $row) {
            $quote = $this->productService()->getPriceQuote(
                $this->reload($row->product_id),
                $this->cartService()->getRowSelection(cartItem: $row),
                (int) $row->quantity,
                app(ColorService::class)->getNameMap(),
            );
            $expectedSubtotal += (float) $quote['subtotal'] - (float) $quote['discount_amount'];
            $expectedTotal += (float) $quote['total'];
        }

        $summary = $paymentResponse->viewData('summary');

        $this->assertEqualsWithDelta($expectedSubtotal, $summary['subtotal'] - $summary['discount'], 0.0001);
        $this->assertEqualsWithDelta($expectedTotal, $summary['total'] - $summary['shipping'] + $summary['coupon_discount'], 0.0001);
    }

    public function test_tax_line_shows_only_for_exclude(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $response = $this->openCheckout(customer: $customer)->assertOk();
        $summary = $response->viewData('summary');
        $isTaxIncluded = (bool) CartService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE)['is_included'];

        $this->assertSame($isTaxIncluded, $response->viewData('isTaxIncluded'));

        if ($isTaxIncluded) {
            $this->assertEquals(0.0, $summary['tax']);
        }

        $this->assertEqualsWithDelta(
            $summary['subtotal'] - $summary['discount'] - $summary['coupon_discount'] + $summary['shipping'] + $summary['tax'],
            $summary['total'],
            0.0001,
        );
    }

    public function test_wholesale_session_keys_do_not_disturb_a_retail_checkout(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);

        $retailSession = ['coupon_code' => 'RETAILCODE', 'coupon_discount' => 25, 'address_id' => 987654, 'billing_address_id' => 987655, 'payment_method' => 'retail_method'];

        $this->actingAs($customer, 'customer')
            ->withSession($retailSession)
            ->postJson(route('wholesale.checkout.address'), ['address_id' => $address->id])
            ->assertOk()
            ->assertJsonPath('address_id', $address->id);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.billing-address'), ['address_id' => $address->id])
            ->assertOk()
            ->assertJsonPath('payment_url', route('wholesale.orders.checkout.payment'));

        $this->openCheckout(customer: $customer)->assertOk();

        foreach ($retailSession as $key => $value) {
            $this->assertSame($value, session($key));
        }

        $this->assertSame($address->id, session(CheckoutSessionKey::ADDRESS_ID));
        $this->assertSame($address->id, session(CheckoutSessionKey::BILLING_ADDRESS_ID));
    }

    public function test_a_new_address_is_saved_for_the_customer_and_chosen(): void
    {
        $customer = $this->customerWithoutWholesaleCart();

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.address'), $this->newAddressInput())
            ->assertOk();

        $address = ShippingAddress::query()->findOrFail($response->json('address_id'));

        $this->assertSame((int) $customer->id, (int) $address->customer_id);
        $this->assertSame(0, (int) $address->is_guest);
        $this->assertSame($address->id, session(CheckoutSessionKey::ADDRESS_ID));

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.address'), ['contact_person_name' => 'Only a name'])
            ->assertUnprocessable();
    }

    public function test_a_new_address_without_save_this_address_is_used_once_and_not_listed(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $input = $this->newAddressInput();
        unset($input['save_address']);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.address'), $input)
            ->assertOk();

        $addressId = (int) $response->json('address_id');

        $this->assertSame(0, (int) ShippingAddress::query()->findOrFail($addressId)->is_saved);
        $this->assertSame($addressId, session(CheckoutSessionKey::ADDRESS_ID));

        $listedIds = collect($this->openCheckout(customer: $customer)->assertOk()->viewData('shippingAddresses'))->pluck('id');

        $this->assertNotContains($addressId, $listedIds);
    }

    public function test_payment_method_must_be_one_the_payment_step_offers(): void
    {
        $customer = $this->customerWithoutWholesaleCart();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.payment-method'), ['payment_method' => CheckoutPaymentMethod::DIGITAL, 'gateway' => 'not-a-gateway'])
            ->assertUnprocessable();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.payment-method'), ['payment_method' => 'bank_robbery'])
            ->assertUnprocessable();

        $this->assertNull(session(CheckoutSessionKey::PAYMENT_METHOD));

        $cashOnDelivery = getWebConfig(name: 'cash_on_delivery');
        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.payment-method'), ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY]);

        if (is_array($cashOnDelivery) && (int) ($cashOnDelivery['status'] ?? 0) === 1) {
            $response->assertOk();
            $this->assertSame(['method' => CheckoutPaymentMethod::CASH_ON_DELIVERY, 'gateway' => null], session(CheckoutSessionKey::PAYMENT_METHOD));
        } else {
            $response->assertUnprocessable();
        }
    }

    public function test_web_order_controllers_extend_the_base_controller_directly(): void
    {
        $controllerClasses = $this->getWebOrderControllerClasses();

        $this->assertNotEmpty($controllerClasses);

        foreach ($controllerClasses as $controllerClass) {
            $this->assertSame(Controller::class, (new ReflectionClass($controllerClass))->getParentClass()->getName(), $controllerClass);
        }
    }

    public function test_no_abstract_controller_remains_in_web_orders(): void
    {
        foreach ($this->getWebOrderControllerClasses() as $controllerClass) {
            $this->assertFalse((new ReflectionClass($controllerClass))->isAbstract(), $controllerClass);
        }
    }

    /** @return array<int, class-string> */
    private function getWebOrderControllerClasses(): array
    {
        return array_map(
            static fn (string $path): string => self::WEB_ORDERS_CONTROLLER_NAMESPACE.basename($path, '.php'),
            glob(self::WEB_ORDERS_CONTROLLER_PATH.'/*.php') ?: [],
        );
    }

    private function useInHouseWholesaleShippingType(string $shippingType): void
    {
        ShippingType::query()->where(['seller_id' => 0, 'context' => BusinessModule::WHOLESALE])->delete();
        ShippingType::query()->create(['seller_id' => 0, 'shipping_type' => $shippingType, 'context' => BusinessModule::WHOLESALE]);
    }

    private function chooseAddresses(User $customer): void
    {
        $address = $this->createAddress(customer: $customer);

        foreach (['wholesale.checkout.address', 'wholesale.checkout.billing-address'] as $routeName) {
            $this->actingAs($customer, 'customer')
                ->postJson(route($routeName), ['address_id' => $address->id])
                ->assertOk();
        }
    }

    private function createAddress(User $customer): ShippingAddress
    {
        $country = getWebConfig(name: 'delivery_country_restriction')
            ? $this->requireFixture(null, 'delivery country fixture for a restricted country list')
            : 'Bangladesh';

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale checkout fixture',
            'address_type' => 'office',
            'address' => 'Warehouse road 1',
            'city' => 'Dhaka',
            'zip' => '1200',
            'country' => $country,
            'phone' => '01700000000',
            'latitude' => '23.8',
            'longitude' => '90.4',
            'is_billing' => 0,
        ]);
    }

    /** @return array<string, mixed> */
    private function newAddressInput(): array
    {
        return [
            'contact_person_name' => 'Wholesale buyer',
            'phone' => '01700000000',
            'address_type' => 'office',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'zip' => '1200',
            'address' => 'Warehouse road 2',
            'latitude' => 23.8,
            'longitude' => 90.4,
            'save_address' => true,
        ];
    }

    private function openCheckout(User $customer): TestResponse
    {
        return $this->actingAs($customer, 'customer')->get(route('wholesale.orders.checkout'));
    }

    private function openCart(User $customer): TestResponse
    {
        return $this->actingAs($customer, 'customer')->get(route('wholesale.shop-cart'))->assertOk();
    }

    /** @param  array<int, array<string, mixed>>  $cartGroups */
    private function flattenItems(array $cartGroups): Collection
    {
        return collect($cartGroups)->flatMap(static fn (array $group): array => $group['items'])->values();
    }

    private function addRow(Model $product, User $customer, int $quantity, array $selection = []): Cart
    {
        $quote = $this->productService()->getPriceQuote($this->reload($product->id), $selection, $quantity, app(ColorService::class)->getNameMap());

        return $this->cartService()->addWholesaleItem(
            product: $this->reload($product->id),
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
            'tier_unit_price' => [70, 60],
        ]);
    }

    private function createProduct(array $overrides): Model
    {
        $product = $this->productService()->create(
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale checkout web fixture'],
                'description' => ['Wholesale checkout web fixture'],
                'code' => 'WSCHK'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                ...$overrides,
            ],
            colorNames: [],
        );

        $this->productService()->updateStatus($product->id, true);
        $product->newQuery()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        return $this->reload($product->id);
    }

    private function reload(int $productId): Model
    {
        return $this->productService()->findUnscoped($productId, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }

    private function customerWithoutWholesaleCart(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::WHOLESALE])->delete();

        return $customer;
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function productService(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function stubThemeView(string $view): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/'.str_replace('.', '/', $view).'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, self::STUB_VIEW_CONTENTS);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
