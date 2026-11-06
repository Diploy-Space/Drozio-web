<?php

namespace Tests\Feature\Wholesale\PushNotification;

use App\Enums\BusinessModule;
use App\Enums\NotificationUserType;
use App\Events\OrderStatusEvent;
use App\Events\ProductRequestStatusUpdateEvent;
use App\Events\RefundEvent;
use App\Listeners\ProductRequestStatusUpdateListener;
use App\Listeners\RefundListener;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use App\Models\User;
use App\Services\New\PushNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Enums\CampaignShopStatus;
use Modules\Wholesale\app\Enums\OrderActor;
use Modules\Wholesale\app\Enums\PushNotificationKey;
use Modules\Wholesale\app\Events\CampaignShopReviewedEvent;
use Modules\Wholesale\app\Events\ProductReviewSubmittedEvent;
use Modules\Wholesale\app\Listeners\CampaignShopReviewedListener;
use Modules\Wholesale\app\Listeners\ProductReviewSubmittedListener;
use Modules\Wholesale\app\Models\Campaign;
use Modules\Wholesale\app\Models\CampaignShop;
use Modules\Wholesale\app\Services\OrderService;
use Modules\Wholesale\app\Services\OrderStatusService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class VendorAppTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const SELLER_TOKEN = 'wholesale-vendor-app-token';

    private const SEED_MIGRATION = 'Modules/Wholesale/database/migrations/2026_09_27_100020_seed_wholesale_seller_push_notification_messages.php';

    private const NEW_SELLER_KEYS = [
        'order_confirmation_message',
        'order_processing_message',
        'out_for_delivery_message',
        'order_returned_message',
        'order_failed_message',
        'order_payment_confirmed_message',
        'campaign_join_request_approved_message',
        'campaign_join_request_denied_message',
        'new_product_review_message',
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

    public function test_new_seller_keys_are_listed_for_wholesale_only(): void
    {
        $service = app(PushNotificationService::class);
        $wholesaleKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_WHOLESALE, userType: NotificationUserType::SELLER);
        $retailKeys = $service->getMessageKeyData(businessModule: PushNotificationService::MODULE_RETAIL, userType: NotificationUserType::SELLER);

        foreach (self::NEW_SELLER_KEYS as $key) {
            $this->assertContains($key, $wholesaleKeys);
            $this->assertNotContains($key, $retailKeys);
        }
    }

    public function test_seed_migration_creates_each_row_once(): void
    {
        $migration = require base_path(self::SEED_MIGRATION);

        $migration->up();
        $migration->up();

        foreach (self::NEW_SELLER_KEYS as $key) {
            $this->assertSame(1, DB::table('notification_messages')->where([
                'business_module' => BusinessModule::WHOLESALE,
                'user_type' => NotificationUserType::SELLER,
                'key' => $key,
                'status' => 1,
            ])->count());
        }
    }

    #[DataProvider('vendorNotifiedStatuses')]
    public function test_vendor_hears_about_each_status_on_a_vendor_order(string $orderStatus): void
    {
        Event::fake([OrderStatusEvent::class]);
        $order = $this->vendorOrder();

        $result = app(OrderStatusService::class)->changeStatus(orderId: $order->id, orderStatus: $orderStatus, actorType: OrderActor::ADMIN, actorId: 1, sellerId: null);

        $this->assertNull($result['blocker']);
        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === $orderStatus
            && $event->type === OrderService::NOTIFICATION_TYPE_SELLER
            && (int) $event->order['id'] === (int) $order->id);
    }

    public static function vendorNotifiedStatuses(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return [
                'confirmed' => [OrderService::STATUS_CONFIRMED],
                'processing' => [OrderService::STATUS_PROCESSING],
                'out for delivery' => [OrderService::STATUS_OUT_FOR_DELIVERY],
                'failed' => [OrderService::STATUS_FAILED],
                'returned' => [OrderService::STATUS_RETURNED],
                'canceled' => [OrderService::STATUS_CANCELED],
            ];
        });
    }

    public function test_payment_confirmed_reaches_the_vendor_of_a_vendor_order(): void
    {
        Event::fake([OrderStatusEvent::class]);
        $order = $this->vendorOrder(['payment_method' => 'offline_payment']);

        app(OrderService::class)->updatePaymentStatus(orderId: $order->id, paymentStatus: OrderService::PAYMENT_STATUS_PAID, actorType: OrderActor::ADMIN, sellerId: null);

        Event::assertDispatched(OrderStatusEvent::class, fn (OrderStatusEvent $event): bool => $event->key === OrderService::NOTIFICATION_ORDER_PAYMENT_CONFIRMED
            && $event->type === OrderService::NOTIFICATION_TYPE_SELLER);
    }

    public function test_refund_request_sends_the_vendor_the_request_key(): void
    {
        $this->enableMessage(key: 'refund_request_message', message: 'Refund requested on {orderId}');
        $order = $this->vendorOrder();
        $orderDetail = OrderDetail::query()->where('order_id', $order->id)->firstOrFail();

        $listener = new class extends RefundListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new RefundEvent(status: RefundListener::STATUS_REFUND_REQUESTED, order: $order, refund: ['id' => 77], orderDetails: $orderDetail));

        $vendorPush = collect($listener->sent)->firstWhere('type', 'refund');
        $this->assertSame('refund_request_message', $vendorPush['message_key']);
        $this->assertSame(77, (int) $vendorPush['refund_id']);
        $this->assertSame((int) $orderDetail->id, (int) $vendorPush['order_details_id']);
    }

    public function test_approving_and_denying_a_wholesale_product_notifies_its_vendor(): void
    {
        Event::fake([ProductRequestStatusUpdateEvent::class]);
        $approved = $this->pendingVendorProduct();
        $denied = $this->pendingVendorProduct();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.products.vendor-approve'), ['id' => $approved->id])
            ->assertOk();
        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.wholesale.products.vendor-deny'), ['id' => $denied->id, 'denied_note' => 'Missing images'])
            ->assertOk();

        Event::assertDispatched(ProductRequestStatusUpdateEvent::class, fn (ProductRequestStatusUpdateEvent $event): bool => $event->key === PushNotificationKey::PRODUCT_REQUEST_APPROVED
            && $event->businessModule === BusinessModule::WHOLESALE
            && $event->productId === (int) $approved->id
            && $event->fcmToken === self::SELLER_TOKEN);
        Event::assertDispatched(ProductRequestStatusUpdateEvent::class, fn (ProductRequestStatusUpdateEvent $event): bool => $event->key === PushNotificationKey::PRODUCT_REQUEST_REJECTED
            && $event->productId === (int) $denied->id);
    }

    public function test_product_request_push_carries_the_product_id_and_wholesale_template(): void
    {
        $this->enableMessage(key: PushNotificationKey::PRODUCT_REQUEST_APPROVED, message: 'Wholesale product approved');

        $listener = new class extends ProductRequestStatusUpdateListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new ProductRequestStatusUpdateEvent(
            key: PushNotificationKey::PRODUCT_REQUEST_APPROVED,
            type: NotificationUserType::SELLER,
            lang: 'en',
            fcmToken: self::SELLER_TOKEN,
            businessModule: BusinessModule::WHOLESALE,
            productId: 501,
        ));

        $this->assertSame('Wholesale product approved', $listener->sent[0]['description']);
        $this->assertSame(501, (int) $listener->sent[0]['product_id']);
    }

    #[DataProvider('businessModules')]
    public function test_product_request_push_names_its_business_module(string $businessModule): void
    {
        $this->enableMessage(key: PushNotificationKey::PRODUCT_REQUEST_REJECTED, message: 'Product denied', businessModule: $businessModule);

        $listener = new class extends ProductRequestStatusUpdateListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new ProductRequestStatusUpdateEvent(
            key: PushNotificationKey::PRODUCT_REQUEST_REJECTED,
            type: NotificationUserType::SELLER,
            lang: 'en',
            fcmToken: self::SELLER_TOKEN,
            businessModule: $businessModule,
        ));

        $this->assertSame($businessModule, $listener->sent[0]['business_module']);
    }

    #[DataProvider('businessModules')]
    public function test_refund_request_push_names_its_business_module_to_the_vendor(string $businessModule): void
    {
        $this->enableMessage(key: 'refund_request_message', message: 'Refund requested on {orderId}', businessModule: $businessModule);
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $deliveryMan = $this->createDeliveryMan(['seller_id' => $this->seller()->id]);
        $order = $businessModule === BusinessModule::WHOLESALE
            ? $this->createWholesaleOrder($deliveryMan, ['customer_id' => $customer->id])
            : $this->createRetailOrder($deliveryMan, ['customer_id' => $customer->id]);

        $listener = new class extends RefundListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new RefundEvent(status: RefundListener::STATUS_REFUND_REQUESTED, order: $order, refund: ['id' => 78], orderDetails: null));

        $vendorPush = collect($listener->sent)->firstWhere('type', 'refund');
        $this->assertSame($businessModule, $vendorPush['business_module']);
    }

    public static function businessModules(): array
    {
        return [
            'retail' => [BusinessModule::RETAIL],
            'wholesale' => [BusinessModule::WHOLESALE],
        ];
    }

    public function test_retail_product_request_event_keeps_its_positional_signature(): void
    {
        $event = new ProductRequestStatusUpdateEvent('product_request_approved_message', 'seller', 'en', 'token');

        $this->assertSame(BusinessModule::RETAIL, $event->businessModule);
        $this->assertNull($event->productId);
    }

    public function test_campaign_join_approval_and_denial_dispatch_the_result(): void
    {
        Event::fake([CampaignShopReviewedEvent::class]);
        $approved = $this->pendingParticipation();
        $denied = $this->pendingParticipation();

        $this->actingAs($this->admin(), 'admin')->post(route('admin.wholesale.campaign.shop.approve', ['shop' => $approved->id]));
        $this->actingAs($this->admin(), 'admin')->post(route('admin.wholesale.campaign.shop.deny', ['shop' => $denied->id]), ['deny_note' => 'Discount too low']);

        Event::assertDispatched(CampaignShopReviewedEvent::class, fn (CampaignShopReviewedEvent $event): bool => (int) $event->participation->id === (int) $approved->id
            && $event->notificationKey === PushNotificationKey::CAMPAIGN_JOIN_REQUEST_APPROVED);
        Event::assertDispatched(CampaignShopReviewedEvent::class, fn (CampaignShopReviewedEvent $event): bool => (int) $event->participation->id === (int) $denied->id
            && $event->notificationKey === PushNotificationKey::CAMPAIGN_JOIN_REQUEST_DENIED);
    }

    public function test_campaign_push_carries_the_campaign_id(): void
    {
        $this->enableMessage(key: PushNotificationKey::CAMPAIGN_JOIN_REQUEST_APPROVED, message: 'You are in the campaign');
        $participation = $this->pendingParticipation();

        $listener = new class extends CampaignShopReviewedListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new CampaignShopReviewedEvent(participation: $participation, notificationKey: PushNotificationKey::CAMPAIGN_JOIN_REQUEST_APPROVED));

        $this->assertSame('campaign', $listener->sent[0]['type']);
        $this->assertSame((int) $participation->campaign_id, (int) $listener->sent[0]['campaign_id']);
        $this->assertSame('You are in the campaign', $listener->sent[0]['description']);
    }

    public function test_a_new_api_review_dispatches_once_and_an_update_does_not(): void
    {
        Event::fake([ProductReviewSubmittedEvent::class]);
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $order = $this->createWholesaleOrder($this->createDeliveryMan(), [
            'customer_id' => $customer->id,
            'order_status' => OrderService::STATUS_DELIVERED,
        ]);
        $productId = (int) OrderDetail::query()->where('order_id', $order->id)->value('product_id');
        $payload = ['product_id' => $productId, 'order_id' => $order->id, 'comment' => 'Solid batch', 'rating' => 5];

        $this->postJson('api/v6/wholesale/customer/reviews', $payload, $this->actingAsCustomer($customer))->assertOk();
        $this->postJson('api/v6/wholesale/customer/reviews', $payload, $this->actingAsCustomer($customer))->assertOk();

        Event::assertDispatchedTimes(ProductReviewSubmittedEvent::class, 1);
    }

    public function test_review_push_carries_product_and_review_ids_for_a_vendor_product(): void
    {
        $this->enableMessage(key: PushNotificationKey::NEW_PRODUCT_REVIEW, message: 'New review');
        $product = $this->pendingVendorProduct();
        $review = new Review;
        $review->forceFill(['id' => random_int(900000, 999999), 'product_id' => $product->id]);

        $listener = new class extends ProductReviewSubmittedListener
        {
            public array $sent = [];

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sent[] = $data;

                return true;
            }
        };
        $listener->handle(new ProductReviewSubmittedEvent(review: $review));

        $this->assertSame('review', $listener->sent[0]['type']);
        $this->assertSame((int) $product->id, (int) $listener->sent[0]['product_id']);
        $this->assertSame((int) $review->id, (int) $listener->sent[0]['review_id']);
    }

    private function seller(): Seller
    {
        $seller = $this->requireFixture(Seller::query()->orderBy('id')->first(), 'seller');
        $seller->forceFill(['cm_firebase_token' => self::SELLER_TOKEN, 'app_language' => 'en'])->save();

        return $seller;
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function vendorOrder(array $overrides = []): Order
    {
        $customer = $this->requireFixture(User::query()->first(), 'customer');
        $deliveryMan = $this->createDeliveryMan(['seller_id' => $this->seller()->id]);

        return $this->createWholesaleOrder($deliveryMan, ['customer_id' => $customer->id, ...$overrides]);
    }

    private function pendingVendorProduct(): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->where('business_module', BusinessModule::WHOLESALE)->orderBy('id')->first(), 'wholesale product');
        $product = $template->replicate();
        $product->forceFill([
            'added_by' => ProductWholesaleService::ADDED_BY_SELLER,
            'user_id' => $this->seller()->id,
            'request_status' => 0,
            'slug' => 'vendor-push-'.Str::lower(Str::random(12)),
            'code' => 'VPN'.random_int(10000, 99999),
        ])->save();

        return $product;
    }

    private function pendingParticipation(): CampaignShop
    {
        $sellerId = $this->seller()->id;
        $template = $this->requireFixture(CampaignShop::query()->orderBy('id')->first(), 'campaign shop');
        $unjoinedCampaignId = $this->requireFixture(
            Campaign::query()->whereNotIn('id', CampaignShop::query()->where('seller_id', $sellerId)->select('campaign_id'))->orderBy('id')->value('id'),
            'campaign the seller has not joined',
        );
        $participation = $template->replicate();
        $participation->forceFill([
            'campaign_id' => $unjoinedCampaignId,
            'seller_id' => $sellerId,
            'status' => CampaignShopStatus::PENDING,
            'deny_note' => null,
        ])->save();

        return $participation;
    }

    private function enableMessage(string $key, string $message, string $businessModule = BusinessModule::WHOLESALE): void
    {
        DB::table('notification_messages')->updateOrInsert(
            ['business_module' => $businessModule, 'user_type' => NotificationUserType::SELLER, 'key' => $key],
            ['message' => $message, 'status' => 1, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
