<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboard;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;

/**
 * Query-count guard for the admin and vendor dashboards.
 *
 * Invokes the real index() methods (they return unrendered Views, so all data
 * queries run) and counts DB queries cold (cache miss) and warm (chart cache hit).
 * Also asserts the vendor status counts are seller-scoped and match the old
 * per-status loop.
 */
class DashboardQueryContractTest extends ContractTestCase
{
    private function seedDashboard(): int
    {
        DB::table('admin_wallets')->insert(['admin_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $sellerId = DB::table('sellers')->insertGetId([
            'f_name' => 'Dash', 'l_name' => 'Vendor', 'email' => 'dash.vendor@example.test',
            'password' => bcrypt('x'), 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('seller_wallets')->insert(['seller_id' => $sellerId, 'created_at' => now(), 'updated_at' => now()]);

        // One order per status for this seller (drives getOrderStatusArray).
        foreach (getOrderStatusList() as $i => $status) {
            DB::table('orders')->insert([
                'customer_id' => ($i % 2) + 1, 'is_guest' => 0, 'seller_id' => $sellerId, 'seller_is' => 'seller',
                'order_status' => $status, 'payment_status' => 'paid', 'order_type' => 'default_type',
                'order_amount' => 100, 'created_at' => '2024-06-01 00:00:00', 'updated_at' => '2024-06-01 00:00:00',
            ]);
            DB::table('order_transactions')->insert([
                'seller_id' => $sellerId, 'seller_is' => 'seller', 'status' => 'disburse',
                'seller_amount' => 50, 'admin_commission' => 10,
                'created_at' => '2024-06-01 00:00:00', 'updated_at' => '2024-06-01 00:00:00',
            ]);
        }
        return $sellerId;
    }

    private function countIndexQueries(object $controller): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $controller->index(Request::create('/', 'GET'), null);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();
        return $n;
    }

    #[Test]
    public function vendor_dashboard_query_count(): void
    {
        $sellerId = $this->seedDashboard();

        // A different seller with a 'delivered' order — must NOT be counted for our vendor.
        $otherSellerId = DB::table('sellers')->insertGetId([
            'f_name' => 'Other', 'l_name' => 'Vendor', 'email' => 'other.vendor@example.test',
            'password' => bcrypt('x'), 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        // Another seller with an order from a 3rd customer — must not affect our vendor.
        DB::table('orders')->insert([
            'customer_id' => 99, 'is_guest' => 0, 'seller_id' => $otherSellerId, 'seller_is' => 'seller',
            'order_status' => 'delivered', 'payment_status' => 'paid', 'order_type' => 'default_type',
            'order_amount' => 100, 'created_at' => '2024-06-01 00:00:00', 'updated_at' => '2024-06-01 00:00:00',
        ]);

        $this->actingAs(Seller::find($sellerId), 'seller');
        $controller = app(VendorDashboard::class);

        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $dashboardData = $controller->index(Request::create('/', 'GET'), null)->getData()['dashboardData'];
        $orderStatus = $dashboardData['orderStatus'];
        $cold = count(DB::getQueryLog());
        DB::disableQueryLog();
        $warm = $this->countIndexQueries($controller); // chart cache hit

        fwrite(STDERR, "\n[VENDOR-DASH] cold={$cold}, warm(cache)={$warm}\n");

        // Scoping + correctness: our vendor has exactly one order per status; the other
        // seller's delivered order is excluded.
        $this->assertSame(1, $orderStatus['delivered'], 'Vendor status count leaked another seller\'s order.');
        foreach (getOrderStatusList() as $status) {
            $this->assertSame(1, $orderStatus[$status], "Status {$status} count wrong.");
        }

        // Customers = this vendor's own distinct buyers (2), not all users, not the
        // other seller's buyer (customer 99).
        $this->assertSame(2, $dashboardData['customers'], 'Vendor customer count is not scoped to this vendor\'s buyers.');

        // Scoped SQL counts: this vendor's 8 orders (one per status), the other seller's
        // order excluded; no products seeded for this vendor.
        $this->assertSame(count(getOrderStatusList()), $dashboardData['orders'], 'Vendor order count wrong/leaked.');
        $this->assertSame(0, $dashboardData['products'], 'Vendor product count not scoped.');
        $this->assertLessThanOrEqual($cold, $warm);
    }

    #[Test]
    public function admin_dashboard_query_count(): void
    {
        $this->seedDashboard();
        $controller = app(AdminDashboard::class);

        Cache::flush();
        $cold = $this->countIndexQueries($controller);
        $warm = $this->countIndexQueries($controller);

        fwrite(STDERR, "\n[ADMIN-DASH] cold={$cold}, warm(cache)={$warm}\n");
        $this->assertGreaterThan(0, $cold);
        $this->assertLessThanOrEqual($cold, $warm);
    }
}
