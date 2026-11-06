<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\ExpenseTransactionReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderTransaction;
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

class ExpenseTransactionReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/expense-transaction-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const COUPON_DISCOUNT = [
        BusinessModule::RETAIL => 50.0,
        BusinessModule::WHOLESALE => 120.0,
    ];

    private const REFERRAL_DISCOUNT = 10.0;

    private const FREE_SHIPPING_DISCOUNT = 5.0;

    private int $nextOrderId = 0;

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

        foreach (self::COUPON_DISCOUNT as $businessModule => $couponDiscount) {
            $this->orderIds[$businessModule] = $this->createExpenseOrder(businessModule: $businessModule, couponDiscount: $couponDiscount)->id;
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
    public function test_page_reports_only_its_own_module_expenses(string $businessModule): void
    {
        $expectedExpense = self::COUPON_DISCOUNT[$businessModule] + self::REFERRAL_DISCOUNT + self::FREE_SHIPPING_DISCOUNT;
        $orderId = $this->orderIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.expense-transaction-list', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('expenseTransactions', function (LengthAwarePaginator $orders) use ($orderId, $expectedExpense): bool {
                $order = $orders->first();

                return $orders->total() === 1
                    && $order->id === $orderId
                    && abs($order['expense_amount'] - $expectedExpense) < 0.0001
                    && $order['expense_types']['has_untyped_coupon'] === true
                    && $order['expense_types']['free_delivery_type'] === 'free_shipping_over_order_amount'
                    && $order['expense_types']['has_referral_discount'] === true;
            })
            ->assertViewHas('expenseSummary', fn (array $summary): bool => abs($summary['total_expense'] - $expectedExpense) < 0.0001
                && abs($summary['total_coupon_discount'] - self::COUPON_DISCOUNT[$businessModule]) < 0.0001
                && abs($summary['total_referral_discount'] - self::REFERRAL_DISCOUNT) < 0.0001
                && abs($summary['total_free_delivery_over_amount'] - self::FREE_SHIPPING_DISCOUNT) < 0.0001
                && abs($summary['total_free_delivery']) < 0.0001)
            ->assertViewHas('expenseChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['expense_amount'] - $expectedExpense) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_orders(string $businessModule): void
    {
        Excel::fake();

        $orderId = $this->orderIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.expense-transaction-export-excel', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('Expense-Transaction-Report-List.xlsx', function (ExpenseTransactionReportExport $export) use ($orderId): bool {
            $transactions = $export->view()->getData()['data']['transactions'];

            return $transactions->count() === 1 && $transactions->first()->id === $orderId;
        });
    }

    public function test_order_pdf_of_another_module_is_not_found(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.transaction.pdf-order-wise-expense-transaction', ['id' => $this->orderIds[BusinessModule::WHOLESALE]]))
            ->assertNotFound();
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.transaction.expense-transaction-list', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    private function createExpenseOrder(string $businessModule, float $couponDiscount): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'order_type' => 'default_type',
            'order_status' => 'delivered',
            'order_group_id' => 'expense-'.Str::lower(Str::random(16)),
            'coupon_code' => 'NO-SUCH-COUPON-'.Str::upper(Str::random(8)),
            'coupon_discount_bearer' => 'inhouse',
            'discount_type' => 'coupon_discount',
            'discount_amount' => $couponDiscount,
            'refer_and_earn_discount' => self::REFERRAL_DISCOUNT,
            'free_delivery_bearer' => 'admin',
            'is_shipping_free' => 1,
            'extra_discount' => self::FREE_SHIPPING_DISCOUNT,
            'extra_discount_type' => 'free_shipping_over_order_amount',
            'created_at' => self::REPORT_DATE,
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();
        $transaction->forceFill([
            'order_id' => $order->id,
            'transaction_id' => 'expense-'.Str::lower(Str::random(12)),
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
