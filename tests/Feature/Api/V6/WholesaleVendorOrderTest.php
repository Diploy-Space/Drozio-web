<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Events\OrderStatusEvent;
use App\Http\Middleware\VendorDeliveryPartnerSetupMiddleware;
use App\Http\Resources\V6\Vendor\OrderDetailResource as RetailOrderDetailResource;
use App\Http\Resources\V6\Vendor\OrderResource as RetailOrderResource;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Seller;
use App\Models\User;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\OrderDetailService as RetailOrderDetailService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Courier\app\DataTransferObjects\Requests\OrderData;
use Modules\Courier\app\DataTransferObjects\Responses\ShipmentResult;
use Modules\Courier\app\Enums\ShipmentStatus;
use Modules\Courier\app\Events\ShipmentDispatched;
use Modules\Courier\app\Events\ShipmentStatusUpdated;
use Modules\Courier\app\Models\CourierShipment;
use Modules\Courier\app\Services\New\CourierConfigService;
use Modules\Courier\app\ValueObjects\CourierOwner;
use Modules\Courier\CourierProviders\Contracts\CreatesOrders;
use Modules\Courier\CourierProviders\CourierProvider;
use Modules\Wholesale\app\Services\OrderDetailService;
use Modules\Wholesale\app\Services\OrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleVendorOrderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const LIST_URI = 'api/v6/wholesale/seller/orders';

    private const STATUS_COUNTS_URI = 'api/v6/wholesale/seller/orders/status-counts';

    private const RETAIL_FIND_URI = 'api/v6/seller/orders/';

    private const FIXTURE_LIMIT = 500;

    private const WHOLESALE_LINE_KEYS = ['unit_price', 'tax_percent', 'variation', 'thumbnail', 'total_price', 'is_product_unavailable'];

    private const FIND_EXTRA_KEYS = ['linked_orders', 'previous_order_id', 'next_order_id', 'delivery_verification_enabled'];

    private const ACTION_CURRENT_STOCK = 50;

    private const ACTION_VARIANT = 'Blue-Small';

    private const ACTION_VARIANT_STOCK = 30;

    private const ACTION_LINE_QUANTITY = 4;

    private const OWN_DELIVERY_MAN = 'own-delivery-man';

    private const SELLERWISE_SHIPPING = 'sellerwise_shipping';

    private const INHOUSE_SHIPPING = 'inhouse_shipping';

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

    public function test_list_requires_v6_token(): void
    {
        $this->getJson(self::LIST_URI)->assertUnauthorized();
        $this->getJson(self::LIST_URI, ['Authorization' => 'Bearer '.Str::random(40)])->assertUnauthorized();
        $this->getJson(self::STATUS_COUNTS_URI)->assertUnauthorized();
        $this->getJson(self::LIST_URI.'/1')->assertUnauthorized();
    }

    public function test_list_is_forbidden_when_wholesale_is_off(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createOrder(seller: $seller);

        $this->app->instance(BusinessModuleManager::class, new class extends BusinessModuleManager
        {
            public function isInstalled(string $key): bool
            {
                return $key !== BusinessModule::WHOLESALE && parent::isInstalled($key);
            }
        });

        foreach ([self::LIST_URI, self::STATUS_COUNTS_URI, self::LIST_URI.'/'.$order->id] as $uri) {
            $this->getJson($uri, $this->sellerHeaders($seller))
                ->assertForbidden()
                ->assertExactJson([
                    'identical_code' => 'wholesale_feature_disabled_403',
                    'message' => 'Wholesale feature is not available for vendors',
                    'content' => null,
                    'errors' => [],
                ]);
        }
    }

    public function test_list_returns_only_own_wholesale_orders(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);

        $ownOrders = [$this->createOrder(seller: $seller), $this->createOrder(seller: $seller)];
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $otherSellerOrder = $this->createOrder(seller: $otherSeller);
        $inHouseOrder = $this->createOrder(seller: null);

        $response = $this->listFor(seller: $seller)->assertOk();
        $orderIds = array_column($response->json('content.data'), 'id');

        foreach ($ownOrders as $order) {
            $this->assertContains($order->id, $orderIds);
        }

        $this->assertNotContains($retailOrder->id, $orderIds);
        $this->assertNotContains($otherSellerOrder->id, $orderIds);
        $this->assertNotContains($inHouseOrder->id, $orderIds);
        $this->assertSame([BusinessModule::WHOLESALE], array_values(array_unique(array_column($response->json('content.data'), 'business_module'))));
        $this->assertSame([$seller->id], array_values(array_unique(array_column($response->json('content.data'), 'seller_id'))));
        $response->assertJsonStructure(['content' => ['data', 'params', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]]);
    }

    #[DataProvider('listFilters')]
    public function test_list_filters_and_json_string_arrays(array $query, array $expectedFixtures): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        [$customer, $otherCustomer] = $this->customers(count: 2);

        $fixtures = [
            'pendingToday' => $this->createOrder(seller: $seller, attributes: [
                'order_status' => 'pending', 'payment_status' => 'unpaid', 'customer_id' => $customer->id, 'created_at' => now(),
            ]),
            'deliveredLastMonth' => $this->createOrder(seller: $seller, attributes: [
                'order_status' => 'delivered', 'payment_status' => 'paid', 'customer_id' => $otherCustomer->id, 'created_at' => now()->subDays(40),
            ]),
            'canceledToday' => $this->createOrder(seller: $seller, attributes: [
                'order_status' => 'canceled', 'payment_status' => 'paid', 'customer_id' => $customer->id, 'created_at' => now(),
            ]),
        ];

        $query = array_map(
            static fn ($value) => $value === ':customer' ? $customer->id : $value,
            $query,
        );

        $response = $this->listFor(seller: $seller, query: $query)->assertOk();
        $fixtureIds = array_map(static fn (Order $order): int => $order->id, $fixtures);

        $this->assertEqualsCanonicalizing(
            array_map(static fn (string $key): int => $fixtureIds[$key], $expectedFixtures),
            array_values(array_intersect(array_column($response->json('content.data'), 'id'), $fixtureIds)),
        );
    }

    public static function listFilters(): array
    {
        $lastMonth = now()->subDays(40)->toDateString();

        return [
            'order status as list' => [['order_current_status' => ['pending', 'canceled']], ['pendingToday', 'canceledToday']],
            'order status as json string' => [['order_current_status' => '["delivered"]'], ['deliveredLastMonth']],
            'payment status as json string' => [['payment_status' => '["paid"]'], ['deliveredLastMonth', 'canceledToday']],
            'date type today' => [['date_type' => 'today'], ['pendingToday', 'canceledToday']],
            'custom date range' => [['date_type' => 'custom_date', 'start_date' => $lastMonth, 'end_date' => $lastMonth], ['deliveredLastMonth']],
            'customer' => [['customer_id' => ':customer'], ['pendingToday', 'canceledToday']],
            'combined json status and payment' => [['order_current_status' => '["pending","canceled"]', 'payment_status' => '["paid"]'], ['canceledToday']],
        ];
    }

    public function test_list_rejects_unknown_sort_key(): void
    {
        [$seller] = $this->approvedSellers(count: 1);

        $this->listFor(seller: $seller, query: ['sort_by' => 'order_amount'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.message', 'Choose a valid sort order');
    }

    #[DataProvider('sortKeys')]
    public function test_list_sort_keys(string $sortBy, array $expectedFixtures): void
    {
        [$seller] = $this->approvedSellers(count: 1);

        $fixtures = [
            'small' => $this->createOrder(seller: $seller, attributes: ['order_amount' => 10, 'order_status' => 'pending', 'created_at' => now()->subDays(1)]),
            'large' => $this->createOrder(seller: $seller, attributes: ['order_amount' => 900, 'order_status' => 'canceled', 'created_at' => now()->subDays(3)]),
            'medium' => $this->createOrder(seller: $seller, attributes: ['order_amount' => 300, 'order_status' => 'delivered', 'created_at' => now()->subDays(2)]),
        ];

        $fixtureIds = array_map(static fn (Order $order): int => $order->id, $fixtures);
        $response = $this->listFor(seller: $seller, query: ['sort_by' => $sortBy])->assertOk();

        $this->assertSame(
            array_map(static fn (string $key): int => $fixtureIds[$key], $expectedFixtures),
            array_values(array_intersect(array_column($response->json('content.data'), 'id'), $fixtureIds)),
        );
        $response->assertJsonPath('content.params.sort_by', $sortBy);
    }

    public static function sortKeys(): array
    {
        return self::wholesaleDataSets(static function (): array {
            return [
                'order id ascending' => [OrderService::SORT_ORDER_ID_ASC, ['small', 'large', 'medium']],
                'newest' => [OrderService::SORT_NEWEST, ['medium', 'large', 'small']],
                'order amount ascending' => [OrderService::SORT_ORDER_AMOUNT_ASC, ['small', 'medium', 'large']],
                'order amount descending' => [OrderService::SORT_ORDER_AMOUNT_DESC, ['large', 'medium', 'small']],
                'order date ascending' => [OrderService::SORT_ORDER_DATE_ASC, ['large', 'medium', 'small']],
                'order status ascending' => [OrderService::SORT_ORDER_STATUS_ASC, ['large', 'medium', 'small']],
            ];
        });
    }

    public function test_status_counts_match_list_totals(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);

        foreach (OrderService::SELLER_STATISTICS_STATUSES as $status) {
            $this->createOrder(seller: $seller, attributes: ['order_status' => $status, 'created_at' => now()]);
        }

        $this->createOrder(seller: $seller, attributes: ['order_status' => 'pending', 'created_at' => now()->subDays(40)]);
        $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL, attributes: ['order_status' => 'pending', 'created_at' => now()]);
        $this->createOrder(seller: $otherSeller, attributes: ['order_status' => 'pending', 'created_at' => now()]);

        $lastMonth = now()->subDays(40)->toDateString();

        foreach ([[], ['date_type' => 'today'], ['date_type' => 'custom_date', 'start_date' => $lastMonth, 'end_date' => $lastMonth]] as $query) {
            $counts = $this->getJson(self::STATUS_COUNTS_URI.'?'.http_build_query($query), $this->sellerHeaders($seller))
                ->assertOk()
                ->json('content.data');

            $this->assertSame([], array_diff(OrderService::SELLER_STATISTICS_STATUSES, array_keys($counts)));
            $this->assertSame(
                $this->listFor(seller: $seller, query: $query)->json('content.pagination.total'),
                array_sum($counts),
                'Counts disagree with the list for '.json_encode($query),
            );
        }

        $todayCounts = $this->getJson(self::STATUS_COUNTS_URI.'?date_type=today', $this->sellerHeaders($seller))->json('content.data');
        $customCounts = $this->getJson(self::STATUS_COUNTS_URI.'?'.http_build_query(['date_type' => 'custom_date', 'start_date' => $lastMonth, 'end_date' => $lastMonth]), $this->sellerHeaders($seller))->json('content.data');

        $this->assertSame(array_fill_keys(OrderService::SELLER_STATISTICS_STATUSES, 1), array_intersect_key($todayCounts, array_flip(OrderService::SELLER_STATISTICS_STATUSES)));
        $this->assertSame(1, $customCounts['pending']);
        $this->assertSame(1, array_sum($customCounts));
    }

    public function test_find_returns_retail_compatible_shape(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $group = 'wholesale-api-'.Str::random(12);
        $order = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $group]);
        $linkedOrder = $this->createOrder(seller: $seller, attributes: ['order_group_id' => $group]);
        $line = $this->createDetail(order: $order);

        $response = $this->getJson(self::LIST_URI.'/'.$order->id, $this->sellerHeaders($seller))->assertOk();
        $content = $response->json('content');

        $retailLine = OrderDetail::query()->with(['order.offlinePayments', 'order.customer', 'order.deliveryMan', 'productAllStatus', 'verificationImages', 'latestEditHistory', 'orderEditHistory'])->find($line->id);
        foreach (['delivery_partner_available', 'courier_shipment', 'modified_variation', 'current_stock', 'current_price', 'edit_order_payment_histories'] as $attribute) {
            $retailLine->setAttribute($attribute, null);
        }

        $retailLineKeys = array_keys((new RetailOrderDetailResource($retailLine))->resolve(request()));
        $retailOrderKeys = array_keys((new RetailOrderResource($retailLine->order))->resolve(request()));

        $this->assertSame([], array_diff($retailLineKeys, array_keys($content['data'][0])));
        $this->assertSame([], array_diff($retailOrderKeys, array_keys($content['data'][0]['order'])));
        $this->assertSame([], array_diff(array_keys($content['data'][0]), [...$retailLineKeys, 'wholesale']));
        $this->assertEqualsCanonicalizing(self::WHOLESALE_LINE_KEYS, array_keys($content['data'][0]['wholesale']));
        $this->assertSame([], array_diff(self::FIND_EXTRA_KEYS, array_keys($content)));

        $this->assertSame($order->id, $content['data'][0]['order_id']);
        $this->assertSame(BusinessModule::WHOLESALE, $content['data'][0]['order']['business_module']);
        $this->assertSame([['key' => 'Color', 'value' => 'Blue'], ['key' => 'Size', 'value' => 'Small']], $content['data'][0]['variation']);
        $this->assertEquals(10.0, $content['data'][0]['wholesale']['unit_price']);
        $this->assertEquals(28.0, $content['data'][0]['wholesale']['total_price']);
        $this->assertEquals(5.0, $content['data'][0]['wholesale']['tax_percent']);
        $this->assertSame('Blue-Small', $content['data'][0]['wholesale']['variation']);
        $this->assertSame([$linkedOrder->id], array_column($content['linked_orders'], 'id'));
        $this->assertSame($linkedOrder->id, $content['next_order_id']);
        $this->assertIsBool($content['delivery_verification_enabled']);
        $this->assertIsBool($content['data'][0]['delivery_partner_available']);
    }

    public function test_find_retail_order_id_is_not_found(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $this->createDetail(order: $retailOrder);

        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id, $this->sellerHeaders($seller)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.((int) Order::query()->max('id') + 1_000_000), $this->sellerHeaders($seller)));
    }

    public function test_wholesale_order_id_is_not_found_on_retail_endpoint(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $wholesaleOrder = $this->createOrder(seller: $seller);
        $this->createDetail(order: $wholesaleOrder);
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $retailLine = $this->createDetail(order: $retailOrder);

        $this->assertOrderNotFound($this->getJson(self::RETAIL_FIND_URI.$wholesaleOrder->id, $this->sellerHeaders($seller)));

        $this->getJson(self::RETAIL_FIND_URI.$retailOrder->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('content.data.0.id', $retailLine->id)
            ->assertJsonPath('content.data.0.order.business_module', BusinessModule::RETAIL);
    }

    public function test_seller_order_line_lookups_are_scoped_to_business_module(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $wholesaleLine = $this->createDetail(order: $this->createOrder(seller: $seller));
        $retailLine = $this->createDetail(order: $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL));

        $retailDetails = app(RetailOrderDetailService::class);
        $wholesaleDetails = app(OrderDetailService::class);

        $this->assertNull($retailDetails->findForSeller(id: $wholesaleLine->id, sellerId: $seller->id));
        $this->assertSame($retailLine->id, $retailDetails->findForSeller(id: $retailLine->id, sellerId: $seller->id)?->id);
        $this->assertTrue($retailDetails->getSellerOrderLines(orderId: $wholesaleLine->order_id, sellerId: $seller->id)->isEmpty());
        $this->assertSame([$retailLine->id], $retailDetails->getSellerOrderLines(orderId: $retailLine->order_id, sellerId: $seller->id)->pluck('id')->all());

        $this->assertNull($wholesaleDetails->findForSeller(id: $retailLine->id, sellerId: $seller->id));
        $this->assertSame($wholesaleLine->id, $wholesaleDetails->findForSeller(id: $wholesaleLine->id, sellerId: $seller->id)?->id);
        $this->assertTrue($wholesaleDetails->getSellerOrderLines(orderId: $retailLine->order_id, sellerId: $seller->id)->isEmpty());
        $this->assertSame([$wholesaleLine->id], $wholesaleDetails->getSellerOrderLines(orderId: $wholesaleLine->order_id, sellerId: $seller->id)->pluck('id')->all());
    }

    public function test_find_other_vendor_order_is_not_found(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $otherSellerOrder = $this->createOrder(seller: $otherSeller);
        $this->createDetail(order: $otherSellerOrder);
        $inHouseOrder = $this->createOrder(seller: null);
        $this->createDetail(order: $inHouseOrder);

        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$otherSellerOrder->id, $this->sellerHeaders($seller)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$inHouseOrder->id, $this->sellerHeaders($seller)));
        $this->getJson(self::LIST_URI.'/'.$otherSellerOrder->id, $this->sellerHeaders($otherSeller))->assertOk();
    }

    public function test_invoice_returns_pdf_bytes_of_own_wholesale_order(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createOrder(seller: $seller);
        $this->createDetail(order: $order);

        $bytes = $this->getJson(self::LIST_URI.'/'.$order->id.'/invoice', $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content.data');

        $this->assertIsArray($bytes);
        $this->assertStringStartsWith('%PDF-', pack('C*', ...$bytes));
    }

    public function test_invoice_of_retail_or_other_vendor_order_is_not_found(): void
    {
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $retailOrder = $this->createOrder(seller: $seller, businessModule: BusinessModule::RETAIL);
        $this->createDetail(order: $retailOrder);
        $otherSellerOrder = $this->createOrder(seller: $otherSeller);
        $this->createDetail(order: $otherSellerOrder);

        $this->getJson(self::LIST_URI.'/1/invoice')->assertUnauthorized();
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id.'/invoice', $this->sellerHeaders($seller)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$otherSellerOrder->id.'/invoice', $this->sellerHeaders($seller)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/not-a-number/invoice', $this->sellerHeaders($seller)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/not-a-number', $this->sellerHeaders($seller)));
    }

    public function test_find_includes_courier_state_for_wholesale_settings(): void
    {
        if (! addon_published_status('Courier') || ! Schema::hasColumn('courier_shipments', 'business_module')) {
            $this->markTestSkipped('Courier is unpublished or its business_module migrations have not run on this database.');
        }

        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createOrder(seller: $seller);
        $this->createDetail(order: $order);
        $provider = (string) array_key_first((array) config('courier.providers'));

        CourierShipment::query()->create([
            'owner_type' => 'vendor',
            'owner_id' => $seller->id,
            'business_module' => BusinessModule::WHOLESALE,
            'provider' => $provider,
            'consignment_id' => 'wholesale-api-'.$order->id,
            'host_order_reference' => (string) $order->id,
            'tracking_code' => 'wholesale-track-'.$order->id,
            'status' => 'pending',
        ]);

        $shipment = $this->getJson(self::LIST_URI.'/'.$order->id, $this->sellerHeaders($seller))
            ->assertOk()
            ->json('content.data.0.courier_shipment');

        $this->assertSame($provider, $shipment['provider']);
        $this->assertSame('wholesale-api-'.$order->id, $shipment['consignment_id']);
        $this->assertTrue($shipment['can_manage'], 'The api/v6/wholesale path did not resolve the wholesale vendor courier owner.');
    }

    public function test_list_runs_constant_queries(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        [$customer] = $this->customers(count: 1);

        $this->createOrderWithDetail(seller: $seller, customerId: $customer->id);
        $fewOrders = $this->countListQueries(seller: $seller);

        foreach (range(1, 4) as $ignored) {
            $this->createOrderWithDetail(seller: $seller, customerId: $customer->id);
        }

        $this->assertSame($fewOrders, $this->countListQueries(seller: $seller));
    }

    public function test_status_update_changes_status_and_restores_stock_on_cancel(): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller] = $this->approvedSellers(count: 1);
        $product = $this->createStockedProduct();
        $order = $this->createActionOrder(seller: $seller);
        $detail = $this->createStockedDetail(order: $order, product: $product, isStockDecreased: 1);

        $this->putJson($this->actionUri(order: $order, action: 'status'), ['order_status' => OrderService::STATUS_CANCELED, 'cause' => 'Buyer asked'], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_status_updated_200')
            ->assertJsonPath('content.id', $order->id)
            ->assertJsonPath('content.order_status', OrderService::STATUS_CANCELED)
            ->assertJsonPath('content.business_module', BusinessModule::WHOLESALE);

        $this->assertSame(OrderService::STATUS_CANCELED, $order->refresh()->order_status);
        $this->assertSame(self::ACTION_CURRENT_STOCK + self::ACTION_LINE_QUANTITY, (int) Product::query()->withoutGlobalScopes()->whereKey($product->id)->value('current_stock'));
        $this->assertSame(self::ACTION_VARIANT_STOCK + self::ACTION_LINE_QUANTITY, (int) ProductStock::query()->where(['product_id' => $product->id, 'variant' => self::ACTION_VARIANT])->value('qty'));
        $this->assertSame(0, (int) $detail->refresh()->is_stock_decreased);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'user_id' => $seller->id, 'user_type' => 'seller', 'status' => OrderService::STATUS_CANCELED, 'cause' => 'Buyer asked']);
    }

    public function test_status_update_on_admin_shipped_order_is_limited_to_preparation(): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller, attributes: ['shipping_responsibility' => self::INHOUSE_SHIPPING]);

        $this->putJson($this->actionUri(order: $order, action: 'status'), ['order_status' => OrderService::STATUS_DELIVERED], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertExactJson([
                'identical_code' => 'order_status_shipping_not_owned_403',
                'message' => 'Only the admin can update the delivery status of this order',
                'content' => null,
                'errors' => [],
            ]);
        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);

        $this->putJson($this->actionUri(order: $order, action: 'status'), ['order_status' => OrderService::STATUS_CONFIRMED], $this->sellerHeaders($seller))
            ->assertOk();
        $this->assertSame(OrderService::STATUS_CONFIRMED, $order->refresh()->order_status);
    }

    public function test_status_update_blocked_returns_403_message(): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller] = $this->approvedSellers(count: 1);

        $offlineOrder = $this->createActionOrder(seller: $seller, attributes: ['payment_method' => OrderService::PAYMENT_METHOD_OFFLINE]);

        $this->putJson($this->actionUri(order: $offlineOrder, action: 'status'), ['order_status' => OrderService::STATUS_CONFIRMED], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertExactJson([
                'identical_code' => 'order_status_offline_payment_unconfirmed_403',
                'message' => 'Please confirm the offline payment information before changing the order status.',
                'content' => null,
                'errors' => [],
            ]);
        $this->assertSame(OrderService::STATUS_PENDING, $offlineOrder->refresh()->order_status);

        $canceledOrder = $this->createActionOrder(seller: $seller, attributes: ['order_status' => OrderService::STATUS_CANCELED]);
        $this->createStockedDetail(order: $canceledOrder, product: $this->createStockedProduct(), isStockDecreased: 0, quantity: self::ACTION_CURRENT_STOCK + 1);

        $this->putJson($this->actionUri(order: $canceledOrder, action: 'status'), ['order_status' => OrderService::STATUS_PENDING], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_status_insufficient_stock_403')
            ->assertJsonPath('message', 'There is not enough quantity in stock');
        $this->assertSame(OrderService::STATUS_CANCELED, $canceledOrder->refresh()->order_status);
        $this->assertDatabaseMissing('order_status_histories', ['order_id' => $canceledOrder->id]);

        $deliveredOrder = $this->createActionOrder(seller: $seller, attributes: ['order_status' => OrderService::STATUS_DELIVERED, 'payment_status' => OrderService::PAYMENT_STATUS_PAID]);

        $this->putJson($this->actionUri(order: $deliveredOrder, action: 'status'), ['order_status' => OrderService::STATUS_RETURNED], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_status_already_delivered_403');
    }

    public function test_payment_status_accepts_only_paid(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $digitalOrder = $this->createActionOrder(seller: $seller, attributes: ['payment_method' => 'digital_payment']);

        $this->putJson($this->actionUri(order: $digitalOrder, action: 'payment-status'), ['payment_status' => OrderService::PAYMENT_STATUS_UNPAID], $this->sellerHeaders($seller))
            ->assertUnprocessable()
            ->assertJsonPath('identical_code', 'unprocessable_entity_422')
            ->assertJsonPath('message', 'The payment status can only be changed to paid')
            ->assertJsonPath('errors.0.error_code', 'payment_status');

        $this->putJson($this->actionUri(order: $digitalOrder, action: 'payment-status'), ['payment_status' => OrderService::PAYMENT_STATUS_PAID], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_payment_status_updated_200')
            ->assertJsonPath('content.payment_status', OrderService::PAYMENT_STATUS_PAID);
        $this->assertSame(OrderService::PAYMENT_STATUS_PAID, $digitalOrder->refresh()->payment_status);

        $cashOnDeliveryOrder = $this->createActionOrder(seller: $seller);

        $this->putJson($this->actionUri(order: $cashOnDeliveryOrder, action: 'payment-status'), ['payment_status' => OrderService::PAYMENT_STATUS_PAID], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_payment_status_before_delivery_403');
        $this->assertSame(OrderService::PAYMENT_STATUS_UNPAID, $cashOnDeliveryOrder->refresh()->payment_status);
    }

    public function test_delivery_man_assign_rejects_foreign_delivery_man(): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $order = $this->createActionOrder(seller: $seller);
        $foreignDeliveryMan = $this->createDeliveryMan(ownerId: $otherSeller->id);
        $ownDeliveryMan = $this->createDeliveryMan(ownerId: $seller->id);

        $this->putJson($this->actionUri(order: $order, action: 'delivery-man'), ['delivery_man_id' => $foreignDeliveryMan->id], $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'delivery_man_not_found_404');
        $this->assertNull($order->refresh()->delivery_man_id);

        $this->putJson($this->actionUri(order: $order, action: 'delivery-man'), ['delivery_man_id' => $ownDeliveryMan->id], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_delivery_man_assigned_200')
            ->assertJsonPath('content.delivery_man_id', $ownDeliveryMan->id)
            ->assertJsonPath('content.delivery_man.id', $ownDeliveryMan->id);
        $this->assertSame($ownDeliveryMan->id, (int) $order->refresh()->delivery_man_id);
        $this->assertSame(OrderService::DELIVERY_TYPE_SELF, $order->delivery_type);

        $deliveredOrder = $this->createActionOrder(seller: $seller, attributes: ['order_status' => OrderService::STATUS_DELIVERED, 'payment_status' => OrderService::PAYMENT_STATUS_PAID]);

        $this->putJson($this->actionUri(order: $deliveredOrder, action: 'delivery-man'), ['delivery_man_id' => $ownDeliveryMan->id], $this->sellerHeaders($seller))
            ->assertForbidden()
            ->assertJsonPath('identical_code', 'order_delivery_man_assign_not_allowed_403');
    }

    public function test_delivery_schedule_update(): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller, attributes: ['deliveryman_charge' => 7]);

        $this->putJson($this->actionUri(order: $order, action: 'delivery-schedule'), ['expected_delivery_date' => '2030-05-06'], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_delivery_schedule_updated_200')
            ->assertJsonPath('content.id', $order->id);

        $order->refresh();
        $this->assertSame('2030-05-06', substr((string) $order->expected_delivery_date, 0, 10));
        $this->assertEquals(7, (float) $order->deliveryman_charge);
        $this->assertDatabaseHas('order_expected_delivery_histories', ['order_id' => $order->id, 'user_id' => $seller->id, 'user_type' => 'seller', 'expected_delivery_date' => '2030-05-06']);

        $this->putJson($this->actionUri(order: $order, action: 'delivery-schedule'), ['deliveryman_charge' => 12, 'expected_delivery_date' => '2030-06-07'], $this->sellerHeaders($seller))
            ->assertOk();

        $order->refresh();
        $this->assertGreaterThan(0, (float) $order->deliveryman_charge);
        $this->assertNotEquals(7, (float) $order->deliveryman_charge);
        $this->assertSame('2030-06-07', substr((string) $order->expected_delivery_date, 0, 10));

        $this->putJson($this->actionUri(order: $order, action: 'delivery-schedule'), [], $this->sellerHeaders($seller))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Enter the deliveryman charge or the expected delivery date');
    }

    public function test_address_update(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller, attributes: ['shipping_address_data' => ['city' => 'Old city', 'zip' => '1000']]);

        $this->putJson($this->actionUri(order: $order, action: 'address'), [
            'address_type' => OrderService::ADDRESS_TYPE_SHIPPING,
            'contact_person_name' => 'Wholesale Buyer',
            'phone' => '01700000000',
            'city' => 'New city',
            'zip' => '1207',
            'email' => 'buyer@example.test',
            'address' => 'Road 7',
            'latitude' => '23.7',
            'longitude' => '90.4',
        ], $this->sellerHeaders($seller))
            ->assertOk()
            ->assertJsonPath('identical_code', 'order_address_updated_200')
            ->assertJsonPath('content.shipping_address_data.city', 'New city');

        $address = json_decode(json_encode($order->refresh()->shipping_address_data), true);
        $this->assertSame('Wholesale Buyer', $address['contact_person_name']);
        $this->assertSame('New city', $address['city']);
        $this->assertSame('1207', $address['zip']);
        $this->assertSame('Road 7', $address['address']);

        $this->putJson($this->actionUri(order: $order, action: 'address'), ['address_type' => 'pickup'], $this->sellerHeaders($seller))
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.error_code', 'address_type');
    }

    #[DataProvider('actionRequests')]
    public function test_actions_on_retail_or_other_vendor_order_return_404(string $action, array $body): void
    {
        Event::fake([OrderStatusEvent::class]);
        [$seller, $otherSeller] = $this->approvedSellers(count: 2);
        $deliveryMan = $this->createDeliveryMan(ownerId: $seller->id);
        $body = $this->resolveActionBody(body: $body, deliveryManId: $deliveryMan->id);

        $orders = [
            'own retail' => $this->createActionOrder(seller: $seller, businessModule: BusinessModule::RETAIL, attributes: ['payment_method' => 'digital_payment']),
            'other vendor' => $this->createActionOrder(seller: $otherSeller, attributes: ['payment_method' => 'digital_payment']),
            'in-house' => $this->createActionOrder(seller: null, attributes: ['payment_method' => 'digital_payment']),
        ];

        foreach ($orders as $case => $order) {
            $before = $order->refresh()->getAttributes();

            $response = $this->putJson($this->actionUri(order: $order, action: $action), $body, $this->sellerHeaders($seller));

            $this->assertSame(404, $response->status(), "{$case}: expected 404, got {$response->status()}.");
            $response->assertJsonPath('identical_code', 'order_not_found_404');
            $this->assertSame($before, $order->refresh()->getAttributes(), "{$case}: the order changed.");
        }

        $wholesaleOrder = $this->createActionOrder(seller: $seller, attributes: ['payment_method' => 'digital_payment']);
        $wholesaleBefore = $wholesaleOrder->refresh()->getAttributes();

        $this->putJson(self::RETAIL_FIND_URI.$wholesaleOrder->id.'/'.$action, $body, $this->sellerHeaders($seller))
            ->assertNotFound()
            ->assertJsonPath('identical_code', 'order_not_found_404');
        $this->assertSame($wholesaleBefore, $wholesaleOrder->refresh()->getAttributes(), 'The retail endpoint changed a wholesale order.');
    }

    public static function actionRequests(): array
    {
        return [
            'status' => ['status', ['order_status' => 'confirmed']],
            'payment status' => ['payment-status', ['payment_status' => 'paid']],
            'delivery man' => ['delivery-man', ['delivery_man_id' => self::OWN_DELIVERY_MAN]],
            'delivery schedule' => ['delivery-schedule', ['deliveryman_charge' => 5, 'expected_delivery_date' => '2030-01-02']],
            'address' => ['address', ['address_type' => 'shipping', 'contact_person_name' => 'Nobody', 'phone' => '01700000000', 'city' => 'Dhaka', 'zip' => '1207', 'email' => 'nobody@example.test', 'address' => 'Road 1', 'latitude' => '23.7', 'longitude' => '90.4']],
        ];
    }

    public function test_actions_forbidden_when_wholesale_is_off(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller);

        $this->app->instance(BusinessModuleManager::class, new class extends BusinessModuleManager
        {
            public function isInstalled(string $key): bool
            {
                return $key !== BusinessModule::WHOLESALE && parent::isInstalled($key);
            }
        });

        foreach (self::actionRequests() as [$action, $body]) {
            $this->putJson($this->actionUri(order: $order, action: $action), $this->resolveActionBody(body: $body, deliveryManId: 1), $this->sellerHeaders($seller))
                ->assertForbidden()
                ->assertExactJson([
                    'identical_code' => 'wholesale_feature_disabled_403',
                    'message' => 'Wholesale feature is not available for vendors',
                    'content' => null,
                    'errors' => [],
                ]);
        }

        $this->assertSame(OrderService::STATUS_PENDING, $order->refresh()->order_status);
    }

    public function test_validation_errors_use_v6_envelope(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller);

        $cases = [
            'status' => [['order_status' => 'shipped'], 'order_status', 'Choose a valid order status'],
            'payment-status' => [[], 'payment_status', 'Choose a payment status'],
            'delivery-man' => [['delivery_man_id' => 0], 'delivery_man_id', 'Choose a valid delivery man'],
            'delivery-schedule' => [['expected_delivery_date' => '06/05/2030'], 'expected_delivery_date', 'Enter the expected delivery date as year-month-day'],
            'address' => [[], 'address_type', 'Choose whether to update the shipping or the billing address'],
        ];

        foreach ($cases as $action => [$body, $field, $message]) {
            $this->putJson($this->actionUri(order: $order, action: $action), $body, $this->sellerHeaders($seller))
                ->assertUnprocessable()
                ->assertExactJson([
                    'identical_code' => 'unprocessable_entity_422',
                    'message' => $message,
                    'content' => null,
                    'errors' => [['error_code' => $field, 'message' => $message]],
                ]);
        }

        $this->putJson($this->actionUri(order: $order, action: 'status'), ['order_status' => 'confirmed'])->assertUnauthorized();
    }

    public function test_courier_dispatch_for_wholesale_order_uses_wholesale_settings(): void
    {
        if (! addon_published_status('Courier') || ! Schema::hasColumn('courier_provider_settings', 'business_module')) {
            $this->markTestSkipped('Courier is unpublished or its business_module migrations have not run on this database.');
        }

        [$seller] = $this->approvedSellers(count: 1);
        $order = $this->createActionOrder(seller: $seller);

        config(['courier.providers' => [WholesaleOrderLedgerProvider::PROVIDER => WholesaleOrderLedgerProvider::class]]);
        Event::fake([ShipmentDispatched::class, ShipmentStatusUpdated::class]);
        WholesaleOrderLedgerProvider::$credentialsSeen = [];
        $this->withoutMiddleware(VendorDeliveryPartnerSetupMiddleware::class);

        $configService = $this->app->make(CourierConfigService::class);
        foreach ([BusinessModule::RETAIL => 'retail-vendor-token', BusinessModule::WHOLESALE => 'wholesale-vendor-token'] as $businessModule => $token) {
            $configService->save(
                data: ['provider' => WholesaleOrderLedgerProvider::PROVIDER, 'is_enabled' => true, 'credentials' => ['token' => $token], 'environment' => CourierProvider::ENVIRONMENT_SANDBOX],
                owner: CourierOwner::vendor(id: $seller->id, businessModule: $businessModule),
            );
        }

        $response = $this->postJson('api/v6/seller/courier/dispatch', [
            'provider' => WholesaleOrderLedgerProvider::PROVIDER,
            'host_order_reference' => (string) $order->id,
            'recipient_name' => 'Wholesale Buyer',
            'recipient_phone' => '01700000000',
            'recipient_address' => '1/B/3 Farmgate, Dhaka',
            'city_id' => '1',
            'zone_id' => '1',
            'area_id' => '1',
            'weight' => 1.5,
            'cod_amount' => 250,
        ], $this->sellerHeaders($seller));

        $this->assertSame(201, $response->status(), (string) $response->getContent());
        $this->assertSame(['wholesale-vendor-token'], WholesaleOrderLedgerProvider::$credentialsSeen);
        $this->assertDatabaseHas('courier_shipments', [
            'host_order_reference' => (string) $order->id,
            'owner_type' => 'vendor',
            'owner_id' => $seller->id,
            'business_module' => BusinessModule::WHOLESALE,
        ]);
    }

    private function countListQueries(Seller $seller): int
    {
        $this->listFor(seller: $seller);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->listFor(seller: $seller)->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }

    private function listFor(Seller $seller, array $query = []): TestResponse
    {
        return $this->getJson(
            self::LIST_URI.'?'.http_build_query(['limit' => self::FIXTURE_LIMIT, ...$query]),
            $this->sellerHeaders($seller),
        );
    }

    private function assertOrderNotFound(TestResponse $response): void
    {
        $response->assertNotFound()->assertJsonPath('identical_code', 'order_not_found_404');
    }

    private function createOrderWithDetail(Seller $seller, int $customerId): Order
    {
        $order = $this->createOrder(seller: $seller, attributes: ['customer_id' => $customerId]);
        $this->createDetail(order: $order);

        return $order;
    }

    private function createOrder(?Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        $template = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order');
        $order = $template->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_id' => $seller?->id ?? 1,
            'seller_is' => $seller === null ? 'admin' : 'seller',
            'is_guest' => 0,
            'order_type' => 'default_type',
            'order_group_id' => 'wholesale-api-'.Str::random(12),
            'delivery_service_name' => null,
            'third_party_delivery_tracking_id' => null,
            'created_at' => now(),
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createDetail(Order $order): OrderDetail
    {
        $productId = (int) $this->requireFixture(
            Product::query()->withoutGlobalScopes()->where('status', 1)->value('id'),
            'active product',
        );

        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $productId,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $productId, 'name' => 'Wholesale api line', 'product_type' => 'physical', 'unit_price' => 10]),
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

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
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
            ->where('id', '!=', 1)
            ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
            ->orderBy('id')
            ->limit($count)
            ->get();

        if ($sellers->count() < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' approved sellers with a vendor shop.');
        }

        return $sellers
            ->map(static function (Seller $seller): Seller {
                $seller->forceFill(['auth_token' => Str::random(50)])->saveQuietly();

                return $seller;
            })
            ->all();
    }

    private function actionUri(Order $order, string $action): string
    {
        return self::LIST_URI.'/'.$order->id.'/'.$action;
    }

    private function resolveActionBody(array $body, int $deliveryManId): array
    {
        return array_map(static fn (mixed $value): mixed => $value === self::OWN_DELIVERY_MAN ? $deliveryManId : $value, $body);
    }

    private function createActionOrder(?Seller $seller, string $businessModule = BusinessModule::WHOLESALE, array $attributes = []): Order
    {
        [$customer] = $this->customers(count: 1);

        return $this->createOrder(seller: $seller, businessModule: $businessModule, attributes: [
            'customer_id' => $customer->id,
            'order_status' => OrderService::STATUS_PENDING,
            'payment_status' => OrderService::PAYMENT_STATUS_UNPAID,
            'payment_method' => OrderService::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'order_amount' => 100,
            'shipping_responsibility' => self::SELLERWISE_SHIPPING,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'delivery_man_id' => null,
            'deliveryman_charge' => 0,
            'expected_delivery_date' => null,
            'delivery_type' => null,
            'is_pause' => 0,
            ...$attributes,
        ]);
    }

    private function createStockedProduct(): Product
    {
        $template = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product');
        $product = $template->replicate();
        $product->forceFill([
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'current_stock' => self::ACTION_CURRENT_STOCK,
            'slug' => 'wholesale-api-action-'.Str::lower(Str::random(12)),
            'code' => 'WSAPI'.random_int(10000, 99999),
        ])->saveQuietly();

        ProductStock::query()->create(['product_id' => $product->id, 'variant' => self::ACTION_VARIANT, 'sku' => 'WSAPI-'.$product->id, 'price' => 25, 'qty' => self::ACTION_VARIANT_STOCK]);

        return $product;
    }

    private function createStockedDetail(Order $order, Product $product, int $isStockDecreased, int $quantity = self::ACTION_LINE_QUANTITY): OrderDetail
    {
        $detail = new OrderDetail;
        $detail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $order->seller_id,
            'product_details' => json_encode(['id' => $product->id, 'name' => 'Wholesale api action line', 'product_type' => 'physical']),
            'qty' => $quantity,
            'price' => 25,
            'discount' => 0,
            'tax' => 0,
            'tax_model' => 'exclude',
            'variant' => self::ACTION_VARIANT,
            'variation' => ['Color' => 'Blue', 'Size' => 'Small'],
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'discount_type' => 'discount_on_product',
            'is_stock_decreased' => $isStockDecreased,
        ])->save();

        return $detail;
    }

    private function createDeliveryMan(int $ownerId): DeliveryMan
    {
        $deliveryMan = new DeliveryMan;
        $deliveryMan->forceFill([
            'seller_id' => $ownerId,
            'f_name' => 'Wholesale',
            'l_name' => 'Rider',
            'phone' => '01'.random_int(100000000, 999999999),
            'email' => 'wholesale-rider-'.Str::lower(Str::random(10)).'@example.test',
            'password' => bcrypt(Str::random(16)),
            'is_active' => 1,
        ])->save();

        return $deliveryMan;
    }
}

class WholesaleOrderLedgerProvider extends CourierProvider implements CreatesOrders
{
    public const PROVIDER = 'wholesale_order_ledger';

    /** @var array<int, string|null> */
    public static array $credentialsSeen = [];

    public function getName(): string
    {
        return self::PROVIDER;
    }

    public function displayName(): string
    {
        return 'Wholesale Order Ledger';
    }

    public function supportedCountries(): array
    {
        return ['BD'];
    }

    public function credentialFields(): array
    {
        return [['key' => 'token', 'label' => 'Token', 'required' => true]];
    }

    public function createOrder(OrderData $order): ShipmentResult
    {
        static::$credentialsSeen[] = $this->credentials['token'] ?? null;

        return new ShipmentResult(
            consignmentId: 'wholesale-ledger-'.$order->hostOrderReference,
            status: ShipmentStatus::Pending,
            trackingCode: 'wholesale-ledger-track-'.$order->hostOrderReference,
        );
    }
}
