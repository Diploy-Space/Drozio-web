<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Enums\NotificationUserType;
use App\Events\RefundEvent;
use App\Listeners\RefundListener;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Services\New\PushNotificationService;
use App\Services\New\RefundRequestService;
use App\Traits\PushNotificationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Wholesale\app\Services\OrderService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class UserAppTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const CUSTOMER_TOKEN = 'wholesale-user-app-token';

    private const SEED_MIGRATION = 'Modules/Wholesale/database/migrations/2026_09_27_100010_seed_wholesale_customer_push_notification_messages.php';

    private const NEW_CUSTOMER_KEYS = [
        'refund_request_message',
        'refund_request_approved_message',
        'order_payment_confirmed_message',
    ];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_new_customer_keys_are_listed_for_wholesale_only(): void
    {
        $service = app(PushNotificationService::class);
        $wholesaleKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_WHOLESALE, userType: NotificationUserType::CUSTOMER);
        $retailKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_RETAIL, userType: NotificationUserType::CUSTOMER);

        foreach (self::NEW_CUSTOMER_KEYS as $key) {
            $this->assertContains($key, $wholesaleKeys);
            $this->assertNotContains($key, $retailKeys);
        }
    }

    public function test_seed_migration_creates_each_row_once(): void
    {
        $migration = require base_path(self::SEED_MIGRATION);

        $migration->up();
        $migration->up();

        foreach (self::NEW_CUSTOMER_KEYS as $key) {
            $this->assertSame(1, DB::table('notification_messages')->where([
                'business_module' => BusinessModule::WHOLESALE,
                'user_type' => NotificationUserType::CUSTOMER,
                'key' => $key,
                'status' => 1,
            ])->count());
        }
    }

    public function test_refund_request_notifies_the_customer(): void
    {
        $this->enableMessage(key: 'refund_request_message', message: 'Refund requested for {orderId}');
        [$order, $orderDetail] = $this->wholesaleOrderWithCustomerToken();

        $sent = $this->handleRefund(status: RefundListener::STATUS_REFUND_REQUESTED, order: $order, orderDetail: $orderDetail);

        $this->assertCustomerPush(sent: $sent, key: 'refund_request_message', order: $order, orderDetail: $orderDetail);
    }

    public function test_refund_approval_notifies_the_customer(): void
    {
        $this->enableMessage(key: 'refund_request_approved_message', message: 'Refund approved for {orderId}');
        [$order, $orderDetail] = $this->wholesaleOrderWithCustomerToken();

        $sent = $this->handleRefund(status: RefundRequestService::STATUS_APPROVED, order: $order, orderDetail: $orderDetail);

        $this->assertCustomerPush(sent: $sent, key: 'refund_request_approved_message', order: $order, orderDetail: $orderDetail);
    }

    public function test_refund_rejection_and_completion_still_notify_the_customer(): void
    {
        $this->enableMessage(key: 'refund_request_canceled_message', message: 'Refund rejected');
        $this->enableMessage(key: 'order_refunded_message', message: 'Refunded');
        [$order, $orderDetail] = $this->wholesaleOrderWithCustomerToken();

        $rejected = $this->handleRefund(status: RefundRequestService::STATUS_REJECTED, order: $order, orderDetail: $orderDetail);
        $refunded = $this->handleRefund(status: RefundRequestService::STATUS_REFUNDED, order: $order, orderDetail: $orderDetail);

        $this->assertCustomerPush(sent: $rejected, key: 'refund_request_canceled_message', order: $order, orderDetail: $orderDetail);
        $this->assertCustomerPush(sent: $refunded, key: 'order_refunded_message', order: $order, orderDetail: $orderDetail);
    }

    public function test_retail_refund_request_still_sends_the_customer_nothing(): void
    {
        [$order, $orderDetail] = $this->wholesaleOrderWithCustomerToken(businessModule: BusinessModule::RETAIL);

        $sent = $this->handleRefund(status: RefundListener::STATUS_REFUND_REQUESTED, order: $order, orderDetail: $orderDetail);

        $this->assertSame([], $sent);
    }

    public function test_payment_confirmed_reaches_the_customer_with_the_wholesale_template(): void
    {
        $this->enableMessage(key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, message: 'Payment for {orderId} confirmed');
        [$order] = $this->wholesaleOrderWithCustomerToken();

        $sent = $this->sendCustomerOrderNotification(key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, order: $order);

        $this->assertCount(1, $sent);
        $this->assertSame('Payment for '.$order->id.' confirmed', $sent[0]['description']);
        $this->assertSame((int) $order->id, (int) $sent[0]['order_id']);
    }

    public function test_failed_and_returned_statuses_use_the_wholesale_template(): void
    {
        $this->enableMessage(key: 'order_failed_message', message: 'Wholesale order failed');
        $this->enableMessage(key: 'order_returned_message', message: 'Wholesale order returned');
        [$order] = $this->wholesaleOrderWithCustomerToken();

        $failed = $this->sendCustomerOrderNotification(key: OrderService::STATUS_FAILED, order: $order);
        $returned = $this->sendCustomerOrderNotification(key: OrderService::STATUS_RETURNED, order: $order);

        $this->assertSame('Wholesale order failed', $failed[0]['description']);
        $this->assertSame('Wholesale order returned', $returned[0]['description']);
    }

    public function test_wholesale_customer_push_carries_the_business_module(): void
    {
        $this->enableMessage(key: 'order_canceled', message: 'Wholesale order canceled');
        [$order] = $this->wholesaleOrderWithCustomerToken();

        $sent = $this->sendCustomerOrderNotification(key: OrderService::STATUS_CANCELED, order: $order);

        $this->assertCount(1, $sent);
        $this->assertSame(BusinessModule::WHOLESALE, $sent[0]['business_module']);
    }

    public function test_retail_customer_push_payload_is_unchanged(): void
    {
        $this->enableMessage(key: 'order_canceled', message: 'Retail order canceled', businessModule: BusinessModule::RETAIL);
        [$order] = $this->wholesaleOrderWithCustomerToken(businessModule: BusinessModule::RETAIL);

        $sent = $this->sendCustomerOrderNotification(key: OrderService::STATUS_CANCELED, order: $order);

        $this->assertCount(1, $sent);
        $this->assertArrayNotHasKey('business_module', $sent[0]);
    }

    private function enableMessage(string $key, string $message, string $businessModule = BusinessModule::WHOLESALE): void
    {
        DB::table('notification_messages')->updateOrInsert(
            ['business_module' => $businessModule, 'user_type' => NotificationUserType::CUSTOMER, 'key' => $key],
            ['message' => $message, 'status' => 1, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** @return array{0: Order, 1: OrderDetail} */
    private function wholesaleOrderWithCustomerToken(string $businessModule = BusinessModule::WHOLESALE): array
    {
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $customer->forceFill(['cm_firebase_token' => self::CUSTOMER_TOKEN, 'app_language' => 'en'])->save();

        $deliveryMan = $this->createDeliveryMan();
        $order = $businessModule === BusinessModule::WHOLESALE
            ? $this->createWholesaleOrder($deliveryMan, ['customer_id' => $customer->id])
            : $this->createRetailOrder($deliveryMan, ['customer_id' => $customer->id]);

        return [$order->fresh(), OrderDetail::query()->where('order_id', $order->id)->firstOrFail()];
    }

    /** @return array<int, array<string, mixed>> */
    private function handleRefund(string $status, Order $order, OrderDetail $orderDetail): array
    {
        $listener = new class extends RefundListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };

        $listener->handle(new RefundEvent(status: $status, order: $order, refund: ['id' => 1], orderDetails: $orderDetail));

        return $listener->sent;
    }

    /** @return array<int, array<string, mixed>> */
    private function sendCustomerOrderNotification(string $key, Order $order): array
    {
        $notifier = new class
        {
            use PushNotificationTrait;

            public array $sent = [];

            public function notify(string $key, Order $order): void
            {
                $this->sendOrderNotification(key: $key, type: NotificationUserType::CUSTOMER, order: $order);
            }

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };

        $notifier->notify(key: $key, order: $order);

        return $notifier->sent;
    }

    private function assertCustomerPush(array $sent, string $key, Order $order, OrderDetail $orderDetail): void
    {
        $customerPushes = array_values(array_filter($sent, static fn (array $push): bool => $push['message_key'] === $key));

        $this->assertCount(1, $customerPushes);
        $this->assertSame((int) $order->id, (int) $customerPushes[0]['order_id']);
        $this->assertSame((int) $orderDetail->id, (int) $customerPushes[0]['order_details_id']);
        $this->assertSame('order', $customerPushes[0]['type']);
        $this->assertSame(BusinessModule::WHOLESALE, $customerPushes[0]['business_module']);
    }
}
