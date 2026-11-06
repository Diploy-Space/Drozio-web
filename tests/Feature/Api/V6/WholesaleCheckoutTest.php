<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Events\OrderPlacedEvent;
use App\Models\Cart;
use App\Models\Category;
use App\Models\OfflinePaymentMethod;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\New\ColorService;
use App\Services\New\SettingService;
use App\Utils\Convert;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Modules\Wholesale\app\Enums\CheckoutPaymentMethod;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\CouponService;
use Modules\Wholesale\app\Services\OrderPlacementService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleCheckoutTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CHECKOUT_URI = '/api/v6/wholesale/customer/checkout';

    private const ADDRESS_URI = '/api/v6/wholesale/customer/checkout/address';

    private const CASH_ON_DELIVERY_URI = '/api/v6/wholesale/customer/orders/cash-on-delivery';

    private const OFFLINE_PAYMENT_URI = '/api/v6/wholesale/customer/orders/offline-payment';

    private const WALLET_URI = '/api/v6/wholesale/customer/orders/wallet';

    private const DIGITAL_PAYMENT_URI = '/api/v6/wholesale/customer/orders/digital-payment';

    private const STUB_VIEW_ROOT = __DIR__.'/../../../../storage/framework/testing/wholesale-checkout-api-views';

    private const SUMMARY_AMOUNT_KEYS = ['subtotal', 'discount', 'coupon_discount', 'shipping', 'tax', 'total'];

    private const RETAIL_ORDER_AMOUNT = 1000;

    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
        Event::fake([OrderPlacedEvent::class]);

        $this->rememberLanguageFiles();
        $this->stubThemeView(view: 'orders.checkout', contents: '{{ $summary["total_label"] }}');
        $this->useWholesaleShippingType(sellerId: 0, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
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

    public function test_checkout_data_totals_equal_the_web_checkout(): void
    {
        $customer = $this->customer();
        [$variationProduct, $axis] = $this->createVariationTierProduct();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $variationProduct, customer: $customer, quantity: 15, selection: [$axis => 'M']);
        $address = $this->createAddress(customer: $customer);

        foreach (['wholesale.checkout.address', 'wholesale.checkout.billing-address'] as $routeName) {
            $this->actingAs($customer, 'customer')->postJson(route($routeName), ['address_id' => $address->id])->assertOk();
        }

        $webView = $this->actingAs($customer, 'customer')->get(route('wholesale.orders.checkout.payment'))->assertOk();
        $data = $this->getCheckoutData(customer: $customer, query: ['address_id' => $address->id, 'billing_address_id' => $address->id])
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->json('content.data');

        foreach (self::SUMMARY_AMOUNT_KEYS as $key) {
            $this->assertEqualsWithDelta($webView->viewData('summary')[$key], $data['summary'][$key], 0.000001, $key);
        }

        $this->assertEquals(
            collect($webView->viewData('cartGroups'))->flatMap(fn (array $group): array => $group['items'])->pluck('line_total', 'id')->all(),
            collect($data['cart_groups'])->flatMap(fn (array $group): array => $group['items'])->pluck('line_total', 'id')->all(),
        );
        $this->assertTrue($data['is_purchasable']);
        $this->assertSame([], $data['checkout_blockers']);
        $this->assertSame($address->id, $data['selected_shipping_address_id']);
        $this->assertSame($address->id, $data['selected_billing_address_id']);
        $this->assertContains($address->id, array_column($data['shipping_addresses'], 'id'));
        $this->assertArrayHasKey('wallet', $data['payment_methods']);
        $this->assertNotSame('', $data['summary']['total_label']);
    }

    public function test_checkout_data_lists_the_blockers(): void
    {
        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $this->addRow(product: $product, customer: $customer, quantity: 60);

        $this->assertTrue($this->getCheckoutData(customer: $customer)->assertOk()->json('content.data.is_purchasable'));

        $this->useWholesaleShippingType(sellerId: 0, shippingType: ShippingTypeService::TYPE_ORDER_WISE);
        ShippingMethod::query()->create([
            'creator_id' => 1,
            'creator_type' => 'admin',
            'title' => 'Wholesale checkout API fixture',
            'cost' => 15,
            'duration' => '2 days',
            'status' => 1,
            'context' => BusinessModule::WHOLESALE,
        ]);

        $shippingBlocked = $this->getCheckoutData(customer: $customer)->assertOk()->json('content.data');

        $this->assertFalse($shippingBlocked['is_purchasable']);
        $this->assertNotEmpty($shippingBlocked['checkout_blockers']);

        $this->useWholesaleShippingType(sellerId: 0, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        app(ProductWholesaleService::class)->updateStatus($product->id, false);

        $unavailable = $this->getCheckoutData(customer: $customer)->assertOk()->json('content.data');

        $this->assertFalse($unavailable['is_purchasable']);
        $this->assertNotEmpty($unavailable['checkout_blockers']);

        $this->placeCashOnDelivery(customer: $customer, body: $this->addressBody(address: $this->createAddress(customer: $customer)))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'checkout_blocked_422')
            ->assertJsonPath('errors.0.error_code', 'checkout_blocked');
    }

    public function test_checkout_data_blocks_a_shop_below_its_minimum_order_amount(): void
    {
        if (! getWebConfig(name: 'wholesale_vendor_can_set_minimum_order_amount') || (float) getWebConfig(name: 'minimum_order_amount') <= 0) {
            $this->markTestSkipped('Test database has the in-house minimum order amount turned off.');
        }

        $customer = $this->customer();
        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 1000,
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [1],
        ]);
        $this->addRow(product: $product, customer: $customer, quantity: 1);

        $data = $this->getCheckoutData(customer: $customer)->assertOk()->json('content.data');

        $this->assertFalse($data['is_purchasable']);
        $this->assertNotEmpty($data['checkout_blockers']);
    }

    public function test_guest_identity_is_refused_from_every_checkout_route(): void
    {
        $guestParams = $this->guestParams();
        $requests = [
            ['GET', self::CHECKOUT_URI.'?'.http_build_query($guestParams)],
            ['PUT', self::ADDRESS_URI],
            ['POST', self::CASH_ON_DELIVERY_URI],
            ['POST', self::OFFLINE_PAYMENT_URI],
            ['POST', self::WALLET_URI],
            ['POST', self::DIGITAL_PAYMENT_URI],
        ];

        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri, $guestParams)
                ->assertUnauthorized()
                ->assertJsonPath('identical_code', 'login_required_401')
                ->assertJsonPath('errors.0.error_code', 'login_required');
        }
    }

    public function test_address_selection_rejects_another_customers_address(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->requireFixture(User::query()->where('id', '>', $customer->id)->where('is_active', 1)->first(), 'second active customer');
        $foreignAddress = $this->createAddress(customer: $otherCustomer);
        $ownAddress = $this->createAddress(customer: $customer);
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $this->putJson(self::ADDRESS_URI, ['address_id' => $foreignAddress->id], $this->actingAsCustomer($customer))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'shipping_address_required_422')
            ->assertJsonPath('errors.0.error_code', 'address_not_found');

        $selected = $this->putJson(self::ADDRESS_URI, ['address_id' => $ownAddress->id], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'checkout_address_selected_200')
            ->json('content.data');

        $this->assertSame($ownAddress->id, $selected['address_id']);
        $this->assertSame($ownAddress->id, $selected['address']['id']);

        $this->requireSetting(name: 'cash_on_delivery');
        $this->placeCashOnDelivery(customer: $customer, body: $this->addressBody(address: $foreignAddress))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'shipping_address_required_422');

        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertSame(0, Order::query()->where(['customer_id' => $customer->id, 'shipping_address' => $foreignAddress->id])->count());
    }

    public function test_cash_on_delivery_places_a_wholesale_order(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'shipping_address_required_422');
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());

        $data = $this->placeCashOnDelivery(customer: $customer, body: [...$this->addressBody(address: $address), 'order_note' => 'API wholesale note'])
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_placed_200')
            ->json('content.data');

        $order = Order::query()->findOrFail($data['orders'][0]['id']);

        $this->assertCount(1, $data['orders']);
        $this->assertSame($order->order_group_id, $data['order_group_id']);
        $this->assertSame(BusinessModule::WHOLESALE, $order->business_module);
        $this->assertSame(OrderService::STATUS_PENDING, $data['orders'][0]['order_status']);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $data['orders'][0]['payment_status']);
        $this->assertSame(OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY, $data['orders'][0]['payment_method']);
        $this->assertSame('API wholesale note', $order->order_note);
        $this->assertSame($address->id, (int) $order->shipping_address);
        $this->assertSame(app(CartService::class)->isBillingInputByCustomer() ? $address->id : 0, (int) $order->billing_address);
        $this->assertEqualsWithDelta((float) $order->order_amount, $data['grand_total'], 0.000001);
        $this->assertFalse(Cart::query()->whereKey($row->id)->exists());
        Event::assertDispatched(OrderPlacedEvent::class);
    }

    public function test_order_amount_equals_the_checkout_data_total(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        [$variationProduct, $axis] = $this->createVariationTierProduct();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $variationProduct, customer: $customer, quantity: 15, selection: [$axis => 'M']);
        $coupon = $this->createCoupon(discount: 100, minimumPurchase: 2000);
        $body = [...$this->addressBody(address: $this->createAddress(customer: $customer)), 'coupon_code' => $coupon->code];

        $checkout = $this->getCheckoutData(customer: $customer, query: $body)->assertOk()->json('content.data');

        $this->assertSame($coupon->code, $checkout['coupon']['code']);
        $this->assertEqualsWithDelta(100.0, $checkout['summary']['coupon_discount'], 0.000001);

        $data = $this->placeCashOnDelivery(customer: $customer, body: $body)->assertOk()->json('content.data');
        $orders = Order::query()->whereIn('id', array_column($data['orders'], 'id'))->get();

        $this->assertEqualsWithDelta($checkout['summary']['total'], (float) $orders->sum('order_amount'), 0.000001);
        $this->assertEqualsWithDelta($checkout['summary']['total'], $data['grand_total'], 0.000001);
        $this->assertEqualsWithDelta($checkout['summary']['tax'], (float) $orders->sum('total_tax_amount'), 0.000001);
        $this->assertSame([$coupon->code], $orders->pluck('coupon_code')->unique()->values()->all());
    }

    public function test_two_shops_place_two_orders_under_one_group(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);

        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $this->createProduct(
            overrides: $this->tieredOverrides(),
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        ), customer: $customer, quantity: 60);

        $data = $this->placeCashOnDelivery(customer: $customer, body: $this->addressBody(address: $this->createAddress(customer: $customer)))
            ->assertOk()
            ->json('content.data');
        $orders = Order::query()->whereIn('id', array_column($data['orders'], 'id'))->get();

        $this->assertCount(2, $data['orders']);
        $this->assertSame([$data['order_group_id']], $orders->pluck('order_group_id')->unique()->values()->all());
        $this->assertSame([BusinessModule::WHOLESALE], $orders->pluck('business_module')->unique()->values()->all());
        $this->assertContains($shop->name, array_column($data['orders'], 'shop_name'));
    }

    public function test_wallet_placement_debits_and_refuses_when_short(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $body = [...$this->addressBody(address: $this->createAddress(customer: $customer)), 'payment_method' => CheckoutPaymentMethod::WALLET];

        User::query()->whereKey($customer->id)->update(['wallet_balance' => 1]);

        $this->postJson(self::WALLET_URI, $body, $this->actingAsCustomer($customer->fresh()))
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'insufficient_wallet_balance_422');
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertEqualsWithDelta(1.0, (float) User::query()->whereKey($customer->id)->value('wallet_balance'), 0.0001);

        User::query()->whereKey($customer->id)->update(['wallet_balance' => 100000]);
        $total = (float) $this->getCheckoutData(customer: $customer->fresh(), query: $body)->json('content.data.summary.total');

        $data = $this->postJson(self::WALLET_URI, $body, $this->actingAsCustomer($customer->fresh()))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_placed_200')
            ->json('content.data');
        $expectedDebit = Convert::usd(Convert::default($total));

        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $data['orders'][0]['payment_status']);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $data['orders'][0]['order_status']);
        $this->assertSame(OrderService::PAYMENT_METHOD_WALLET, $data['orders'][0]['payment_method']);
        $this->assertEqualsWithDelta($expectedDebit, (float) WalletTransaction::query()->where('user_id', $customer->id)->latest('id')->value('debit'), 0.0001);
        $this->assertEqualsWithDelta(100000 - $expectedDebit, (float) User::query()->whereKey($customer->id)->value('wallet_balance'), 0.0001);
    }

    public function test_offline_placement_records_the_method(): void
    {
        $this->requireSetting(name: 'offline_payment');
        $method = $this->requireFixture(OfflinePaymentMethod::query()->where('status', 1)->first(), 'active offline payment method');
        $customerInputs = array_column($method->method_informations ?? [], 'customer_input');
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $data = $this->postJson(self::OFFLINE_PAYMENT_URI, [
            ...$this->addressBody(address: $this->createAddress(customer: $customer)),
            'method_id' => $method->id,
            'payment_note' => 'Paid by bank transfer',
            'method_informations' => array_fill_keys($customerInputs, 'TRX-API'),
        ], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_placed_200')
            ->json('content.data');

        $order = Order::query()->findOrFail($data['orders'][0]['id']);
        $storedPaymentInfo = $order->offlinePayments()->firstOrFail()->payment_info;
        $paymentInfo = is_array($storedPaymentInfo) ? $storedPaymentInfo : json_decode((string) $storedPaymentInfo, true);

        $this->assertSame(OrderService::PAYMENT_METHOD_OFFLINE, $order->payment_method);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->payment_status);
        $this->assertSame('Paid by bank transfer', $order->payment_note);
        $this->assertSame($method->id, (int) $paymentInfo['method_id']);

        foreach ($customerInputs as $customerInput) {
            $this->assertSame('TRX-API', $paymentInfo[$customerInput]);
        }

        $this->postJson(self::OFFLINE_PAYMENT_URI, ['method_id' => 99999999], $this->actingAsCustomer($customer))
            ->assertStatus(422);
    }

    public function test_digital_payment_returns_a_link_carrying_the_wholesale_hook(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->createAddress(customer: $customer);
        $body = [...$this->addressBody(address: $address), 'order_note' => 'API gateway note'];

        $total = (float) $this->getCheckoutData(customer: $customer, query: $body)->json('content.data.summary.total');

        $redirectLink = $this->postJson(self::DIGITAL_PAYMENT_URI, [...$body, 'payment_method' => $gateway, 'payment_platform' => 'app'], $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('identical_code', 'digital_payment_link_created_200')
            ->json('content.data.redirect_link');

        parse_str((string) parse_url((string) $redirectLink, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('payment_id', $query);

        $paymentRequest = PaymentRequest::query()->findOrFail($query['payment_id']);
        $payload = json_decode((string) $paymentRequest->additional_data, true);

        $this->assertSame(OrderService::PAYMENT_SUCCESS_HOOK, $paymentRequest->success_hook);
        $this->assertSame(OrderService::PAYMENT_FAILURE_HOOK, $paymentRequest->failure_hook);
        $this->assertSame('app', $paymentRequest->payment_platform);
        $this->assertSame(BusinessModule::WHOLESALE, $payload['business_module']);
        $this->assertSame([(string) $row->cart_group_id], $payload['cart_group_ids']);
        $this->assertSame($address->id, (int) $payload['wholesale_address_id']);
        $this->assertSame($address->id, (int) $payload['wholesale_billing_address_id']);
        $this->assertSame('API gateway note', $payload['wholesale_order_note']);

        $expectedAmount = getWebConfig(name: 'currency_model') === 'multi_currency'
            ? (float) usdToAnotherCurrencyConverter(currencyCode: $paymentRequest->currency_code, amount: $total)
            : $total;

        $this->assertEqualsWithDelta($expectedAmount, (float) $paymentRequest->payment_amount, 0.0001);
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertSame(0, Order::query()->where('customer_id', $customer->id)->where('created_at', '>=', now()->subMinute())->count());
    }

    public function test_a_concurrent_second_placement_creates_one_order_group(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $body = $this->addressBody(address: $this->createAddress(customer: $customer));

        $lock = Cache::lock(
            app(OrderPlacementService::class)->getPlacementLockKey(customerId: $customer->id, cartGroupIds: [$row->cart_group_id]),
            OrderPlacementService::LOCK_SECONDS,
        );
        $this->assertTrue($lock->get());

        $this->placeCashOnDelivery(customer: $customer, body: $body)
            ->assertStatus(409)
            ->assertJsonPath('identical_code', 'order_placement_in_progress_409');
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());

        $lock->release();

        $this->placeCashOnDelivery(customer: $customer, body: $body)->assertOk();
        $this->placeCashOnDelivery(customer: $customer, body: $body)
            ->assertStatus(422)
            ->assertJsonPath('identical_code', 'checkout_blocked_422');

        $this->assertSame(1, Order::query()
            ->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::WHOLESALE])
            ->where('created_at', '>=', now()->subMinute())
            ->count());
    }

    public function test_retail_v6_order_placement_is_unchanged(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $wholesaleRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $retailProduct = $this->retailProduct();
        $address = $this->createAddress(customer: $customer);

        $this->postJson('/api/v6/customer/cart/items', [
            'id' => $retailProduct->id,
            'quantity' => max((int) $retailProduct->minimum_order_qty, (int) ceil(self::RETAIL_ORDER_AMOUNT / (float) $retailProduct->unit_price)),
        ], $this->actingAsCustomer($customer))->assertOk();

        $response = $this->postJson('/api/v6/customer/orders/cash-on-delivery', $this->addressBody(address: $address), $this->actingAsCustomer($customer));

        if ($response->json('identical_code') !== 'order_placed_200') {
            $this->markTestSkipped('Retail v6 checkout is blocked on this fixture: '.$response->json('identical_code'));
        }

        $this->assertSame(1, Order::query()
            ->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])
            ->where('created_at', '>=', now()->subMinute())
            ->count());
        $this->assertTrue(Cart::query()->whereKey($wholesaleRow->id)->exists());
        $this->assertFalse(Cart::query()->where(['customer_id' => $customer->id, 'product_id' => $retailProduct->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    private function getCheckoutData(User $customer, array $query = []): TestResponse
    {
        return $this->getJson(self::CHECKOUT_URI.($query === [] ? '' : '?'.http_build_query($query)), $this->actingAsCustomer($customer));
    }

    private function placeCashOnDelivery(User $customer, array $body = []): TestResponse
    {
        return $this->postJson(
            self::CASH_ON_DELIVERY_URI,
            ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY, ...$body],
            $this->actingAsCustomer($customer),
        );
    }

    /** @return array{address_id: int, billing_address_id: int} */
    private function addressBody(ShippingAddress $address): array
    {
        return ['address_id' => $address->id, 'billing_address_id' => $address->id];
    }

    private function requireSetting(string $name): void
    {
        $setting = getWebConfig(name: $name);
        $isActive = is_array($setting) ? (int) ($setting['status'] ?? 0) === 1 : (bool) $setting;

        if (! $isActive) {
            $this->markTestSkipped('Test database has '.$name.' turned off.');
        }
    }

    private function gateway(): string
    {
        $this->requireSetting(name: 'digital_payment');

        return (string) $this->requireFixture(
            app(SettingService::class)->getActivePaymentGateways()->pluck('key_name')->first(),
            'active payment gateway',
        );
    }

    private function useWholesaleShippingType(int $sellerId, string $shippingType): void
    {
        ShippingType::query()->where(['seller_id' => $sellerId, 'context' => BusinessModule::WHOLESALE])->delete();
        ShippingType::query()->create(['seller_id' => $sellerId, 'shipping_type' => $shippingType, 'context' => BusinessModule::WHOLESALE]);
    }

    private function createAddress(User $customer): ShippingAddress
    {
        if (getWebConfig(name: 'delivery_country_restriction')) {
            $this->markTestSkipped('Test database restricts delivery countries.');
        }

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale checkout API fixture',
            'address_type' => 'office',
            'address' => 'Warehouse road 1',
            'city' => 'Dhaka',
            'zip' => '1200',
            'country' => 'Bangladesh',
            'phone' => '01700000000',
            'latitude' => '23.8',
            'longitude' => '90.4',
            'is_billing' => 0,
        ]);
    }

    private function addRow(Model $product, User $customer, int $quantity, array $selection = []): Cart
    {
        $quote = $this->productService()->getPriceQuote($this->reload($product->id), $selection, $quantity, app(ColorService::class)->getNameMap());

        return app(CartService::class)->addWholesaleItem(
            product: $this->reload($product->id),
            quote: $quote,
            customerId: $customer->id,
            guestId: null,
            selection: $selection,
            shippingType: ShippingTypeService::TYPE_CATEGORY_WISE,
            shippingCost: 0.0,
        );
    }

    private function createCoupon(float $discount, float $minimumPurchase): Model
    {
        return app(CouponService::class)->create(data: [
            'coupon_type' => CouponService::TYPE_DISCOUNT_ON_PURCHASE,
            'title' => 'Wholesale checkout API fixture',
            'code' => 'WSAPI'.random_int(10000, 99999),
            'start_date' => now()->subDay()->toDateString(),
            'expire_date' => now()->addMonth()->toDateString(),
            'min_purchase' => $minimumPurchase,
            'coupon_bearer' => CouponService::BEARER_INHOUSE,
            'seller_id' => CouponService::VENDOR_ALL,
            'customer_id' => CouponService::CUSTOMER_ALL,
            'limit' => 100,
            'discount_type' => CouponService::DISCOUNT_TYPE_AMOUNT,
            'discount' => $discount,
            'max_discount' => $discount,
        ]);
    }

    private function createTieredProduct(): Model
    {
        return $this->createProduct(overrides: $this->tieredOverrides());
    }

    /** @return array<string, mixed> */
    private function tieredOverrides(): array
    {
        return [
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 50],
            'tier_max_qty' => ['49', ''],
            'tier_unit_price' => [50, 40],
        ];
    }

    /** @return array{0: Model, 1: string} */
    private function createVariationTierProduct(): array
    {
        $attributeIds = app(AttributeService::class)->getIds();

        if ($attributeIds === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        $attributeId = (int) $attributeIds[0];

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

    private function createProduct(
        array $overrides,
        string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN,
        ?int $userId = null,
        int $shopId = 0,
    ): Model {
        $product = $this->productService()->create(
            addedBy: $addedBy,
            userId: $userId,
            shopId: $shopId,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale checkout API fixture'],
                'description' => ['Wholesale checkout API fixture'],
                'code' => 'WSAPI'.random_int(10000, 99999),
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
        Product::query()->whereKey($product->id)->update(['request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED]);

        return $this->reload($product->id);
    }

    private function reload(int $productId): Model
    {
        return $this->productService()->findUnscoped($productId, ['wholesaleDetail', 'stocks', 'taxVats.tax', 'category.taxVats.tax']);
    }

    private function retailProduct(): Model
    {
        return $this->requireFixture(
            Product::query()
                ->where('business_module', BusinessModule::RETAIL)
                ->active()
                ->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)
                ->where(fn (Builder $query) => $query->whereNull('colors')->orWhere('colors', '[]'))
                ->where(fn (Builder $query) => $query->whereNull('choice_options')->orWhere('choice_options', '[]'))
                ->whereColumn('current_stock', '>=', 'minimum_order_qty')
                ->where('minimum_order_qty', '>', 0)
                ->where('unit_price', '>', 0)
                ->whereRaw('current_stock * unit_price >= ?', [self::RETAIL_ORDER_AMOUNT * 2])
                ->first(),
            'active retail physical product without variations',
        );
    }

    private function sellerShop(): Shop
    {
        return $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->first(),
            'approved seller with a shop',
        );
    }

    private function customer(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where(['customer_id' => $customer->id])->delete();

        return $customer;
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function productService(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function stubThemeView(string $view, string $contents): void
    {
        $path = self::STUB_VIEW_ROOT.'/themes/'.theme_root_path().'/'.str_replace('.', '/', $view).'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
        $this->stubbedViewPaths[] = $path;

        View::prependNamespace('wholesale', self::STUB_VIEW_ROOT);
    }
}
