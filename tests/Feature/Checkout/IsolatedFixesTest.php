<?php

namespace Tests\Feature\Checkout;

use App\Enums\BusinessModule;
use App\Events\OrderPaymentReceivedEvent;
use App\Listeners\OrderPaymentReceivedListener;
use App\Models\Admin;
use App\Models\BusinessSetting;
use App\Models\NotificationMessage;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Traits\PushNotificationTrait;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class IsolatedFixesTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const ORDER_STATUS_KEY = 'pending';

    private const MESSAGE_KEY = 'order_pending_message';

    private const RETAIL_TEMPLATE = 'Retail pending template for isolated fixes';

    private const WHOLESALE_TEMPLATE = 'Wholesale pending template for isolated fixes';

    private const GUEST_CHECKOUT_KEY = 'wholesale_guest_checkout_status';

    private const BILLING_ADDRESS_KEY = 'wholesale_billing_address_status';

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/isolated-fixes-views';

    private const STUB_ADMIN_LAYOUT = self::STUB_VIEW_ROOT.'/layouts/admin/app.blade.php';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_ADMIN_LAYOUT)) {
            unlink(self::STUB_ADMIN_LAYOUT);
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_wholesale_order_push_uses_the_wholesale_template(): void
    {
        $this->storeTemplate(businessModule: BusinessModule::RETAIL, message: self::RETAIL_TEMPLATE);
        $this->storeTemplate(businessModule: BusinessModule::WHOLESALE, message: self::WHOLESALE_TEMPLATE);

        $sentNotifications = $this->sendCustomerOrderNotification(order: $this->orderFor(businessModule: BusinessModule::WHOLESALE));

        $this->assertSame([self::WHOLESALE_TEMPLATE], array_column($sentNotifications, 'description'));
    }

    public function test_wholesale_order_push_falls_back_to_retail_without_a_wholesale_row(): void
    {
        $this->storeTemplate(businessModule: BusinessModule::RETAIL, message: self::RETAIL_TEMPLATE);
        $this->removeTemplate(businessModule: BusinessModule::WHOLESALE);

        $sentNotifications = $this->sendCustomerOrderNotification(order: $this->orderFor(businessModule: BusinessModule::WHOLESALE));

        $this->assertSame([self::RETAIL_TEMPLATE], array_column($sentNotifications, 'description'));
    }

    public function test_retail_order_push_uses_the_retail_template(): void
    {
        $this->storeTemplate(businessModule: BusinessModule::RETAIL, message: self::RETAIL_TEMPLATE);
        $this->storeTemplate(businessModule: BusinessModule::WHOLESALE, message: self::WHOLESALE_TEMPLATE);

        $sentNotifications = $this->sendCustomerOrderNotification(order: $this->orderFor(businessModule: BusinessModule::RETAIL));

        $this->assertSame([self::RETAIL_TEMPLATE], array_column($sentNotifications, 'description'));
    }

    public function test_order_without_business_module_uses_retail(): void
    {
        $this->storeTemplate(businessModule: BusinessModule::RETAIL, message: self::RETAIL_TEMPLATE);
        $this->storeTemplate(businessModule: BusinessModule::WHOLESALE, message: self::WHOLESALE_TEMPLATE);

        $order = $this->orderFor(businessModule: BusinessModule::WHOLESALE);
        unset($order['business_module']);

        $this->assertArrayNotHasKey('business_module', $order->getAttributes());

        $sentNotifications = $this->sendCustomerOrderNotification(order: $order);

        $this->assertSame([self::RETAIL_TEMPLATE], array_column($sentNotifications, 'description'));
    }

    public function test_order_config_update_ignores_guest_checkout(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        config(['app.mode' => 'live']);
        $storedGuestCheckout = $this->storedSetting(settingKey: self::GUEST_CHECKOUT_KEY);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.settings.wholesale.order-config'), [
                self::GUEST_CHECKOUT_KEY => 'not-a-boolean',
                self::BILLING_ADDRESS_KEY => 1,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($storedGuestCheckout, $this->storedSetting(settingKey: self::GUEST_CHECKOUT_KEY));
        $this->assertSame('1', (string) $this->storedSetting(settingKey: self::BILLING_ADDRESS_KEY));
    }

    public function test_order_config_page_does_not_render_guest_checkout(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->stubAdminLayout();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.wholesale.order-config'))
            ->assertOk()
            ->assertSee('name="'.self::BILLING_ADDRESS_KEY.'"', false)
            ->assertDontSee('name="'.self::GUEST_CHECKOUT_KEY.'"', false);
    }

    public function test_retail_payment_hooks_exist_after_boot(): void
    {
        $this->assertTrue(function_exists('retail_payment_success'));
        $this->assertTrue(function_exists('retail_payment_fail'));
    }

    public function test_retail_payment_hook_hands_the_payment_to_the_order_payment_listener(): void
    {
        Event::fake([OrderPaymentReceivedEvent::class]);
        $paymentRequest = (new PaymentRequest)->forceFill(['is_paid' => 1, 'transaction_id' => 'RETAIL-HOOK-EVENT']);

        retail_payment_success($paymentRequest);

        Event::assertDispatched(OrderPaymentReceivedEvent::class, static fn (OrderPaymentReceivedEvent $event): bool => $event->paymentRequest === $paymentRequest);
        Event::assertListening(OrderPaymentReceivedEvent::class, OrderPaymentReceivedListener::class);
    }

    /** @return array<int, array<string, mixed>> */
    private function sendCustomerOrderNotification(Order $order): array
    {
        $notifier = new class
        {
            use PushNotificationTrait;

            /** @var array<int, array<string, mixed>> */
            public array $sentNotifications = [];

            public function notify(string $key, Order $order): void
            {
                $this->sendOrderNotification(key: $key, type: 'customer', order: $order);
            }

            protected function sendPushNotificationToDevice(string $fcmToken, array $data): bool|string
            {
                $this->sentNotifications[] = $data;

                return true;
            }
        };

        $notifier->notify(key: self::ORDER_STATUS_KEY, order: $order);

        return $notifier->sentNotifications;
    }

    private function orderFor(string $businessModule): Order
    {
        $customer = new User(['f_name' => 'Isolated', 'l_name' => 'Fixes']);
        $customer->forceFill(['cm_firebase_token' => 'isolated-fixes-token', 'app_language' => null]);

        $order = new Order;
        $order->forceFill(['id' => 1, 'business_module' => $businessModule]);
        $order->setRelation('customer', $customer);
        $order->setRelation('seller', null);
        $order->setRelation('deliveryMan', null);

        return $order;
    }

    private function storeTemplate(string $businessModule, string $message): void
    {
        $template = NotificationMessage::query()->updateOrCreate(
            ['business_module' => $businessModule, 'key' => self::MESSAGE_KEY, 'user_type' => 'customer'],
            ['message' => $message, 'status' => 1],
        );

        $template->translations()->delete();
    }

    private function removeTemplate(string $businessModule): void
    {
        NotificationMessage::query()
            ->where(['business_module' => $businessModule, 'key' => self::MESSAGE_KEY, 'user_type' => 'customer'])
            ->get()
            ->each(static function (NotificationMessage $template): void {
                $template->translations()->delete();
                $template->delete();
            });
    }

    private function storedSetting(string $settingKey): mixed
    {
        return BusinessSetting::query()->where('type', $settingKey)->value('value');
    }

    private function stubAdminLayout(): void
    {
        if (! is_dir(dirname(self::STUB_ADMIN_LAYOUT))) {
            mkdir(dirname(self::STUB_ADMIN_LAYOUT), 0755, true);
        }

        file_put_contents(self::STUB_ADMIN_LAYOUT, "@yield('content')");
        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
