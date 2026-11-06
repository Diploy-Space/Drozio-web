<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Seller;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\VendorTaxExport;
use Modules\TaxModule\app\Models\OrderTax;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class VendorVatReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-vat-report-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const SALES_TAX = 'Vendor vat report sales tax';

    private const SERVICE_TAX = 'Vendor vat report service tax';

    private const SEARCH_TAX = 'Vendor vat report unique levy';

    private Seller $seller;

    private Seller $otherSeller;

    private int $nextOrderId = 0;

    private array $transactionIds = [];

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

        OrderTransaction::query()->whereIn('shop_id', [$this->seller->shop->id, $this->otherSeller->shop->id])->delete();

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $this->transactionIds[$businessModule] = [
                $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 50, tax: 4, taxes: [
                    ['basic', self::SALES_TAX, 8, 4],
                ], createdAt: now()->subHours(1))->id,
                $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 100, tax: 10, taxes: [
                    ['basic', self::SALES_TAX, 8, 8],
                    ['tax_on_delivery_charge', self::SERVICE_TAX, 6, 2],
                ], createdAt: now()->subHours(3))->id,
            ];

            $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 70, tax: 7, taxes: [['basic', self::SALES_TAX, 8, 7]], transactionStatus: 'hold');
            $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 70, tax: 7, taxes: [['basic', self::SALES_TAX, 8, 7]], orderStatus: 'confirmed');
            $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 70, tax: 7, taxes: [['basic', self::SALES_TAX, 8, 7]], orderType: 'POS');
            $this->createTransaction(seller: $this->seller, businessModule: $businessModule, orderAmount: 70, tax: 7, taxes: [['basic', self::SALES_TAX, 8, 7]], updatedAt: now()->subDays(30));
            $this->createTransaction(seller: $this->otherSeller, businessModule: $businessModule, orderAmount: 90, tax: 9, taxes: [['basic', self::SEARCH_TAX, 5, 9]]);
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
    public function test_page_reports_the_vendors_delivered_disbursed_module_orders(string $businessModule): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.get-vat-report'))
            ->assertOk()
            ->assertViewHas('summary', ['total_orders' => 2, 'total_order_amount' => 150.0, 'total_tax' => 14.0])
            ->assertViewHas('taxBreakdown', [
                ['tax_on' => 'basic', 'rows' => [['name' => self::SALES_TAX, 'tax_rate' => 8.0, 'total_amount' => 12.0]]],
                ['tax_on' => 'tax_on_delivery_charge', 'rows' => [['name' => self::SERVICE_TAX, 'tax_rate' => 6.0, 'total_amount' => 2.0]]],
            ])
            ->assertViewHas('orderTransactions', fn (LengthAwarePaginator $transactions): bool => $transactions->total() === 2
                && $transactions->getCollection()->pluck('id')->all() === $this->transactionIds[$businessModule])
            ->assertSee(ucwords(self::SERVICE_TAX))
            ->assertSee(route('vendor.'.$businessModule.'.orders.details', ['id' => OrderTransaction::query()->find($this->transactionIds[$businessModule][0])->order_id]));
    }

    public function test_date_range_outside_the_orders_reports_nothing(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.get-vat-report', ['dates' => now()->addYear()->format('m/d/Y').' - '.now()->addYear()->addDay()->format('m/d/Y')]))
            ->assertOk()
            ->assertViewHas('summary', ['total_orders' => 0, 'total_order_amount' => 0.0, 'total_tax' => 0.0])
            ->assertViewHas('taxBreakdown', []);
    }

    public function test_search_by_tax_name_stays_within_the_vendors_own_rows(): void
    {
        $ownTransaction = $this->createTransaction(seller: $this->seller, businessModule: BusinessModule::RETAIL, orderAmount: 30, tax: 3, taxes: [['basic', self::SEARCH_TAX, 5, 3]]);

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.get-vat-report', ['search' => self::SEARCH_TAX]))
            ->assertOk()
            ->assertViewHas('summary', ['total_orders' => 1, 'total_order_amount' => 30.0, 'total_tax' => 3.0])
            ->assertViewHas('orderTransactions', fn (LengthAwarePaginator $transactions): bool => $transactions->getCollection()->pluck('id')->all() === [$ownTransaction->id]);
    }

    public function test_search_by_order_id_ignores_orders_outside_the_date_range(): void
    {
        $oldTransaction = $this->createTransaction(seller: $this->seller, businessModule: BusinessModule::RETAIL, orderAmount: 30, tax: 3, taxes: [['basic', self::SALES_TAX, 8, 3]], updatedAt: now()->subDays(60));

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.get-vat-report', ['search' => (string) $oldTransaction->order_id]))
            ->assertOk()
            ->assertViewHas('summary', ['total_orders' => 0, 'total_order_amount' => 0.0, 'total_tax' => 0.0]);
    }

    public static function exportTypes(): array
    {
        return self::withWholesaleDataSets(
            dataSets: [
                'retail excel' => [BusinessModule::RETAIL, 'excel', 'xlsx'],
                'retail csv' => [BusinessModule::RETAIL, 'csv', 'csv'],
            ],
            wholesaleDataSets: [
                'wholesale excel' => [BusinessModule::WHOLESALE, 'excel', 'xlsx'],
            ],
        );
    }

    #[DataProvider('exportTypes')]
    public function test_export_downloads_the_vendors_own_module_rows(string $businessModule, string $exportType, string $extension): void
    {
        Excel::fake();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.get-vat-report-export', ['export_type' => $exportType]))
            ->assertOk();

        Excel::assertDownloaded($this->seller->shop->name.'s TaxExport.'.$extension, function (VendorTaxExport $export) use ($businessModule): bool {
            $data = $export->view()->getData()['data'];

            return $data['totalOrders'] === 2
                && $data['totalOrderAmount'] === 150.0
                && $data['totalTax'] === 14.0
                && $data['orderTransactions']->pluck('id')->all() === $this->transactionIds[$businessModule];
        });
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.get-vat-report', ['dates' => 'not a date - nope', 'export_type' => 'pdf', 'page' => 0]))
            ->assertRedirect()
            ->assertSessionHasErrors(['dates', 'export_type', 'page']);
    }

    private function createTransaction(
        Seller $seller,
        string $businessModule,
        float $orderAmount,
        float $tax,
        array $taxes,
        string $transactionStatus = 'disburse',
        string $orderStatus = 'delivered',
        string $orderType = 'default_type',
        mixed $createdAt = null,
        mixed $updatedAt = null,
    ): OrderTransaction {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();
        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'seller_is' => 'seller',
            'seller_id' => $seller->id,
            'order_status' => $orderStatus,
            'order_type' => $orderType,
            'order_group_id' => 'vendor-vat-report-'.Str::lower(Str::random(16)),
            'order_amount' => $orderAmount,
            'total_tax_amount' => $tax,
            'tax_model' => 'exclude',
        ])->saveQuietly();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();
        $transaction->forceFill([
            'order_id' => $order->id,
            'shop_id' => $seller->shop->id,
            'seller_id' => $seller->id,
            'seller_is' => 'seller',
            'status' => $transactionStatus,
            'order_amount' => $orderAmount,
            'tax' => $tax,
            'transaction_id' => 'vendor-vat-report-'.Str::lower(Str::random(12)),
            'created_at' => $createdAt ?? now()->subHours(2),
            'updated_at' => $updatedAt ?? now()->subMinutes(5),
        ])->saveQuietly();

        foreach ($taxes as [$taxOn, $taxName, $taxRate, $taxAmount]) {
            OrderTax::query()->forceCreate([
                'tax_name' => $taxName,
                'tax_type' => 'order_wise',
                'tax_on' => $taxOn,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'before_tax_amount' => $orderAmount - $tax,
                'after_tax_amount' => $orderAmount,
                'tax_payer' => 'vendor',
                'order_id' => $order->id,
                'order_type' => 'order',
                'quantity' => 1,
                'tax_id' => 0,
                'taxable_id' => '',
                'taxable_type' => '',
                'seller_id' => $seller->id,
                'seller_type' => 'seller',
                'system_tax_setup_id' => 0,
                'tax_for' => 'tax_for_orders',
            ]);
        }

        return $transaction;
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
