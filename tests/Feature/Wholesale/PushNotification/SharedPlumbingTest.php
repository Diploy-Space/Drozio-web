<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Enums\NotificationUserType;
use App\Events\OrderStatusEvent;
use App\Models\User;
use App\Services\New\PushNotificationService;
use App\Traits\PushNotificationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderStatusService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class SharedPlumbingTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const REFERRAL_ONLY_KEYS = [
        'your_referred_customer_has_been_place_order',
        'your_referred_customer_order_has_been_delivered',
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

    public function test_wholesale_key_map_keeps_every_retail_key_except_referral_ones(): void
    {
        $service = app(PushNotificationService::class);

        foreach ([NotificationUserType::CUSTOMER, NotificationUserType::SELLER, NotificationUserType::DELIVERY_MAN] as $userType) {
            $retailKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_RETAIL, userType: $userType);
            $wholesaleKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_WHOLESALE, userType: $userType);

            $this->assertSame([], array_values(array_diff(array_diff($retailKeys, self::REFERRAL_ONLY_KEYS), $wholesaleKeys)));
            $this->assertSame([], array_values(array_intersect(self::REFERRAL_ONLY_KEYS, $wholesaleKeys)));
            $this->assertSame(count(array_unique($wholesaleKeys)), count($wholesaleKeys));
        }
    }

    public function test_key_outside_the_alias_table_resolves_by_its_own_name(): void
    {
        $this->insertMessage(businessModule: BusinessModule::WHOLESALE, userType: NotificationUserType::CUSTOMER, key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, message: 'Order {orderId} is paid');

        $notifier = $this->messageResolver();

        $this->assertSame('Order {orderId} is paid', $notifier->resolve(key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, userType: NotificationUserType::CUSTOMER, businessModule: BusinessModule::WHOLESALE));
        $this->assertFalse($notifier->resolve(key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, userType: NotificationUserType::CUSTOMER, businessModule: BusinessModule::RETAIL));
    }

    public function test_device_and_topic_payloads_carry_the_new_data_keys(): void
    {
        $transport = $this->capturingTransport();

        $transport->sendToDevice(data: [
            'title' => 'Title',
            'description' => 'Body',
            'image' => '',
            'type' => 'order',
            'business_module' => BusinessModule::WHOLESALE,
            'order_status' => OrderService::STATUS_CONFIRMED,
            'product_id' => 11,
            'campaign_id' => 12,
            'review_id' => 13,
        ]);
        $transport->sendToTopic(data: ['title' => 'Title', 'description' => 'Body', 'type' => 'notification', 'business_module' => BusinessModule::WHOLESALE]);

        $deviceData = $transport->posted[0]['message']['data'];
        $this->assertSame(BusinessModule::WHOLESALE, $deviceData['business_module']);
        $this->assertSame(OrderService::STATUS_CONFIRMED, $deviceData['order_status']);
        $this->assertSame('11', $deviceData['product_id']);
        $this->assertSame('12', $deviceData['campaign_id']);
        $this->assertSame('13', $deviceData['review_id']);
        $this->assertSame(BusinessModule::WHOLESALE, $transport->posted[1]['message']['data']['business_module']);
    }

    public function test_device_payload_defaults_new_data_keys_to_empty_strings(): void
    {
        $transport = $this->capturingTransport();

        $transport->sendToDevice(data: ['title' => 'Title', 'description' => 'Body', 'image' => '', 'type' => 'order']);

        $deviceData = $transport->posted[0]['message']['data'];
        foreach (['business_module', 'order_status', 'product_id', 'campaign_id', 'review_id'] as $dataKey) {
            $this->assertSame('', $deviceData[$dataKey]);
        }
    }

    public function test_status_change_recipients_are_unchanged(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $deliveryMan = $this->createDeliveryMan();
        $canceledOrder = $this->createWholesaleOrder($deliveryMan);
        $confirmedOrder = $this->createWholesaleOrder($deliveryMan);

        $this->statusService()->changeStatus(orderId: $canceledOrder->id, orderStatus: OrderService::STATUS_CANCELED, actorType: OrderActor::ADMIN, actorId: 1, sellerId: null);
        $this->statusService()->changeStatus(orderId: $confirmedOrder->id, orderStatus: OrderService::STATUS_CONFIRMED, actorType: OrderActor::ADMIN, actorId: 1, sellerId: null);

        $this->assertDispatchedFor(orderId: $canceledOrder->id, key: OrderService::STATUS_CANCELED, type: OrderService::NOTIFICATION_TYPE_CUSTOMER);
        $this->assertDispatchedFor(orderId: $canceledOrder->id, key: OrderService::STATUS_CANCELED, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
        $this->assertDispatchedFor(orderId: $confirmedOrder->id, key: OrderService::STATUS_CONFIRMED, type: OrderService::NOTIFICATION_TYPE_CUSTOMER);
        Event::assertNotDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->type === OrderService::NOTIFICATION_TYPE_SELLER);
    }

    public function test_marking_an_order_paid_dispatches_payment_confirmed_to_customer_and_delivery_man(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $order = $this->createWholesaleOrder($this->createDeliveryMan(), [
            'customer_id' => $customer->id,
            'payment_method' => 'offline_payment',
        ]);

        $result = app(OrderService::class)->updatePaymentStatus(orderId: $order->id, paymentStatus: OrderService::PAYMENT_STATUS_PAID, actorType: OrderActor::ADMIN, sellerId: null);

        $this->assertNull($result['blocker']);
        $this->assertDispatchedFor(orderId: $order->id, key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, type: OrderService::NOTIFICATION_TYPE_CUSTOMER);
        $this->assertDispatchedFor(orderId: $order->id, key: OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED, type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
        Event::assertNotDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->type === OrderService::NOTIFICATION_TYPE_SELLER);
    }

    public function test_an_unchanged_payment_status_dispatches_nothing(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $order = $this->createWholesaleOrder($this->createDeliveryMan(), ['payment_method' => 'offline_payment']);

        app(OrderService::class)->updatePaymentStatus(orderId: $order->id, paymentStatus: OrderService::PAYMENT_STATUS_UNPAID, actorType: OrderActor::ADMIN, sellerId: null);

        Event::assertNotDispatched(OrderStatusEvent::class);
    }

    private function assertDispatchedFor(int $orderId, string $key, string $type): void
    {
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === $key
            && $event->type === $type
            && (int) $event->order['id'] === $orderId);
    }

    private function statusService(): OrderStatusService
    {
        return app(OrderStatusService::class);
    }

    private function insertMessage(string $businessModule, string $userType, string $key, string $message): void
    {
        DB::table('notification_messages')->insert([
            'business_module' => $businessModule,
            'user_type' => $userType,
            'key' => $key,
            'message' => $message,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function messageResolver(): object
    {
        return new class
        {
            use PushNotificationTrait;

            public function resolve(string $key, string $userType, string $businessModule): mixed
            {
                return $this->pushNotificationMessage(key: $key, userType: $userType, lang: 'en', businessModule: $businessModule);
            }
        };
    }

    private function capturingTransport(): object
    {
        return new class
        {
            use PushNotificationTrait;

            public array $posted = [];

            public function sendToDevice(array $data): void
            {
                $this->sendPushNotificationToDevice('device-token', $data);
            }

            public function sendToTopic(array $data): void
            {
                $this->sendPushNotificationToTopic(data: $data, topic: 'test-topic');
            }

            protected function sendNotificationToHttp(?array $data): bool|string|null
            {
                $this->posted[] = $data;

                return true;
            }
        };
    }
}
