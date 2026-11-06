<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\OrderTransactionReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Models\OrderTransaction;
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

class OrderTransactionReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/order-transaction-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const ORDER_AMOUNTS = [
        BusinessModule::RETAIL => 500.0,
        BusinessModule::WHOLESALE => 2000.0,
    ];

    private const UNIT_PRICE = 150.0;

    private const QUANTITY = 3;

    private const ADMIN_COMMISSION = 40.0;

    private const EDIT_DUE_AMOUNT = 25.0;

    private int $nextOrderId = 0;

    private array $orderIds = [];

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

        foreach (self::ORDER_AMOUNTS as $businessModule => $orderAmount) {
            $this->orderIds[$businessModule] = $this->createPaidVendorOrderWithTransaction(businessModule: $businessModule, orderAmount: $orderAmount)->id;
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
                'retail' => [BusinessModule::RETAIL, BusinessModule::WHOLESALE],
            ],
            wholesaleDataSets: [
                'wholesale' => [BusinessModule::WHOLESALE, BusinessModule::RETAIL],
            ],
        );
    }

    #[DataProvider('modulePages')]
    public function test_page_lists_only_its_own_module_transactions(string $businessModule, string $otherModule): void
    {
        $orderId = $this->orderIds[$businessModule];
        $otherOrderId = $this->orderIds[$otherModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.order-transaction-list', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('transactionRows', function (LengthAwarePaginator $rows) use ($orderId, $otherOrderId): bool {
                $row = $rows->firstWhere('order_id', $orderId);

                return $rows->total() === 1
                    && $row !== null
                    && abs($row['total_product_amount'] - self::UNIT_PRICE * self::QUANTITY) < 0.0001
                    && abs($row['admin_commission'] - self::ADMIN_COMMISSION) < 0.0001
                    && $rows->firstWhere('order_id', $otherOrderId) === null;
            })
            ->assertViewHas('orderSummary', fn (array $summary): bool => $summary['seller_orders'] === 1 && $summary['in_house_orders'] === 0 && $summary['total_stores'] === 1)
            ->assertViewHas('orderAmountChart', fn (array $chart): bool => count($chart) === 31 && abs($chart[9]['order_amount'] - self::ORDER_AMOUNTS[$businessModule]) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_payment_statistics_add_paid_order_edit_dues(string $businessModule): void
    {
        $orderAmount = self::ORDER_AMOUNTS[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.order-transaction-list', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('paymentStatistics', fn (array $payments): bool => abs($payments['cash_payment'] - $orderAmount) < 0.0001
                && abs($payments['wallet_payment'] - self::EDIT_DUE_AMOUNT) < 0.0001
                && abs($payments['total_payment'] - $orderAmount - self::EDIT_DUE_AMOUNT) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $orderId = $this->orderIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.transaction.order-transaction-export-excel', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk();

        Excel::assertDownloaded('Order-Transaction-Report-List.xlsx', function (OrderTransactionReportExport $export) use ($orderId): bool {
            $transactions = $export->view()->getData()['data']['transactions'];

            return $transactions->count() === 1 && $transactions->keys()->first() === 1 && $transactions->first()['order_id'] == $orderId;
        });
    }

    public function test_admin_net_income_counts_seller_borne_free_delivery_on_in_house_shipping(): void
    {
        $shippingCost = 50.0;
        $adminCouponDiscount = 90.0;
        $deliverymanCharge = 10.0;
        $adminDeliveryman = $this->requireFixture(DeliveryMan::query()->where('seller_id', 0)->orderBy('id')->first(), 'admin delivery man');
        $orderId = $this->orderIds[BusinessModule::RETAIL];

        Order::query()->whereKey($orderId)->update([
            'shipping_responsibility' => 'inhouse_shipping',
            'shipping_cost' => $shippingCost,
            'is_shipping_free' => 1,
            'free_delivery_bearer' => 'seller',
            'extra_discount' => $shippingCost,
            'coupon_discount_bearer' => 'inhouse',
            'discount_type' => 'coupon_discount',
            'discount_amount' => $adminCouponDiscount,
            'refer_and_earn_discount' => 0,
            'delivery_type' => 'self_delivery',
            'delivery_man_id' => $adminDeliveryman->id,
            'deliveryman_charge' => $deliverymanCharge,
        ]);
        OrderTransaction::query()->where('order_id', $orderId)->update(['delivery_charge' => 0]);

        $expectedAdminNetIncome = self::ADMIN_COMMISSION + $shippingCost - $adminCouponDiscount - $deliverymanCharge;

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.transaction.order-transaction-list', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('transactionRows', fn (LengthAwarePaginator $rows): bool => abs($rows->firstWhere('order_id', $orderId)['admin_net_income'] - $expectedAdminNetIncome) < 0.0001);
    }

    public function test_search_does_not_bypass_the_other_filters(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.transaction.order-transaction-list', self::REPORT_FILTERS + ['search' => (string) $this->orderIds[BusinessModule::WHOLESALE]]))
            ->assertOk()
            ->assertViewHas('transactionRows', fn (LengthAwarePaginator $rows): bool => $rows->total() === 0);
    }

    public function test_order_pdf_of_another_module_is_not_found(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.transaction.pdf-order-wise-transaction', ['order_id' => $this->orderIds[BusinessModule::RETAIL]]))
            ->assertNotFound();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.transaction.order-transaction-list', ['date_type' => 'custom_date', 'status' => 'paid', 'seller_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to', 'status', 'seller_id']);
    }

    private function createPaidVendorOrderWithTransaction(string $businessModule, float $orderAmount): Order
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $this->seller->id,
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
            'order_status' => 'delivered',
            'order_group_id' => 'order-transaction-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'delivery_type' => null,
            'delivery_man_id' => null,
            'created_at' => self::REPORT_DATE,
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();
        $transaction->forceFill([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'seller_is' => 'seller',
            'status' => 'disburse',
            'order_amount' => $orderAmount,
            'admin_commission' => self::ADMIN_COMMISSION,
            'transaction_id' => 'report-'.Str::lower(Str::random(12)),
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
