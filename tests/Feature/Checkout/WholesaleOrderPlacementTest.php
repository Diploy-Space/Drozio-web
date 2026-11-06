<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Events\OrderPlacedEvent;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Color;
use App\Models\OfflinePaymentMethod;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\OrderTransaction;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ShippingAddress;
use App\Models\ShippingType;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\New\AdminService;
use App\Services\New\BusinessSettingService;
use App\Services\New\ColorService;
use App\Services\New\CustomerService;
use App\Services\New\SettingService;
use App\Services\New\ShippingAddressService;
use App\Services\New\VendorService;
use App\Utils\Convert;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Modules\TaxModule\app\Models\OrderTax;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\Wholesale\app\Enums\CheckoutPaymentMethod;
use Modules\Wholesale\app\Enums\CheckoutSessionKey;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\CartService;
use Modules\Wholesale\app\Services\CouponService;
use Modules\Wholesale\app\Services\OrderPlacementService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\Shipping\ShippingTypeService;
use Modules\Wholesale\app\Services\ShopService;
use Modules\Wholesale\app\Utils\ProductViewSections;
use ReflectionMethod;
use ReflectionParameter;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOrderPlacementTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-placement-views';

    private const STUB_VIEW_CONTENTS = '{{ $summary["total_label"] }}';

    private const PLACED_VIEW_CONTENTS = '{{ $grandTotalLabel }}';

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

    public function test_cash_on_delivery_places_a_wholesale_order(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);

        $this->placeCashOnDelivery(customer: $customer)
            ->assertUnprocessable()
            ->assertJsonPath('redirect', route('wholesale.orders.checkout'));
        $this->assertNull(session(CheckoutSessionKey::ORDER_IDS));
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());

        $this->chooseAddresses(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('redirect', route('wholesale.orders.placed'));

        $orders = $this->placedOrders();

        $this->assertCount(1, $orders);
        $this->assertSame(BusinessModule::WHOLESALE, $orders[0]->business_module);
        $this->assertSame(OrderService::STATUS_PENDING, $orders[0]->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $orders[0]->payment_status);
        $this->assertSame(OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY, $orders[0]->payment_method);
        $this->assertSame((int) $customer->id, (int) $orders[0]->customer_id);
        $this->assertSame(1, OrderDetail::query()->where('order_id', $orders[0]->id)->count());
        Event::assertDispatched(OrderPlacedEvent::class);
    }

    public function test_order_amount_equals_the_checkout_total(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        [$variationProduct, $variationAxis] = $this->createVariationTierProduct();
        [$colorProduct, $colorAxis, $colorCode] = $this->createColorPricedProduct();

        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $colorProduct, customer: $customer, quantity: 12, selection: [$colorAxis => $colorCode]);
        $this->addRow(product: $variationProduct, customer: $customer, quantity: 15, selection: [$variationAxis => 'M']);
        $this->chooseAddresses(customer: $customer);

        $summary = $this->openPaymentStep(customer: $customer)->viewData('summary');

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $orders = $this->placedOrders();

        $this->assertEqualsWithDelta($summary['total'], (float) $orders->sum('order_amount'), 0.000001);
        $this->assertEqualsWithDelta($summary['tax'], (float) $orders->sum('total_tax_amount'), 0.000001);
        $this->assertSame(3, OrderDetail::query()->whereIn('order_id', $orders->pluck('id'))->count());
    }

    public function test_order_details_carry_the_wholesale_variant_and_labels(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        [$product, $axis] = $this->createVariationTierProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 15, selection: [$axis => 'M']);
        $stockBefore = (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => 'M'])->value('qty');
        $currentStockBefore = (int) Product::query()->whereKey($product->id)->value('current_stock');
        $this->chooseAddresses(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $detail = OrderDetail::query()->where('order_id', $this->placedOrders()[0]->id)->firstOrFail();
        $expectedLabels = ProductViewSections::formatSelectionLabels(
            selection: [$axis => 'M'],
            colorNames: app(ColorService::class)->getNameMap(),
            choiceOptions: json_decode((string) $product->choice_options, true) ?? [],
        );

        $this->assertGreaterThan(0, (float) $row->discount);
        $this->assertEquals(0, (float) Product::query()->whereKey($product->id)->value('discount'));
        $this->assertSame('M', $detail->variant);
        $this->assertSame(array_column($expectedLabels, 'value', 'title'), $detail->variation);
        $this->assertSame(15, $detail->qty);
        $this->assertEqualsWithDelta((float) $row->price, $detail->price, 0.000001);
        $this->assertEqualsWithDelta((float) $row->discount * 15, $detail->discount, 0.000001);
        $this->assertSame($stockBefore - 15, (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => 'M'])->value('qty'));
        $this->assertSame($currentStockBefore - 15, (int) Product::query()->whereKey($product->id)->value('current_stock'));
    }

    public function test_two_shops_place_two_orders_under_one_group(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);

        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)->assertOk()->assertJsonPath('status', 1);

        $orders = $this->placedOrders();

        $this->assertCount(2, $orders);
        $this->assertCount(1, $orders->pluck('order_group_id')->unique());
        $this->assertSame([BusinessModule::WHOLESALE], $orders->pluck('business_module')->unique()->values()->all());
    }

    public function test_in_house_and_seller_rows_place_with_the_right_seller_is(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);

        $adminRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $sellerRow = $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $orders = $this->placedOrders()->keyBy('seller_is');

        $this->assertSame((int) $adminRow->seller_id, (int) $orders[OrderService::SELLER_IS_ADMIN]->seller_id);
        $this->assertSame((int) $shop->seller_id, (int) $sellerRow->seller_id);
        $this->assertSame((int) $shop->seller_id, (int) $orders[OrderService::SELLER_IS_SELLER]->seller_id);
    }

    public function test_coupon_discount_reaches_the_order(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $coupon = $this->createCoupon(discount: 100, minimumPurchase: 2000);
        $this->chooseAddresses(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.cart.coupon.apply'), ['code' => $coupon->code])
            ->assertOk();

        $summary = $this->openPaymentStep(customer: $customer)->viewData('summary');
        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $order = $this->placedOrders()[0];

        $this->assertSame($coupon->code, $order->coupon_code);
        $this->assertEqualsWithDelta(100.0, (float) $order->discount_amount, 0.000001);
        $this->assertEqualsWithDelta($summary['total'], (float) $order->order_amount, 0.000001);
        $this->assertNull(session(CheckoutSessionKey::COUPON_CODE));
    }

    public function test_free_shipping_reaches_the_order(): void
    {
        if ((int) getWebConfig(name: 'free_delivery_status') !== 1) {
            $this->markTestSkipped('Test database has free delivery turned off, and getWebConfig() memoizes it for the process.');
        }

        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $vendorCart = $this->cartService()->getOrderGenerateCartList(
            customerId: $customer->id,
            sessionGuestId: null,
            requestGuestId: null,
            groupCouponDiscounts: [],
            groupReferralDiscounts: [],
            shippingAddressId: 0,
            billingAddressId: 0,
        )[0];

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $order = $this->placedOrders()[0];

        $this->assertSame((int) $vendorCart['is_shipping_free'], (int) $order->is_shipping_free);
        $this->assertEqualsWithDelta((float) $vendorCart['free_delivery_discount'], (float) $order->extra_discount, 0.000001);
    }

    public function test_tax_model_and_total_tax_match_the_checkout(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $this->makeWholesaleTaxModelDifferFromRetail();
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $paymentStep = $this->openPaymentStep(customer: $customer);
        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $order = $this->placedOrders()[0];
        $wholesaleTaxModel = $paymentStep->viewData('isTaxIncluded') ? 'include' : 'exclude';

        $this->assertNotSame(CartService::getTaxSystemType(businessModule: BusinessModule::RETAIL)['is_included'] ? 'include' : 'exclude', $wholesaleTaxModel);
        $this->assertSame($wholesaleTaxModel, $order->tax_model);
        $this->assertSame(
            CartService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE)['SystemTaxVatType'],
            $order->tax_type,
        );
        $this->assertSame(getWebConfig(name: 'wholesale_shipping_method'), $order->shipping_responsibility);
        $this->assertEqualsWithDelta($paymentStep->viewData('summary')['tax'], (float) $order->total_tax_amount, 0.000001);
        $this->assertSame($wholesaleTaxModel, OrderDetail::query()->where('order_id', $order->id)->value('tax_model'));
    }

    public function test_order_taxes_use_the_wholesale_tax_setup(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $taxConfig = CartService::getTaxSystemType(businessModule: BusinessModule::WHOLESALE);
        $wholesaleSetup = $taxConfig['SystemTaxVat'];

        if ($wholesaleSetup === null || $taxConfig['is_included'] || $taxConfig['SystemTaxVatType'] !== 'product_wise') {
            $this->markTestSkipped('Test database has no active, tax-excluded, product-wise wholesale tax setup.');
        }

        $retailSetupId = CartService::getTaxSystemType(businessModule: BusinessModule::RETAIL)['SystemTaxVat']?->id;
        $wholesaleTax = $this->requireFixture(
            Tax::query()->where(['business_module' => BusinessModule::WHOLESALE, 'is_active' => 1])->where('tax_rate', '>', 0)->first(),
            'active wholesale tax',
        );
        $shippingTaxIds = collect($wholesaleSetup->additionalData)->flatMap(static fn (Model $additional): array => (array) $additional->tax_ids)->map(static fn (mixed $id): int => (int) $id)->all();

        $customer = $this->customer();
        $product = $this->createTieredProduct();
        $product->taxVats()->create([
            'system_tax_setup_id' => $wholesaleSetup->id,
            'tax_id' => $wholesaleTax->id,
            'tax_for' => 'tax_for_orders',
        ]);
        $this->addRow(product: $product, customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $summary = $this->openPaymentStep(customer: $customer)->viewData('summary');
        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $order = $this->placedOrders()[0];
        $orderTaxes = OrderTax::query()->where('order_id', $order->id)->get();

        $this->assertGreaterThan(0.0, $summary['tax']);
        $this->assertEqualsWithDelta($summary['total'], (float) $order->order_amount, 0.000001);
        $this->assertNotEmpty($orderTaxes);
        $this->assertNotSame($retailSetupId, $wholesaleSetup->id);
        $this->assertSame([(int) $wholesaleSetup->id], $orderTaxes->pluck('system_tax_setup_id')->map(static fn (mixed $id): int => (int) $id)->unique()->values()->all());
        $this->assertTrue($orderTaxes->where('tax_on', 'basic')->contains('tax_id', $wholesaleTax->id));

        foreach ($shippingTaxIds as $shippingTaxId) {
            $this->assertTrue($orderTaxes->where('tax_on', '!=', 'basic')->contains('tax_id', $shippingTaxId));
        }
    }

    public function test_an_oversold_variant_rolls_the_placement_back(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        [$product, $axis] = $this->createVariationTierProduct();
        $row = $this->addRow(product: $product, customer: $customer, quantity: 15, selection: [$axis => 'M']);
        $this->chooseAddresses(customer: $customer);

        OrderStatusHistory::created(static function () use ($product): void {
            ProductStock::query()->where(['product_id' => $product->id, 'variant' => 'M'])->update(['qty' => 5]);
        });

        $this->placeCashOnDelivery(customer: $customer)
            ->assertUnprocessable()
            ->assertJsonPath('redirect', route('wholesale.shop-cart'))
            ->assertJsonPath('message', translate('The following items in your cart are currently out of stock'));

        $this->assertNull(session(CheckoutSessionKey::ORDER_IDS));
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertFalse(OrderDetail::query()->where('product_id', $product->id)->exists());
        $this->assertSame(200, (int) Product::query()->whereKey($product->id)->value('current_stock'));
    }

    public function test_wallet_payment_debits_and_marks_paid(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $customer = $this->customer();
        User::query()->whereKey($customer->id)->update(['wallet_balance' => 100000]);
        $customer = $customer->fresh();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $total = (float) $this->openPaymentStep(customer: $customer)->viewData('summary')['total'];

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.wallet'), ['payment_method' => CheckoutPaymentMethod::WALLET])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $order = $this->placedOrders()[0];
        $expectedDebit = Convert::usd(Convert::default($total));

        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->order_status);
        $this->assertSame(OrderService::PAYMENT_METHOD_WALLET, $order->payment_method);
        $this->assertEqualsWithDelta($expectedDebit, (float) WalletTransaction::query()->where('user_id', $customer->id)->latest('id')->value('debit'), 0.0001);
        $this->assertEqualsWithDelta(100000 - $expectedDebit, (float) User::query()->whereKey($customer->id)->value('wallet_balance'), 0.0001);
    }

    public function test_wallet_payment_is_refused_on_insufficient_balance(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $customer = $this->customer();
        User::query()->whereKey($customer->id)->update(['wallet_balance' => 1]);
        $customer = $customer->fresh();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.wallet'), ['payment_method' => CheckoutPaymentMethod::WALLET])
            ->assertUnprocessable()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', translate('Insufficient balance in your wallet to pay for this order'));

        $this->assertNull(session(CheckoutSessionKey::ORDER_IDS));
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());
        $this->assertEqualsWithDelta(1.0, (float) User::query()->whereKey($customer->id)->value('wallet_balance'), 0.0001);
    }

    public function test_offline_payment_records_the_method(): void
    {
        $this->requireSetting(name: 'offline_payment');
        $method = $this->requireFixture(OfflinePaymentMethod::query()->where('status', 1)->first(), 'active offline payment method');
        $customerInputs = array_column($method->method_informations ?? [], 'customer_input');
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.offline'), [
                'method_id' => $method->id,
                'payment_note' => 'Paid by bank transfer',
                'method_informations' => array_fill_keys($customerInputs, 'TRX-1'),
            ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $order = $this->placedOrders()[0];
        $storedPaymentInfo = $order->offlinePayments()->firstOrFail()->payment_info;
        $paymentInfo = is_array($storedPaymentInfo) ? $storedPaymentInfo : json_decode((string) $storedPaymentInfo, true);

        $this->assertSame(OrderService::PAYMENT_METHOD_OFFLINE, $order->payment_method);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->payment_status);
        $this->assertSame(OrderService::STATUS_PENDING, $order->order_status);
        $this->assertSame('Paid by bank transfer', $order->payment_note);
        $this->assertSame($method->id, (int) $paymentInfo['method_id']);
        $this->assertSame($method->method_name, $paymentInfo['method_name']);

        foreach ($customerInputs as $customerInput) {
            $this->assertSame('TRX-1', $paymentInfo[$customerInput]);
        }

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.offline'), ['method_id' => 99999999])
            ->assertUnprocessable();
    }

    public function test_cart_groups_are_cleared_and_only_wholesale_session_keys_are_forgotten(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $retailProduct = $this->retailProduct();

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $retailProduct->minimum_order_qty])
            ->assertOk();

        $this->chooseAddresses(customer: $customer);
        $retailSession = ['coupon_code' => 'RETAILCODE', 'address_id' => 987654, 'billing_address_id' => 987655, 'order_note' => 'retail note'];
        session($retailSession);
        session([CheckoutSessionKey::ORDER_NOTE => 'wholesale note']);

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $order = $this->placedOrders()[0];

        $this->assertSame('wholesale note', $order->order_note);
        $this->assertFalse(Cart::query()->whereKey($row->id)->exists());
        $this->assertTrue(Cart::query()->where(['customer_id' => $customer->id, 'product_id' => $retailProduct->id, 'business_module' => BusinessModule::RETAIL])->exists());

        foreach ($retailSession as $key => $value) {
            $this->assertSame($value, session($key));
        }

        foreach ([CheckoutSessionKey::ADDRESS_ID, CheckoutSessionKey::BILLING_ADDRESS_ID, CheckoutSessionKey::ORDER_NOTE] as $wholesaleKey) {
            $this->assertNull(session($wholesaleKey));
        }
    }

    public function test_a_concurrent_second_submit_places_one_order_group(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $row = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $lock = Cache::lock(
            app(OrderPlacementService::class)->getPlacementLockKey(customerId: $customer->id, cartGroupIds: [$row->cart_group_id]),
            OrderPlacementService::LOCK_SECONDS,
        );
        $this->assertTrue($lock->get());

        $this->placeCashOnDelivery(customer: $customer)
            ->assertUnprocessable()
            ->assertJsonPath('message', translate('Your order is already being placed'));
        $this->assertTrue(Cart::query()->whereKey($row->id)->exists());

        $lock->release();

        $this->placeCashOnDelivery(customer: $customer)->assertOk()->assertJsonPath('status', 1);
        $orderIds = session(CheckoutSessionKey::ORDER_IDS);

        $this->placeCashOnDelivery(customer: $customer)->assertUnprocessable()->assertJsonPath('status', 0);

        $this->assertSame(1, Order::query()->where('customer_id', $customer->id)->whereIn('order_group_id', Order::query()->whereIn('id', $orderIds)->pluck('order_group_id'))->distinct()->count('order_group_id'));
        $this->assertSame(1, Order::query()->where('customer_id', $customer->id)->where('created_at', '>=', now()->subMinute())->where('business_module', BusinessModule::WHOLESALE)->count());
    }

    public function test_a_guest_cannot_place_an_order(): void
    {
        $routes = [
            'wholesale.checkout.place.cash-on-delivery' => ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY],
            'wholesale.checkout.place.offline' => ['method_id' => 1],
            'wholesale.checkout.place.wallet' => ['payment_method' => CheckoutPaymentMethod::WALLET],
        ];

        foreach ($routes as $routeName => $body) {
            $this->postJson(route($routeName), $body)
                ->assertUnauthorized()
                ->assertJsonPath('reason', 'login_required');
        }

        $this->post(route('wholesale.checkout.place.cash-on-delivery'), $routes['wholesale.checkout.place.cash-on-delivery'])
            ->assertRedirect(route('customer.auth.login'));
        $this->get(route('wholesale.orders.placed'))->assertRedirect(route('customer.auth.login'));
    }

    public function test_retail_order_placement_is_unchanged(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $wholesaleRow = $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $retailProduct = $this->retailProduct();
        $address = $this->createAddress(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $retailProduct->id, 'quantity' => $this->retailQuantityAboveMinimumOrderAmount(product: $retailProduct)])
            ->assertOk();

        $response = $this->actingAs($customer, 'customer')
            ->withSession(['address_id' => $address->id, 'billing_address_id' => $address->id, CheckoutSessionKey::ADDRESS_ID => $address->id])
            ->postJson(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY], ['X-Requested-With' => 'XMLHttpRequest']);

        if ((int) $response->json('status') !== 1) {
            $this->markTestSkipped('Retail checkout is blocked on this fixture: '.$response->json('message'));
        }

        $retailOrder = Order::query()->whereIn('id', (array) session('order_success_ids'))->firstOrFail();

        $this->assertSame(BusinessModule::RETAIL, $retailOrder->business_module);
        $this->assertTrue(Cart::query()->whereKey($wholesaleRow->id)->exists());
        $this->assertSame($address->id, session(CheckoutSessionKey::ADDRESS_ID));
        $this->assertFalse(Cart::query()->where(['customer_id' => $customer->id, 'product_id' => $retailProduct->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    public function test_placed_page_renders_once_and_then_redirects(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->actingAs($customer, 'customer')
            ->post(route('wholesale.checkout.place.cash-on-delivery'), ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY])
            ->assertRedirect(route('wholesale.orders.placed'));

        $order = $this->placedOrders()[0];

        $response = $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.placed'))
            ->assertOk()
            ->assertViewHasAll(['orders', 'orderGroupId', 'grandTotal', 'grandTotalLabel', 'continueShoppingUrl', 'myOrdersUrl']);

        $this->assertSame($order->order_group_id, $response->viewData('orderGroupId'));
        $this->assertSame(
            ['id', 'shop_name', 'is_verified', 'total', 'total_label', 'payment_method_label', 'order_status_label', 'details_url'],
            array_keys($response->viewData('orders')[0]),
        );
        $this->assertSame((int) $order->id, $response->viewData('orders')[0]['id']);
        $this->assertEqualsWithDelta((float) $order->order_amount, $response->viewData('grandTotal'), 0.000001);
        $this->assertNull(session(CheckoutSessionKey::ORDER_IDS));

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.placed'))
            ->assertRedirect(route('wholesale.shop-cart'));
    }

    public function test_placement_service_holds_no_service(): void
    {
        $parameterTypes = array_map(
            static fn (ReflectionParameter $parameter): string => (string) $parameter->getType(),
            (new ReflectionMethod(OrderPlacementService::class, '__construct'))->getParameters(),
        );

        $this->assertSame([Order::class], $parameterTypes);
        $this->assertTrue(is_subclass_of(OrderPlacementService::class, OrderService::class));

        foreach ([CouponService::class, ShopService::class, ShippingAddressService::class, VendorService::class, AdminService::class, CustomerService::class, ColorService::class, BusinessSettingService::class] as $lookupService) {
            $this->assertNotContains($lookupService, $parameterTypes);
        }
    }

    public function test_placed_order_mails_come_from_the_order_service(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customer();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $this->chooseAddresses(customer: $customer);

        $this->placeCashOnDelivery(customer: $customer)->assertOk();

        $orders = $this->placedOrders()->load(['customer', 'seller.shop'])->keyBy('seller_is');
        $inHouseAdmin = app(AdminService::class)->find(id: CartService::IN_HOUSE_CART_SELLER_ID);
        $mailingOrderService = new class(new Order) extends OrderService
        {
            protected function isMailActive(): bool
            {
                return true;
            }
        };

        $inHouseMails = $mailingOrderService->getPlacedOrderMails(order: $orders[OrderService::SELLER_IS_ADMIN], inHouseAdmin: $inHouseAdmin);
        $vendorMails = $mailingOrderService->getPlacedOrderMails(order: $orders[OrderService::SELLER_IS_SELLER], inHouseAdmin: $inHouseAdmin);
        $seller = $orders[OrderService::SELLER_IS_SELLER]->seller;

        $this->assertSame([$customer->email, $inHouseAdmin->email], array_column($inHouseMails, 'email'));
        $this->assertSame(['order-place', 'order-received'], array_column(array_column($inHouseMails, 'data'), 'templateName'));
        $this->assertSame(['customer', 'admin'], array_column(array_column($inHouseMails, 'data'), 'userType'));
        $this->assertSame(getInHouseShopConfig(key: 'name'), $inHouseMails[0]['data']['shopName']);
        $this->assertSame($inHouseAdmin->name, $inHouseMails[1]['data']['adminName']);

        $this->assertSame([$customer->email, $seller->email, $inHouseAdmin->email], array_column($vendorMails, 'email'));
        $this->assertSame(['order-place', 'order-received', 'order-received'], array_column(array_column($vendorMails, 'data'), 'templateName'));
        $this->assertSame(['customer', 'vendor', 'admin'], array_column(array_column($vendorMails, 'data'), 'userType'));
        $this->assertSame($shop->name, $vendorMails[0]['data']['shopName']);
        $this->assertSame((int) $shop->id, (int) $vendorMails[0]['data']['shopId']);
        $this->assertSame($seller->f_name, $vendorMails[1]['data']['vendorName']);
        $this->assertSame($inHouseAdmin->name, $vendorMails[2]['data']['adminName']);
        $this->assertSame($seller->f_name, $vendorMails[2]['data']['vendorName']);
    }

    public function test_gateway_hook_places_the_same_order_as_before(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $this->requireSetting(name: 'digital_payment');
        $gateway = (string) $this->requireFixture(app(SettingService::class)->getActivePaymentGateways()->pluck('key_name')->first(), 'active payment gateway');
        $customer = $this->customer();
        User::query()->whereKey($customer->id)->update(['wallet_balance' => 1000000]);
        $customer = $customer->fresh();
        $shop = $this->sellerShop();
        $this->useWholesaleShippingType(sellerId: (int) $shop->seller_id, shippingType: ShippingTypeService::TYPE_CATEGORY_WISE);
        $this->addRow(product: $this->createTieredProduct(), customer: $customer, quantity: 60);
        $this->addRow(product: $this->createSellerTieredProduct(shop: $shop), customer: $customer, quantity: 60);
        $coupon = $this->createCoupon(discount: 100, minimumPurchase: 2000);
        $this->chooseAddresses(customer: $customer);
        $this->actingAs($customer, 'customer')->postJson(route('wholesale.cart.coupon.apply'), ['code' => $coupon->code])->assertOk();

        $paymentResponse = $this->actingAs($customer, 'customer')->post(route('wholesale.checkout.digital-payment'), ['payment_method' => $gateway])->assertRedirect();
        parse_str((string) parse_url((string) $paymentResponse->headers->get('Location'), PHP_URL_QUERY), $paymentQuery);
        $paymentRequest = PaymentRequest::query()->findOrFail($paymentQuery['payment_id']);
        $paymentRequest->forceFill(['is_paid' => 1, 'transaction_id' => 'WSHOOK-'.random_int(10000, 99999)])->save();

        DB::beginTransaction();
        Cache::flush();
        wholesale_payment_success($paymentRequest->fresh());
        $hookOrderIds = Order::query()->where('transaction_ref', $paymentRequest->transaction_id)->orderBy('id')->pluck('id')->all();
        $hookRows = $this->getPlacedOrderRows(orderIds: $hookOrderIds);
        DB::rollBack();
        Cache::flush();

        $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.wallet'), ['payment_method' => CheckoutPaymentMethod::WALLET])
            ->assertOk()
            ->assertJsonPath('status', 1);
        $walletRows = $this->getPlacedOrderRows(orderIds: $this->placedOrders()->pluck('id')->all());

        $this->assertCount(2, $hookRows['orders']);
        $this->assertCount(2, $hookRows['order_details']);
        $this->assertCount(2, $hookRows['order_transactions']);
        $this->assertSame([OrderService::SELLER_IS_ADMIN, OrderService::SELLER_IS_SELLER], array_column($hookRows['orders'], 'seller_is'));
        $this->assertSame($coupon->code, $hookRows['orders'][0]['coupon_code']);
        $this->assertNotNull($hookRows['order_transactions'][0]['shop_id']);
        $this->assertSame((int) $shop->id, (int) $hookRows['order_transactions'][1]['shop_id']);
        $this->assertSame($walletRows, $hookRows);
    }

    /**
     * @param  array<int, int>  $orderIds
     * @return array{orders: array<int, array<string, mixed>>, order_details: array<int, array<string, mixed>>, order_taxes: array<int, array<string, mixed>>, order_transactions: array<int, array<string, mixed>>}
     */
    private function getPlacedOrderRows(array $orderIds): array
    {
        $paymentColumns = ['id', 'order_id', 'order_details_id', 'order_group_id', 'verification_code', 'transaction_ref', 'transaction_id', 'payment_method', 'payment_status', 'order_status', 'created_at', 'updated_at'];
        $withoutPaymentColumns = static fn (Model $row): array => array_diff_key($row->getAttributes(), array_flip($paymentColumns));

        return [
            'orders' => Order::query()->whereIn('id', $orderIds)->orderBy('id')->get()->map($withoutPaymentColumns)->all(),
            'order_details' => OrderDetail::query()->whereIn('order_id', $orderIds)->orderBy('id')->get()->map($withoutPaymentColumns)->all(),
            'order_taxes' => OrderTax::query()->whereIn('order_id', $orderIds)->orderBy('id')->get()->map($withoutPaymentColumns)->all(),
            'order_transactions' => OrderTransaction::query()->whereIn('order_id', $orderIds)->orderBy('id')->get()->map($withoutPaymentColumns)->all(),
        ];
    }

    private function placeCashOnDelivery(User $customer): TestResponse
    {
        return $this->actingAs($customer, 'customer')
            ->postJson(route('wholesale.checkout.place.cash-on-delivery'), ['payment_method' => CheckoutPaymentMethod::CASH_ON_DELIVERY]);
    }

    private function openPaymentStep(User $customer): TestResponse
    {
        return $this->actingAs($customer, 'customer')->get(route('wholesale.orders.checkout.payment'))->assertOk();
    }

    /** @return Collection<int, Order> */
    private function placedOrders(): Collection
    {
        $orderIds = session(CheckoutSessionKey::ORDER_IDS);

        $this->assertIsArray($orderIds);

        return Order::query()->whereIn('id', $orderIds)->orderBy('id')->get();
    }

    private function makeWholesaleTaxModelDifferFromRetail(): void
    {
        $isRetailTaxIncluded = (bool) CartService::getTaxSystemType(businessModule: BusinessModule::RETAIL)['is_included'];

        $updatedRows = SystemTaxSetup::query()
            ->where(['business_module' => BusinessModule::WHOLESALE, 'tax_payer' => 'vendor', 'is_default' => 1, 'is_active' => 1])
            ->update(['is_included' => $isRetailTaxIncluded ? 0 : 1]);

        if ($updatedRows === 0) {
            $this->markTestSkipped('Test database has no active default wholesale tax setup.');
        }

        Cache::flush();
    }

    private function requireSetting(string $name): void
    {
        $setting = getWebConfig(name: $name);
        $isActive = is_array($setting) ? (int) ($setting['status'] ?? 0) === 1 : (bool) $setting;

        if (! $isActive) {
            $this->markTestSkipped('Test database has '.$name.' turned off.');
        }
    }

    private function useWholesaleShippingType(int $sellerId, string $shippingType): void
    {
        ShippingType::query()->where(['seller_id' => $sellerId, 'context' => BusinessModule::WHOLESALE])->delete();
        ShippingType::query()->create(['seller_id' => $sellerId, 'shipping_type' => $shippingType, 'context' => BusinessModule::WHOLESALE]);
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
        if (getWebConfig(name: 'delivery_country_restriction')) {
            $this->markTestSkipped('Test database restricts delivery countries.');
        }

        return ShippingAddress::query()->create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'contact_person_name' => 'Wholesale placement fixture',
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

    private function createCoupon(float $discount, float $minimumPurchase): Model
    {
        return app(CouponService::class)->create(data: [
            'coupon_type' => CouponService::TYPE_DISCOUNT_ON_PURCHASE,
            'title' => 'Wholesale placement fixture',
            'code' => 'WSPLACE'.random_int(10000, 99999),
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

    private function createSellerTieredProduct(Shop $shop): Model
    {
        return $this->createProduct(
            overrides: $this->tieredOverrides(),
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: (int) $shop->seller_id,
            shopId: (int) $shop->id,
        );
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

    /** @return array{0: Model, 1: string, 2: string} */
    private function createColorPricedProduct(): array
    {
        $colorCode = (string) $this->requireFixture(Color::query()->value('code'), 'colour');

        $product = $this->createProduct(overrides: [
            'minimum_order_qty' => 1,
            'current_stock' => 200,
            'colors_active' => 1,
            'colors' => [$colorCode],
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [25],
        ], colorNames: app(ColorService::class)->getNameMap());

        Product::query()->whereKey($product->id)->update(['current_stock' => 200]);
        $product->stocks()->update(['qty' => 200]);

        return [$this->reload($product->id), ProductWholesaleService::AXIS_COLOR, $colorCode];
    }

    private function createProduct(
        array $overrides,
        string $addedBy = ProductWholesaleService::ADDED_BY_ADMIN,
        ?int $userId = null,
        int $shopId = 0,
        array $colorNames = [],
    ): Model {
        $product = $this->productService()->create(
            addedBy: $addedBy,
            userId: $userId,
            shopId: $shopId,
            data: [
                'lang' => ['en'],
                'name' => ['Wholesale placement fixture'],
                'description' => ['Wholesale placement fixture'],
                'code' => 'WSPLC'.random_int(10000, 99999),
                'category_id' => $this->categoryId(),
                'unit' => 'pc',
                'shipping_cost' => 0,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
                ...$overrides,
            ],
            colorNames: $colorNames,
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
