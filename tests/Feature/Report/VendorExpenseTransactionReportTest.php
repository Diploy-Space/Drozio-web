<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\ExpenseTransactionReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Seller;
use App\Services\New\ExpenseTransactionReportService as RetailExpenseTransactionReportService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wholesale\app\Services\ExpenseTransactionReportService as WholesaleExpenseTransactionReportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorExpenseTransactionReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-expense-transaction-report-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const COUPON_DISCOUNT = [
        BusinessModule::RETAIL => 50.0,
        BusinessModule::WHOLESALE => 120.0,
    ];

    private const FREE_SHIPPING_DISCOUNT = 5.0;

    private int $nextOrderId = 0;

    private array $orderIds = [];

    private array $transactionIds = [];

    private Seller $seller;

    private int $otherSellerOrderId;

    private int $heldOrderId;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubVendorLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        $sellers = Seller::query()->where('status', 'approved')->whereHas('shop')->orderBy('id')->limit(2)->get();
        $this->seller = $this->requireFixture($sellers->get(0), 'approved vendor');
        $otherSeller = $this->requireFixture($sellers->get(1), 'second approved vendor');

        foreach (self::COUPON_DISCOUNT as $businessModule => $couponDiscount) {
            $order = $this->createExpenseOrder(seller: $this->seller, businessModule: $businessModule, couponDiscount: $couponDiscount);
            $this->orderIds[$businessModule] = $order->id;
            $this->transactionIds[$businessModule] = $order->orderTransaction->transaction_id;
        }

        $this->otherSellerOrderId = $this->createExpenseOrder(seller: $otherSeller, businessModule: BusinessModule::RETAIL, couponDiscount: 70.0)->id;
        $this->heldOrderId = $this->createExpenseOrder(seller: $this->seller, businessModule: BusinessModule::RETAIL, couponDiscount: 30.0, transactionStatus: 'hold')->id;
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
    public function test_page_reports_only_the_vendors_own_module_expenses(string $businessModule): void
    {
        $couponDiscount = self::COUPON_DISCOUNT[$businessModule];
        $expectedExpense = $couponDiscount + self::FREE_SHIPPING_DISCOUNT;
        $orderId = $this->orderIds[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.transaction.expense-list', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('expenseTransactions', function (LengthAwarePaginator $orders) use ($orderId, $expectedExpense): bool {
                $order = $orders->first();

                return $orders->total() === 1
                    && $order->id === $orderId
                    && abs($order['expense_amount'] - $expectedExpense) < 0.0001
                    && $order['expense_types']['coupon_type'] === null
                    && $order['expense_types']['free_delivery_type'] === 'free_shipping_over_order_amount';
            })
            ->assertViewHas('expenseSummary', fn (array $summary): bool => abs($summary['total_expense'] - $expectedExpense) < 0.0001
                && abs($summary['total_coupon_discount'] - $couponDiscount) < 0.0001
                && abs($summary['total_free_delivery'] - self::FREE_SHIPPING_DISCOUNT) < 0.0001)
            ->assertViewHas('expenseChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['expense_amount'] - $expectedExpense) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_the_vendors_own_module_orders(string $businessModule): void
    {
        Excel::fake();

        $orderId = $this->orderIds[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.transaction.expense-transaction-export-excel', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('Expense-Transaction-Report-List.xlsx', function (ExpenseTransactionReportExport $export) use ($orderId): bool {
            $data = $export->view()->getData()['data'];

            return $data['data-from'] === 'vendor'
                && $data['vendor']->id === $this->seller->id
                && $data['transactions']->count() === 1
                && $data['transactions']->first()->id === $orderId;
        });
    }

    #[DataProvider('modulePages')]
    public function test_summary_pdf_totals_cover_only_the_vendors_own_module_orders(string $businessModule): void
    {
        $service = app($businessModule === BusinessModule::RETAIL ? RetailExpenseTransactionReportService::class : WholesaleExpenseTransactionReportService::class);

        $totals = $service->getVendorExpenseSummaryPdfTotals(
            sellerId: $this->seller->id,
            dateType: self::REPORT_FILTERS['date_type'],
            from: self::REPORT_FILTERS['from'],
            to: self::REPORT_FILTERS['to'],
        );

        $this->assertEqualsWithDelta(self::COUPON_DISCOUNT[$businessModule] + self::FREE_SHIPPING_DISCOUNT, $totals['total_expense'], 0.0001);
        $this->assertEqualsWithDelta(self::COUPON_DISCOUNT[$businessModule], $totals['total_coupon_discount'], 0.0001);
        $this->assertEqualsWithDelta(self::FREE_SHIPPING_DISCOUNT, $totals['total_free_delivery_over_amount'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $totals['total_free_delivery'], 0.0001);
    }

    public function test_search_matches_the_transaction_id_of_a_disbursed_order_only(): void
    {
        $orderId = $this->orderIds[BusinessModule::RETAIL];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.transaction.expense-list', self::REPORT_FILTERS + ['search' => $this->transactionIds[BusinessModule::RETAIL]]))
            ->assertOk()
            ->assertViewHas('expenseTransactions', fn (LengthAwarePaginator $orders): bool => $orders->total() === 1 && $orders->first()->id === $orderId);

        $heldTransactionId = OrderTransaction::query()->where('order_id', $this->heldOrderId)->value('transaction_id');

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.transaction.expense-list', self::REPORT_FILTERS + ['search' => $heldTransactionId]))
            ->assertOk()
            ->assertViewHas('expenseTransactions', fn (LengthAwarePaginator $orders): bool => $orders->total() === 0);
    }

    public function test_order_pdf_of_another_vendor_is_not_found(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.transaction.pdf-order-wise-expense-transaction', ['id' => $this->otherSellerOrderId]))
            ->assertNotFound();
    }

    public function test_order_pdf_of_another_module_is_not_found(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.wholesale.transaction.pdf-order-wise-expense-transaction', ['id' => $this->orderIds[BusinessModule::RETAIL]]))
            ->assertNotFound();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.wholesale.transaction.expense-list', ['date_type' => 'custom_date', 'page' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to', 'page']);
    }

    private function createExpenseOrder(Seller $seller, string $businessModule, float $couponDiscount, string $transactionStatus = 'disburse'): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $seller->id,
            'order_type' => 'default_type',
            'order_status' => 'delivered',
            'order_group_id' => 'vendor-expense-'.Str::lower(Str::random(16)),
            'coupon_code' => 'NO-SUCH-COUPON-'.Str::upper(Str::random(8)),
            'coupon_discount_bearer' => 'seller',
            'discount_type' => 'coupon_discount',
            'discount_amount' => $couponDiscount,
            'refer_and_earn_discount' => 0,
            'free_delivery_bearer' => 'seller',
            'is_shipping_free' => 1,
            'extra_discount' => self::FREE_SHIPPING_DISCOUNT,
            'extra_discount_type' => 'free_shipping_over_order_amount',
            'created_at' => self::REPORT_DATE,
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();
        $transaction->forceFill([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'status' => $transactionStatus,
            'transaction_id' => 'vendor-expense-'.Str::lower(Str::random(12)),
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

    private function stubVendorLayout(): void
    {
        $path = self::STUB_VIEW_ROOT.'/'.self::STUB_LAYOUT.'.blade.php';

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, "@yield('content')@stack('script')");

        View::getFinder()->prependLocation(self::STUB_VIEW_ROOT);
        View::getFinder()->flush();
    }
}
