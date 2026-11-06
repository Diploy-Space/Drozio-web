<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Http\Requests\Customer\Order\ListRequest;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\OrderService;
use App\Utils\Helpers;
use App\View\Composers\WebConfigComposer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class CustomerOrderTabsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private const LINE_QUANTITY = 3;

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->bootActiveTheme();
        $this->bootStorefrontViewData();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
        Event::fake([OrderStatusEvent::class]);
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_retail_list_shows_both_tabs_with_counts_and_retail_active(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $this->createOrder(customer: $customer, businessModule: BusinessModule::RETAIL);
        $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($customer, 'customer')->get(route('account-oder'))->assertOk();

        $tabs = $response->viewData('moduleTabs');
        $this->assertCount(2, $tabs);
        $this->assertSame([true, false], array_column($tabs, 'is_active'));
        $this->assertSame([$this->orderCount($customer, BusinessModule::RETAIL), $this->orderCount($customer, BusinessModule::WHOLESALE)], array_column($tabs, 'count'));
        $this->assertSame(route('wholesale.orders.my-orders'), $tabs[1]['url']);
    }

    public function test_wholesale_list_lists_only_wholesale_orders_with_wholesale_tab_active(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $retailOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::RETAIL);
        $wholesaleOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $response = $this->actingAs($customer, 'customer')->get(route('wholesale.orders.my-orders'))->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($wholesaleOrder->id, $orderIds);
        $this->assertNotContains($retailOrder->id, $orderIds);
        $this->assertSame([false, true], array_column($response->viewData('moduleTabs'), 'is_active'));

        $listedOrder = $response->viewData('orders')->firstWhere('id', $wholesaleOrder->id);
        $this->assertSame(self::LINE_QUANTITY, (int) $listedOrder->order_details_sum_qty);
        $this->assertSame('wholesale.orders.details', $response->viewData('orderRoutes')['details']);
        $response->assertSee(route('wholesale.orders.details', ['id' => $wholesaleOrder->id]), false);
    }

    public function test_status_filter_narrows_the_list_and_keeps_the_filter_on_pagination(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $pendingOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);
        $deliveredOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE, attributes: ['order_status' => OrderService::STATUS_DELIVERED]);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.my-orders', ['status' => OrderService::STATUS_DELIVERED]))
            ->assertOk();

        $orderIds = $response->viewData('orders')->pluck('id')->all();
        $this->assertContains($deliveredOrder->id, $orderIds);
        $this->assertNotContains($pendingOrder->id, $orderIds);
        $this->assertStringContainsString('status='.OrderService::STATUS_DELIVERED, $response->viewData('orders')->url(2));
        $this->assertTrue(collect($response->viewData('statusFilterOptions'))->firstWhere('value', OrderService::STATUS_DELIVERED)['is_selected']);

        $retailResponse = $this->actingAs($customer, 'customer')
            ->get(route('account-oder', ['status' => OrderService::STATUS_DELIVERED]))
            ->assertOk();

        $this->assertTrue($retailResponse->viewData('orders')->getCollection()->every(fn (Order $order): bool => $order->order_status === OrderService::STATUS_DELIVERED));
    }

    public function test_unknown_status_filter_is_rejected(): void
    {
        $this->actingAs($this->customer(), 'customer')
            ->from(route('account-oder'))
            ->get(route('account-oder', ['status' => 'archived']))
            ->assertRedirect(route('account-oder'))
            ->assertSessionHasErrors('status');
    }

    public function test_tabs_are_hidden_when_wholesale_is_unavailable(): void
    {
        $businessModuleManager = $this->mock(BusinessModuleManager::class);
        $businessModuleManager->shouldReceive('isAvailable')->andReturnFalse();
        $businessModuleManager->shouldReceive('available')->andReturn([]);

        $response = $this->actingAs($this->customer(), 'customer')->get(route('account-oder'))->assertOk();

        $this->assertSame([], $response->viewData('moduleTabs'));
    }

    #[DataProvider('detailRoutes')]
    public function test_detail_pages_render_for_the_owner_only(string $routeName): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        [$owner, $otherCustomer] = $this->customerPair();
        $order = $this->createOrder(customer: $owner, businessModule: BusinessModule::WHOLESALE);
        $retailOrder = $this->createOrder(customer: $owner, businessModule: BusinessModule::RETAIL);

        $response = $this->actingAs($owner, 'customer')->get(route($routeName, ['id' => $order->id]))->assertOk();
        $this->assertSame($order->id, $response->viewData('orderHeader')['id']);

        $this->actingAs($otherCustomer, 'customer')
            ->get(route($routeName, ['id' => $order->id]))
            ->assertRedirect(route('wholesale.orders.my-orders'));

        $this->actingAs($owner, 'customer')
            ->get(route($routeName, ['id' => $retailOrder->id]))
            ->assertRedirect(route('wholesale.orders.my-orders'));
    }

    public static function detailRoutes(): array
    {
        return [
            'summary' => ['wholesale.orders.details'],
            'vendor info' => ['wholesale.orders.details.vendor-info'],
            'delivery man info' => ['wholesale.orders.details.delivery-man-info'],
            'reviews' => ['wholesale.orders.details.reviews'],
            'track order' => ['wholesale.orders.details.track-order'],
        ];
    }

    public function test_profile_sidebar_links_the_base_module_url_on_every_page(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        foreach ([route('wholesale.orders.my-orders'), route('wholesale.orders.details', ['id' => $order->id])] as $url) {
            $this->actingAs($customer, 'customer')->get($url)
                ->assertOk()
                ->assertSee('href="'.e(route('wishlists.index')).'"', false)
                ->assertSee('href="'.e(route('account-oder')).'"', false);
        }
    }

    public function test_module_tabs_carry_the_module_as_a_trailing_url_segment(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();

        $response = $this->actingAs($customer, 'customer')->get(route('wishlists.index'))->assertOk();

        $this->assertSame(
            [route('wishlists.index'), route('wholesale.wishlist.index')],
            array_column($response->viewData('moduleTabs'), 'url'),
        );
        $this->assertStringEndsWith('/customer/wishlist/wholesale', route('wholesale.wishlist.index'));
    }

    public function test_guest_is_sent_to_login_from_wholesale_orders(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->get(route('wholesale.orders.my-orders'))->assertRedirect();
    }

    public function test_owner_cancels_a_pending_cash_on_delivery_order(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $details = $this->actingAs($customer, 'customer')->get(route('wholesale.orders.details', ['id' => $order->id]));
        $this->assertTrue($details->viewData('orderHeader')['can_cancel']);

        $this->actingAs($customer, 'customer')
            ->from(route('wholesale.orders.details', ['id' => $order->id]))
            ->post(route('wholesale.orders.cancel', ['id' => $order->id]))
            ->assertRedirect(route('wholesale.orders.details', ['id' => $order->id]));

        $this->assertSame(OrderService::STATUS_CANCELED, $order->refresh()->order_status);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => OrderService::STATUS_CANCELED, 'user_type' => 'customer', 'user_id' => $customer->id]);
    }

    public function test_offline_payment_details_render_for_the_customer(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE, attributes: [
            'payment_method' => OrderService::PAYMENT_METHOD_OFFLINE,
            'payment_note' => 'Paid at the branch counter',
        ]);
        DB::table('offline_payments')->insert([
            'order_id' => $order->id,
            'payment_info' => json_encode(['method_id' => 1, 'method_name' => 'Bank Payment', 'account_number' => '876543']),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.details', ['id' => $order->id]))
            ->assertOk()
            ->assertSee('876543')
            ->assertSee('Paid at the branch counter')
            ->assertSee(translate('See payment details'));

        $this->assertSame(['Method name', 'Account number'], array_column($response->viewData('offlinePayment')['fields'], 'label'));

        $cashOnDeliveryOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE);

        $this->actingAs($customer, 'customer')
            ->get(route('wholesale.orders.details', ['id' => $cashOnDeliveryOrder->id]))
            ->assertOk()
            ->assertViewHas('offlinePayment', null)
            ->assertDontSee(translate('See payment details'));
    }

    #[DataProvider('uncancelableOrders')]
    public function test_cancel_is_refused_outside_the_retail_rule(array $attributes): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $order = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE, attributes: $attributes);

        $this->assertFalse($this->actingAs($customer, 'customer')->get(route('wholesale.orders.details', ['id' => $order->id]))->viewData('orderHeader')['can_cancel']);

        $this->actingAs($customer, 'customer')->post(route('wholesale.orders.cancel', ['id' => $order->id]))->assertRedirect();

        $this->assertSame($attributes['order_status'] ?? OrderService::STATUS_PENDING, $order->refresh()->order_status);
    }

    public static function uncancelableOrders(): array
    {
        return [
            'offline payment' => [['payment_method' => OrderService::PAYMENT_METHOD_OFFLINE]],
            'wallet payment' => [['payment_method' => 'pay_by_wallet', 'payment_status' => OrderService::PAYMENT_STATUS_PAID]],
            'confirmed cash on delivery' => [['order_status' => OrderService::STATUS_CONFIRMED]],
        ];
    }

    public function test_another_customer_cannot_cancel_or_download_the_order(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        [$owner, $otherCustomer] = $this->customerPair();
        $order = $this->createOrder(customer: $owner, businessModule: BusinessModule::WHOLESALE);

        $this->actingAs($otherCustomer, 'customer')->post(route('wholesale.orders.cancel', ['id' => $order->id]))->assertRedirect(route('wholesale.orders.my-orders'));
        $this->actingAs($otherCustomer, 'customer')->get(route('wholesale.orders.invoice', ['id' => $order->id]))->assertRedirect(route('wholesale.orders.my-orders'));

        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
    }

    public function test_delivery_man_review_needs_a_paid_wholesale_order_with_a_delivery_man(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $customer = $this->customer();
        $deliveryMan = $this->createDeliveryMan();
        $paidOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE, attributes: [
            'order_status' => OrderService::STATUS_DELIVERED,
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            'delivery_man_id' => $deliveryMan->id,
        ]);
        $unpaidOrder = $this->createOrder(customer: $customer, businessModule: BusinessModule::WHOLESALE, attributes: ['delivery_man_id' => $deliveryMan->id]);

        $this->actingAs($customer, 'customer')
            ->post(route('wholesale.orders.delivery-man-review'), ['order_id' => $paidOrder->id, 'rating' => 4, 'comment' => 'On time'])
            ->assertRedirect();
        $this->assertDatabaseHas('reviews', ['order_id' => $paidOrder->id, 'delivery_man_id' => $deliveryMan->id, 'customer_id' => $customer->id, 'rating' => 4]);

        $this->actingAs($customer, 'customer')
            ->post(route('wholesale.orders.delivery-man-review'), ['order_id' => $unpaidOrder->id, 'rating' => 4, 'comment' => 'On time'])
            ->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['order_id' => $unpaidOrder->id, 'delivery_man_id' => $deliveryMan->id]);
    }

    public function test_web_list_request_messages_cover_every_rule(): void
    {
        $request = ListRequest::create('/account-oder', Request::METHOD_GET, ['status' => 'archived', 'page' => 0]);
        $validator = validator($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame($request->messages()['status.in'], $validator->errors()->first('status'));
        $this->assertSame($request->messages()['page.min'], $validator->errors()->first('page'));
        $this->assertArrayHasKey('page.integer', $request->messages());
    }

    private function orderCount(User $customer, string $businessModule): int
    {
        return Order::query()->where(['customer_id' => $customer->id, 'is_guest' => 0, 'business_module' => $businessModule])->count();
    }

    private function createOrder(User $customer, string $businessModule, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'seller_id' => self::IN_HOUSE_SELLER_ID,
            'seller_is' => 'admin',
            'order_type' => 'default_type',
            'order_group_id' => 'customer-order-tabs-'.Str::lower(Str::random(16)),
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
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
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
            'product_details' => json_encode(['id' => $product->id, 'name' => 'Order tabs fixture', 'product_type' => 'physical']),
            'qty' => self::LINE_QUANTITY,
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
            'slug' => 'customer-order-tabs-'.Str::lower(Str::random(12)),
            'code' => 'COTAB'.random_int(10000, 99999),
        ])->save();

        return $product;
    }

    private function createDeliveryMan(): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => 0,
            'f_name' => 'Order',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'order-tabs-rider-'.Str::lower(Str::random(10)).'@example.test',
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

    /** @return array{0: User, 1: User} */
    private function customerPair(): array
    {
        $customers = User::query()->where('id', '>', 0)->orderBy('id')->limit(2)->get()->all();

        if (count($customers) < 2) {
            $this->markTestSkipped('Test database has fewer than 2 customers.');
        }

        return $customers;
    }

    private function bootStorefrontViewData(): void
    {
        Paginator::useBootstrap();
        Helpers::currency_load();
        View::share('language', getWebConfig(name: 'language') ?? []);
        View::composer('*', WebConfigComposer::class);
    }

    private function bootActiveTheme(): void
    {
        $themePath = base_path('resources/themes/'.config('app.theme'));

        if (! defined('VIEW_FILE_NAMES')) {
            define('VIEW_FILE_NAMES', include $themePath.'/file_names.php');
        }

        $this->app['view']->addLocation($themePath);
    }
}
