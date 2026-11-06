<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wholesale\app\Exports\OrderExport;
use Modules\Wholesale\app\Services\OrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOrderListScreenTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private const SNAPSHOT_THUMBNAIL_URL = 'https://cdn.example.test/wholesale/list-thumbnail.png';

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/wholesale-order-list-views';

    private const STUB_LAYOUTS = ['layouts/admin/app', 'layouts/vendor/app'];

    private int $nextOrderId = 0;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubPanelLayouts();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);
    }

    protected function tearDown(): void
    {
        foreach (self::STUB_LAYOUTS as $layout) {
            if (is_file(self::STUB_VIEW_ROOT.'/'.$layout.'.blade.php')) {
                unlink(self::STUB_VIEW_ROOT.'/'.$layout.'.blade.php');
            }
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_admin_list_renders_summary_and_rows(): void
    {
        $order = $this->createOrder(seller: $this->approvedSellers(count: 1)[0]);
        $this->createDetail(order: $order, snapshotName: 'Summary row item');

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all']))
            ->assertOk()
            ->assertSee(route('admin.wholesale.orders.details', [$order->id]), false);

        $statusCounts = $response->viewData('statusCounts');

        $this->assertSame(
            ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'canceled', 'returned', 'failed'],
            array_keys($statusCounts),
        );
        $this->assertSame(8, substr_count($response->getContent(), 'p-20 bg-section rounded'));
        $this->assertTrue($response->viewData('orders')->getCollection()->contains('id', $order->id));
        $this->assertGreaterThanOrEqual(1, $response->viewData('totalCount'));
    }

    public function test_admin_list_excludes_retail_orders(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $wholesaleOrder = $this->createOrder(seller: $seller);
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'searchValue' => $retailOrder->id]))
            ->assertOk()
            ->assertViewHas('orders', static fn ($orders): bool => ! $orders->getCollection()->contains('id', $retailOrder->id));

        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.export', ['all', 'searchValue' => $retailOrder->id]))
            ->assertOk();

        Excel::assertDownloaded('Wholesale-orders.xlsx', fn (OrderExport $export): bool => ! $this->exportedOrderIds(export: $export)->contains($retailOrder->id));

        $this->assertSame(0, app(OrderService::class)->count(filters: ['id' => $retailOrder->id]));
        $this->assertNotNull(app(OrderService::class)->find(id: $wholesaleOrder->id));
    }

    #[DataProvider('listFilters')]
    public function test_each_filter_narrows_rows(string $case, array $expectedFixtures): void
    {
        $fixtures = $this->createFilterFixtures();

        $orders = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', ...$this->queryFor(case: $case, fixtures: $fixtures)]))
            ->assertOk()
            ->viewData('orders')
            ->getCollection();

        foreach (['pendingVendorOrder', 'deliveredInHouseOrder', 'canceledVendorOrder'] as $fixtureKey) {
            $this->assertSame(
                in_array($fixtureKey, $expectedFixtures, true),
                $orders->contains('id', $fixtures[$fixtureKey]->id),
                $case.' → '.$fixtureKey,
            );
        }
    }

    public static function listFilters(): array
    {
        return [
            'F1 date type today' => ['today', ['pendingVendorOrder']],
            'F1 custom date range' => ['custom_date', ['canceledVendorOrder']],
            'F2 in-house orders' => ['source_in_house', ['deliveredInHouseOrder']],
            'F2 vendor orders' => ['source_vendor', ['pendingVendorOrder', 'canceledVendorOrder']],
            'F3 vendor' => ['vendor', ['pendingVendorOrder']],
            'F4 customer' => ['customer', ['pendingVendorOrder', 'canceledVendorOrder']],
            'F5 order status' => ['order_status', ['pendingVendorOrder', 'canceledVendorOrder']],
            'F6 payment status' => ['payment_status', ['deliveredInHouseOrder', 'canceledVendorOrder']],
        ];
    }

    public function test_delivery_man_id_filter_narrows_lists_counts_and_export(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $customer = $this->customers(count: 1)[0];
        $deliveryMan = $this->createDeliveryMan(sellerId: $seller->id);
        $otherDeliveryMan = $this->createDeliveryMan(sellerId: $otherSeller->id);

        $assignedOrder = $this->createOrder(seller: $seller, attributes: [
            'delivery_man_id' => $deliveryMan->id, 'customer_id' => $customer->id, 'order_status' => 'confirmed', 'payment_status' => 'unpaid',
        ]);
        $unassignedOrder = $this->createOrder(seller: $seller, attributes: [
            'delivery_man_id' => null, 'customer_id' => $customer->id, 'order_status' => 'confirmed', 'payment_status' => 'unpaid',
        ]);
        $this->createDetail(order: $assignedOrder, snapshotName: 'Assigned item');
        $this->createDetail(order: $unassignedOrder, snapshotName: 'Unassigned item');

        $adminResponse = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'delivery_man_id' => $deliveryMan->id, 'customer_id' => $customer->id]))
            ->assertOk();

        $adminOrders = $adminResponse->viewData('orders');
        $this->assertTrue($adminOrders->getCollection()->contains('id', $assignedOrder->id));
        $this->assertFalse($adminOrders->getCollection()->contains('id', $unassignedOrder->id));
        $this->assertSame(1, $adminResponse->viewData('statusCounts')['confirmed']);
        $this->assertStringContainsString('delivery_man_id='.$deliveryMan->id, urldecode($adminOrders->url(2)));

        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.export', ['all', 'delivery_man_id' => $deliveryMan->id, 'customer_id' => $customer->id]))
            ->assertOk();

        Excel::assertDownloaded('Wholesale-orders.xlsx', function (OrderExport $export) use ($assignedOrder, $unassignedOrder): bool {
            $orderIds = $this->exportedOrderIds(export: $export);

            return $orderIds->contains($assignedOrder->id) && ! $orderIds->contains($unassignedOrder->id);
        });

        $vendorResponse = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.list', ['all', 'delivery_man_id' => $deliveryMan->id, 'customer_id' => $customer->id]))
            ->assertOk();

        $vendorOrders = $vendorResponse->viewData('orders');
        $this->assertTrue($vendorOrders->getCollection()->contains('id', $assignedOrder->id));
        $this->assertFalse($vendorOrders->getCollection()->contains('id', $unassignedOrder->id));
        $this->assertSame(1, $vendorResponse->viewData('statusCounts')['confirmed']);
        $this->assertStringContainsString('delivery_man_id='.$deliveryMan->id, urldecode($vendorOrders->url(2)));

        $foreignFilterResponse = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.list', ['all', 'delivery_man_id' => $otherDeliveryMan->id]))
            ->assertOk();

        $this->assertCount(0, $foreignFilterResponse->viewData('orders')->getCollection());
    }

    public function test_filter_dot_shows_only_with_active_filters(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'searchValue' => 'none']))
            ->assertOk()
            ->assertViewHas('hasActiveFilters', false)
            ->assertDontSee('wholesale-order-filter-dot', false);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'payment_status' => ['paid']]))
            ->assertOk()
            ->assertViewHas('hasActiveFilters', true)
            ->assertSee('wholesale-order-filter-dot', false);

        $this->actingAs($this->approvedSellers(count: 1)[0], 'seller')
            ->get(route('vendor.wholesale.orders.list', ['all', 'date_type' => 'today']))
            ->assertOk()
            ->assertViewHas('hasActiveFilters', true)
            ->assertSee('wholesale-order-filter-dot', false);
    }

    public function test_sort_links_toggle_direction(): void
    {
        $unsorted = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all']))
            ->assertOk();

        $this->assertSame(OrderService::SORT_NEWEST, $unsorted->viewData('sortKey'));
        $this->assertNull($unsorted->viewData('sortLinks')['order_id']['direction']);
        $this->assertStringContainsString('sort='.OrderService::SORT_ORDER_ID_ASC, $unsorted->viewData('sortLinks')['order_id']['url']);

        $ascending = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'sort' => OrderService::SORT_ORDER_ID_ASC, 'payment_status' => ['paid']]))
            ->assertOk();
        $orderIdLink = $ascending->viewData('sortLinks')['order_id'];

        $this->assertSame('asc', $orderIdLink['direction']);
        $this->assertStringContainsString('sort='.OrderService::SORT_ORDER_ID_DESC, $orderIdLink['url']);
        $this->assertStringContainsString('payment_status', urldecode($orderIdLink['url']));
        $this->assertStringContainsString('sort='.OrderService::SORT_STORE_NAME_ASC, $ascending->viewData('sortLinks')['store_name']['url']);

        $ids = $ascending->viewData('orders')->getCollection()->pluck('id')->all();
        $sortedIds = $ids;
        sort($sortedIds);
        $this->assertSame($sortedIds, $ids);

        $descending = $this->actingAs($this->admin(), 'admin')
            ->get($orderIdLink['url'])
            ->assertOk();

        $this->assertSame('desc', $descending->viewData('sortLinks')['order_id']['direction']);
        $this->assertStringContainsString('sort='.OrderService::SORT_ORDER_ID_ASC, $descending->viewData('sortLinks')['order_id']['url']);
    }

    public function test_items_popover_lists_line_items(): void
    {
        $order = $this->createOrder(seller: $this->approvedSellers(count: 1)[0]);

        foreach (['Popover first line', 'Popover second line', 'Popover third line'] as $lineName) {
            $this->createDetail(order: $order, snapshotName: $lineName);
        }

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['all', 'searchValue' => $order->id]))
            ->assertOk()
            ->assertSee('Popover first line')
            ->assertSee('Popover second line')
            ->assertSee('Popover third line')
            ->assertSee('x 3')
            ->assertSee(setCurrencySymbol(amount: usdToDefaultCurrency(amount: 28)), false)
            ->assertSee('aria-controls="wholesale-order-items-'.$order->id.'"', false);

        $this->assertCount(3, $response->viewData('itemPreviews')[$order->id]);
    }

    public function test_list_runs_constant_queries(): void
    {
        $sellers = $this->approvedSellers(count: 2);
        $customers = $this->customers(count: 2);
        $admin = $this->admin();

        $this->createOrdersWithDetails(count: 5, sellers: $sellers, customers: $customers);
        $this->actingAs($admin, 'admin')->get(route('admin.wholesale.orders.list', ['all']))->assertOk();

        $fewOrdersQueries = $this->countListQueries(admin: $admin);

        $this->createOrdersWithDetails(count: 10, sellers: $sellers, customers: $customers);

        $manyOrdersQueries = $this->countListQueries(admin: $admin);

        $this->assertSame($fewOrdersQueries, $manyOrdersQueries);
    }

    public function test_vendor_list_shows_only_own_orders(): void
    {
        [$ownSeller, $otherSeller] = $this->approvedSellers(count: 2);
        $ownOrder = $this->createOrder(seller: $ownSeller);
        $otherOrder = $this->createOrder(seller: $otherSeller);
        $retailOrder = $this->createOrder(seller: $ownSeller, businessModule: BusinessModule::RETAIL);

        $orders = $this->actingAs($ownSeller, 'seller')
            ->get(route('vendor.wholesale.orders.list', ['all']))
            ->assertOk()
            ->viewData('orders')
            ->getCollection();

        $this->assertTrue($orders->contains('id', $ownOrder->id));
        $this->assertFalse($orders->contains('id', $otherOrder->id));
        $this->assertFalse($orders->contains('id', $retailOrder->id));
        $this->assertTrue($orders->every(static fn ($order): bool => (int) $order->seller_id === (int) $ownSeller->id && $order->seller_is === 'seller'));
    }

    public function test_vendor_list_hides_store_column_and_vendor_filter(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $this->createDetail(order: $this->createOrder(seller: $seller), snapshotName: 'Vendor list item');

        $response = $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.orders.list', ['all']))
            ->assertOk()
            ->assertSee('wholesale-order-customer', false)
            ->assertSee('Vendor list item')
            ->assertDontSee('wholesale-order-vendor', false)
            ->assertDontSee('name="filter"', false)
            ->assertViewMissing('vendorOptions');

        $this->assertArrayNotHasKey('store_name', $response->viewData('sortLinks'));
    }

    public function test_export_downloads_filtered_rows(): void
    {
        $fixtures = $this->createFilterFixtures();

        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.export', ['all', 'payment_status' => ['paid']]))
            ->assertOk();

        Excel::assertDownloaded('Wholesale-orders.xlsx', function (OrderExport $export) use ($fixtures): bool {
            $orderIds = $this->exportedOrderIds(export: $export);

            return $orderIds->contains($fixtures['deliveredInHouseOrder']->id)
                && $orderIds->contains($fixtures['canceledVendorOrder']->id)
                && ! $orderIds->contains($fixtures['pendingVendorOrder']->id)
                && $export->view()->getData()['data']['showStoreColumn'] === true;
        });

        Excel::fake();

        $this->actingAs($fixtures['sellers'][0], 'seller')
            ->get(route('vendor.wholesale.orders.export', ['all']))
            ->assertOk();

        Excel::assertDownloaded('Wholesale-orders.xlsx', function (OrderExport $export) use ($fixtures): bool {
            $orderIds = $this->exportedOrderIds(export: $export);

            return $orderIds->contains($fixtures['pendingVendorOrder']->id)
                && ! $orderIds->contains($fixtures['canceledVendorOrder']->id)
                && ! $orderIds->contains($fixtures['deliveredInHouseOrder']->id)
                && ! $orderIds->contains($fixtures['retailOrder']->id)
                && $export->view()->getData()['data']['showStoreColumn'] === false;
        });
    }

    public function test_export_file_renders_every_row(): void
    {
        $fixtures = $this->createFilterFixtures();

        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.export', ['all', 'customer_id' => $fixtures['customer']->id]))
            ->assertOk()
            ->assertDownload('Wholesale-orders.xlsx');

        $this->assertGreaterThan(0, strlen((string) $response->getFile()->getContent()));
    }

    public function test_customer_search_returns_json(): void
    {
        $customer = $this->customers(count: 1)[0];

        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('admin.wholesale.orders.customers', ['q' => $customer->phone]))
            ->assertOk()
            ->assertJsonPath('0.id', 'all')
            ->assertJsonFragment(['id' => $customer->id]);

        $this->actingAs($this->approvedSellers(count: 1)[0], 'seller')
            ->getJson(route('vendor.wholesale.orders.customers', ['q' => $customer->phone]))
            ->assertOk()
            ->assertJsonPath('0.id', 'all')
            ->assertJsonFragment(['id' => $customer->id]);
    }

    public function test_invalid_status_and_sort_do_not_error(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.list', ['not-a-status', 'sort' => 'id;drop', 'date_type' => 'yesterday', 'payment_status' => ['bogus']]))
            ->assertOk()
            ->assertViewHas('status', 'all')
            ->assertViewHas('sortKey', OrderService::SORT_NEWEST);

        $this->actingAs($this->approvedSellers(count: 1)[0], 'seller')
            ->get(route('vendor.wholesale.orders.list', ['not-a-status', 'sort' => OrderService::SORT_STORE_NAME_ASC]))
            ->assertOk()
            ->assertViewHas('status', 'all')
            ->assertViewHas('sortKey', OrderService::SORT_NEWEST);

        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.wholesale.orders.export', ['not-a-status', 'sort' => 'id;drop']))
            ->assertOk();
    }

    private function countListQueries(Admin $admin): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin, 'admin')->get(route('admin.wholesale.orders.list', ['all']))->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }

    private function createOrdersWithDetails(int $count, array $sellers, array $customers): void
    {
        for ($index = 0; $index < $count; $index++) {
            $order = $this->createOrder(
                seller: $index % 3 === 0 ? null : $sellers[$index % 2],
                attributes: ['customer_id' => $customers[$index % 2]->id],
            );
            $this->createDetail(order: $order, snapshotName: 'Query count item '.$index);
            $this->createDetail(order: $order, snapshotName: 'Query count extra '.$index);
        }
    }

    private function exportedOrderIds(OrderExport $export): Collection
    {
        return collect($export->view()->getData()['data']['orders'])->pluck('id');
    }

    /** @return array<string, mixed> */
    private function queryFor(string $case, array $fixtures): array
    {
        return match ($case) {
            'today' => ['date_type' => 'today'],
            'custom_date' => ['date_type' => 'custom_date', 'from' => now()->subDays(41)->toDateString(), 'to' => now()->subDays(39)->toDateString()],
            'source_in_house' => ['filter' => 'admin', 'seller_id' => $fixtures['sellers'][0]->id],
            'source_vendor' => ['filter' => 'seller', 'customer_id' => $fixtures['customer']->id],
            'vendor' => ['seller_id' => $fixtures['sellers'][0]->id, 'customer_id' => $fixtures['customer']->id],
            'customer' => ['customer_id' => $fixtures['customer']->id],
            'order_status' => ['order_current_status' => ['pending', 'canceled'], 'customer_id' => $fixtures['customer']->id],
            'payment_status' => ['payment_status' => ['paid']],
        };
    }

    /** @return array<string, mixed> */
    private function createFilterFixtures(): array
    {
        $sellers = $this->approvedSellers(count: 2);
        [$customer, $otherCustomer] = $this->customers(count: 2);

        $pendingVendorOrder = $this->createOrder(seller: $sellers[0], attributes: [
            'customer_id' => $customer->id, 'order_status' => 'pending', 'payment_status' => 'unpaid', 'created_at' => now(),
        ]);
        $deliveredInHouseOrder = $this->createOrder(seller: null, attributes: [
            'customer_id' => $otherCustomer->id, 'order_status' => 'delivered', 'payment_status' => 'paid', 'created_at' => now()->subDays(400),
        ]);
        $canceledVendorOrder = $this->createOrder(seller: $sellers[1], attributes: [
            'customer_id' => $customer->id, 'order_status' => 'canceled', 'payment_status' => 'paid', 'created_at' => now()->subDays(40),
        ]);
        $retailOrder = $this->createOrder(seller: $sellers[0], businessModule: BusinessModule::RETAIL, attributes: [
            'customer_id' => $customer->id, 'order_status' => 'pending', 'payment_status' => 'paid', 'created_at' => now(),
        ]);

        $this->createDetail(order: $pendingVendorOrder, snapshotName: 'Vendor item', addedBy: 'seller');
        $this->createDetail(order: $deliveredInHouseOrder, snapshotName: 'In-house item', addedBy: 'admin');
        $this->createDetail(order: $canceledVendorOrder, snapshotName: 'Vendor item', addedBy: 'seller');
        $this->createDetail(order: $retailOrder, snapshotName: 'Retail item', addedBy: 'seller');

        return compact('sellers', 'customer', 'pendingVendorOrder', 'deliveredInHouseOrder', 'canceledVendorOrder', 'retailOrder');
    }

    private function createOrder(?Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_id' => $seller?->id ?? self::IN_HOUSE_SELLER_ID,
            'seller_is' => $seller === null ? 'admin' : 'seller',
            'customer_id' => $this->customers(count: 1)[0]->id,
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-list-'.Str::lower(Str::random(16)),
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, string $snapshotName, string $addedBy = 'seller'): OrderDetail
    {
        $productId = $this->activeProductId(addedBy: $addedBy);

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $productId,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode([
                'id' => $productId,
                'name' => $snapshotName,
                'added_by' => $addedBy,
                'product_type' => 'physical',
                'thumbnail_full_url' => ['key' => 'thumbnail.png', 'path' => self::SNAPSHOT_THUMBNAIL_URL, 'status' => 200],
            ]),
            'qty' => 3,
            'price' => 10,
            'discount' => 2,
            'tax' => 1.4,
            'tax_model' => 'exclude',
            'variant' => 'Blue-Small',
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
        ])->save();

        return $detail;
    }

    private function createDeliveryMan(int $sellerId): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => $sellerId,
            'f_name' => 'Rider',
            'l_name' => Str::random(6),
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => Str::lower(Str::random(10)).'@rider.test',
            'password' => bcrypt(Str::random(12)),
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

    private function activeProductId(string $addedBy): int
    {
        return (int) $this->requireFixture(
            Product::query()->withoutGlobalScopes()->where(['status' => 1, 'added_by' => $addedBy])->value('id'),
            'active product added by '.$addedBy,
        );
    }

    /** @return array<int, User> */
    private function customers(int $count): array
    {
        $customers = User::query()->where('id', '>', 0)->whereNotNull('phone')->orderBy('id')->limit($count)->get()->all();

        if (count($customers) < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' customers.');
        }

        return $customers;
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

    private function stubPanelLayouts(): void
    {
        foreach (self::STUB_LAYOUTS as $layout) {
            $path = self::STUB_VIEW_ROOT.'/'.$layout.'.blade.php';

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, "@yield('content')@stack('script')");
        }

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
