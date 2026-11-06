<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\VendorReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\Seller;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorSaleReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-sale-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const ORDER_AMOUNTS = [
        BusinessModule::RETAIL => [400.0, 600.0],
        BusinessModule::WHOLESALE => [1500.0, 2500.0],
    ];

    private const SHIPPING_COST = 10.0;

    private const ADMIN_COMMISSION = 50.0;

    private const REFUND_AMOUNT = 100.0;

    private int $nextOrderId = 0;

    private Seller $seller;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        $this->seller = $this->requireFixture(Seller::query()->where('status', 'approved')->whereHas('shop')->orderBy('id')->first(), 'approved vendor');

        foreach (self::ORDER_AMOUNTS as $businessModule => $orderAmounts) {
            $orders = array_map(fn (float $orderAmount): Order => $this->createVendorOrder(businessModule: $businessModule, orderAmount: $orderAmount, orderStatus: 'delivered'), $orderAmounts);
            $this->createVendorOrder(businessModule: $businessModule, orderAmount: 9999.0, orderStatus: 'pending');
            $this->createSellerRefund(order: $orders[0]);
        }
    }

    protected function tearDown(): void
    {
        if (is_file(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php')) {
            unlink(self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php');
        }

        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public static function modulePages(): array
    {
        return self::withWholesaleDataSets(
            dataSets: [
                'retail' => [BusinessModule::RETAIL],
            ],
            wholesaleDataSets: [
                'wholesale' => [BusinessModule::WHOLESALE],
            ],
        );
    }

    #[DataProvider('modulePages')]
    public function test_page_lists_delivered_vendor_sales_of_its_own_module(string $businessModule): void
    {
        $totalOrderAmount = array_sum(self::ORDER_AMOUNTS[$businessModule]);
        $sellerId = $this->seller->id;

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-report', self::REPORT_FILTERS + ['seller_id' => $sellerId]))
            ->assertOk()
            ->assertViewHas('vendorSaleRows', function (LengthAwarePaginator $rows) use ($sellerId, $totalOrderAmount): bool {
                $row = $rows->first();

                return $rows->total() === 1
                    && $row['seller_id'] === $sellerId
                    && $row['total_order_count'] === 2
                    && abs($row['total_order_amount'] - $totalOrderAmount) < 0.0001
                    && abs($row['total_admin_commission'] - 2 * self::ADMIN_COMMISSION) < 0.0001
                    && abs($row['refund_rate'] - self::REFUND_AMOUNT / $totalOrderAmount * 100) < 0.0001;
            })
            ->assertViewHas('orderAmountChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['order_amount'] - $totalOrderAmount) < 0.0001)
            ->assertSee(route('admin.report.'.$businessModule.'.vendor-report-export', ['date_type' => self::REPORT_FILTERS['date_type'], 'seller_id' => $sellerId, 'from' => self::REPORT_FILTERS['from'], 'to' => self::REPORT_FILTERS['to']]));
    }

    #[DataProvider('modulePages')]
    public function test_summary_reports_shop_earning_net_of_shipping_commission_and_refunds(string $businessModule): void
    {
        $totalOrderAmount = array_sum(self::ORDER_AMOUNTS[$businessModule]);
        $expectedShopEarning = $totalOrderAmount - 2 * self::SHIPPING_COST - 2 * self::ADMIN_COMMISSION - self::REFUND_AMOUNT;

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-report', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('vendorSaleSummary', fn (array $summary): bool => abs($summary['total_store_earning'] - $expectedShopEarning) < 0.0001
                && $summary['delivered_order_count'] === 2
                && $summary['active_vendor_count'] === 1
                && abs($summary['average_vendor_revenue'] - $totalOrderAmount) < 0.0001
                && abs($summary['average_order_value'] - $totalOrderAmount / 2) < 0.0001);
    }

    public static function sellerWiseCouponBearers(): array
    {
        return [
            'vendor bears the coupon' => ['seller', 900.0],
            'admin bears the coupon' => ['inhouse', 950.0],
        ];
    }

    #[DataProvider('sellerWiseCouponBearers')]
    public function test_shop_earning_keeps_seller_wise_shipping_and_admin_borne_discounts(string $couponBearer, float $expectedShopEarning): void
    {
        $order = $this->createVendorOrder(businessModule: BusinessModule::RETAIL, orderAmount: 950.0, orderStatus: 'delivered');
        $order->forceFill([
            'shipping_cost' => 100,
            'shipping_responsibility' => 'sellerwise_shipping',
            'is_shipping_free' => 1,
            'free_delivery_bearer' => 'seller',
            'extra_discount' => 100,
            'discount_type' => 'coupon_discount',
            'discount_amount' => 50,
            'coupon_discount_bearer' => $couponBearer,
            'created_at' => '2031-06-10 12:00:00',
        ])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.vendor-report', ['date_type' => 'custom_date', 'from' => '2031-06-01', 'to' => '2031-06-30', 'seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('vendorSaleSummary', fn (array $summary): bool => abs($summary['total_store_earning'] - $expectedShopEarning) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $totalOrderAmount = array_sum(self::ORDER_AMOUNTS[$businessModule]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-report-export', self::REPORT_FILTERS + ['seller_id' => 'all']))
            ->assertOk();

        Excel::assertDownloaded('vendor-report.xlsx', function (VendorReportExport $export) use ($totalOrderAmount): bool {
            $rows = $export->view()->getData()['data']['vendorSaleRows'];

            return $rows->count() === 1 && abs($rows->first()['total_order_amount'] - $totalOrderAmount) < 0.0001;
        });
    }

    public function test_search_matching_no_vendor_returns_no_rows(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.vendor-report', self::REPORT_FILTERS + ['search' => 'no-such-vendor-'.Str::random(8)]))
            ->assertOk()
            ->assertViewHas('vendorSaleRows', fn (LengthAwarePaginator $rows): bool => $rows->total() === 0);
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.vendor-report', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_non_numeric_vendor_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.vendor-report', ['seller_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['seller_id']);
    }

    private function createVendorOrder(string $businessModule, float $orderAmount, string $orderStatus): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $this->seller->id,
            'order_status' => $orderStatus,
            'order_type' => 'default_type',
            'order_group_id' => 'vendor-sale-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'shipping_cost' => self::SHIPPING_COST,
            'shipping_responsibility' => 'inhouse_shipping',
            'admin_commission' => self::ADMIN_COMMISSION,
            'refer_and_earn_discount' => 0,
            'discount_type' => null,
            'discount_amount' => 0,
            'is_shipping_free' => 0,
            'extra_discount' => 0,
            'delivery_type' => null,
            'deliveryman_charge' => 0,
            'delivery_man_id' => null,
            'created_at' => self::REPORT_DATE,
        ])->save();

        return $order;
    }

    private function createSellerRefund(Order $order): void
    {
        $refund = $this->requireFixture(RefundTransaction::query()->orderBy('id')->first(), 'refund transaction')->replicate();

        $refund->forceFill([
            'order_id' => $order->id,
            'payer_id' => $this->seller->id,
            'paid_by' => 'seller',
            'payment_status' => 'paid',
            'transaction_type' => 'Refund',
            'amount' => self::REFUND_AMOUNT,
            'created_at' => self::REPORT_DATE,
        ])->save();
    }

    private function nextOrderId(): int
    {
        if ($this->nextOrderId === 0) {
            $this->nextOrderId = (int) Order::query()->max('id') + random_int(100000, 900000);
        }

        return ++$this->nextOrderId;
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

    private function admin(): Admin
    {
        return $this->requireFixture(Admin::query()->orderBy('id')->first(), 'admin');
    }
}
