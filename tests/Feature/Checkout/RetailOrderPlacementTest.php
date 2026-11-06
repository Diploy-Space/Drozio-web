<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Events\CheckoutCartStateRequestedEvent;
use App\Events\CheckoutOrdersPlacingEvent;
use App\Events\OrderPlacedEvent;
use App\Http\Controllers\RestAPI\v6\Customer\OrderPlacementController as ApiOrderPlacementController;
use App\Http\Controllers\Web\v6\OrderPlacementController as WebOrderPlacementController;
use App\Listeners\CheckoutCartStateListener;
use App\Listeners\CheckoutOrderPlacementListener;
use App\Models\Cart;
use App\Models\OfflinePaymentMethod;
use App\Models\OfflinePayments;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\OrderStatusHistory;
use App\Models\OrderTransaction;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\New\CartService;
use App\Services\New\OrderPlacementService;
use App\Services\New\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\TaxModule\app\Models\OrderTax;
use ReflectionClass;
use ReflectionParameter;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailOrderPlacementTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const ORDER_SUBTOTAL = 1000;

    private const ORDER_NOTE = 'Retail placement characterization';

    private const API_CASH_ON_DELIVERY_URI = '/api/v6/customer/orders/cash-on-delivery';

    private const VOLATILE_ORDER_COLUMNS = ['id', 'order_group_id', 'verification_code', 'created_at', 'updated_at'];

    private const VOLATILE_LINE_COLUMNS = ['id', 'order_id', 'order_details_id', 'created_at', 'updated_at'];

    private const SELLER_SECRET_KEYS = ['password', 'auth_token', 'remember_token', 'cm_firebase_token', 'bank_name', 'account_no', 'holder_name'];

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([OrderPlacedEvent::class]);
    }

    public function test_cash_on_delivery_writes_every_placement_row(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customerWithRetailCart();

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY])
            ->assertRedirect(route('account-oder'));

        $orders = $this->placedOrders(customer: $customer);

        $this->assertCount(1, $orders);
        $order = $orders->first();

        $this->assertSame(BusinessModule::RETAIL, $order->business_module);
        $this->assertSame(OrderService::STATUS_PENDING, $order->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->payment_status);
        $this->assertSame(OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY, $order->payment_method);
        $this->assertSame(0, (int) $order->is_guest);
        $this->assertSame((int) $customer->id, (int) $order->customer_id);
        $this->assertEqualsWithDelta(self::ORDER_SUBTOTAL, (float) $order->order_amount - (float) $order->shipping_cost - (float) $order->total_tax_amount, 0.01);

        $this->assertSame(1, OrderDetail::query()->where('order_id', $order->id)->count());
        $this->assertSame(1, OrderStatusHistory::query()->where(['order_id' => $order->id, 'status' => OrderService::STATUS_PENDING])->count());
        $this->assertSame(1, OrderDetailsRewards::query()->where('order_id', $order->id)->count());
        $this->assertTrue(OrderTax::query()->where('order_id', $order->id)->exists());

        $this->assertSame(0, OrderTransaction::query()->where('order_id', $order->id)->count(), 'Cash on delivery is settled on delivery, never at placement.');
        $this->assertFalse(Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    public function test_cash_on_delivery_deducts_the_ordered_stock(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customerWithRetailCart();
        $product = $this->retailProduct();
        $stockBefore = (int) $product->current_stock;
        $quantity = $this->orderQuantity(product: $product);

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY])
            ->assertRedirect(route('account-oder'));

        $this->assertSame($stockBefore - $quantity, (int) Product::query()->whereKey($product->id)->value('current_stock'));
    }

    public function test_offline_payment_records_the_payment_information(): void
    {
        $this->requireSetting(name: 'offline_payment');
        $method = OfflinePaymentMethod::query()->where('status', 1)->first();

        if ($method === null) {
            $this->markTestSkipped('Test database has no active offline payment method.');
        }

        $customer = $this->customerWithRetailCart();

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('offline-payment-checkout-complete'), [
                'payment_method' => OrderService::PAYMENT_METHOD_OFFLINE,
                'method_id' => $method->id,
                'payment_note' => 'Retail offline characterization',
            ]);

        $order = $this->placedOrders(customer: $customer)->first();

        $this->assertNotNull($order);
        $this->assertSame(OrderService::PAYMENT_METHOD_OFFLINE, $order->payment_method);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->payment_status);
        $this->assertSame(OrderService::STATUS_PENDING, $order->order_status);
        $this->assertSame('Retail offline characterization', $order->payment_note);

        $offlinePayment = OfflinePayments::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($offlinePayment);
        $paymentInfo = is_array($offlinePayment->payment_info) ? $offlinePayment->payment_info : (array) json_decode((string) $offlinePayment->payment_info, true);

        $this->assertSame((int) $method->id, (int) ($paymentInfo['method_id'] ?? 0));
        $this->assertSame(0, OrderTransaction::query()->where('order_id', $order->id)->count());
    }

    public function test_wallet_payment_confirms_the_order_and_debits_the_wallet(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $customer = $this->customerWithRetailCart();
        $customer->forceFill(['wallet_balance' => 100000])->save();
        $balanceBefore = (float) $customer->wallet_balance;

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('checkout-complete-wallet'), ['payment_method' => OrderService::PAYMENT_METHOD_WALLET]);

        $order = $this->placedOrders(customer: $customer)->first();

        $this->assertNotNull($order);
        $this->assertSame(OrderService::PAYMENT_METHOD_WALLET, $order->payment_method);
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->order_status);

        $this->assertSame(1, OrderStatusHistory::query()->where(['order_id' => $order->id, 'status' => OrderService::STATUS_CONFIRMED])->count());
        $this->assertSame(1, OrderTransaction::query()->where('order_id', $order->id)->count(), 'A paid order records its transaction at placement.');

        $debit = WalletTransaction::query()
            ->where(['user_id' => $customer->id, 'transaction_type' => 'order_place'])
            ->latest('id')
            ->first();

        $this->assertNotNull($debit);
        $this->assertGreaterThan(0, (float) $debit->debit);
        $this->assertLessThan($balanceBefore, (float) $customer->fresh()->wallet_balance);
    }

    public function test_the_placement_transaction_records_the_coupon_discount(): void
    {
        $this->requireSetting(name: 'wallet_status');
        $customer = $this->customerWithRetailCart();
        $customer->forceFill(['wallet_balance' => 100000])->save();

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('checkout-complete-wallet'), ['payment_method' => OrderService::PAYMENT_METHOD_WALLET]);

        $order = $this->placedOrders(customer: $customer)->first();
        $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($transaction);

        $lineTotal = (float) OrderDetail::query()->where('order_id', $order->id)->sum(DB::raw('price * qty'));
        $lineDiscount = (float) OrderDetail::query()->where('order_id', $order->id)->sum('discount');
        $expected = $lineTotal + (float) $order->total_tax_amount - $lineDiscount - (float) $order->discount_amount;

        $this->assertEqualsWithDelta($expected, (float) $transaction->order_amount, 0.01);
        $this->assertEqualsWithDelta((float) $transaction->order_amount - (float) $transaction->admin_commission, (float) $transaction->seller_amount, 0.01);
    }

    public function test_a_stock_shortfall_places_nothing(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');
        $customer = $this->customerWithRetailCart();
        $product = $this->retailProduct();

        Product::query()->whereKey($product->id)->update(['current_stock' => 1]);

        $orderCountBefore = Order::query()->where('customer_id', $customer->id)->count();

        $this->actingAs($customer, 'customer')
            ->withSession($this->checkoutSession(customer: $customer))
            ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY])
            ->assertRedirect(route('shop-cart'));

        $this->assertSame($orderCountBefore, Order::query()->where('customer_id', $customer->id)->count());
        $this->assertSame(1, (int) Product::query()->whereKey($product->id)->value('current_stock'));
        $this->assertTrue(Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    public function test_web_and_api_cash_on_delivery_write_identical_rows(): void
    {
        $this->requireSetting(name: 'cash_on_delivery');

        $customer = $this->customer();
        $address = $this->addressFor(customer: $customer);

        $web = $this->captureRolledBackPlacement(customer: $customer, place: function () use ($customer, $address): void {
            $this->actingAs($customer, 'customer')
                ->withSession([
                    'address_id' => $address->id,
                    'billing_address_id' => $address->id,
                    'order_note' => self::ORDER_NOTE,
                ])
                ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY]);
        });

        $api = $this->captureRolledBackPlacement(customer: $customer, place: function () use ($customer, $address): void {
            $this->withHeaders($this->actingAsCustomer($customer))
                ->postJson(self::API_CASH_ON_DELIVERY_URI, [
                    'address_id' => $address->id,
                    'billing_address_id' => $address->id,
                    'order_note' => self::ORDER_NOTE,
                ]);
        });

        $this->assertNotSame([], $web['orders'], 'The web placement wrote no order, so the comparison proves nothing.');
        $this->assertNoSellerSecrets(details: $web['details']);
        $this->assertNoSellerSecrets(details: $api['details']);
        $this->assertSame($web['orders'], $api['orders']);
        $this->assertSame($web['details'], $api['details']);
        $this->assertSame($web['rewards'], $api['rewards']);
        $this->assertSame($web['taxes'], $api['taxes']);
    }

    public function test_a_replayed_payment_success_places_the_order_only_once(): void
    {
        $customer = $this->customerWithRetailCart();
        $transactionRef = 'RTDP-REPLAY-'.random_int(10000, 99999);
        $paymentRequest = $this->paidPaymentRequest(customer: $customer, transactionRef: $transactionRef);

        retail_payment_success($paymentRequest);
        $placedOrderIds = $this->orderIdsFor(transactionRef: $transactionRef);

        $this->assertNotEmpty($placedOrderIds, 'The first payment placed no order, so the replay proves nothing.');

        $this->seedRetailCart(customer: $customer);

        retail_payment_success($paymentRequest);

        $this->assertSame($placedOrderIds, $this->orderIdsFor(transactionRef: $transactionRef));
    }

    public function test_a_cart_that_changed_after_payment_places_nothing(): void
    {
        $customer = $this->customerWithRetailCart();
        $transactionRef = 'RTDP-CHANGED-'.random_int(10000, 99999);

        retail_payment_success($this->paidPaymentRequest(
            customer: $customer,
            transactionRef: $transactionRef,
            quote: [OrderService::PAYMENT_PAYLOAD_CART_GROUP_IDS => ['a-cart-group-that-is-gone']],
        ));

        $this->assertSame([], $this->orderIdsFor(transactionRef: $transactionRef));
        $this->assertTrue(Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    public function test_payable_amount_deducts_the_referral_discount(): void
    {
        $vendorCarts = [
            ['order_amount_with_tax' => 1020, 'refer_and_earn_discount' => 100],
            ['order_amount_with_tax' => 500, 'refer_and_earn_discount' => 50],
        ];

        $this->assertSame(1370.0, app(CartService::class)->getPayableAmount(vendorWiseCartList: $vendorCarts));
        $this->assertSame(1370.0, app(CartService::class)->getPayableAmount(vendorWiseCartList: collect($vendorCarts)));
    }

    public function test_a_payment_amount_that_no_longer_matches_the_cart_places_nothing(): void
    {
        $customer = $this->customerWithRetailCart();
        $transactionRef = 'RTDP-AMOUNT-'.random_int(10000, 99999);

        retail_payment_success($this->paidPaymentRequest(
            customer: $customer,
            transactionRef: $transactionRef,
            quote: [OrderService::PAYMENT_PAYLOAD_ORDER_AMOUNT => 1.0],
        ));

        $this->assertSame([], $this->orderIdsFor(transactionRef: $transactionRef));
        $this->assertTrue(Cart::query()->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])->exists());
    }

    public function test_an_unchecked_cart_item_does_not_inflate_the_admin_commission(): void
    {
        $customer = $this->customer();
        $sibling = $this->siblingRetailProduct(product: $this->retailProduct());

        $checkedOnly = $this->captureRolledBackPlacement(customer: $customer, place: function () use ($customer): void {
            $this->actingAs($customer, 'customer')
                ->withSession($this->checkoutSession(customer: $customer))
                ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY]);
        });

        $withUnchecked = $this->captureRolledBackPlacement(customer: $customer, place: function () use ($customer, $sibling): void {
            $this->addUncheckedCartItem(customer: $customer, product: $sibling);

            $this->actingAs($customer, 'customer')
                ->withSession($this->checkoutSession(customer: $customer))
                ->post(route('checkout-complete'), ['payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY]);
        });

        $commission = (float) ($checkedOnly['orders'][0]['admin_commission'] ?? 0);

        if ($commission <= 0) {
            $this->markTestSkipped('The fixture seller earns no admin commission, so the comparison proves nothing.');
        }

        $this->assertSame($commission, (float) ($withUnchecked['orders'][0]['admin_commission'] ?? 0));
        $this->assertSame($checkedOnly['orders'][0]['order_amount'], $withUnchecked['orders'][0]['order_amount']);
    }

    private function addUncheckedCartItem(User $customer, Model $product): void
    {
        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $product->id, 'quantity' => (int) $product->minimum_order_qty]);

        $uncheckedRows = Cart::query()
            ->where(['customer_id' => $customer->id, 'product_id' => $product->id, 'business_module' => BusinessModule::RETAIL])
            ->update(['is_checked' => 0]);

        if ($uncheckedRows !== 1) {
            $this->markTestSkipped('The second retail product could not be added to the cart on this fixture.');
        }

        $cartGroupIds = Cart::query()
            ->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])
            ->distinct()
            ->pluck('cart_group_id');

        $this->assertCount(1, $cartGroupIds, 'The unchecked item must share the checked item\'s cart group to exercise the commission.');
    }

    private function siblingRetailProduct(Model $product): Model
    {
        $sibling = Product::query()
            ->where('business_module', BusinessModule::RETAIL)
            ->active()
            ->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)
            ->where(['user_id' => $product->user_id, 'added_by' => $product->added_by])
            ->whereKeyNot($product->id)
            ->where(fn (Builder $query) => $query->whereNull('colors')->orWhere('colors', '[]'))
            ->where(fn (Builder $query) => $query->whereNull('choice_options')->orWhere('choice_options', '[]'))
            ->whereColumn('current_stock', '>=', 'minimum_order_qty')
            ->where('minimum_order_qty', '>', 0)
            ->where('unit_price', '>', 0)
            ->orderBy('id')
            ->first();

        if ($sibling === null) {
            $this->markTestSkipped('The fixture seller has no second retail product to leave unchecked.');
        }

        return $sibling;
    }

    /** @return array<int, int> */
    private function orderIdsFor(string $transactionRef): array
    {
        return Order::query()->where('transaction_ref', $transactionRef)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /** @param  array<string, mixed>  $quote */
    private function paidPaymentRequest(User $customer, string $transactionRef, array $quote = []): PaymentRequest
    {
        $address = $this->addressFor(customer: $customer);

        $paymentRequest = new PaymentRequest;
        $paymentRequest->forceFill([
            'is_paid' => 1,
            'payment_method' => 'stripe',
            'transaction_id' => $transactionRef,
            'additional_data' => json_encode([
                'customer_id' => $customer->id,
                'is_guest' => 0,
                'is_guest_in_order' => 0,
                'new_customer_id' => 0,
                'order_note' => self::ORDER_NOTE,
                'address_id' => $address->id,
                'billing_address_id' => $address->id,
                'coupon_code' => null,
                'payment_mode' => 'web',
                ...$quote,
            ]),
        ]);

        return $paymentRequest;
    }

    /** @param  array<int, array<string, mixed>>  $details */
    private function assertNoSellerSecrets(array $details): void
    {
        foreach ($details as $detail) {
            foreach (self::SELLER_SECRET_KEYS as $secretKey) {
                $this->assertStringNotContainsString('"'.$secretKey.'":', (string) ($detail['product_details'] ?? ''), 'A placed order line stored the vendor secret '.$secretKey.'.');
            }
        }
    }

    /**
     * @param  callable(): void  $place
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function captureRolledBackPlacement(User $customer, callable $place): array
    {
        DB::beginTransaction();

        try {
            $this->seedRetailCart(customer: $customer);
            $place();

            return $this->snapshotPlacement(customer: $customer);
        } finally {
            DB::rollBack();
        }
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function snapshotPlacement(User $customer): array
    {
        $orders = $this->placedOrders(customer: $customer);
        $orderIds = $orders->pluck('id')->all();

        return [
            'orders' => $orders->map(fn (Model $order): array => $this->withoutVolatileColumns(row: $order->getAttributes(), columns: self::VOLATILE_ORDER_COLUMNS))->values()->all(),
            'details' => $this->normalizedLines(query: OrderDetail::query()->whereIn('order_id', $orderIds)),
            'rewards' => $this->normalizedLines(query: OrderDetailsRewards::query()->whereIn('order_id', $orderIds)),
            'taxes' => $this->normalizedLines(query: OrderTax::query()->whereIn('order_id', $orderIds)),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizedLines(Builder $query): array
    {
        return $query->orderBy('id')->get()
            ->map(function (Model $row): array {
                $attributes = $this->withoutVolatileColumns(row: $row->getAttributes(), columns: self::VOLATILE_LINE_COLUMNS);

                if (isset($attributes['product_details'])) {
                    $attributes['product_details'] = $this->comparableProductSnapshot(snapshot: (string) $attributes['product_details']);
                }

                return $attributes;
            })
            ->values()
            ->all();
    }

    private function comparableProductSnapshot(string $snapshot): string
    {
        $decoded = json_decode($snapshot, true);

        if (! is_array($decoded)) {
            return $snapshot;
        }

        unset($decoded['seller']);

        return (string) json_encode($decoded);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $columns
     * @return array<string, mixed>
     */
    private function withoutVolatileColumns(array $row, array $columns): array
    {
        return array_map(
            static fn (mixed $value): mixed => is_scalar($value) || $value === null ? $value : (string) $value,
            array_diff_key($row, array_flip($columns)),
        );
    }

    private function placedOrders(User $customer)
    {
        return Order::query()
            ->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('id')
            ->get();
    }

    private function customerWithRetailCart(): User
    {
        $customer = $this->customer();

        $this->seedRetailCart(customer: $customer);

        return $customer;
    }

    private function seedRetailCart(User $customer): void
    {
        $product = $this->retailProduct();

        $this->actingAs($customer, 'customer')
            ->postJson(route('cart.add'), ['id' => $product->id, 'quantity' => $this->orderQuantity(product: $product)])
            ->assertOk();

        $cartGroupId = Cart::query()
            ->where(['customer_id' => $customer->id, 'business_module' => BusinessModule::RETAIL])
            ->value('cart_group_id');

        if ($cartGroupId === null) {
            $this->markTestSkipped('The retail product could not be added to the cart on this fixture.');
        }

        $this->selectShippingMethod(customer: $customer, product: $product, cartGroupId: (string) $cartGroupId);
    }

    private function selectShippingMethod(User $customer, Model $product, string $cartGroupId): void
    {
        $isOrderWise = ShippingType::query()
            ->where(['seller_id' => (int) $product->user_id, 'context' => BusinessModule::RETAIL])
            ->value('shipping_type') === 'order_wise';

        if (! $isOrderWise) {
            return;
        }

        $method = ShippingMethod::query()
            ->where(['creator_id' => (int) $product->user_id, 'creator_type' => 'seller', 'status' => 1, 'context' => BusinessModule::RETAIL])
            ->first();

        if ($method === null) {
            $this->markTestSkipped('The retail seller has no active order-wise shipping method on this fixture.');
        }

        $this->actingAs($customer, 'customer')
            ->getJson(route('customer.set-shipping-method', ['cart_group_id' => $cartGroupId, 'id' => $method->id]))
            ->assertOk();
    }

    /** @return array<string, mixed> */
    private function checkoutSession(User $customer): array
    {
        $address = $this->addressFor(customer: $customer);

        return [
            'address_id' => $address->id,
            'billing_address_id' => $address->id,
            'order_note' => self::ORDER_NOTE,
        ];
    }

    private function addressFor(User $customer): ShippingAddress
    {
        if (getWebConfig(name: 'delivery_country_restriction') || getWebConfig(name: 'delivery_zip_code_area_restriction')) {
            $this->markTestSkipped('Test database restricts delivery areas.');
        }

        return ShippingAddress::query()->firstOrCreate(
            ['customer_id' => $customer->id, 'is_guest' => 0, 'contact_person_name' => 'Retail placement fixture'],
            [
                'address_type' => 'home',
                'address' => 'Retail road 1',
                'city' => 'Dhaka',
                'zip' => '1200',
                'country' => 'Bangladesh',
                'phone' => '01700000000',
                'latitude' => '23.8',
                'longitude' => '90.4',
                'is_billing' => 0,
            ],
        );
    }

    private function customer(): User
    {
        $customer = User::query()->where('id', '>', 0)->where('is_active', 1)->first();

        if ($customer === null) {
            $this->markTestSkipped('Test database has no active customer.');
        }

        Cart::query()->where('customer_id', $customer->id)->delete();

        if (blank($customer->phone)) {
            $customer->forceFill(['phone' => '01700000000'])->save();
        }

        return $customer;
    }

    private function retailProduct(): Model
    {
        $product = Product::query()
            ->where('business_module', BusinessModule::RETAIL)
            ->active()
            ->where('product_type', CartService::PRODUCT_TYPE_PHYSICAL)
            ->where(fn (Builder $query) => $query->whereNull('colors')->orWhere('colors', '[]'))
            ->where(fn (Builder $query) => $query->whereNull('choice_options')->orWhere('choice_options', '[]'))
            ->whereColumn('current_stock', '>=', 'minimum_order_qty')
            ->where('minimum_order_qty', '>', 0)
            ->where('unit_price', '>', 0)
            ->whereRaw('current_stock * unit_price >= ?', [self::ORDER_SUBTOTAL * 2])
            ->orderBy('id')
            ->first();

        if ($product === null) {
            $this->markTestSkipped('Test database has no active retail physical product without variations.');
        }

        return $product;
    }

    public function test_no_retail_placement_service_injects_another_service(): void
    {
        $offenders = [];

        foreach ([OrderPlacementService::class, OrderService::class] as $class) {
            $constructor = (new ReflectionClass($class))->getConstructor();

            if ($constructor === null || $constructor->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($constructor->getParameters() as $parameter) {
                if (str_ends_with((string) $parameter->getType(), 'Service')) {
                    $offenders[] = $class.'::$'.$parameter->getName();
                }
            }
        }

        $this->assertSame([], $offenders);
        $this->assertSame(OrderService::class, (new ReflectionClass(OrderPlacementService::class))->getConstructor()?->getDeclaringClass()->getName());
    }

    public function test_the_checkout_controllers_place_orders_through_the_placement_event(): void
    {
        foreach ([WebOrderPlacementController::class, ApiOrderPlacementController::class] as $class) {
            $this->assertNotContains(OrderPlacementService::class, $this->getConstructorTypes(class: $class), $class);
        }

        $this->assertCount(1, Event::getListeners(CheckoutOrdersPlacingEvent::class));
        $this->assertCount(1, Event::getListeners(CheckoutCartStateRequestedEvent::class));
        $this->assertNotNull((new ReflectionClass(CheckoutOrderPlacementListener::class))->getConstructor());
        $this->assertNotNull((new ReflectionClass(CheckoutCartStateListener::class))->getConstructor());
    }

    /** @return array<int, string> */
    private function getConstructorTypes(string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        return $constructor === null
            ? []
            : array_map(static fn (ReflectionParameter $parameter): string => (string) $parameter->getType(), $constructor->getParameters());
    }

    private function orderQuantity(Model $product): int
    {
        return max((int) $product->minimum_order_qty, (int) ceil(self::ORDER_SUBTOTAL / (float) $product->unit_price));
    }

    private function requireSetting(string $name): void
    {
        $setting = getWebConfig(name: $name);
        $isActive = is_array($setting) ? (int) ($setting['status'] ?? 0) === 1 : (bool) $setting;

        if (! $isActive) {
            $this->markTestSkipped('Test database has '.$name.' turned off.');
        }
    }
}
