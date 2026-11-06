<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\OrderReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Models\Seller;
use App\Services\New\OrderReportService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wholesale\app\Services\OrderReportService as WholesaleOrderReportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class OrderReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/order-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const AMOUNT_FACTORS = [
        BusinessModule::RETAIL => 1.0,
        BusinessModule::WHOLESALE => 2.0,
    ];

    private int $nextOrderId = 0;

    private Seller $seller;

    private array $orderIds = [];

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

        foreach (self::AMOUNT_FACTORS as $businessModule => $factor) {
            $cashOrder = $this->createVendorOrder(businessModule: $businessModule, orderStatus: 'delivered', orderAmount: 500 * $factor, paymentMethod: 'cash_on_delivery', attributes: [
                'refer_and_earn_discount' => 20 * $factor,
                'edit_due_amount' => 50 * $factor,
                'total_tax_amount' => 10 * $factor,
                'admin_commission' => 30 * $factor,
                'shipping_cost' => 15 * $factor,
                'extra_discount_type' => 'free_shipping_over_order_amount',
                'extra_discount' => 5 * $factor,
            ]);
            $this->createOrderEditHistory(order: $cashOrder, attributes: ['order_due_amount' => 50 * $factor, 'order_due_payment_status' => 'paid', 'order_due_payment_method' => 'wallet']);
            $this->createOrderEditHistory(order: $cashOrder, attributes: ['order_return_amount' => 25 * $factor, 'order_return_payment_status' => 'returned']);
            $this->createOrderDetail(order: $cashOrder, discount: 7 * $factor);

            $this->orderIds[$businessModule] = [
                $cashOrder->id,
                $this->createVendorOrder(businessModule: $businessModule, orderStatus: 'delivered', orderAmount: 300 * $factor, paymentMethod: 'stripe')->id,
                $this->createVendorOrder(businessModule: $businessModule, orderStatus: 'pending', orderAmount: 200 * $factor, paymentMethod: 'cash_on_delivery')->id,
                $this->createVendorOrder(businessModule: $businessModule, orderStatus: 'canceled', orderAmount: 100 * $factor, paymentMethod: 'cash_on_delivery')->id,
            ];
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
    public function test_page_lists_its_own_module_orders_with_legacy_totals(string $businessModule): void
    {
        $factor = self::AMOUNT_FACTORS[$businessModule];
        $orderIds = $this->orderIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.order', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders): bool => $orders->total() === 4
                && $orders->getCollection()->pluck('id')->sort()->values()->all() === collect($orderIds)->sort()->values()->all()
                && abs($orders->getCollection()->firstWhere('id', $orderIds[0])->details_sum_discount - 7 * $factor) < 0.0001)
            ->assertViewHas('orderStatusCounts', ['ongoing_order' => 1, 'canceled_order' => 1, 'delivered_order' => 2, 'total_order' => 4])
            ->assertViewHas('orderAmountSummary', fn (array $summary): bool => abs($summary['due_amount'] - 200 * $factor) < 0.0001
                && abs($summary['settled_amount'] - 800 * $factor) < 0.0001
                && abs($summary['referral_discount'] - 20 * $factor) < 0.0001
                && abs($summary['total_order_amount'] - 980 * $factor) < 0.0001)
            ->assertViewHas('orderAmountChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['order_amount'] - 800 * $factor) < 0.0001)
            ->assertSee(route('admin.report.'.$businessModule.'.order-report-excel', ['date_type' => 'custom_date', 'seller_id' => $this->seller->id, 'from' => self::REPORT_FILTERS['from'], 'to' => self::REPORT_FILTERS['to']]));
    }

    #[DataProvider('modulePages')]
    public function test_payment_statistics_include_order_edit_payments_and_returns(string $businessModule): void
    {
        $factor = self::AMOUNT_FACTORS[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.order', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('paymentStatistics', fn (array $payments): bool => abs($payments['cash_payment'] - 500 * $factor) < 0.0001
                && abs($payments['digital_payment'] - 300 * $factor) < 0.0001
                && abs($payments['wallet_payment'] - 50 * $factor) < 0.0001
                && abs($payments['offline_payment']) < 0.0001
                && abs($payments['return_amount'] - 25 * $factor) < 0.0001
                && abs($payments['total_payment'] - 825 * $factor) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_summary_pdf_totals_follow_the_filtered_orders(string $businessModule): void
    {
        $factor = self::AMOUNT_FACTORS[$businessModule];
        $service = app($businessModule === BusinessModule::RETAIL ? OrderReportService::class : WholesaleOrderReportService::class);

        $totals = $service->getSummaryPdfTotals(dateType: 'custom_date', from: self::REPORT_FILTERS['from'], to: self::REPORT_FILTERS['to'], sellerId: $this->seller->id);

        $this->assertSame(4, $totals['total_orders']);
        $this->assertEqualsWithDelta(1100 * $factor, $totals['total_order_amount'], 0.0001);
        $this->assertEqualsWithDelta(7 * $factor, $totals['total_product_discount'], 0.0001);
        $this->assertEqualsWithDelta(50 * $factor, $totals['total_order_due_amount'], 0.0001);
        $this->assertEqualsWithDelta(10 * $factor, $totals['total_tax'], 0.0001);
        $this->assertEqualsWithDelta(30 * $factor, $totals['total_order_commission'], 0.0001);
        $this->assertEqualsWithDelta(10 * $factor, $totals['total_delivery_charge'], 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_orders(string $businessModule): void
    {
        Excel::fake();

        $orderIds = $this->orderIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.order-report-excel', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk();

        Excel::assertDownloaded('Order-Report-List.xlsx', function (OrderReportExport $export) use ($orderIds): bool {
            $data = $export->view()->getData()['data'];

            return $data['orders']->pluck('id')->sort()->values()->all() === collect($orderIds)->sort()->values()->all()
                && $data['vendor']->id === $this->seller->id;
        });
    }

    public function test_search_filters_the_list_but_not_the_summary(): void
    {
        $orderId = $this->orderIds[BusinessModule::RETAIL][1];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.order', self::REPORT_FILTERS + ['seller_id' => $this->seller->id, 'search' => (string) $orderId]))
            ->assertOk()
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders): bool => $orders->total() === 1 && $orders->first()->id === $orderId)
            ->assertViewHas('orderStatusCounts', fn (array $counts): bool => $counts['total_order'] === 4);
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.order', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_non_numeric_vendor_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.order', ['seller_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['seller_id']);
    }

    private function createVendorOrder(string $businessModule, string $orderStatus, float $orderAmount, string $paymentMethod, array $attributes = []): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $this->seller->id,
            'order_status' => $orderStatus,
            'order_type' => 'default_type',
            'order_group_id' => 'order-report-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'payment_method' => $paymentMethod,
            'refer_and_earn_discount' => 0,
            'edit_due_amount' => 0,
            'edit_return_amount' => 0,
            'total_tax_amount' => 0,
            'admin_commission' => 0,
            'shipping_cost' => 0,
            'extra_discount' => 0,
            'extra_discount_type' => null,
            'discount_amount' => 0,
            'delivery_man_id' => null,
            'created_at' => self::REPORT_DATE,
            ...$attributes,
        ])->save();

        return $order;
    }

    private function createOrderEditHistory(Order $order, array $attributes): void
    {
        (new OrderEditHistory)->forceFill([
            'u_id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'order_amount' => $order->order_amount,
            ...$attributes,
        ])->save();
    }

    private function createOrderDetail(Order $order, float $discount): void
    {
        $this->requireFixture(OrderDetail::query()->orderBy('id')->first(), 'order detail')->replicate()
            ->forceFill(['order_id' => $order->id, 'discount' => $discount])
            ->save();
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
