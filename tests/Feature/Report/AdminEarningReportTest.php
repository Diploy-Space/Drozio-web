<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\AdminEarningReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Order;
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

class AdminEarningReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/admin-earning-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const RETAIL_ORDER_AMOUNT = 777.25;

    private const WHOLESALE_ORDER_AMOUNT = 1234.5;

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

        $this->createDeliveredInHouseOrder(businessModule: BusinessModule::RETAIL, orderAmount: self::RETAIL_ORDER_AMOUNT);
        $this->createDeliveredInHouseOrder(businessModule: BusinessModule::WHOLESALE, orderAmount: self::WHOLESALE_ORDER_AMOUNT);
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
    public function test_page_reports_only_its_own_business_module(string $businessModule, float $expectedInHouseEarning): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.admin-earning', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('earningSummary', fn (array $summary): bool => abs($summary['total_in_house_earning'] - $expectedInHouseEarning) < 0.0001)
            ->assertViewHas('earningRows', fn (array $rows): bool => count($rows) === 31 && abs($rows[9]['in_house_earning'] - $expectedInHouseEarning) < 0.0001)
            ->assertSee(route('admin.report.'.$businessModule.'.admin-earning-excel-export', self::REPORT_FILTERS))
            ->assertSee(route('admin.report.'.$businessModule.'.admin-earning-duration-download-pdf'), false);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_business_module_rows(string $businessModule, float $expectedInHouseEarning): void
    {
        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.admin-earning-excel-export', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('admin-earning-report.xlsx', function (AdminEarningReportExport $export) use ($expectedInHouseEarning): bool {
            $rows = $export->view()->getData()['data']['inhouseEarn'];

            return abs(collect($rows)->sum('in_house_earning') - $expectedInHouseEarning) < 0.0001;
        });
    }

    public static function inHouseCouponBearers(): array
    {
        return [
            'in-house bearer' => ['inhouse'],
            'seller bearer' => ['seller'],
        ];
    }

    #[DataProvider('inHouseCouponBearers')]
    public function test_coupon_on_in_house_order_is_borne_by_admin_whatever_its_bearer(string $couponBearer): void
    {
        $order = $this->createDeliveredInHouseOrder(businessModule: BusinessModule::RETAIL, orderAmount: 950);
        $order->forceFill([
            'shipping_cost' => 100,
            'shipping_responsibility' => 'inhouse_shipping',
            'is_shipping_free' => 1,
            'free_delivery_bearer' => 'admin',
            'extra_discount' => 100,
            'discount_type' => 'coupon_discount',
            'discount_amount' => 50,
            'coupon_discount_bearer' => $couponBearer,
            'created_at' => '2031-05-11 12:00:00',
        ])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.admin-earning', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('earningRows', fn (array $rows): bool => abs($rows[10]['in_house_earning'] - 1000) < 0.0001
                && abs($rows[10]['earn_from_shipping'] - 100) < 0.0001
                && abs($rows[10]['discount_given'] - 150) < 0.0001
                && abs($rows[10]['total_earning'] - 950) < 0.0001);
    }

    public function test_average_order_value_counts_only_in_house_orders(): void
    {
        $vendorOrder = $this->createDeliveredInHouseOrder(businessModule: BusinessModule::RETAIL, orderAmount: 5000);
        $vendorOrder->forceFill(['seller_is' => 'seller'])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.admin-earning', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('averageOrderValue', fn (float $averageOrderValue): bool => abs($averageOrderValue - self::RETAIL_ORDER_AMOUNT) < 0.0001);
    }

    public function test_deliveryman_incentive_is_reported_in_monthly_buckets(): void
    {
        $deliveryMan = $this->requireFixture(DeliveryMan::query()->where('seller_id', 0)->orderBy('id')->first(), 'in-house delivery man');
        $order = $this->createDeliveredInHouseOrder(businessModule: BusinessModule::RETAIL, orderAmount: 0);
        $order->forceFill([
            'delivery_man_id' => $deliveryMan->id,
            'delivery_type' => 'self_delivery',
            'shipping_responsibility' => 'inhouse_shipping',
            'deliveryman_charge' => 30,
        ])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.admin-earning', ['date_type' => 'custom_date', 'from' => '2031-04-01', 'to' => '2031-06-30']))
            ->assertOk()
            ->assertViewHas('earningRows', fn (array $rows): bool => count($rows) === 3 && abs($rows[1]['deliveryman_incentive'] - 30) < 0.0001);
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.admin-earning', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.admin-earning', ['date_type' => 'custom_date', 'from' => '2031-05-10', 'to' => '2031-05-01']))
            ->assertRedirect()
            ->assertSessionHasErrors(['to']);
    }

    public function test_unknown_date_type_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.admin-earning', ['date_type' => 'last_decade']))
            ->assertRedirect()
            ->assertSessionHasErrors(['date_type']);
    }

    public function test_duration_pdf_rejects_non_numeric_amounts(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.report.wholesale.admin-earning-duration-download-pdf'), [
                'duration' => 'May',
                'inhouse_earning' => 'abc',
                'admin_commission' => 0,
                'shipping_earn' => 0,
                'deliveryman_incentive' => 0,
                'discount_given' => 0,
                'total_tax' => 0,
                'refund_given' => 0,
                'total_earning' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['inhouse_earning']);
    }

    private function createDeliveredInHouseOrder(string $businessModule, float $orderAmount): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'admin',
            'seller_id' => 1,
            'order_status' => 'delivered',
            'order_type' => 'default_type',
            'order_group_id' => 'admin-earning-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'refer_and_earn_discount' => 0,
            'total_tax_amount' => 0,
            'shipping_cost' => 0,
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
