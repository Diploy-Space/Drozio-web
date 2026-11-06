<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\OrderTransactionReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Models\OrderTransaction;
use App\Models\Seller;
use App\Services\New\OrderTransactionReportService as RetailOrderTransactionReportService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Wholesale\app\Services\OrderTransactionReportService as WholesaleOrderTransactionReportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorOrderTransactionReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-order-transaction-report-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const ORDER_AMOUNTS = [
        BusinessModule::RETAIL => 500.0,
        BusinessModule::WHOLESALE => 2000.0,
    ];

    private const TRANSACTION_TAX = 30.0;

    private const UNIT_PRICE = 150.0;

    private const QUANTITY = 3;

    private const ADMIN_COMMISSION = 40.0;

    private const EDIT_DUE_AMOUNT = 25.0;

    private int $nextOrderId = 0;

    private array $orderIds = [];

    private Seller $seller;

    private Seller $otherSeller;

    private int $otherSellerOrderId;

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
        $this->otherSeller = $this->requireFixture($sellers->get(1), 'second approved vendor');

        foreach (self::ORDER_AMOUNTS as $businessModule => $orderAmount) {
            $this->orderIds[$businessModule] = $this->createPaidOrderWithTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: $orderAmount)->id;
        }

        $this->otherSellerOrderId = $this->createPaidOrderWithTransaction(seller: $this->otherSeller, businessModule: BusinessModule::RETAIL, orderAmount: 700.0)->id;
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
                'retail' => [BusinessModule::RETAIL, BusinessModule::WHOLESALE],
            ],
            wholesaleDataSets: [
                'wholesale' => [BusinessModule::WHOLESALE, BusinessModule::RETAIL],
            ],
        );
    }

    #[DataProvider('modulePages')]
    public function test_page_lists_only_the_vendors_own_module_transactions(string $businessModule, string $otherModule): void
    {
        $orderId = $this->orderIds[$businessModule];
        $otherOrderId = $this->orderIds[$otherModule];
        $otherSellerOrderId = $this->otherSellerOrderId;
        $orderAmount = self::ORDER_AMOUNTS[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.transaction.order-list', self::REPORT_FILTERS + ['seller_id' => $this->otherSeller->id]))
            ->assertOk()
            ->assertViewHas('transactionRows', function (LengthAwarePaginator $rows) use ($orderId, $otherOrderId, $otherSellerOrderId, $orderAmount): bool {
                $row = $rows->firstWhere('order_id', $orderId);

                return $rows->total() === 1
                    && $row !== null
                    && abs($row['total_product_amount'] - self::UNIT_PRICE * self::QUANTITY) < 0.0001
                    && abs($row['vendor_net_income'] - ($orderAmount - self::ADMIN_COMMISSION)) < 0.0001
                    && $rows->firstWhere('order_id', $otherOrderId) === null
                    && $rows->firstWhere('order_id', $otherSellerOrderId) === null;
            })
            ->assertViewHas('orderAmountChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['order_amount'] - self::ORDER_AMOUNTS[$businessModule]) < 0.0001)
            ->assertViewHas('productCounts', fn (array $counts): bool => $counts['total_products'] === $counts['active_products'] + $counts['inactive_products'] + $counts['pending_products']);
    }

    #[DataProvider('modulePages')]
    public function test_payment_statistics_add_paid_order_edit_dues(string $businessModule): void
    {
        $orderAmount = self::ORDER_AMOUNTS[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.transaction.order-list', self::REPORT_FILTERS))
            ->assertOk()
            ->assertViewHas('paymentStatistics', fn (array $payments): bool => abs($payments['cash_payment'] - $orderAmount) < 0.0001
                && abs($payments['wallet_payment'] - self::EDIT_DUE_AMOUNT) < 0.0001
                && abs($payments['total_payment'] - $orderAmount - self::EDIT_DUE_AMOUNT) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_the_vendors_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $orderId = $this->orderIds[$businessModule];
        $orderAmount = self::ORDER_AMOUNTS[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.transaction.order-transaction-export-excel', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('Order-Transaction-Report-List.xlsx', function (OrderTransactionReportExport $export) use ($orderId, $orderAmount): bool {
            $data = $export->view()->getData()['data'];
            $transactions = $data['transactions'];
            $row = $transactions->first();

            return $data['data-from'] === 'vendor'
                && $data['vendor']->id === $this->seller->id
                && $transactions->count() === 1
                && $transactions->keys()->first() === 1
                && $row['order_id'] == $orderId
                && abs($row['vendor_net_income'] - ($orderAmount - self::ADMIN_COMMISSION)) < 0.0001;
        });
    }

    #[DataProvider('modulePages')]
    public function test_summary_pdf_totals_cover_only_the_vendors_own_module_orders(string $businessModule): void
    {
        $service = app($businessModule === BusinessModule::RETAIL ? RetailOrderTransactionReportService::class : WholesaleOrderTransactionReportService::class);

        $totals = $service->getVendorSummaryPdfTotals(
            dateType: self::REPORT_FILTERS['date_type'],
            from: self::REPORT_FILTERS['from'],
            to: self::REPORT_FILTERS['to'],
            filters: [RetailOrderTransactionReportService::FILTER_SELLER_ID => $this->seller->id],
        );

        $this->assertSame(1, $totals['total_orders']);
        $this->assertSame(1, $totals['completed_order']);
        $this->assertSame(0, $totals['ongoing_order']);
        $this->assertEqualsWithDelta(self::ORDER_AMOUNTS[$businessModule], $totals['total_order_amount'], 0.0001);
        $this->assertEqualsWithDelta(self::ADMIN_COMMISSION, $totals['total_admin_commission'], 0.0001);
        $this->assertEqualsWithDelta(self::ORDER_AMOUNTS[$businessModule] - self::ADMIN_COMMISSION, $totals['total_vendor_net_income'], 0.0001);
    }

    public function test_search_does_not_reach_another_vendors_transactions(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.transaction.order-list', self::REPORT_FILTERS + ['search' => (string) $this->otherSellerOrderId]))
            ->assertOk()
            ->assertViewHas('transactionRows', fn (LengthAwarePaginator $rows): bool => $rows->total() === 0);
    }

    public function test_order_pdf_of_another_vendor_is_not_found(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.transaction.pdf-order-wise-transaction', ['order_id' => $this->otherSellerOrderId]))
            ->assertNotFound();
    }

    public function test_order_pdf_of_another_module_is_not_found(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.wholesale.transaction.pdf-order-wise-transaction', ['order_id' => $this->orderIds[BusinessModule::RETAIL]]))
            ->assertNotFound();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.wholesale.transaction.order-list', ['date_type' => 'custom_date', 'status' => 'paid', 'customer_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to', 'status', 'customer_id']);
    }

    private function createPaidOrderWithTransaction(Seller $seller, string $businessModule, float $orderAmount): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $seller->id,
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
            'order_status' => 'delivered',
            'order_group_id' => 'vendor-order-transaction-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'shipping_responsibility' => 'sellerwise_shipping',
            'coupon_discount_bearer' => 'inhouse',
            'free_delivery_bearer' => 'seller',
            'shipping_cost' => 0,
            'is_shipping_free' => 0,
            'discount_amount' => 0,
            'extra_discount' => 0,
            'delivery_type' => null,
            'delivery_man_id' => null,
            'created_at' => self::REPORT_DATE,
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();
        $transaction->forceFill([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'status' => 'disburse',
            'order_amount' => $orderAmount,
            'tax' => self::TRANSACTION_TAX,
            'delivery_charge' => 0,
            'admin_commission' => self::ADMIN_COMMISSION,
            'transaction_id' => 'vendor-report-'.Str::lower(Str::random(12)),
            'created_at' => self::REPORT_DATE,
        ])->save();

        $orderDetail = $this->requireFixture(OrderDetail::query()->orderBy('id')->first(), 'order detail')->replicate();
        $orderDetail->forceFill([
            'order_id' => $order->id,
            'qty' => self::QUANTITY,
            'price' => self::UNIT_PRICE,
            'discount' => 0,
        ])->save();

        OrderEditHistory::query()->forceCreate([
            'order_id' => $order->id,
            'order_amount' => $orderAmount,
            'order_due_amount' => self::EDIT_DUE_AMOUNT,
            'order_due_payment_status' => 'paid',
            'order_due_payment_method' => 'wallet',
            'order_return_amount' => 0,
        ]);

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
