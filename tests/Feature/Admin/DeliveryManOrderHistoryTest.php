<?php

namespace Tests\Feature\Admin;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Exports\DeliveryManOrderHistory;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\OrderService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class DeliveryManOrderHistoryTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/delivery-man-order-history-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_order_history_log_lists_orders_from_every_module(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log', ['id' => $deliveryMan->id]))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($retailOrder->id, $orderIds);
        $this->assertContains($wholesaleOrder->id, $orderIds);
    }

    public function test_wholesale_filter_narrows_order_history_log(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log', ['id' => $deliveryMan->id, 'business_module' => BusinessModule::WHOLESALE]))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($wholesaleOrder->id, $orderIds);
        $this->assertNotContains($retailOrder->id, $orderIds);
    }

    public function test_unknown_business_module_value_is_ignored(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log', ['id' => $deliveryMan->id, 'business_module' => 'not-a-real-module']))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($retailOrder->id, $orderIds);
        $this->assertContains($wholesaleOrder->id, $orderIds);
    }

    public function test_filter_select_absent_when_wholesale_unavailable(): void
    {
        $this->mock(BusinessModuleManager::class)->shouldReceive('isAvailableInPanel')->andReturnFalse();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log', ['id' => $deliveryMan->id]))
            ->assertOk();

        $this->assertFalse($response->viewData('wholesaleAvailable'));
        $response->assertDontSee('name="business_module"', false);

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($retailOrder->id, $orderIds);
        $this->assertNotContains($wholesaleOrder->id, $orderIds);
    }

    public function test_earning_statement_lists_orders_from_every_module(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-wise-earning', ['id' => $deliveryMan->id]))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($retailOrder->id, $orderIds);
        $this->assertContains($wholesaleOrder->id, $orderIds);
    }

    public function test_earning_ajax_filter_narrows_by_module(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-wise-earning-list-by-filter', ['id' => $deliveryMan->id, 'business_module' => BusinessModule::RETAIL]))
            ->assertOk();

        $view = $response->json('view');
        $this->assertStringContainsString((string) $retailOrder->id, $view);
        $this->assertStringNotContainsString((string) $wholesaleOrder->id, $view);
    }

    public function test_export_contains_module_column_and_both_orders(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        Excel::fake();

        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log-export', ['id' => $deliveryMan->id, 'type' => 'log']))
            ->assertOk();

        Excel::assertDownloaded('Delivery-Man-Order-List.xlsx', function (DeliveryManOrderHistory $export) use ($retailOrder, $wholesaleOrder): bool {
            $orders = collect($export->view()->getData()['data']['orders']);
            $byId = $orders->keyBy('id');

            return $orders->pluck('id')->contains($retailOrder->id)
                && $orders->pluck('id')->contains($wholesaleOrder->id)
                && $byId[$retailOrder->id]->module_label === translate('Retail')
                && $byId[$wholesaleOrder->id]->module_label === translate('Wholesale');
        });
    }

    public function test_another_deliverymans_orders_never_appear(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $deliveryMan = $this->createDeliveryMan();
        $otherDeliveryMan = $this->createDeliveryMan();
        $ownOrder = $this->createOrder(deliveryManId: $deliveryMan->id, businessModule: BusinessModule::WHOLESALE);
        $otherOrder = $this->createOrder(deliveryManId: $otherDeliveryMan->id, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.people.delivery-man.order-history-log', ['id' => $deliveryMan->id]))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($ownOrder->id, $orderIds);
        $this->assertNotContains($otherOrder->id, $orderIds);
    }

    private function createOrder(int $deliveryManId, string $businessModule, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'customer_id' => $this->customer()->id,
            'is_guest' => 0,
            'seller_id' => 1,
            'seller_is' => 'admin',
            'order_type' => 'default_type',
            'order_group_id' => 'deliveryman-history-'.Str::lower(Str::random(16)),
            'order_status' => OrderService::STATUS_PENDING,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 75,
            'admin_commission' => 0,
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
            'edited_status' => 0,
            'delivery_man_id' => $deliveryManId,
            'deliveryman_charge' => 5,
            'delivery_type' => null,
            'is_pause' => 0,
            'created_at' => now(),
            ...$attributes,
        ])->save();

        $this->createDetail(order: $order);

        return $order;
    }

    private function createDetail(Order $order): void
    {
        $product = $this->createProduct(businessModule: $order->business_module);

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $product->id, 'name' => 'Deliveryman history fixture', 'product_type' => 'physical']),
            'qty' => 2,
            'price' => 25,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => null,
            'variation' => [],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
            'is_stock_decreased' => 0,
        ])->save();
    }

    private function createProduct(string $businessModule): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product');
        $product = $template->replicate();
        $product->forceFill([
            'business_module' => $businessModule,
            'slug' => 'deliveryman-history-'.Str::lower(Str::random(12)),
            'code' => 'DMHIST'.random_int(10000, 99999),
        ])->save();

        return $product;
    }

    private function createDeliveryMan(): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => 0,
            'f_name' => 'History',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'history-rider-'.Str::lower(Str::random(10)).'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => 1,
        ])->save();

        return $deliveryMan;
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

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }

    private function stubAdminLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }
}
