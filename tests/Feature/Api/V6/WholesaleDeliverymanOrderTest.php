<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Order;
use App\Models\User;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Wholesale\app\Services\OrderService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Api\Support\InteractsWithWholesaleDeliveryman;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleDeliverymanOrderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleDeliveryman;
    use RequiresWholesaleAddon;

    private const LIST_URI = 'api/v6/wholesale/delivery-man/orders';

    private const RETAIL_URI = 'api/v6/delivery-man/orders';

    private const WHOLESALE_LINE_KEYS = ['unit_price', 'tax_percent', 'variation', 'thumbnail', 'total_price', 'is_product_unavailable'];

    private int $nextCustomerId = 0;

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

    public function test_orders_require_v6_token(): void
    {
        foreach ([
            self::LIST_URI,
            self::LIST_URI.'/current',
            self::LIST_URI.'/search',
            self::LIST_URI.'/summary',
            self::LIST_URI.'/1',
            self::LIST_URI.'/1/items',
            self::LIST_URI.'/1/delivery-history',
            self::LIST_URI.'/1/last-location',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
            $this->getJson($uri, ['Authorization' => 'Bearer '.Str::random(40)])->assertUnauthorized();
        }
    }

    public function test_orders_are_forbidden_when_wholesale_is_off(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $this->disableWholesaleModule();

        $this->getJson(self::LIST_URI, $this->deliveryManHeaders($deliveryMan))
            ->assertForbidden()
            ->assertExactJson([
                'identical_code' => 'wholesale_feature_disabled_403',
                'message' => 'Wholesale feature is not available for delivery men',
                'content' => null,
                'errors' => [],
            ]);
    }

    public function test_list_and_current_never_return_a_retail_order(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_PENDING]);
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_PENDING]);

        $listData = $this->getJson(self::LIST_URI, $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data');
        $listIds = array_column($listData, 'id');
        $this->assertContains($wholesaleOrder->id, $listIds);
        $this->assertNotContains($retailOrder->id, $listIds);
        $this->assertSame([BusinessModule::WHOLESALE], array_values(array_unique(array_column($listData, 'business_module'))));

        $currentIds = array_column($this->getJson(self::LIST_URI.'/current', $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data'), 'id');
        $this->assertContains($wholesaleOrder->id, $currentIds);
        $this->assertNotContains($retailOrder->id, $currentIds);
    }

    public function test_current_holds_only_ongoing_statuses_soonest_first(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $ongoingLater = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => OrderService::STATUS_PROCESSING,
            'expected_delivery_date' => now()->addDays(5),
        ]);
        $ongoingSooner = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => OrderService::STATUS_CONFIRMED,
            'expected_delivery_date' => now()->addDay(),
        ]);
        $delivered = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_DELIVERED]);
        $canceled = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_CANCELED]);

        $ids = array_column($this->getJson(self::LIST_URI.'/current', $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data'), 'id');

        $this->assertSame([$ongoingSooner->id, $ongoingLater->id], array_values(array_intersect($ids, [$ongoingSooner->id, $ongoingLater->id])));
        $this->assertNotContains($delivered->id, $ids);
        $this->assertNotContains($canceled->id, $ids);
    }

    public function test_list_filters_status_pause_and_custom_date(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $pendingToday = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => OrderService::STATUS_PENDING, 'is_pause' => 0, 'created_at' => now(),
        ]);
        $pausedToday = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => OrderService::STATUS_PENDING, 'is_pause' => 1, 'created_at' => now(),
        ]);
        $deliveredLastMonth = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: [
            'order_status' => OrderService::STATUS_DELIVERED, 'is_pause' => 0, 'created_at' => now()->subDays(40),
        ]);
        $fixtureIds = [$pendingToday->id, $pausedToday->id, $deliveredLastMonth->id];

        $statusIds = array_column($this->getJson(self::LIST_URI.'?status='.OrderService::STATUS_DELIVERED, $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data'), 'id');
        $this->assertSame([$deliveredLastMonth->id], array_values(array_intersect($statusIds, $fixtureIds)));

        $pauseIds = array_column($this->getJson(self::LIST_URI.'?is_pause=1', $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data'), 'id');
        $this->assertSame([$pausedToday->id], array_values(array_intersect($pauseIds, $fixtureIds)));

        $lastMonth = now()->subDays(40)->toDateString();
        $dateQuery = http_build_query(['date_type' => 'custom_date', 'start_date' => $lastMonth, 'end_date' => $lastMonth]);
        $dateIds = array_column($this->getJson(self::LIST_URI.'?'.$dateQuery, $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data'), 'id');
        $this->assertSame([$deliveredLastMonth->id], array_values(array_intersect($dateIds, $fixtureIds)));
    }

    public function test_search_falls_back_to_customer_name(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $customer = $this->createCustomer(firstName: 'Zzyxa', lastName: 'Wholetester');
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['customer_id' => $customer->id]);

        $response = $this->getJson(self::LIST_URI.'/search?search=Zzyxa', $this->deliveryManHeaders($deliveryMan))->assertOk();

        $this->assertContains($order->id, array_column($response->json('content.data'), 'id'));
    }

    public function test_summary_counts_wholesale_orders_only(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_PENDING]);
        $this->createWholesaleOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_DELIVERED]);
        $this->createRetailOrder(deliveryMan: $deliveryMan, overrides: ['order_status' => OrderService::STATUS_DELIVERED]);

        $summary = $this->getJson(self::LIST_URI.'/summary', $this->deliveryManHeaders($deliveryMan))->assertOk()->json('content.data');

        $this->assertSame(2, $summary['total_delivery']);
        $this->assertSame(1, $summary['completed']);
        $this->assertSame(1, $summary['ongoing']);
        $this->assertSame(0, $summary['paused']);
    }

    public function test_find_response_matches_retail_shape_plus_delivery_verification_flag(): void
    {
        $wholesaleDeliveryMan = $this->createDeliveryMan();
        $retailDeliveryMan = $this->createDeliveryMan();
        $wholesaleOrder = $this->createWholesaleOrder(deliveryMan: $wholesaleDeliveryMan);
        $retailOrder = $this->createRetailOrder(deliveryMan: $retailDeliveryMan);

        $retailContent = $this->getJson(self::RETAIL_URI.'/'.$retailOrder->id, $this->deliveryManHeaders($retailDeliveryMan))
            ->assertOk()
            ->json('content');

        $wholesaleContent = $this->getJson(self::LIST_URI.'/'.$wholesaleOrder->id, $this->deliveryManHeaders($wholesaleDeliveryMan))
            ->assertOk()
            ->json('content');

        $this->assertSame([], array_diff(array_keys($retailContent), array_keys($wholesaleContent)));
        $this->assertSame(['delivery_verification_enabled'], array_values(array_diff(array_keys($wholesaleContent), array_keys($retailContent))));
        $this->assertSame(array_keys($retailContent['data']), array_keys($wholesaleContent['data']));
        $this->assertIsBool($wholesaleContent['delivery_verification_enabled']);
    }

    #[DataProvider('deliveryVerificationFlags')]
    public function test_find_delivery_verification_enabled_reflects_the_wholesale_order_service(bool $isEnabled): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan);

        $this->app->instance(OrderService::class, new class(app(Order::class)) extends OrderService
        {
            public bool $verificationEnabled = false;

            public function isDeliveryVerificationEnabled(): bool
            {
                return $this->verificationEnabled;
            }
        });
        $this->app->make(OrderService::class)->verificationEnabled = $isEnabled;

        $this->getJson(self::LIST_URI.'/'.$order->id, $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->assertJsonPath('content.delivery_verification_enabled', $isEnabled);
    }

    public static function deliveryVerificationFlags(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }

    public function test_items_carry_the_wholesale_block(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan);

        $line = $this->getJson(self::LIST_URI.'/'.$order->id.'/items', $this->deliveryManHeaders($deliveryMan))
            ->assertOk()
            ->json('content.data.0');

        $this->assertEqualsCanonicalizing(self::WHOLESALE_LINE_KEYS, array_keys($line['wholesale']));
        $this->assertFalse($line['wholesale']['is_product_unavailable']);
    }

    public function test_retail_order_id_is_not_found_on_every_scoped_route(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $retailOrder = $this->createRetailOrder(deliveryMan: $deliveryMan);

        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id, $this->deliveryManHeaders($deliveryMan)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id.'/items', $this->deliveryManHeaders($deliveryMan)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id.'/delivery-history', $this->deliveryManHeaders($deliveryMan)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$retailOrder->id.'/last-location', $this->deliveryManHeaders($deliveryMan)));
    }

    public function test_non_numeric_order_id_answers_the_api_envelope_not_a_route_miss(): void
    {
        $deliveryMan = $this->createDeliveryMan();

        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/abc', $this->deliveryManHeaders($deliveryMan)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/abc/items', $this->deliveryManHeaders($deliveryMan)));

        foreach ([self::LIST_URI.'/abc/delivery-history', self::LIST_URI.'/abc/last-location'] as $uri) {
            $this->getJson($uri, $this->deliveryManHeaders($deliveryMan))
                ->assertUnprocessable()
                ->assertJsonPath('identical_code', 'unprocessable_entity_422')
                ->assertJsonPath('errors.0.error_code', 'order_id');
        }
    }

    public function test_wholesale_order_id_is_not_found_on_retail_endpoint(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder(deliveryMan: $deliveryMan);

        $this->assertOrderNotFound($this->getJson(self::RETAIL_URI.'/'.$order->id, $this->deliveryManHeaders($deliveryMan)));
    }

    public function test_another_delivery_mans_wholesale_order_is_not_found(): void
    {
        $deliveryMan = $this->createDeliveryMan();
        $otherDeliveryMan = $this->createDeliveryMan();
        $order = $this->createWholesaleOrder(deliveryMan: $otherDeliveryMan);

        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$order->id, $this->deliveryManHeaders($deliveryMan)));
        $this->assertOrderNotFound($this->getJson(self::LIST_URI.'/'.$order->id.'/items', $this->deliveryManHeaders($deliveryMan)));
    }

    private function assertOrderNotFound(TestResponse $response): void
    {
        $response->assertNotFound()->assertJsonPath('identical_code', 'order_not_found_404');
    }

    private function createCustomer(string $firstName, string $lastName): User
    {
        $template = $this->requireFixture(User::query()->where('id', '>', 0)->orderBy('id')->first(), 'customer');
        $customer = $template->replicate();
        $customer->forceFill([
            'id' => $this->nextCustomerId(),
            'name' => $firstName.' '.$lastName,
            'f_name' => $firstName,
            'l_name' => $lastName,
            'email' => Str::lower($firstName).'-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '01'.random_int(100000000, 999999999),
        ])->save();

        return $customer;
    }

    private function nextCustomerId(): int
    {
        if ($this->nextCustomerId === 0) {
            $this->nextCustomerId = (int) User::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextCustomerId;
    }
}
