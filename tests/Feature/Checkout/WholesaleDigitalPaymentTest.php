<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Events\OrderPlacedEvent;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\ColorService;
use App\Services\New\CurrencyService;
use App\Services\New\SettingService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Modules\Wholesale\app\Enums\CheckoutPaymentReason;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Events\DigitalPaymentOrderNotPlacedEvent;
use Modules\Wholesale\app\Listeners\DigitalPaymentOrderNotPlacedListener;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleDigitalPaymentTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-digital-payment-views';

    private const STUB_VIEW_CONTENTS = '{{ $summary["total_label"] }}';

    private const PLACED_VIEW_CONTENTS = '{{ $grandTotalLabel }}';

    private const MULTI_CURRENCY = 'multi_currency';

    private const RETAIL_ORDER_AMOUNT = 1000;

    private array $stubbedViewPaths = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
        Event::fake([OrderPlacedEvent::class, DigitalPaymentOrderNotPlacedEvent::class]);

        $this->rememberLanguageFiles();
        $this->stubThemeView(view: 'orders.checkout', contents: self::STUB_VIEW_CONTENTS);
        $this->stubThemeView(view: 'orders.order-placed', contents: self::PLACED_VIEW_CONTENTS);
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

    public function test_payment_link_carries_the_wholesale_hooks_and_module(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->chooseAddresses(customer: $customer);

        $response = $this->startDigitalPayment(customer: $customer, gateway: $gateway)->assertRedirect();

        $paymentRequest = $this->paymentRequestFrom(response: $response);
        $payload = json_decode((string) $paymentRequest->additional_data, true);

        $this->assertSame(OrderService::PAYMENT_SUCCESS_HOOK, $paymentRequest->success_hook);
        $this->assertSame(OrderService::PAYMENT_FAILURE_HOOK, $paymentRequest->failure_hook);
        $this->assertSame(OrderService::PAYMENT_ATTRIBUTE, $paymentRequest->attribute);
        $this->assertSame($gateway, $paymentRequest->payment_method);
        $this->assertSame((string) $customer->id, (string) $paymentRequest->payer_id);
        $this->assertSame(route('wholesale.checkout.payment.success'), $paymentRequest->external_redirect_link);
        $this->assertSame(BusinessModule::WHOLESALE, $payload['business_module']);
        $this->assertSame((int) $customer->id, $payload['customer_id']);
        $this->assertSame([(string) $row->cart_group_id], $payload['cart_group_ids']);
        $this->assertSame($address->id, $payload[CheckoutSessionKey::ADDRESS_ID]);
        $this->assertSame($address->id, $payload[CheckoutSessionKey::BILLING_ADDRESS_ID]);
        $this->assertArrayHasKey(CheckoutSessionKey::COUPON_CODE, $payload);
        $this->assertArrayHasKey(CheckoutSessionKey::ORDER_NOTE, $payload);
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
    }

    public function test_payment_amount_equals_the_checkout_total(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        [$variationProduct, $axisKey] = $this->createVariationTierProduct();
        $this->addRow(product: $variationProduct, customer: $customer, quantity: 15, selection: [$axisKey => 'M']);
        $this->chooseAddresses(customer: $customer);

        $summary = $this->openPaymentStep(customer: $customer)->viewData('summary');
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));

        $this->assertEqualsWithDelta(
            $this->expectedGatewayAmount(total: $summary['total'], currencyCode: $paymentRequest->currency_code),
            (float) $paymentRequest->payment_amount,
            0.0001,
        );

        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: 'WSDP-TOTAL-'.random_int(10000, 99999));

        $this->assertEqualsWithDelta(
            $summary['total'],
            (float) Order::query()->where('transaction_ref', $paymentRequest->fresh()->transaction_id)->sum('order_amount'),
            0.000001,
        );
    }

    public function test_blocked_cart_never_reaches_the_gateway(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $paymentRequestCount = PaymentRequest::query()->count();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway])
            ->assertUnprocessable()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('redirect', route('wholesale.orders.checkout'));

        Cart::query()->where('customer_id', $customer->id)->delete();
        $this->chooseAddresses(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway])
            ->assertUnprocessable()
            ->assertJsonPath('redirect', route('wholesale.shop-cart'));

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.digital-payment'), ['payment_method' => 'not_a_gateway'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', 'payment_method');

        $this->assertSame($paymentRequestCount, PaymentRequest::query()->count());
    }

    public function test_success_hook_places_a_paid_confirmed_wholesale_order(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $address = $this->chooseAddresses(customer: $customer);
        $this->actingAs($customer, 'customer')->postJson(route('wholesale.cart.order-note'), ['order_note' => 'Paid by gateway'])->assertOk();

        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-PLACE-'.random_int(10000, 99999);

        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);

        $orders = Order::query()->where('transaction_ref', $transactionId)->get();

        $this->assertCount(1, $orders);
        $this->assertSame(BusinessModule::WHOLESALE, $orders[0]->business_module);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $orders[0]->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $orders[0]->payment_status);
        $this->assertSame($gateway, $orders[0]->payment_method);
        $this->assertSame((int) $customer->id, (int) $orders[0]->customer_id);
        $this->assertSame('Paid by gateway', $orders[0]->order_note);
        $this->assertSame($address->id, (int) $orders[0]->shipping_address);
        $this->assertFalse(Cart::query()->whereKey($row->id)->exists());
        Event::assertDispatched(OrderPlacedEvent::class);
    }

    public function test_success_landing_puts_the_placed_orders_in_session(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-LAND-'.random_int(10000, 99999);
        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);
        $token = base64_encode('payment_method='.$gateway.'&&transaction_reference='.$transactionId);

        auth('customer')->logout();

        $this->get(route('wholesale.checkout.payment.success').'?flag=success&&token='.$token)
            ->assertRedirect(route('customer.auth.login'));

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.checkout.payment.success').'?flag=success&&token='.$token)
            ->assertRedirect(route('wholesale.orders.placed'));

        $this->assertSame(
            Order::query()->where('transaction_ref', $transactionId)->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
            session(CheckoutSessionKey::ORDER_IDS),
        );
        $this->assertNull(session(CheckoutSessionKey::ADDRESS_ID));

        $otherCustomer = User::query()->where('id', '!=', $customer->id)->where('id', '>', 0)->first();

        if ($otherCustomer !== null) {
            $this->flushSession();
            $this->actingAs($otherCustomer, 'customer')
                ->get(route('wholesale.checkout.payment.success').'?flag=success&&token='.$token)
                ->assertRedirect(route('wholesale.shop-cart'));
            $this->assertNull(session(CheckoutSessionKey::ORDER_IDS));
        }
    }

    public function test_the_app_reads_the_orders_a_payment_placed(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-APP-'.random_int(10000, 99999);
        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);
        $order = Order::query()->where('transaction_ref', $transactionId)->firstOrFail();
        $uri = '/api/v6/wholesale/customer/orders/digital-payment/'.$transactionId;

        $this->getJson($uri, $this->actingAsCustomer($customer))
            ->assertOk()
            ->assertJsonPath('content.data.order_group_id', $order->order_group_id)
            ->assertJsonPath('content.data.orders.0.id', (int) $order->id)
            ->assertJsonPath('content.data.orders.0.payment_status', OrderService::PAYMENT_STATUS_PAID);

        $this->getJson('/api/v6/wholesale/customer/orders/digital-payment/WSDP-UNKNOWN', $this->actingAsCustomer($customer))
            ->assertNotFound();

        $otherCustomer = User::query()->where('id', '!=', $customer->id)->where('id', '>', 0)->first();

        if ($otherCustomer !== null) {
            $this->getJson($uri, $this->actingAsCustomer($otherCustomer))->assertNotFound();
        }
    }

    public function test_success_hook_is_idempotent_for_one_transaction_ref(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-TWICE-'.random_int(10000, 99999);

        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);
        $paidCartGroupId = json_decode((string) $paymentRequest->additional_data, true)['cart_group_ids'][0];
        $refilledRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $refilledRow->forceFill(['cart_group_id' => $paidCartGroupId, 'is_checked' => 1])->save();

        wholesale_payment_success($paymentRequest->fresh());

        $this->assertSame(1, Order::query()->where('transaction_ref', $transactionId)->count());
        $this->assertTrue(Cart::query()->whereKey($refilledRow->id)->exists());
        Event::assertNotDispatched(DigitalPaymentOrderNotPlacedEvent::class);
    }

    public function test_a_cart_that_changed_after_payment_is_not_placed_and_raises_an_alert(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-CHANGED-'.random_int(10000, 99999);

        Cart::query()->whereKey($row->id)->update(['quantity' => 80]);
        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);

        $this->assertSame(0, Order::query()->where('transaction_ref', $transactionId)->count());
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        Event::assertDispatched(
            DigitalPaymentOrderNotPlacedEvent::class,
            static fn (DigitalPaymentOrderNotPlacedEvent $event): bool => $event->reason === CheckoutPaymentReason::AMOUNT_MISMATCH
                && $event->transactionRef === $transactionId
                && $event->customerId === (int) $customer->id,
        );
    }

    public function test_a_paid_cart_group_that_is_gone_is_not_placed_and_raises_an_alert(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $sellerRow = $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $transactionId = 'WSDP-GONE-'.random_int(10000, 99999);

        $this->assertCount(2, json_decode((string) $paymentRequest->additional_data, true)['cart_group_ids']);
        Cart::query()->whereKey($sellerRow->id)->delete();
        $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);

        $this->assertSame(0, Order::query()->where('transaction_ref', $transactionId)->count());
        Event::assertDispatched(
            DigitalPaymentOrderNotPlacedEvent::class,
            static fn (DigitalPaymentOrderNotPlacedEvent $event): bool => $event->reason === CheckoutPaymentReason::CART_CHANGED,
        );
    }

    public function test_the_not_placed_alert_is_logged_as_critical(): void
    {
        Log::spy();

        (new DigitalPaymentOrderNotPlacedListener)->handle(new DigitalPaymentOrderNotPlacedEvent(
            paymentRequestId: 'payment-id',
            transactionRef: 'WSDP-LOG',
            paymentMethod: 'stripe',
            customerId: 7,
            paidAmount: 12.5,
            currencyCode: 'USD',
            reason: CheckoutPaymentReason::AMOUNT_MISMATCH,
        ));

        Log::shouldHaveReceived('critical')->once()->withArgs(
            static fn (string $message, array $context): bool => $context['transaction_ref'] === 'WSDP-LOG' && $context['reason'] === CheckoutPaymentReason::AMOUNT_MISMATCH,
        );
    }

    public function test_success_hook_ignores_a_payload_that_is_not_wholesale(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $payload = json_decode((string) $paymentRequest->additional_data, true);
        $transactionId = 'WSDP-RETAIL-'.random_int(10000, 99999);

        foreach ([[...$payload, 'business_module' => BusinessModule::RETAIL], array_diff_key($payload, ['business_module' => true])] as $foreignPayload) {
            $paymentRequest->forceFill(['additional_data' => json_encode($foreignPayload)])->save();
            $this->payAndRunHook(paymentRequest: $paymentRequest, transactionId: $transactionId);
        }

        $unpaidRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $unpaidRequest->forceFill(['is_paid' => 0, 'transaction_id' => $transactionId])->save();
        wholesale_payment_success($unpaidRequest->fresh());

        $this->assertSame(0, Order::query()->where('transaction_ref', $transactionId)->count());
        $this->assertSame(0, Order::query()->where('customer_id', $customer->id)->where('business_module', BusinessModule::WHOLESALE)->where('created_at', '>=', now()->subMinute())->count());
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
    }

    public function test_retail_digital_payment_success_still_creates_a_retail_order(): void
    {
        $customer = $this->customer();
        $wholesaleRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $retailProduct = $this->retailProduct();
        $address = $this->createAddress(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $this->retailQuantityAboveMinimumOrderAmount(product: $retailProduct)])
            ->assertOk();

        $transactionId = 'WSDP-CORE-'.random_int(10000, 99999);
        $retailPaymentRequest = new PaymentRequest;
        $retailPaymentRequest->forceFill([
            'is_paid' => 1,
            'payment_method' => 'stripe',
            'transaction_id' => $transactionId,
            'additional_data' => json_encode([
                'customer_id' => $customer->id,
                'is_guest' => 0,
                'is_guest_in_order' => 0,
                'new_customer_id' => 0,
                'order_note' => null,
                'address_id' => $address->id,
                'billing_address_id' => $address->id,
                'coupon_code' => null,
                'payment_mode' => 'web',
            ]),
        ]);

        retail_payment_success($retailPaymentRequest);

        $orders = Order::query()->where('transaction_ref', $transactionId)->get();

        $this->assertNotEmpty($orders);
        $this->assertSame([BusinessModule::RETAIL], $orders->pluck('business_module')->unique()->values()->all());
        $this->assertTrue(OrderDetail::query()->whereIn('order_id', $orders->pluck('id'))->where('product_id', $retailProduct->id)->exists());
        $this->assertFalse(Cart::query()->where(['customer_id' => $customer->id, 'product_id' => $retailProduct->id])->exists());
        $this->assertFalse(OrderDetail::query()->whereIn('order_id', $orders->pluck('id'))->where('product_id', $wholesaleRow->product_id)->exists());
        $this->assertTrue(Cart::query()->whereKey($wholesaleRow->id)->exists());
    }

    public function test_retail_payment_success_places_a_guest_order_from_the_guest_cart(): void
    {
        $retailProduct = $this->retailProduct();

        $this->get(route('home'));
        $guestId = session('guest_id');
        $this->assertNotEmpty($guestId);

        $this->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $this->retailQuantityAboveMinimumOrderAmount(product: $retailProduct)])
            ->assertOk();

        $transactionId = 'RTDP-GUEST-'.random_int(10000, 99999);
        $paymentRequest = new PaymentRequest;
        $paymentRequest->forceFill([
            'is_paid' => 1,
            'payment_method' => 'stripe',
            'transaction_id' => $transactionId,
            'additional_data' => json_encode([
                'customer_id' => $guestId,
                'is_guest' => 1,
                'is_guest_in_order' => 1,
                'new_customer_id' => null,
                'order_note' => 'Guest paid order',
                'address_id' => 0,
                'billing_address_id' => 0,
                'coupon_code' => null,
                'payment_mode' => 'web',
            ]),
        ]);

        retail_payment_success($paymentRequest);

        $orders = Order::query()->where('transaction_ref', $transactionId)->get();

        $this->assertNotEmpty($orders);
        $this->assertSame([1], $orders->pluck('is_guest')->map(fn ($isGuest) => (int) $isGuest)->unique()->values()->all());
        $this->assertSame([(string) $guestId], $orders->pluck('customer_id')->map(fn ($customerId) => (string) $customerId)->unique()->values()->all());
        $this->assertSame(['paid'], $orders->pluck('payment_status')->unique()->values()->all());
        $this->assertSame(['Guest paid order'], $orders->pluck('order_note')->unique()->values()->all());
        $this->assertFalse(Cart::query()->where(['customer_id' => $guestId, 'is_guest' => 1, 'product_id' => $retailProduct->id])->exists());
    }

    public function test_retail_cash_on_delivery_places_only_the_retail_cart(): void
    {
        $customer = $this->customer();
        $wholesaleRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $retailProduct = $this->retailProduct();
        $address = $this->createAddress(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $this->retailQuantityAboveMinimumOrderAmount(product: $retailProduct)])
            ->assertOk();

        $this->actingAs($customer, 'customer')
            ->withSession(['address_id' => $address->id, 'billing_address_id' => $address->id, 'order_note' => 'Retail cash order'])
            ->post(route('checkout-complete'), ['payment_method' => 'cash_on_delivery'])
            ->assertRedirect(route('account-oder'));

        $orders = Order::query()->where(['customer_id' => $customer->id, 'order_note' => 'Retail cash order'])->get();

        $this->assertNotEmpty($orders);
        $this->assertSame([BusinessModule::RETAIL], $orders->pluck('business_module')->unique()->values()->all());
        $this->assertSame([0], $orders->pluck('is_guest')->map(fn ($isGuest) => (int) $isGuest)->unique()->values()->all());
        $this->assertFalse(OrderDetail::query()->whereIn('order_id', $orders->pluck('id'))->where('product_id', $wholesaleRow->product_id)->exists());
        $this->assertTrue(Cart::query()->whereKey($wholesaleRow->id)->exists());
    }

    public function test_failure_leaves_the_cart_and_writes_no_order(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));
        $orderCount = Order::query()->count();

        wholesale_payment_fail($paymentRequest->fresh());

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.checkout.payment.success').'?flag=fail&&token=')
            ->assertRedirect(route('wholesale.checkout.payment.failure'));

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.checkout.payment.failure'))
            ->assertRedirect(route('wholesale.orders.checkout.payment'));

        $this->assertSame($orderCount, Order::query()->count());
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertNotNull(session(CheckoutSessionKey::ADDRESS_ID));
        $this->openPaymentStep(customer: $customer);
    }

    public function test_multi_currency_amount_converts_like_retail(): void
    {
        $gateway = $this->gateway();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);
        $summary = $this->openPaymentStep(customer: $customer)->viewData('summary');

        $paymentRequest = $this->paymentRequestFrom(response: $this->startDigitalPayment(customer: $customer, gateway: $gateway));

        if (getWebConfig(name: 'currency_model') === self::MULTI_CURRENCY) {
            $this->assertEqualsWithDelta(usdToAnotherCurrencyConverter(currencyCode: $paymentRequest->currency_code, amount: $summary['total']), (float) $paymentRequest->payment_amount, 0.0001);

            return;
        }

        $this->assertSame(
            strtoupper((string) app(CurrencyService::class)->find(id: getWebConfig(name: 'system_default_currency'))?->code),
            $paymentRequest->currency_code,
        );
        $this->assertEqualsWithDelta($summary['total'], (float) $paymentRequest->payment_amount, 0.0001);
    }

    public function test_a_guest_cannot_start_a_wholesale_payment(): void
    {
        $gateway = $this->gateway();
        $paymentRequestCount = PaymentRequest::query()->count();

        $this->postJson(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway])
            ->assertUnauthorized()
            ->assertJsonPath('reason', 'login_required');

        $this->post(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway])
            ->assertRedirect(route('customer.auth.login'));

        $this->assertSame($paymentRequestCount, PaymentRequest::query()->count());
    }

    private function startDigitalPayment(User $customer, string $gateway): TestResponse
    {
        return $this->actingAs($customer, 'customer')
            ->post(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway]);
    }

    private function openPaymentStep(User $customer): TestResponse
    {
        return $this->actingAs($customer, 'customer')->get(route('wholesale.orders.checkout.payment'))->assertOk();
    }

    private function paymentRequestFrom(TestResponse $response): PaymentRequest
    {
        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('payment_id', $query);

        return PaymentRequest::query()->findOrFail($query['payment_id']);
    }

    private function payAndRunHook(PaymentRequest $paymentRequest, string $transactionId): void
    {
        $paymentRequest->forceFill(['is_paid' => 1, 'transaction_id' => $transactionId])->save();

        wholesale_payment_success($paymentRequest->fresh());
    }

    private function expectedGatewayAmount(float $total, string $currencyCode): float
    {
        return getWebConfig(name: 'currency_model') === self::MULTI_CURRENCY
            ? (float) usdToAnotherCurrencyConverter(currencyCode: $currencyCode, amount: $total)
            : $total;
    }

    private function gateway(): string
    {
        $digitalPayment = getWebConfig(name: 'digital_payment');

        if (! is_array($digitalPayment) || (int) ($digitalPayment['status'] ?? 0) !== 1) {
            $this->markTestSkipped('Test database has digital payment turned off.');
        }

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

    private function chooseAddresses(User $customer): ShippingAddress
    {
        $address = $this->createAddress(customer: $customer);

        foreach (['wholesale.checkout.address', 'wholesale.checkout.billing-address'] as $routeName) {
            $this->actingAs($customer, 'customer')
                ->postJson(route($routeName), ['address_id' => $address->id])
                ->assertOk();
        }

        return $address;
    }

    private function createAddress(User $customer): ShippingAddress
    {
        if (getWebConfig(name: 'delivery_country_restriction')) {
            $this->markTestSkipped('Test database restricts delivery countries.');
        }

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale digital payment fixture',
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

        return $this->cartService()->addWholesaleItem(
            product: $this->reload($product->id),
            quote: $quote,
            customerId: $customer->id,
            guestId: null,
            selection: $selection,
            shippingType: ShippingTypeService::TYPE_CATEGORY_WISE,
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

    /** @return array{0: Model, 1: string} */
    private function createVariationTierProduct(): array
    {
        $attributeId = $this->attributeId();

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

    private function createSellerTieredProduct(Shop $shop): Model
    {
        return $this->createProduct(
            overrides: [
                'minimum_order_qty' => 10,
                'current_stock' => 1000,
                'quantity_based_price' => 1,
                'tier_min_qty' => [10, 50],
                'tier_max_qty' => ['49', ''],
                'tier_unit_price' => [50, 40],
            ],
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        );
    }

    private function sellerShop(): Shop
    {
        return $this->requireFixture(
            Shop::query()->whereHas('seller', fn (Builder $query) => $query->where('status', 'approved'))->first(),
            'approved seller with a shop',
        );
    }

    private function createProduct(array $overrides, string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN, ?int $userId = null, int $shopId = 0): Model
    {
        $product = $this->productService()->create(
            addedBy: $addedBy,
            userId: $userId,
            shopId: $shopId,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale digital payment fixture'],
                'description' => ['Wholesale digital payment fixture'],
                'code' => 'WSDGP'.random_int(10000, 99999),
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

    private function retailQuantityAboveMinimumOrderAmount(Model $product): int
    {
        return max((int) $product->minimum_order_qty, (int) ceil(self::RETAIL_ORDER_AMOUNT / (float) $product->unit_price));
    }

    private function customer(): User
    {
        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        Cart::query()->where(['customer_id' => $customer->id])->delete();

        if (blank($customer->phone)) {
            $customer->forceFill(['phone' => '01700000000'])->save();
        }

        return $customer;
    }

    private function attributeId(): int
    {
        $ids = app(AttributeService::class)->getIds();

        if ($ids === []) {
            $this->markTestSkipped('Test database has no wholesale attributes.');
        }

        return (int) $ids[0];
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
