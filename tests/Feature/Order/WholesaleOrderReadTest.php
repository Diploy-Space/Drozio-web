<?php

namespace Tests\Feature\Order;

use App\Enums\BusinessModule;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\OrderService as RetailOrderService;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wholesale\app\Services\OrderDetailService;
use Modules\Wholesale\app\Services\OrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionFunction;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOrderReadTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const IN_HOUSE_SELLER_ID = 1;

    private const SNAPSHOT_THUMBNAIL_URL = 'https://cdn.example.test/wholesale/thumbnail.png';

    private int $nextOrderId = 0;

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
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    #[DataProvider('adminListFilters')]
    public function test_admin_list_filters_narrow_wholesale_orders(string $case, array $expectedFixtures): void
    {
        $fixtures = $this->createFilterFixtures();

        $orders = $this->orderService()->getList(
            dataLimit: 'all',
            filters: [...$this->filtersFor(case: $case, fixtures: $fixtures), 'order_group_id' => $fixtures['group']],
        );

        $this->assertEqualsCanonicalizing(
            array_map(static fn (string $key): int => $fixtures[$key]->id, $expectedFixtures),
            $orders->pluck('id')->all(),
        );
    }

    public static function adminListFilters(): array
    {
        return [
            'date type today' => ['today', ['pendingVendorOrder']],
            'custom date range' => ['custom_date', ['canceledVendorOrder']],
            'show in-house orders' => ['source_in_house', ['deliveredInHouseOrder']],
            'show vendor orders' => ['source_vendor', ['pendingVendorOrder', 'canceledVendorOrder']],
            'vendor' => ['vendor', ['pendingVendorOrder']],
            'in-house seller zero' => ['in_house_seller', ['deliveredInHouseOrder']],
            'customer' => ['customer', ['pendingVendorOrder', 'canceledVendorOrder']],
            'order status array' => ['order_status', ['pendingVendorOrder', 'canceledVendorOrder']],
            'payment status array' => ['payment_status', ['deliveredInHouseOrder', 'canceledVendorOrder']],
        ];
    }

    public function test_list_never_returns_retail_orders(): void
    {
        $fixtures = $this->createFilterFixtures();

        $orderIds = $this->orderService()->getList(dataLimit: 'all', filters: ['order_group_id' => $fixtures['group']])->pluck('id')->all();

        $this->assertNotContains($fixtures['retailOrder']->id, $orderIds);
        $this->assertCount(3, $orderIds);
        $this->assertNull($this->orderService()->find(id: $fixtures['retailOrder']->id));
        $this->assertSame(0, $this->orderService()->count(filters: ['id' => $fixtures['retailOrder']->id]));
        $this->assertNull(app(RetailOrderService::class)->find(id: $fixtures['pendingVendorOrder']->id));
    }

    public function test_status_counts_respect_active_filters(): void
    {
        $sellers = $this->approvedSellers(count: 2);
        $group = $this->uniqueGroup();

        foreach (RetailOrderService::SELLER_STATISTICS_STATUSES as $status) {
            $this->createOrder(seller: $sellers[0], attributes: ['order_group_id' => $group, 'order_status' => $status, 'payment_status' => 'paid']);
        }

        $this->createOrder(seller: $sellers[1], attributes: ['order_group_id' => $group, 'order_status' => 'pending', 'payment_status' => 'unpaid']);
        $this->createOrder(seller: $sellers[0], businessModule: BusinessModule::RETAIL, attributes: ['order_group_id' => $group, 'order_status' => 'pending', 'payment_status' => 'paid']);

        $expectedCounts = array_fill_keys(RetailOrderService::SELLER_STATISTICS_STATUSES, 1);

        $this->assertEqualsCanonicalizing($expectedCounts, $this->orderService()->getStatusCounts(filters: [
            'order_group_id' => $group,
            'payment_status' => ['paid'],
            'order_status' => ['pending'],
        ]));

        $this->assertEqualsCanonicalizing(
            ['pending' => 2, ...array_diff_key($expectedCounts, ['pending' => null])],
            $this->orderService()->getStatusCounts(filters: ['order_group_id' => $group]),
        );

        $this->assertSame(['pending' => 1], $this->orderService()->getStatusCounts(filters: [
            'order_group_id' => $group,
            'seller_is' => 'seller',
            'seller_id' => $sellers[1]->id,
        ]));
    }

    #[DataProvider('sortKeys')]
    public function test_each_sort_key_orders_rows(string $sortBy, array $expectedFixtures): void
    {
        $fixtures = $this->createSortFixtures();

        $orderIds = $this->orderService()->getList(
            dataLimit: 'all',
            filters: ['order_group_id' => $fixtures['group']],
            sortBy: $sortBy,
        )->pluck('id')->all();

        $this->assertSame($this->fixtureIds(fixtures: $fixtures, keys: $expectedFixtures), $orderIds);
    }

    public static function sortKeys(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return [
                OrderService::SORT_ORDER_ID_ASC => [OrderService::SORT_ORDER_ID_ASC, ['first', 'second', 'third']],
                OrderService::SORT_ORDER_ID_DESC => [OrderService::SORT_ORDER_ID_DESC, ['third', 'second', 'first']],
                OrderService::SORT_ORDER_DATE_ASC => [OrderService::SORT_ORDER_DATE_ASC, ['first', 'third', 'second']],
                OrderService::SORT_ORDER_DATE_DESC => [OrderService::SORT_ORDER_DATE_DESC, ['second', 'third', 'first']],
                OrderService::SORT_CUSTOMER_NAME_ASC => [OrderService::SORT_CUSTOMER_NAME_ASC, ['second', 'third', 'first']],
                OrderService::SORT_CUSTOMER_NAME_DESC => [OrderService::SORT_CUSTOMER_NAME_DESC, ['first', 'third', 'second']],
                OrderService::SORT_STORE_NAME_ASC => [OrderService::SORT_STORE_NAME_ASC, ['third', 'first', 'second']],
                OrderService::SORT_STORE_NAME_DESC => [OrderService::SORT_STORE_NAME_DESC, ['second', 'first', 'third']],
                OrderService::SORT_ORDER_AMOUNT_ASC => [OrderService::SORT_ORDER_AMOUNT_ASC, ['second', 'third', 'first']],
                OrderService::SORT_ORDER_AMOUNT_DESC => [OrderService::SORT_ORDER_AMOUNT_DESC, ['first', 'third', 'second']],
                OrderService::SORT_ORDER_STATUS_ASC => [OrderService::SORT_ORDER_STATUS_ASC, ['third', 'first', 'second']],
                OrderService::SORT_ORDER_STATUS_DESC => [OrderService::SORT_ORDER_STATUS_DESC, ['second', 'first', 'third']],
            ];
        });
    }

    public function test_unknown_sort_key_falls_back_to_newest(): void
    {
        $fixtures = $this->createSortFixtures();
        $newestFirst = $this->fixtureIds(fixtures: $fixtures, keys: ['third', 'second', 'first']);

        foreach (['not-a-sort-key', null] as $sortBy) {
            $this->assertSame($newestFirst, $this->orderService()->getList(
                dataLimit: 'all',
                filters: ['order_group_id' => $fixtures['group']],
                sortBy: $sortBy,
            )->pluck('id')->all());
        }

    }

    public function test_retail_list_sort_is_unchanged(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $group = $this->uniqueGroup();
        $olderOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL, attributes: ['order_group_id' => $group]);
        $newerOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL, attributes: ['order_group_id' => $group]);
        $retailOrderService = app(RetailOrderService::class);

        $listIds = static fn (?string $sortBy): array => $retailOrderService->getList(dataLimit: 'all', filters: ['order_group_id' => $group], sortBy: $sortBy)->pluck('id')->all();

        $this->assertSame([$newerOrder->id, $olderOrder->id], $listIds(RetailOrderService::SORT_NEWEST));
        $this->assertSame([$olderOrder->id, $newerOrder->id], $listIds(RetailOrderService::SORT_OLDEST));
        $this->assertSame([$newerOrder->id, $olderOrder->id], $retailOrderService->getSellerOrderList(sellerId: $seller->id, filters: ['order_group_id' => $group], dataLimit: 'all')->pluck('id')->all());

        DB::enableQueryLog();
        DB::flushQueryLog();
        $retailOrderService->getList(dataLimit: 'all', filters: ['order_group_id' => $group], sortBy: OrderService::SORT_ORDER_AMOUNT_ASC);
        $unknownSortQuery = DB::getQueryLog()[0]['query'];
        DB::disableQueryLog();

        $this->assertStringNotContainsStringIgnoringCase('order by', $unknownSortQuery);
    }

    public function test_vendor_reads_are_scoped_to_the_resolved_seller(): void
    {
        [$ownSeller, $otherSeller] = $this->approvedSellers(count: 2);
        $group = $this->uniqueGroup();
        $countsBefore = $this->orderService()->getStatusCountsForSeller(sellerId: $ownSeller->id);

        $firstOwnOrder = $this->createOrder(seller: $ownSeller, attributes: ['order_group_id' => $group, 'order_status' => 'pending']);
        $otherSellerOrder = $this->createOrder(seller: $otherSeller, attributes: ['order_group_id' => $group, 'order_status' => 'pending']);
        $ownRetailOrder = $this->createOrder(seller: $ownSeller, businessModule: BusinessModule::RETAIL, attributes: ['order_group_id' => $group, 'order_status' => 'pending']);
        $secondOwnOrder = $this->createOrder(seller: $ownSeller, attributes: ['order_group_id' => $group, 'order_status' => 'confirmed']);

        $this->assertSame(
            [$secondOwnOrder->id, $firstOwnOrder->id],
            $this->orderService()->getSellerOrderList(sellerId: $ownSeller->id, filters: ['order_group_id' => $group], dataLimit: 'all')->pluck('id')->all(),
        );
        $this->assertSame(
            [$secondOwnOrder->id, $firstOwnOrder->id],
            $this->orderService()->getSellerOrderList(sellerId: $ownSeller->id, filters: ['order_group_id' => $group], dataLimit: 'all', sortBy: 'not-a-sort-key')->pluck('id')->all(),
        );
        $this->assertSame(
            [$firstOwnOrder->id, $secondOwnOrder->id],
            $this->orderService()->getSellerOrderList(sellerId: $ownSeller->id, filters: ['order_group_id' => $group], dataLimit: 'all', sortBy: OrderService::SORT_ORDER_STATUS_DESC)->pluck('id')->all(),
        );

        $countsAfter = $this->orderService()->getStatusCountsForSeller(sellerId: $ownSeller->id);
        $this->assertSame($countsBefore['pending'] + 1, $countsAfter['pending']);
        $this->assertSame($countsBefore['confirmed'] + 1, $countsAfter['confirmed']);

        $this->assertNotNull($this->orderService()->findForSeller(id: $firstOwnOrder->id, sellerId: $ownSeller->id));
        $this->assertNull($this->orderService()->findForSeller(id: $otherSellerOrder->id, sellerId: $ownSeller->id));
        $this->assertNull($this->orderService()->findForSeller(id: $ownRetailOrder->id, sellerId: $ownSeller->id));

        $this->assertSame($firstOwnOrder->id, $this->orderService()->getAdjacentOrderIds(order: $secondOwnOrder, sellerId: $ownSeller->id)['previous']);
        $this->assertSame($secondOwnOrder->id, $this->orderService()->getAdjacentOrderIds(order: $firstOwnOrder, sellerId: $ownSeller->id)['next']);
        $this->assertSame($otherSellerOrder->id, $this->orderService()->getAdjacentOrderIds(order: $secondOwnOrder)['previous']);

        $this->assertSame([$secondOwnOrder->id], $this->orderService()->getLinkedOrders(order: $firstOwnOrder, sellerId: $ownSeller->id)->pluck('id')->all());
        $this->assertSame([$otherSellerOrder->id, $secondOwnOrder->id], $this->orderService()->getLinkedOrders(order: $firstOwnOrder)->pluck('id')->all());
    }

    public function test_linked_orders_exclude_self_and_default_group(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $group = $this->uniqueGroup();
        $order = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $group]);
        $siblingOrder = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $group]);
        $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL, attributes: ['order_group_id' => $group]);

        $linkedOrders = $this->orderService()->getLinkedOrders(order: $order);

        $this->assertInstanceOf(Collection::class, $linkedOrders);
        $this->assertSame([$siblingOrder->id], $linkedOrders->pluck('id')->all());

        $defaultGroupOrder = $this->createOrder(seller: $seller, attributes: ['order_group_id' => 'def-order-group']);
        $this->createOrder(seller: $seller, attributes: ['order_group_id' => 'def-order-group']);

        $this->assertTrue($this->orderService()->getLinkedOrders(order: $defaultGroupOrder)->isEmpty());
    }

    public function test_adjacent_ids_skip_retail_orders(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $firstOrder = $this->createOrder(seller: $seller);
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $lastOrder = $this->createOrder(seller: $seller);

        $this->assertSame($lastOrder->id, $this->orderService()->getAdjacentOrderIds(order: $firstOrder)['next']);
        $this->assertSame($firstOrder->id, $this->orderService()->getAdjacentOrderIds(order: $lastOrder)['previous']);
        $this->assertNull($this->orderService()->getAdjacentOrderIds(order: $lastOrder)['next']);
        $this->assertNotSame($retailOrder->id, $this->orderService()->getAdjacentOrderIds(order: $firstOrder)['previous']);
    }

    public function test_item_previews_use_snapshot_when_product_deleted(): void
    {
        $order = $this->createOrder(seller: $this->approvedSellers(count: 1)[0]);
        $this->createDetail(order: $order, productId: $this->missingProductId(), snapshotName: 'Snapshot only wholesale widget');

        $loadedOrder = $this->orderService()->find(id: $order->id, relations: $this->itemPreviewRelations());

        $this->assertSame([
            $order->id => [[
                'name' => 'Snapshot only wholesale widget',
                'thumbnail' => self::SNAPSHOT_THUMBNAIL_URL,
                'variation' => 'Blue-Small',
                'quantity' => 3,
                'price' => 28.0,
            ]],
        ], $this->orderService()->getItemPreviews(orders: [$loadedOrder]));
    }

    public function test_item_previews_run_no_query(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $group = $this->uniqueGroup();

        foreach (range(1, 3) as $index) {
            $order = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $group]);
            $this->createDetail(order: $order, productId: $this->missingProductId(), snapshotName: 'Preview item '.$index);
            $this->createDetail(order: $order, productId: $this->missingProductId(), snapshotName: 'Second preview item '.$index);
        }

        $orders = $this->orderService()->getList(dataLimit: 'all', relations: $this->itemPreviewRelations(), filters: ['order_group_id' => $group]);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $previews = $this->orderService()->getItemPreviews(orders: $orders);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queryCount);
        $this->assertCount(3, $previews);
        $this->assertSame([2, 2, 2], array_values(array_map('count', $previews)));
    }

    public function test_detail_rows_render_wholesale_variation_and_tax(): void
    {
        $order = $this->createOrder(seller: $this->approvedSellers(count: 1)[0]);
        $detail = $this->createDetail(order: $order, productId: $this->missingProductId(), snapshotName: 'Tiered wholesale widget');

        $loadedOrder = $this->orderService()->find(id: $order->id, relations: ['details' => fn ($details) => $details->with(['productAllStatus'])]);
        $row = app(OrderDetailService::class)->getDisplayRows(details: $loadedOrder->details)[$detail->id];

        $this->assertSame(10.0, $row['unit_price']);
        $this->assertSame(5.0, $row['tax_percent']);
        $this->assertSame('Blue-Small', $row['variation']);
        $this->assertSame(self::SNAPSHOT_THUMBNAIL_URL, $row['thumbnail']);
        $this->assertSame('Tiered wholesale widget', $row['product']['name']);
        $this->assertTrue($row['is_product_unavailable']);
        $this->assertEquals(28, $row['total_price']);
        $this->assertSame(1, $row['serial']);
    }

    public function test_invoice_data_carries_plain_lines_totals_shop_and_addresses(): void
    {
        $seller = $this->approvedSellers(count: 1)[0];
        $order = $this->createOrder(seller: $seller, attributes: [
            'shipping_cost' => 5,
            'discount_amount' => 0,
            'extra_discount' => 0,
            'refer_and_earn_discount' => 0,
            'is_shipping_free' => 0,
            'total_tax_amount' => 1.4,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'shipping_address_data' => ['contact_person_name' => 'Wholesale receiver', 'city' => 'Dhaka'],
            'billing_address_data' => null,
        ]);
        $this->createDetail(order: $order, productId: $this->missingProductId(), snapshotName: 'Invoice wholesale widget');

        $invoice = $this->orderService()->getInvoiceData(
            order: $this->orderService()->find(id: $order->id, relations: ['seller.shop', 'customer', 'details.product']),
        );

        $this->assertSame([[
            'name' => 'Invoice wholesale widget',
            'variation' => 'Blue-Small',
            'quantity' => 3,
            'unit_price' => 10.0,
            'discount' => 2.0,
            'tax' => 1.4,
            'total' => 28.0,
        ]], $invoice['lines']);
        $this->assertSame(30.0, $invoice['totals']['item_price']);
        $this->assertSame(5.0, $invoice['totals']['shipping']);
        $this->assertEqualsWithDelta(34.4, $invoice['totals']['total'], 0.0001);
        $this->assertSame($seller->shop->name, $invoice['shop']['name']);
        $this->assertFalse($invoice['shop']['is_in_house']);
        $this->assertSame(['contact_person_name' => 'Wholesale receiver', 'city' => 'Dhaka'], $invoice['shipping_address']);
        $this->assertNull($invoice['billing_address']);
        $this->assertSame($order->id, $invoice['order_id']);
    }

    public function test_delivery_verification_reads_wholesale_setting(): void
    {
        $webConfigMemo = (new ReflectionFunction('getWebConfig'))->getStaticVariables();

        if (array_key_exists(OrderService::DELIVERY_VERIFICATION_SETTING, $webConfigMemo['resolved'] ?? [])) {
            $this->markTestSkipped('The wholesale delivery verification setting was already memoized in this process.');
        }

        $isRetailVerificationOn = (bool) (int) getWebConfig(name: 'order_verification');
        $wholesaleValue = (int) ! $isRetailVerificationOn;
        $setting = BusinessSetting::query()->updateOrCreate(['type' => OrderService::DELIVERY_VERIFICATION_SETTING], ['value' => $wholesaleValue]);

        $settings = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings'];
        $settings->firstWhere('type', OrderService::DELIVERY_VERIFICATION_SETTING)?->setAttribute('value', $wholesaleValue)
            ?? $settings->push($setting);

        $this->assertSame('wholesale_order_delivery_verification', OrderService::DELIVERY_VERIFICATION_SETTING);
        $this->assertSame((bool) $wholesaleValue, $this->orderService()->isDeliveryVerificationEnabled());
        $this->assertNotSame($isRetailVerificationOn, $this->orderService()->isDeliveryVerificationEnabled());
    }

    private function filtersFor(string $case, array $fixtures): array
    {
        return match ($case) {
            'today' => [OrderService::FILTER_DATE_TYPE => OrderService::DATE_TYPE_TODAY],
            'custom_date' => [
                OrderService::FILTER_DATE_TYPE => OrderService::DATE_TYPE_CUSTOM,
                OrderService::FILTER_DATE_RANGE => [now()->subDays(45)->toDateString(), now()->subDays(35)->toDateString()],
            ],
            'source_in_house' => [OrderService::FILTER_SOURCE => OrderService::SELLER_IS_ADMIN],
            'source_vendor' => [OrderService::FILTER_SOURCE => OrderService::SELLER_IS_SELLER],
            'vendor' => ['seller_is' => OrderService::SELLER_IS_SELLER, 'seller_id' => $fixtures['pendingVendorOrder']->seller_id],
            'in_house_seller' => ['seller_is' => OrderService::SELLER_IS_ADMIN, 'seller_id' => self::IN_HOUSE_SELLER_ID],
            'customer' => ['customer_id' => $fixtures['pendingVendorOrder']->customer_id],
            'order_status' => ['order_status' => ['pending', 'canceled']],
            'payment_status' => ['payment_status' => ['paid']],
        };
    }

    /** @return array<string, mixed> */
    private function createFilterFixtures(): array
    {
        [$firstSeller, $secondSeller] = $this->approvedSellers(count: 2);
        [$firstCustomer, $secondCustomer] = $this->customers(count: 2);
        $group = $this->uniqueGroup();
        $inHouseProductId = $this->activeProductId(addedBy: 'admin');
        $vendorProductId = $this->activeProductId(addedBy: 'seller');

        $pendingVendorOrder = $this->createOrder(seller: $firstSeller, attributes: [
            'order_group_id' => $group, 'customer_id' => $firstCustomer->id, 'order_status' => 'pending', 'payment_status' => 'unpaid', 'created_at' => now(),
        ]);
        $deliveredInHouseOrder = $this->createOrder(seller: null, attributes: [
            'order_group_id' => $group, 'customer_id' => $secondCustomer->id, 'order_status' => 'delivered', 'payment_status' => 'paid', 'created_at' => now()->subDays(400),
        ]);
        $canceledVendorOrder = $this->createOrder(seller: $secondSeller, attributes: [
            'order_group_id' => $group, 'customer_id' => $firstCustomer->id, 'order_status' => 'canceled', 'payment_status' => 'paid', 'created_at' => now()->subDays(40),
        ]);
        $retailOrder = $this->createOrder(seller: $firstSeller, businessModule: BusinessModule::RETAIL, attributes: [
            'order_group_id' => $group, 'customer_id' => $firstCustomer->id, 'order_status' => 'pending', 'payment_status' => 'paid', 'created_at' => now(),
        ]);

        $this->createDetail(order: $pendingVendorOrder, productId: $vendorProductId, snapshotName: 'Vendor item');
        $this->createDetail(order: $deliveredInHouseOrder, productId: $inHouseProductId, snapshotName: 'In-house item');
        $this->createDetail(order: $canceledVendorOrder, productId: $vendorProductId, snapshotName: 'Vendor item');
        $this->createDetail(order: $retailOrder, productId: $vendorProductId, snapshotName: 'Retail item');

        return compact('group', 'pendingVendorOrder', 'deliveredInHouseOrder', 'canceledVendorOrder', 'retailOrder');
    }

    /** @return array<string, mixed> */
    private function createSortFixtures(): array
    {
        [$firstSeller, $secondSeller] = $this->approvedSellers(count: 2);
        [$firstCustomer, $secondCustomer, $thirdCustomer] = $this->customers(count: 3);
        $group = $this->uniqueGroup();

        $this->renameCustomer(customer: $firstCustomer, firstName: 'Cyrus', lastName: 'Carter');
        $this->renameCustomer(customer: $secondCustomer, firstName: 'Aaron', lastName: 'Adams');
        $this->renameCustomer(customer: $thirdCustomer, firstName: 'Bella', lastName: 'Brooks');
        Shop::query()->where(['seller_id' => $firstSeller->id, 'author_type' => 'vendor'])->update(['name' => 'Beta wholesale store']);
        Shop::query()->where(['seller_id' => $secondSeller->id, 'author_type' => 'vendor'])->update(['name' => 'Alpha wholesale store']);
        Shop::query()->where('author_type', 'admin')->update(['name' => 'Gamma wholesale store']);

        $first = $this->createOrder(seller: $firstSeller, attributes: [
            'order_group_id' => $group, 'customer_id' => $firstCustomer->id, 'created_at' => now()->subDays(3), 'order_amount' => 200, 'order_status' => 'delivered',
        ]);
        $second = $this->createOrder(seller: null, attributes: [
            'order_group_id' => $group, 'customer_id' => $secondCustomer->id, 'created_at' => now()->subDay(), 'order_amount' => 50, 'order_status' => 'pending',
        ]);
        $third = $this->createOrder(seller: $secondSeller, attributes: [
            'order_group_id' => $group, 'customer_id' => $thirdCustomer->id, 'created_at' => now()->subDays(2), 'order_amount' => 125, 'order_status' => 'canceled',
        ]);

        return compact('group', 'first', 'second', 'third');
    }

    private function fixtureIds(array $fixtures, array $keys): array
    {
        return array_map(static fn (string $key): int => $fixtures[$key]->id, $keys);
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
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_group_id' => $this->uniqueGroup(),
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order, int $productId, string $snapshotName): OrderDetail
    {
        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $productId,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode([
                'id' => $productId,
                'name' => $snapshotName,
                'added_by' => $order->seller_is,
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

    private function itemPreviewRelations(): array
    {
        return [
            'details' => fn ($details) => $details->select(['id', 'order_id', 'seller_id', 'product_details', 'qty', 'price', 'discount', 'variant']),
        ];
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
    }

    private function uniqueGroup(): string
    {
        return 'wholesale-read-'.Str::lower(Str::random(16));
    }

    private function renameCustomer(User $customer, string $firstName, string $lastName): void
    {
        User::query()->whereKey($customer->id)->update(['f_name' => $firstName, 'l_name' => $lastName]);
    }

    private function missingProductId(): int
    {
        return (int) Product::query()->withoutGlobalScopes()->max('id') + 100000;
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
        $customers = User::query()->where('id', '>', 0)->orderBy('id')->limit($count)->get()->all();

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

    private function orderService(): OrderService
    {
        return app(OrderService::class);
    }
}
