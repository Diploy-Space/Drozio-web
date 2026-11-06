<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboard;
use App\Models\Seller;
use App\Services\DashboardChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Cache-behaviour guard for the admin AND vendor dashboards.
 *
 * Exercises the real controller methods (no HTTP routing, so the domain-scoped
 * admin routes and login captcha are irrelevant) and asserts:
 *  - the year-chart aggregates are cached and reused (miss = N queries, hit = 0);
 *  - every dashboard block caches under the expected key;
 *  - vendor cache keys are namespaced by seller id and never collide/leak;
 *  - the real-time endpoints (SQL-count rewrite) return a valid payload.
 *
 * Runs inside a DB transaction (rolls back) against the mysql_testing DB. The
 * test cache store is `array`; each test flushes it for isolation.
 */
class DashboardCacheContractTest extends ContractTestCase
{
    /** Inserts an approved seller and returns its id. */
    private function makeSeller(string $email): int
    {
        return DB::table('sellers')->insertGetId([
            'f_name' => 'Cache', 'l_name' => 'Vendor', 'email' => $email,
            'password' => bcrypt('x'), 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Seeds CURRENT-year paid orders + disburse transactions for a scope so the
     * year charts (startOfYear..endOfYear) are non-zero. Amount drives the series.
     */
    private function seedCurrentYearSales(string $sellerIs, int $sellerId, int $amount): void
    {
        $date = now()->startOfYear()->addMonths(3)->format('Y-m-d H:i:s');
        DB::table('orders')->insert([
            'customer_id' => 1, 'is_guest' => 0, 'seller_id' => $sellerId, 'seller_is' => $sellerIs,
            'order_status' => 'delivered', 'payment_status' => 'paid', 'order_type' => 'default_type',
            'order_amount' => $amount, 'created_at' => $date, 'updated_at' => $date,
        ]);
        DB::table('order_transactions')->insert([
            'seller_id' => $sellerId, 'seller_is' => $sellerIs, 'order_id' => 0, 'received_by' => 'seller',
            'status' => 'disburse', 'order_amount' => $amount,
            'seller_amount' => $amount * 0.9, 'admin_commission' => $amount * 0.1,
            'created_at' => $date, 'updated_at' => $date,
        ]);
    }

    // ---------------------------------------------------------------- ADMIN

    #[Test]
    public function admin_year_chart_cache_is_populated_and_reused(): void
    {
        DB::table('admin_wallets')->insert(['admin_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->seedCurrentYearSales('admin', 1, 1000);
        Cache::flush();

        $key = DashboardChartService::adminYearChartsKey();
        $this->assertFalse(Cache::has($key), 'Chart key should be cold before the first load.');

        app(AdminDashboard::class)->index(Request::create('/', 'GET'), null);

        $this->assertTrue(Cache::has($key), 'Admin index() must populate the year-chart cache.');
        $cached = Cache::get($key);
        $this->assertCount(5, $cached, 'Cached chart bundle must hold all 5 series.');
        $this->assertCount(12, $cached['inHouseEarning'], 'Each series must have 12 month buckets.');
        // The status block is cached too (default range this_year).
        $this->assertTrue(Cache::has('dash_admin_orderstatus_this_year'), 'Order-status block must be cached per range.');
    }

    #[Test]
    public function chart_cache_hit_serves_zero_queries(): void
    {
        DB::table('admin_wallets')->insert(['admin_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->seedCurrentYearSales('admin', 1, 1000);
        Cache::flush();

        $chart = app(DashboardChartService::class);
        $key = DashboardChartService::adminYearChartsKey();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $miss = Cache::remember($key, CACHE_FOR_1_HOUR, fn () => $chart->getAdminYearCharts());
        $missQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $hit = Cache::remember($key, CACHE_FOR_1_HOUR, fn () => $chart->getAdminYearCharts());
        $hitQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $missQueries, 'Cold chart build must issue queries.');
        $this->assertSame(0, $hitQueries, 'A cache hit must issue zero queries.');
        $this->assertEquals($miss, $hit, 'Cached value must equal freshly computed value.');
    }

    #[Test]
    public function admin_ajax_chart_endpoints_cache_per_range(): void
    {
        $this->seedCurrentYearSales('admin', 1, 1000);
        $this->seedCurrentYearSales('seller', 5, 2000);
        Cache::flush();
        $controller = app(AdminDashboard::class);

        $orderResp = $controller->getOrderStatistics(Request::create('/', 'GET', ['type' => 'yearEarn']));
        $earnResp = $controller->getEarningStatistics(Request::create('/', 'GET', ['type' => 'MonthEarn']));

        $this->assertSame(200, $orderResp->getStatusCode());
        $this->assertSame(200, $earnResp->getStatusCode());
        $this->assertTrue(Cache::has('dash_admin_orderstat_yearEarn'), 'Order-stats AJAX must cache per range.');
        $this->assertTrue(Cache::has('dash_admin_earnstat_MonthEarn'), 'Earning-stats AJAX must cache per range.');
        // Distinct ranges → distinct keys (no cross-range collision).
        $this->assertFalse(Cache::has('dash_admin_orderstat_MonthEarn'));
    }

    #[Test]
    public function admin_realtime_activities_returns_sql_count_payload(): void
    {
        $resp = app(AdminDashboard::class)->getRealTimeActivities();
        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode($resp->getContent(), true);
        foreach (['new_order_count', 'restockProductCount', 'newMessagesExist'] as $key) {
            $this->assertArrayHasKey($key, $body, "Real-time payload missing {$key}.");
        }
        $this->assertIsInt($body['new_order_count']);
    }

    // --------------------------------------------------------------- VENDOR

    #[Test]
    public function vendor_chart_cache_is_namespaced_per_seller_and_isolated(): void
    {
        $a = $this->makeSeller('cache.a@example.test');
        $b = $this->makeSeller('cache.b@example.test');
        DB::table('seller_wallets')->insert([
            ['seller_id' => $a, 'created_at' => now(), 'updated_at' => now()],
            ['seller_id' => $b, 'created_at' => now(), 'updated_at' => now()],
        ]);
        // Different current-year sales → different chart series per seller.
        $this->seedCurrentYearSales('seller', $a, 1000);
        $this->seedCurrentYearSales('seller', $b, 5000);
        Cache::flush();

        $controller = app(VendorDashboard::class);

        $this->actingAs(Seller::find($a), 'seller');
        $controller->index(Request::create('/', 'GET'), null);
        $this->actingAs(Seller::find($b), 'seller');
        $controller->index(Request::create('/', 'GET'), null);

        $keyA = DashboardChartService::vendorYearChartsKey($a);
        $keyB = DashboardChartService::vendorYearChartsKey($b);
        $this->assertNotSame($keyA, $keyB, 'Vendor chart keys must be namespaced by seller id.');
        $this->assertTrue(Cache::has($keyA));
        $this->assertTrue(Cache::has($keyB));

        // Isolation: each seller's cached series reflects only its own sales.
        $sumA = array_sum(Cache::get($keyA)['vendorEarning']);
        $sumB = array_sum(Cache::get($keyB)['vendorEarning']);
        $this->assertGreaterThan(0, $sumA);
        $this->assertGreaterThan(0, $sumB);
        $this->assertNotEqualsWithDelta($sumA, $sumB, 0.01, 'Seller A and B chart caches must differ (no cross-vendor bleed).');
        // Seller B (5000) must be ~5x seller A (1000).
        $this->assertGreaterThan($sumA, $sumB);

        // Status block is cached with the seller id in the key (overall range from index).
        $this->assertTrue(Cache::has("dash_vendor_orderstatus_{$a}_overall"));
        $this->assertTrue(Cache::has("dash_vendor_orderstatus_{$b}_overall"));
    }

    #[Test]
    public function vendor_earning_ajax_caches_with_seller_id_in_key(): void
    {
        $a = $this->makeSeller('cache.earn.a@example.test');
        $b = $this->makeSeller('cache.earn.b@example.test');
        $this->seedCurrentYearSales('seller', $a, 1000);
        $this->seedCurrentYearSales('seller', $b, 5000);
        Cache::flush();
        $controller = app(VendorDashboard::class);

        $this->actingAs(Seller::find($a), 'seller');
        $respA = $controller->getEarningStatistics(Request::create('/', 'GET', ['type' => 'yearEarn']));
        $this->actingAs(Seller::find($b), 'seller');
        $respB = $controller->getEarningStatistics(Request::create('/', 'GET', ['type' => 'yearEarn']));

        $this->assertSame(200, $respA->getStatusCode());
        $this->assertSame(200, $respB->getStatusCode());
        $this->assertTrue(Cache::has("dash_vendor_earnstat_{$a}_yearEarn"), 'Vendor earning AJAX key must carry seller id.');
        $this->assertTrue(Cache::has("dash_vendor_earnstat_{$b}_yearEarn"));
        // A's key must never hold B's data.
        $this->assertNotEquals(
            Cache::get("dash_vendor_earnstat_{$a}_yearEarn"),
            Cache::get("dash_vendor_earnstat_{$b}_yearEarn"),
            'Per-seller earning caches must not be identical.'
        );
    }

    #[Test]
    public function vendor_realtime_activities_is_seller_scoped_payload(): void
    {
        $a = $this->makeSeller('cache.rt@example.test');
        $this->actingAs(Seller::find($a), 'seller');
        $resp = app(VendorDashboard::class)->getRealTimeActivities();
        $this->assertSame(200, $resp->getStatusCode());
        $body = json_decode($resp->getContent(), true);
        $this->assertArrayHasKey('new_order_count', $body);
        $this->assertIsInt($body['new_order_count']);
    }
}
