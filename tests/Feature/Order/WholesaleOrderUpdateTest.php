<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Models\Admin;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Seller;
use App\Models\User;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderSettlementService;
use App\Utils\Helpers;
use ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Enums\OrderBlocker;
use Modules\Wholesale\app\Http\Requests\Shared\Order\AddressUpdateRequest;
use Modules\Wholesale\app\Http\Requests\Shared\Order\DeliveryManAssignRequest;
use Modules\Wholesale\app\Http\Requests\Shared\Order\DeliveryScheduleUpdateRequest;
use Modules\Wholesale\app\Http\Requests\Shared\Order\PaymentStatusUpdateRequest;
use Modules\Wholesale\app\Http\Requests\Shared\Order\StatusUpdateRequest;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderStatusService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionNamedType;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOrderUpdateTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private const ADMIN_ACTOR_ID = 0;

    private const CURRENT_STOCK = 50;

    private const VARIANT = 'Blue-Small';

    private const VARIANT_STOCK = 30;

    private const LINE_QUANTITY = 4;

    private const MAX_COLLABORATORS = 6;

    private const SELLERWISE_SHIPPING = 'sellerwise_shipping';

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_cancel_restores_stock_and_writes_history(): void
    {
        $product = $this->createStockedProduct();
        $order = $this->createOrder(seller: $this->approvedSeller());
        $detail = $this->createDetail(order: $order, product: $product, isStockDecreased: 1);

        $result = $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_CANCELED);

        $this->assertNull($result['blocker']);
        $this->assertSame(OrderService::STATUS_CANCELED, $result['order']->order_status);
        $this->assertStock(product: $product, currentStock: self::CURRENT_STOCK + self::LINE_QUANTITY, variantStock: self::VARIANT_STOCK + self::LINE_QUANTITY);
        $this->assertSame(0, (int) $detail->refresh()->is_stock_decreased);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => OrderService::STATUS_CANCELED, 'user_type' => 'admin']);
    }

    public function test_failed_deduct_rolls_back_the_whole_status_change(): void
    {
        $product = $this->createStockedProduct();
        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: ['order_status' => OrderService::STATUS_CANCELED]);
        $restorableProduct = $this->createStockedProduct();
        $restorableDetail = $this->createDetail(order: $order, product: $restorableProduct, isStockDecreased: 0);
        $this->createDetail(order: $order, product: $product, isStockDecreased: 0, quantity: self::CURRENT_STOCK + 1);

        $result = $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_PENDING);

        $this->assertSame(OrderBlocker::INSUFFICIENT_STOCK, $result['blocker']);
        $this->assertSame(OrderService::STATUS_CANCELED, $order->refresh()->order_status);
        $this->assertStock(product: $restorableProduct, currentStock: self::CURRENT_STOCK, variantStock: self::VARIANT_STOCK);
        $this->assertSame(0, (int) $restorableDetail->refresh()->is_stock_decreased);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
    }

    public function test_delivered_marks_paid_and_runs_settlement(): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller);
        $this->createDetail(order: $order, product: $this->createStockedProduct(), isStockDecreased: 1);
        $commissionGivenBefore = (float) DB::table('seller_wallets')->where('seller_id', $seller->id)->value('commission_given');

        $result = $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_DELIVERED);

        $this->assertNull($result['blocker']);
        $order->refresh();
        $this->assertSame(OrderService::STATUS_DELIVERED, $order->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $order->payment_status);
        $this->assertSame(0, (int) $order->is_pause);
        $this->assertDatabaseHas('order_details', ['order_id' => $order->id, 'delivery_status' => 'delivered', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('order_transactions', ['order_id' => $order->id, 'seller_id' => $seller->id, 'received_by' => 'admin', 'delivered_by' => 'admin', 'admin_commission' => 5]);
        $this->assertEqualsWithDelta($commissionGivenBefore + 5, (float) DB::table('seller_wallets')->where('seller_id', $seller->id)->value('commission_given'), 0.001);
    }

    public function test_vendor_delivered_marks_received_by_seller(): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING]);
        $this->createDetail(order: $order, product: $this->createStockedProduct(), isStockDecreased: 1);

        $result = $this->service()->changeStatus(
            orderId: $order->id,
            orderStatus: OrderService::STATUS_DELIVERED,
            actorType: OrderActor::VENDOR,
            actorId: $seller->id,
            sellerId: $seller->id,
        );

        $this->assertNull($result['blocker']);
        $this->assertSame(OrderService::STATUS_DELIVERED, $order->refresh()->order_status);
        $this->assertDatabaseHas('order_transactions', ['order_id' => $order->id, 'seller_id' => $seller->id, 'received_by' => 'seller', 'delivered_by' => 'seller']);
    }

    public function test_delivered_credits_loyalty_and_delivery_man(): void
    {
        $this->setSettings(settings: ['loyalty_point_status' => 1, 'loyalty_point_for_each_order' => 1, 'loyalty_point_item_purchase_point' => 10]);
        $customer = $this->customer();
        $deliveryMan = $this->createDeliveryMan(ownerId: 0);
        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: [
            'customer_id' => $customer->id,
            'delivery_man_id' => $deliveryMan->id,
            'deliveryman_charge' => 7,
        ]);
        $detail = $this->createDetail(order: $order, product: $this->createStockedProduct(), isStockDecreased: 1);
        $reward = $this->createLoyaltyReward(order: $order, detail: $detail);
        $loyaltyPointBefore = (float) $customer->loyalty_point;

        $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_DELIVERED);

        $this->assertDatabaseHas('loyalty_point_transactions', ['user_id' => $customer->id, 'reference' => (string) $order->id, 'transaction_type' => 'order_place']);
        $this->assertGreaterThan($loyaltyPointBefore, (float) $customer->refresh()->loyalty_point);
        $this->assertSame(1, (int) $reward->refresh()->reward_delivered);
        $this->assertEqualsWithDelta(7, (float) DB::table('deliveryman_wallets')->where('delivery_man_id', $deliveryMan->id)->value('current_balance'), 0.001);
        $this->assertEqualsWithDelta(100, (float) DB::table('deliveryman_wallets')->where('delivery_man_id', $deliveryMan->id)->value('cash_in_hand'), 0.001);
        $this->assertDatabaseHas('delivery_man_transactions', ['delivery_man_id' => $deliveryMan->id, 'credit' => 7, 'transaction_type' => 'deliveryman_charge', 'user_type' => 'admin']);
    }

    #[DataProvider('statusChangeBlockers')]
    public function test_blocked_status_change_returns_reason_key(string $case, string $actorType, string $orderStatus, string $expectedBlocker): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller, attributes: [...$this->blockerAttributes(case: $case), 'shipping_responsibility' => self::SELLERWISE_SHIPPING]);

        if ($case === 'settled') {
            DB::table('order_transactions')->insert(['order_id' => $order->id, 'seller_id' => $seller->id, 'seller_is' => 'seller', 'status' => 'disburse', 'order_amount' => 100, 'seller_amount' => 95, 'admin_commission' => 5, 'received_by' => 'admin', 'delivered_by' => 'admin', 'delivery_charge' => 0, 'tax' => 0, 'customer_id' => $order->customer_id, 'shop_id' => 0, 'payment_method' => 'cash_on_delivery', 'transaction_id' => Str::uuid()->toString(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $result = $this->service()->changeStatus(
            orderId: $order->id,
            orderStatus: $orderStatus,
            actorType: $actorType,
            actorId: $actorType === OrderActor::VENDOR ? $seller->id : self::ADMIN_ACTOR_ID,
            sellerId: $actorType === OrderActor::VENDOR ? $seller->id : null,
        );

        $this->assertSame($expectedBlocker, $result['blocker']);
        $this->assertSame($order->order_status, $order->refresh()->order_status);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
    }

    public static function statusChangeBlockers(): array
    {
        return self::wholesaleDataSets(static function (): array {
            $admin = OrderActor::ADMIN;
            $vendor = OrderActor::VENDOR;

            return [
                'admin already delivered' => ['delivered', $admin, OrderService::STATUS_CONFIRMED, OrderBlocker::ALREADY_DELIVERED],
                'vendor already delivered' => ['delivered', $vendor, OrderService::STATUS_CANCELED, OrderBlocker::ALREADY_DELIVERED],
                'already settled' => ['settled', $admin, OrderService::STATUS_DELIVERED, OrderBlocker::ALREADY_DELIVERED],
                'customer account deleted' => ['deleted_customer', $admin, OrderService::STATUS_CONFIRMED, OrderBlocker::CUSTOMER_ACCOUNT_DELETED],
                'offline payment unconfirmed' => ['offline_unpaid', $admin, OrderService::STATUS_CONFIRMED, OrderBlocker::OFFLINE_PAYMENT_UNCONFIRMED],
                'vendor delivers unpaid digital payment' => ['digital_unpaid', $vendor, OrderService::STATUS_DELIVERED, OrderBlocker::PAYMENT_UNCONFIRMED_BEFORE_DELIVERY],
            ];
        });
    }

    #[DataProvider('shippingOwnerOnlyStatuses')]
    public function test_vendor_cannot_set_delivery_statuses_when_admin_ships(string $orderStatus): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller);

        $result = $this->service()->changeStatus(
            orderId: $order->id,
            orderStatus: $orderStatus,
            actorType: OrderActor::VENDOR,
            actorId: $seller->id,
            sellerId: $seller->id,
        );

        $this->assertSame(OrderBlocker::SHIPPING_NOT_OWNED, $result['blocker']);
        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
    }

    public static function shippingOwnerOnlyStatuses(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return [
                'out for delivery' => [OrderService::STATUS_OUT_FOR_DELIVERY],
                'delivered' => [OrderService::STATUS_DELIVERED],
                'returned' => [OrderService::STATUS_RETURNED],
                'failed' => [OrderService::STATUS_FAILED],
                'canceled' => [OrderService::STATUS_CANCELED],
            ];
        });
    }

    public function test_vendor_can_move_admin_shipped_order_through_preparation_statuses(): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller);

        foreach (OrderStatusService::VENDOR_STATUSES_WITHOUT_SHIPPING as $orderStatus) {
            $result = $this->service()->changeStatus(
                orderId: $order->id,
                orderStatus: $orderStatus,
                actorType: OrderActor::VENDOR,
                actorId: $seller->id,
                sellerId: $seller->id,
            );

            $this->assertNull($result['blocker'], $orderStatus);
            $this->assertSame($orderStatus, $order->refresh()->order_status);
        }
    }

    public function test_vendor_shipping_owner_and_admin_may_set_delivery_statuses(): void
    {
        $seller = $this->approvedSeller();
        $vendorShippedOrder = $this->createOrder(seller: $seller, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING]);
        $adminShippedOrder = $this->createOrder(seller: $seller);

        $vendorResult = $this->service()->changeStatus(
            orderId: $vendorShippedOrder->id,
            orderStatus: OrderService::STATUS_OUT_FOR_DELIVERY,
            actorType: OrderActor::VENDOR,
            actorId: $seller->id,
            sellerId: $seller->id,
        );
        $adminResult = $this->service()->changeStatus(
            orderId: $adminShippedOrder->id,
            orderStatus: OrderService::STATUS_OUT_FOR_DELIVERY,
            actorType: OrderActor::ADMIN,
            actorId: self::ADMIN_ACTOR_ID,
            sellerId: null,
        );

        $this->assertNull($vendorResult['blocker']);
        $this->assertNull($adminResult['blocker']);
        $this->assertSame(OrderService::STATUS_OUT_FOR_DELIVERY, $vendorShippedOrder->refresh()->order_status);
        $this->assertSame(OrderService::STATUS_OUT_FOR_DELIVERY, $adminShippedOrder->refresh()->order_status);
    }

    public function test_vendor_actor_cannot_touch_another_vendor_order(): void
    {
        [$owner, $otherVendor] = $this->approvedSellers(count: 2);
        $order = $this->createOrder(seller: $owner);
        $orderService = $this->orderService();

        $results = [
            $this->service()->changeStatus(orderId: $order->id, orderStatus: OrderService::STATUS_CONFIRMED, actorType: OrderActor::VENDOR, actorId: $otherVendor->id, sellerId: $otherVendor->id),
            $orderService->updatePaymentStatus(orderId: $order->id, paymentStatus: OrderService::PAYMENT_STATUS_PAID, actorType: OrderActor::VENDOR, sellerId: $otherVendor->id),
            $orderService->updateAddress(orderId: $order->id, addressType: OrderService::ADDRESS_TYPE_SHIPPING, address: ['city' => 'Elsewhere'], actorType: OrderActor::VENDOR, sellerId: $otherVendor->id),
        ];

        foreach ($results as $result) {
            $this->assertSame(['order' => null, 'blocker' => OrderBlocker::ORDER_NOT_FOUND], $result);
        }

        $this->assertNull($orderService->findForActor(orderId: $order->id, sellerId: $otherVendor->id));

        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $order->payment_status);
    }

    public function test_retail_order_id_is_not_found(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller(), businessModule: BusinessModule::RETAIL);

        $result = $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_CONFIRMED);

        $this->assertSame(['order' => null, 'blocker' => OrderBlocker::ORDER_NOT_FOUND], $result);
        $this->assertSame(
            OrderBlocker::ORDER_NOT_FOUND,
            $this->orderService()->updatePaymentStatus(orderId: $order->id, paymentStatus: OrderService::PAYMENT_STATUS_PAID, actorType: OrderActor::ADMIN, sellerId: null)['blocker'],
        );
        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
    }

    public function test_repeat_status_is_a_noop(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: ['order_status' => OrderService::STATUS_CONFIRMED]);

        $result = $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_CONFIRMED);

        $this->assertNull($result['blocker']);
        $this->assertSame($order->id, $result['order']->id);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
        Event::assertNotDispatched(OrderStatusEvent::class);
    }

    public function test_payment_status_blockers(): void
    {
        $seller = $this->approvedSeller();
        $deletedCustomerOrder = $this->createOrder(seller: $seller, attributes: $this->blockerAttributes(case: 'deleted_customer'));
        $offlineOrder = $this->createOrder(seller: $seller, attributes: $this->blockerAttributes(case: 'offline_unpaid'));
        $cashOnDeliveryOrder = $this->createOrder(seller: $seller);
        $paidOrder = $this->createOrder(seller: $seller, attributes: ['payment_status' => OrderService::PAYMENT_STATUS_PAID, 'payment_method' => 'digital_payment']);
        $orderService = $this->orderService();

        $blockerFor = fn (Order $order, string $paymentStatus, string $actorType): ?string => $orderService->updatePaymentStatus(
            orderId: $order->id,
            paymentStatus: $paymentStatus,
            actorType: $actorType,
            sellerId: $actorType === OrderActor::VENDOR ? $seller->id : null,
        )['blocker'];

        $this->assertSame(OrderBlocker::CUSTOMER_ACCOUNT_DELETED, $blockerFor($deletedCustomerOrder, OrderService::PAYMENT_STATUS_PAID, OrderActor::ADMIN));
        $this->assertSame(OrderBlocker::OFFLINE_PAYMENT_UNCONFIRMED, $blockerFor($offlineOrder, OrderService::PAYMENT_STATUS_PAID, OrderActor::VENDOR));
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $offlineOrder->refresh()->payment_status);
        $this->assertNull($blockerFor($offlineOrder, OrderService::PAYMENT_STATUS_PAID, OrderActor::ADMIN));
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $offlineOrder->refresh()->payment_status);
        $this->assertSame(OrderBlocker::CASH_ON_DELIVERY_NOT_DELIVERED, $blockerFor($cashOnDeliveryOrder, OrderService::PAYMENT_STATUS_PAID, OrderActor::ADMIN));
        $this->assertSame(OrderBlocker::PAID_STATUS_IRREVERSIBLE, $blockerFor($paidOrder, OrderService::PAYMENT_STATUS_UNPAID, OrderActor::VENDOR));
        $this->assertSame(OrderBlocker::PAID_STATUS_IRREVERSIBLE, $blockerFor($paidOrder, OrderService::PAYMENT_STATUS_UNPAID, OrderActor::ADMIN));
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $paidOrder->refresh()->payment_status);

        $unpaidDigitalOrder = $this->createOrder(seller: $seller, attributes: ['payment_method' => 'digital_payment']);
        $this->assertNull($blockerFor($unpaidDigitalOrder, OrderService::PAYMENT_STATUS_PAID, OrderActor::ADMIN));
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $unpaidDigitalOrder->refresh()->payment_status);
    }

    public function test_admin_can_change_status_after_confirming_an_offline_payment(): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller, attributes: $this->blockerAttributes(case: 'offline_unpaid'));
        $orderService = $this->orderService();

        $this->assertSame(OrderBlocker::OFFLINE_PAYMENT_UNCONFIRMED, $this->updateStatus($order, OrderService::STATUS_CONFIRMED)['blocker']);

        $orderService->updatePaymentStatus(
            orderId: $order->id,
            paymentStatus: OrderService::PAYMENT_STATUS_PAID,
            actorType: OrderActor::ADMIN,
            sellerId: null,
        );

        $this->assertNull($this->updateStatus($order, OrderService::STATUS_CONFIRMED)['blocker']);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->refresh()->order_status);
    }

    #[DataProvider('referralDeliveryStates')]
    public function test_referrer_bonus_is_paid_once_per_referred_customer(?int $deliveredNotify, bool $expectsBonus): void
    {
        $this->setSettings(settings: ['ref_earning_status' => 1, 'ref_earning_exchange_rate' => 20, 'wallet_status' => 1]);
        [$referrer, $customer] = $this->customerPair();
        User::query()->whereKey($customer->id)->update(['referred_by' => $referrer->id]);
        DB::table('referral_customers')->where('user_id', $customer->id)->delete();

        if ($deliveredNotify !== null) {
            DB::table('referral_customers')->insert(['user_id' => $customer->id, 'refer_by' => $referrer->id, 'delivered_notify' => $deliveredNotify, 'created_at' => now(), 'updated_at' => now()]);
        }

        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: ['customer_id' => $customer->id]);
        $referralCredits = fn (): int => DB::table('wallet_transactions')->where(['user_id' => $referrer->id, 'reference' => 'earned_by_referral'])->count();
        $creditsBefore = $referralCredits();

        $this->assertNull($this->updateStatus(order: $order, orderStatus: OrderService::STATUS_DELIVERED)['blocker']);

        $this->assertSame($creditsBefore + ($expectsBonus ? 1 : 0), $referralCredits());

        if ($deliveredNotify !== null) {
            $this->assertSame(1, (int) DB::table('referral_customers')->where('user_id', $customer->id)->value('delivered_notify'));
        }

        $this->assertCount(
            $deliveredNotify === 0 ? 1 : 0,
            Event::dispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->type === OrderService::NOTIFICATION_TYPE_PROMOTER),
        );
    }

    public static function referralDeliveryStates(): array
    {
        return [
            'first delivery for a referral record' => [0, true],
            'already delivered in another module' => [1, false],
            'referred customer without a referral record' => [null, true],
        ];
    }

    public function test_delivery_man_must_belong_to_order_owner(): void
    {
        [$owner, $otherVendor] = $this->approvedSellers(count: 2);
        $order = $this->createOrder(seller: $owner, attributes: ['shipping_responsibility' => self::SELLERWISE_SHIPPING]);
        $ownDeliveryMan = $this->createDeliveryMan(ownerId: $owner->id);
        $foreignDeliveryMan = $this->createDeliveryMan(ownerId: $otherVendor->id);
        $inactiveDeliveryMan = $this->createDeliveryMan(ownerId: $owner->id, isActive: 0);
        $orderService = $this->orderService();
        $deliveryManService = app(DeliveryManService::class);

        $blockerFor = fn (Order $assignedOrder, int $deliveryManId): ?string => $orderService->getDeliveryManAssignBlocker(
            order: $orderService->findForActor(orderId: $assignedOrder->id, sellerId: $owner->id),
            deliveryMan: $deliveryManService->find(id: $deliveryManId),
            actorType: OrderActor::VENDOR,
            sellerId: $owner->id,
        );

        foreach ([$foreignDeliveryMan, $inactiveDeliveryMan] as $deliveryMan) {
            $this->assertSame(OrderBlocker::DELIVERY_MAN_UNAVAILABLE, $blockerFor($order, $deliveryMan->id));
        }

        $inHouseOrder = $this->createOrder(seller: $owner);
        $this->assertSame(OrderBlocker::DELIVERY_MAN_UNAVAILABLE, $blockerFor($inHouseOrder, $ownDeliveryMan->id));
        $this->assertNull($order->refresh()->delivery_man_id);
        $this->assertNull($blockerFor($order, $ownDeliveryMan->id));

        $assignedOrder = $orderService->assignDeliveryMan(orderId: $order->id, sellerId: $owner->id, deliveryManId: $ownDeliveryMan->id);

        $this->assertSame($ownDeliveryMan->id, (int) $order->refresh()->delivery_man_id);
        $this->assertSame(OrderService::DELIVERY_TYPE_SELF, $order->delivery_type);
        $this->assertSame(
            [[OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN]],
            $orderService->getDeliveryManAssignNotifications(order: $assignedOrder, actorType: OrderActor::VENDOR),
        );
        $this->assertSame(
            [
                [OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN],
                [OrderService::NOTIFICATION_DELIVERY_MAN_ASSIGNED_BY_ADMIN, OrderService::NOTIFICATION_TYPE_SELLER],
            ],
            $orderService->getDeliveryManAssignNotifications(order: $assignedOrder, actorType: OrderActor::ADMIN),
        );
    }

    public function test_schedule_writes_only_the_known_fields(): void
    {
        $seller = $this->approvedSeller();
        $order = $this->createOrder(seller: $seller);
        $orderService = $this->orderService();
        $scheduledOrder = $orderService->findForActor(orderId: $order->id, sellerId: $seller->id, relations: OrderService::DELIVERY_SCHEDULE_RELATIONS);

        $this->assertTrue($orderService->updateScheduleField(order: $scheduledOrder, fieldName: OrderSettlementService::FIELD_EXPECTED_DELIVERY_DATE, value: '2030-05-06'));
        $this->assertTrue($orderService->updateScheduleField(order: $scheduledOrder, fieldName: OrderSettlementService::FIELD_DELIVERYMAN_CHARGE, value: 12));
        $this->assertFalse($orderService->updateScheduleField(order: $scheduledOrder, fieldName: 'order_amount', value: 1));

        $order->refresh();
        $this->assertSame('2030-05-06', substr((string) $order->expected_delivery_date, 0, 10));
        $this->assertGreaterThan(0, (float) $order->deliveryman_charge);
        $this->assertSame(
            [OrderService::NOTIFICATION_EXPECTED_DELIVERY_DATE, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN],
            $orderService->getScheduleUpdateNotification(fieldName: OrderSettlementService::FIELD_EXPECTED_DELIVERY_DATE),
        );
        $this->assertSame(
            [OrderService::NOTIFICATION_DELIVERY_MAN_CHARGE, OrderService::NOTIFICATION_TYPE_DELIVERY_MAN],
            $orderService->getScheduleUpdateNotification(fieldName: OrderSettlementService::FIELD_DELIVERYMAN_CHARGE),
        );
    }

    public function test_address_update_changes_snapshot(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller(), attributes: ['shipping_address_data' => ['city' => 'Old city', 'zip' => '1000']]);

        $result = $this->orderService()->updateAddress(
            orderId: $order->id,
            addressType: OrderService::ADDRESS_TYPE_SHIPPING,
            address: ['city' => 'New city', 'address' => 'Road 7'],
            actorType: OrderActor::ADMIN,
            sellerId: null,
        );

        $this->assertNull($result['blocker']);
        $address = json_decode(json_encode($order->refresh()->shipping_address_data), true);
        $this->assertSame('New city', $address['city']);
        $this->assertSame('Road 7', $address['address']);
        $this->assertSame('1000', $address['zip']);
        $this->assertSame(
            OrderBlocker::INVALID_ADDRESS_TYPE,
            $this->orderService()->updateAddress(orderId: $order->id, addressType: 'pickup', address: [], actorType: OrderActor::ADMIN, sellerId: null)['blocker'],
        );
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === OrderService::NOTIFICATION_ORDER_EDIT && $event->type === OrderService::NOTIFICATION_TYPE_SELLER);
    }

    public function test_notifications_dispatch_after_commit(): void
    {
        $transactionLevels = new ArrayObject;
        $this->app->instance(OrderStatusService::class, new class(app(Order::class), $transactionLevels) extends OrderStatusService
        {
            public function __construct(Order $order, private readonly ArrayObject $transactionLevels)
            {
                parent::__construct($order);
            }

            public function dispatchStatusNotification(string $key, string $type, object|array $order): void
            {
                $this->transactionLevels->append(DB::transactionLevel());
            }
        });
        $outerTransactionLevel = DB::transactionLevel();

        $product = $this->createStockedProduct();
        $order = $this->createOrder(seller: $this->approvedSeller());
        $this->createDetail(order: $order, product: $product, isStockDecreased: 1);

        $this->updateStatus(order: $order, orderStatus: OrderService::STATUS_CANCELED);

        $this->assertNotEmpty($transactionLevels->getArrayCopy());
        $this->assertSame([$outerTransactionLevel], array_values(array_unique($transactionLevels->getArrayCopy())));

        $transactionLevels->exchangeArray([]);
        $failingOrder = $this->createOrder(seller: $this->approvedSeller(), attributes: ['order_status' => OrderService::STATUS_CANCELED]);
        $this->createDetail(order: $failingOrder, product: $product, isStockDecreased: 0, quantity: self::CURRENT_STOCK * 10);

        $this->assertSame(OrderBlocker::INSUFFICIENT_STOCK, $this->updateStatus(order: $failingOrder, orderStatus: OrderService::STATUS_PENDING)['blocker']);
        $this->assertSame([], $transactionLevels->getArrayCopy());
    }

    public function test_constructor_composes_services_only_within_the_documented_cap(): void
    {
        $parameters = (new ReflectionClass(OrderStatusService::class))->getConstructor()->getParameters();
        $collaboratorTypes = [];

        foreach (array_slice($parameters, 1) as $parameter) {
            $type = $parameter->getType();
            $this->assertInstanceOf(ReflectionNamedType::class, $type);
            $this->assertStringEndsWith('Service', $type->getName());
            $collaboratorTypes[] = $type->getName();
        }

        $this->assertSame(Order::class, $parameters[0]->getType()->getName());
        $this->assertLessThanOrEqual(self::MAX_COLLABORATORS, count($collaboratorTypes));
        $this->assertNotContains(OrderStatusService::class, $collaboratorTypes);
    }

    #[DataProvider('requestClasses')]
    public function test_request_messages_cover_every_rule(string $requestClass, array $input, bool $isApiRequest): void
    {
        $request = $requestClass::create($isApiRequest ? '/api/v6/wholesale/seller/orders/1' : '/admin/wholesale/orders/action', 'POST', $input);
        $request->setContainer($this->app);

        $rules = $request->rules();
        $messages = $request->messages();

        foreach ($rules as $field => $fieldRules) {
            foreach ((array) $fieldRules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $ruleName = is_string($rule) ? Str::before($rule, ':') : 'in';
                $this->assertArrayHasKey($field.'.'.$ruleName, $messages, $requestClass.' '.$field.'.'.$ruleName);
            }
        }
    }

    public static function requestClasses(): array
    {
        return [
            'status web' => [StatusUpdateRequest::class, ['id' => 1], false],
            'status api' => [StatusUpdateRequest::class, [], true],
            'payment status web' => [PaymentStatusUpdateRequest::class, ['id' => 1], false],
            'payment status api' => [PaymentStatusUpdateRequest::class, [], true],
            'delivery man web' => [DeliveryManAssignRequest::class, ['id' => 1], false],
            'delivery man api' => [DeliveryManAssignRequest::class, [], true],
            'schedule charge web' => [DeliveryScheduleUpdateRequest::class, ['id' => 1, 'field_name' => 'deliveryman_charge'], false],
            'schedule date web' => [DeliveryScheduleUpdateRequest::class, ['id' => 1, 'field_name' => 'expected_delivery_date'], false],
            'schedule api' => [DeliveryScheduleUpdateRequest::class, [], true],
            'address web' => [AddressUpdateRequest::class, ['id' => 1], false],
            'address api' => [AddressUpdateRequest::class, [], true],
        ];
    }

    public function test_request_order_id_comes_from_route_or_body(): void
    {
        $webRequest = StatusUpdateRequest::create('/admin/wholesale/orders/status', 'POST', ['id' => 41]);
        $this->assertSame(41, $webRequest->orderId());

        $apiRequest = StatusUpdateRequest::create('/api/v6/wholesale/seller/orders/42/status', 'PUT', ['id' => 99]);
        $apiRequest->setRouteResolver(fn () => (new Route('PUT', 'api/v6/wholesale/seller/orders/{id}/status', []))->bind(Request::create('/api/v6/wholesale/seller/orders/42/status', 'PUT')));
        $this->assertSame('42', $apiRequest->orderId());
    }

    public function test_retail_status_change_is_unchanged(): void
    {
        $order = $this->createOrder(seller: $this->approvedSeller(), businessModule: BusinessModule::RETAIL);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.retail.orders.status'), ['id' => $order->id, 'order_status' => OrderService::STATUS_CONFIRMED])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->refresh()->order_status);
        $this->assertSame(BusinessModule::RETAIL, $order->business_module);
        $this->assertSame(1, DB::table('order_status_histories')->where(['order_id' => $order->id, 'status' => OrderService::STATUS_CONFIRMED, 'user_type' => 'admin', 'user_id' => 0])->count());
    }

    /** @return array{order: ?Model, blocker: ?string} */
    private function updateStatus(Order $order, string $orderStatus): array
    {
        return $this->service()->changeStatus(
            orderId: $order->id,
            orderStatus: $orderStatus,
            actorType: OrderActor::ADMIN,
            actorId: self::ADMIN_ACTOR_ID,
            sellerId: null,
        );
    }

    private function blockerAttributes(string $case): array
    {
        return match ($case) {
            'delivered' => ['order_status' => OrderService::STATUS_DELIVERED, 'payment_status' => OrderService::PAYMENT_STATUS_PAID],
            'deleted_customer' => ['customer_id' => (int) User::query()->max('id') + 100000],
            'offline_unpaid' => ['payment_method' => OrderService::PAYMENT_METHOD_OFFLINE],
            'digital_unpaid' => ['payment_method' => 'digital_payment'],
            default => [],
        };
    }

    private function createOrder(?Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'customer_id' => $this->customer()->id,
            'is_guest' => 0,
            'seller_id' => $seller?->id ?? self::IN_HOUSE_SELLER_ID,
            'seller_is' => $seller === null ? 'admin' : 'seller',
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-fulfilment-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_PENDING,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'admin_commission' => 5,
            'shipping_cost' => 0,
            'is_shipping_free' => 0,
            'shipping_responsibility' => 'inhouse_shipping',
            'discount_amount' => 0,
            'discount_type' => null,
            'coupon_code' => null,
            'extra_discount' => 0,
            'refer_and_earn_discount' => 0,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
            'delivery_type' => null,
            'is_pause' => 0,
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, Model $product, int $isStockDecreased, int $quantity = self::LINE_QUANTITY): OrderDetail
    {
        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $product->id, 'name' => 'Fulfilment fixture', 'product_type' => 'physical']),
            'qty' => $quantity,
            'price' => 25,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => self::VARIANT,
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
            'is_stock_decreased' => $isStockDecreased,
        ])->save();

        return $detail;
    }

    private function createStockedProduct(): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product');
        $product = $template->replicate();
        $product->forceFill([
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'current_stock' => self::CURRENT_STOCK,
            'slug' => 'wholesale-fulfilment-'.Str::lower(Str::random(12)),
            'code' => 'WSFUL'.random_int(10000, 99999),
        ])->save();

        ProductStock::query()->create(['product_id' => $product->id, 'variant' => self::VARIANT, 'sku' => 'WSFUL-'.$product->id, 'price' => 25, 'qty' => self::VARIANT_STOCK]);

        return $product;
    }

    private function createDeliveryMan(int $ownerId, int $isActive = 1): DeliveryMan
    {
        $suffix = Str::lower(Str::random(10));
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => $ownerId,
            'f_name' => 'Fulfilment',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'rider-'.$suffix.'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => $isActive,
        ])->save();

        return $deliveryMan;
    }

    private function createLoyaltyReward(Order $order, OrderDetail $detail): OrderDetailsRewards
    {
        $reward = new OrderDetailsRewards;
        $reward->forceFill([
            'order_id' => $order->id,
            'order_details_id' => $detail->id,
            'reward_type' => 'loyalty_point',
            'reward_amount' => 10,
            'reward_details' => [],
            'reward_delivered' => 0,
        ])->save();

        return $reward;
    }

    private function setSettings(array $settings): void
    {
        foreach ($settings as $type => $value) {
            BusinessSetting::query()->updateOrInsert(['type' => $type], ['value' => $value]);
        }

        Cache::flush();
        Helpers::resetMemo();
    }

    private function assertStock(Product $product, int $currentStock, int $variantStock): void
    {
        $this->assertSame($currentStock, (int) Product::query()->withoutGlobalScopes()->whereKey($product->id)->value('current_stock'));
        $this->assertSame($variantStock, (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::VARIANT])->value('qty'));
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
    }

    private function customer(): User
    {
        return $this->requireFixture(User::query()->where('id', '>', 0)->orderBy('id')->first(), 'customer');
    }

    /** @return array{0: User, 1: User} */
    private function customerPair(): array
    {
        $customers = User::query()->where('id', '>', 0)->orderBy('id')->limit(2)->get()->all();

        if (count($customers) < 2) {
            $this->markTestSkipped('Test database has fewer than 2 customers.');
        }

        return $customers;
    }

    private function approvedSeller(): Seller
    {
        return $this->approvedSellers(count: 1)[0];
    }

    /** @return array<int, Seller> */
    private function approvedSellers(int $count): array
    {
        $sellers = Seller::query()
            ->where('status', 'approved')
            ->where('id', '!=', self::IN_HOUSE_SELLER_ID)
            ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
            ->orderBy('id')
            ->limit($count)
            ->get()
            ->all();

        if (count($sellers) < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' approved sellers with a vendor shop.');
        }

        return $sellers;
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function service(): OrderStatusService
    {
        return app(OrderStatusService::class);
    }

    private function orderService(): OrderService
    {
        return app(OrderService::class);
    }
}
