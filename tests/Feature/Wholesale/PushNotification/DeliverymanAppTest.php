<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Enums\NotificationUserType;
use App\Events\OrderStatusEvent;
use App\Models\Admin;
use App\Models\DeliverymanNotification;
use App\Models\Order;
use App\Services\New\PushNotificationService;
use App\Traits\PushNotificationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Events\OrderDeliveredEvent;
use Modules\Wholesale\app\Services\NotificationService;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderStatusService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class DeliverymanAppTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const DELIVERY_MAN_TOKEN = 'wholesale-deliveryman-app-token';

    private const BASE = 'api/v6/wholesale/delivery-man';

    private const SEED_MIGRATION = 'Modules/Wholesale/database/migrations/2026_09_27_100030_seed_wholesale_delivery_man_push_notification_messages.php';

    private const NEW_DELIVERY_MAN_KEYS = [
        'order_processing_message',
        'out_for_delivery_message',
        'order_delivered_message',
        'order_paused_message',
        'order_resumed_message',
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

    public function test_new_delivery_man_keys_are_listed_for_wholesale_only(): void
    {
        $service = app(PushNotificationService::class);
        $wholesaleKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_WHOLESALE, userType: NotificationUserType::DELIVERY_MAN);
        $retailKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_RETAIL, userType: NotificationUserType::DELIVERY_MAN);

        foreach (self::NEW_DELIVERY_MAN_KEYS as $key) {
            $this->assertContains($key, $wholesaleKeys);
            $this->assertNotContains($key, $retailKeys);
        }
    }

    public function test_seed_migration_creates_each_row_once(): void
    {
        $migration = require base_path(self::SEED_MIGRATION);

        $migration->up();
        $migration->up();

        foreach (self::NEW_DELIVERY_MAN_KEYS as $key) {
            $this->assertSame(1, DB::table('notification_messages')->where([
                'business_module' => BusinessModule::WHOLESALE,
                'user_type' => NotificationUserType::DELIVERY_MAN,
                'key' => $key,
                'status' => 1,
            ])->count());
        }
    }

    #[DataProvider('deliveryManNotifiedStatuses')]
    public function test_delivery_man_hears_about_each_status(string $orderStatus): void
    {
        Event::fake([OrderStatusEvent::class, OrderDeliveredEvent::class]);
        $order = $this->createWholesaleOrder($this->createDeliveryMan());

        $result = app(OrderStatusService::class)->changeStatus(orderId: $order->id, orderStatus: $orderStatus, actorType: OrderActor::ADMIN, actorId: 1, sellerId: null);

        $this->assertNull($result['blocker']);
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === $orderStatus
            && $event->type === OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
    }

    public static function deliveryManNotifiedStatuses(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return [
                'processing' => [OrderService::STATUS_PROCESSING],
                'out for delivery' => [OrderService::STATUS_OUT_FOR_DELIVERY],
                'delivered' => [OrderService::STATUS_DELIVERED],
                'canceled' => [OrderService::STATUS_CANCELED],
            ];
        });
    }

    public function test_confirmed_is_not_sent_to_the_delivery_man(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $order = $this->createWholesaleOrder($this->createDeliveryMan());

        app(OrderStatusService::class)->changeStatus(orderId: $order->id, orderStatus: OrderService::STATUS_CONFIRMED, actorType: OrderActor::ADMIN, actorId: 1, sellerId: null);

        Event::assertNotDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->type === OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
    }

    public function test_delivery_man_payload_carries_module_status_and_key(): void
    {
        $this->enableMessage(key: 'order_processing_message', message: 'Order {orderId} is being packaged');
        $deliveryMan = $this->createDeliveryMan(['fcm_token' => self::DELIVERY_MAN_TOKEN, 'app_language' => 'en']);
        $order = $this->createWholesaleOrder($deliveryMan, ['order_status' => OrderService::STATUS_PROCESSING]);

        $sent = $this->sendDeliveryManOrderNotification(key: OrderService::STATUS_PROCESSING, order: $order->fresh());

        $this->assertCount(1, $sent);
        $this->assertSame(BusinessModule::WHOLESALE, $sent[0]['business_module']);
        $this->assertSame(OrderService::STATUS_PROCESSING, $sent[0]['order_status']);
        $this->assertSame(OrderService::STATUS_PROCESSING, $sent[0]['message_key']);
        $this->assertSame((int) $order->id, (int) $sent[0]['order_id']);
        $this->assertTrue(DeliverymanNotification::query()->where(['delivery_man_id' => $deliveryMan->id, 'order_id' => $order->id])->exists());
    }

    public function test_pausing_and_resuming_notify_the_delivery_man_once_per_change(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder($deliveryMan, ['order_status' => OrderService::STATUS_OUT_FOR_DELIVERY]);
        $pauseUrl = self::BASE.'/orders/'.$order->id.'/pause';

        $this->putJson($pauseUrl, ['is_pause' => 1, 'cause' => 'Road closed'], $this->deliveryManHeaders($deliveryMan))->assertOk();
        $this->putJson($pauseUrl, ['is_pause' => 0], $this->deliveryManHeaders($deliveryMan))->assertOk();
        $this->putJson($pauseUrl, ['is_pause' => 0], $this->deliveryManHeaders($deliveryMan))->assertOk();

        Event::assertDispatchedTimes(OrderStatusEvent::class, 2);
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === OrderService::NOTIFICATION_ORDER_PAUSED
            && $event->type === OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === OrderService::NOTIFICATION_ORDER_RESUMED
            && $event->type === OrderService::NOTIFICATION_TYPE_DELIVERY_MAN);
    }

    public function test_admin_broadcast_ignores_a_delivery_man_audience(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.wholesale.notification.index'), [
                'title' => 'Depot closed',
                'description' => 'The north depot is closed today',
                'sent_to' => 'delivery_man',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue(DB::table('notifications')->where([
            'title' => 'Depot closed',
            'sent_to' => NotificationService::AUDIENCE_CUSTOMER,
            'business_module' => BusinessModule::WHOLESALE,
        ])->exists());
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function enableMessage(string $key, string $message): void
    {
        DB::table('notification_messages')->updateOrInsert(
            ['business_module' => BusinessModule::WHOLESALE, 'user_type' => NotificationUserType::DELIVERY_MAN, 'key' => $key],
            ['message' => $message, 'status' => 1, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function sendDeliveryManOrderNotification(string $key, Order $order): array
    {
        $notifier = new class
        {
            use PushNotificationTrait;

            public array $sent = [];

            public function notify(string $key, Order $order): void
            {
                $this->sendOrderNotification(key: $key, type: NotificationUserType::DELIVERY_MAN, order: $order);
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
}
