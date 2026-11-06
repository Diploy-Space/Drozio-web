<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\VendorEarningReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Seller;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorEarningReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-earning-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const RETAIL_ORDER_AMOUNT = 777.25;

    private const WHOLESALE_ORDER_AMOUNT = 1234.5;

    private const ADMIN_COMMISSION = 10.0;

    private int $nextOrderId = 0;

    private Seller $vendor;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        $this->vendor = $this->requireFixture(Seller::query()->where('status', 'approved')->orderBy('id')->first(), 'approved vendor');

        $this->createDeliveredVendorOrder(businessModule: BusinessModule::RETAIL, orderAmount: self::RETAIL_ORDER_AMOUNT);
        $this->createDeliveredVendorOrder(businessModule: BusinessModule::WHOLESALE, orderAmount: self::WHOLESALE_ORDER_AMOUNT);
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
                'retail' => [BusinessModule::RETAIL, self::RETAIL_ORDER_AMOUNT],
            ],
            wholesaleDataSets: [
                'wholesale' => [BusinessModule::WHOLESALE, self::WHOLESALE_ORDER_AMOUNT],
            ],
        );
    }

    #[DataProvider('modulePages')]
    public function test_page_lists_only_its_own_business_module_vendor_earnings(string $businessModule, float $orderAmount): void
    {
        $expectedEarning = $orderAmount - self::ADMIN_COMMISSION;

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-earning', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('vendorEarningRows', fn (array $rows): bool => count($rows) === 1
                && (int) $rows[0]['vendor_id'] === $this->vendor->id
                && abs($rows[0]['earn_from_order'] - $orderAmount) < 0.0001
                && abs($rows[0]['commission_given'] - self::ADMIN_COMMISSION) < 0.0001)
            ->assertViewHas('vendorEarningSummary', fn (array $summary): bool => $summary['active_vendor_count'] === 1
                && abs($summary['total_earning'] - $expectedEarning) < 0.0001
                && abs($summary['average_vendor_revenue'] - $expectedEarning) < 0.0001)
            ->assertViewHas('earningChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['total_earning'] - $expectedEarning) < 0.0001)
            ->assertViewHas('vendorSalesTotal', fn (float $vendorSalesTotal): bool => abs($vendorSalesTotal - $orderAmount) < 0.0001)
            ->assertSee(route('admin.report.'.$businessModule.'.vendor-earning-excel-export', self::REPORT_FILTERS));
    }

    #[DataProvider('modulePages')]
    public function test_vendor_sales_keep_admin_coupon_and_seller_borne_free_delivery_on_in_house_shipping(string $businessModule, float $orderAmount): void
    {
        $shippingCost = 10.0;
        $adminCoupon = 5.0;

        Order::query()
            ->where(['business_module' => $businessModule, 'seller_id' => $this->vendor->id, 'created_at' => self::REPORT_DATE])
            ->update([
                'order_amount' => $orderAmount - $adminCoupon,
                'shipping_cost' => $shippingCost,
                'shipping_responsibility' => 'inhouse_shipping',
                'is_shipping_free' => 1,
                'free_delivery_bearer' => 'seller',
                'extra_discount' => $shippingCost,
                'discount_type' => 'coupon_discount',
                'coupon_discount_bearer' => 'inhouse',
                'discount_amount' => $adminCoupon,
            ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-earning', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('vendorSalesTotal', fn (float $vendorSalesTotal): bool => abs($vendorSalesTotal - $orderAmount) < 0.0001)
            ->assertViewHas('vendorEarningRows', fn (array $rows): bool => count($rows) === 1
                && abs($rows[0]['earn_from_order'] - $orderAmount) < 0.0001
                && abs($rows[0]['discount_given'] - $shippingCost) < 0.0001
                && abs($rows[0]['total_earning'] - ($orderAmount - $shippingCost - self::ADMIN_COMMISSION)) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_business_module_vendor_rows(string $businessModule, float $orderAmount): void
    {
        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-earning-excel-export', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('vendor-earning-report.xlsx', function (VendorEarningReportExport $export) use ($orderAmount): bool {
            $rows = $export->view()->getData()['data']['vendorEarnTable'];

            return count($rows) === 1 && abs($rows[0]['earn_from_order'] - $orderAmount) < 0.0001;
        });
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.vendor-earning', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    private function createDeliveredVendorOrder(string $businessModule, float $orderAmount): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $this->vendor->id,
            'order_status' => 'delivered',
            'order_type' => 'default_type',
            'order_group_id' => 'vendor-earning-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'admin_commission' => self::ADMIN_COMMISSION,
            'refer_and_earn_discount' => 0,
            'total_tax_amount' => 0,
            'shipping_cost' => 0,
            'shipping_responsibility' => 'inhouse_shipping',
            'discount_amount' => 0,
            'discount_type' => null,
            'is_shipping_free' => 0,
            'extra_discount' => 0,
            'delivery_man_id' => null,
            'created_at' => self::REPORT_DATE,
        ])->save();

        return $order;
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
