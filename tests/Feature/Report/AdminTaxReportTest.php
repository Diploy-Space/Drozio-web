<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\AdminTaxReportDetailsExport;
use Modules\TaxModule\app\Exports\AdminTaxReportExport;
use Modules\TaxModule\app\Models\Tax;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class AdminTaxReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/admin-tax-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const OUT_OF_RANGE_DATE = '2031-07-10 12:00:00';

    private const REPORT_DATES = '05/01/2031 - 05/31/2031';

    private const ADMIN_COMMISSION = [BusinessModule::RETAIL => 120.0, BusinessModule::WHOLESALE => 340.0];

    private const DELIVERY_CHARGE = [BusinessModule::RETAIL => 30.0, BusinessModule::WHOLESALE => 55.0];

    private const ORDER_AMOUNT = [BusinessModule::RETAIL => 900.0, BusinessModule::WHOLESALE => 2500.0];

    private int $nextOrderId = 0;

    private array $transactionIds = [];

    private array $heldOrderIds = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $this->transactionIds[$businessModule] = $this->createTransaction(
                businessModule: $businessModule,
                adminCommission: self::ADMIN_COMMISSION[$businessModule],
                deliveryCharge: self::DELIVERY_CHARGE[$businessModule],
                orderAmount: self::ORDER_AMOUNT[$businessModule],
            );
            $this->createTransaction(businessModule: $businessModule, adminCommission: 999.0, deliveryCharge: 99.0, orderAmount: 5000.0, updatedAt: self::OUT_OF_RANGE_DATE);
            $heldTransactionId = $this->createTransaction(businessModule: $businessModule, adminCommission: 777.0, deliveryCharge: 77.0, orderAmount: 7000.0, status: 'hold');
            $this->heldOrderIds[$businessModule] = OrderTransaction::query()->whereKey($heldTransactionId)->value('order_id');
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
    public function test_same_tax_for_all_sources_reports_only_its_own_module(string $businessModule): void
    {
        $taxes = $this->moduleTaxes(businessModule: $businessModule, count: 2);
        $totalRate = $taxes->sum('tax_rate');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', $this->allSourceFilters(taxes: $taxes)))
            ->assertOk()
            ->assertViewHas('reportList', function (array $reportList) use ($businessModule, $totalRate, $taxes): bool {
                [$commission, $delivery] = $reportList;

                return $commission['type'] === 'admin_commission'
                    && abs($commission['amount'] - self::ADMIN_COMMISSION[$businessModule]) < 0.001
                    && abs($commission['total_tax_amount'] - self::ADMIN_COMMISSION[$businessModule] * $totalRate / 100) < 0.001
                    && count($commission['taxes']) === $taxes->count()
                    && $delivery['type'] === 'delivery_charge'
                    && abs($delivery['amount'] - self::DELIVERY_CHARGE[$businessModule]) < 0.001
                    && abs($delivery['total_tax_amount'] - self::DELIVERY_CHARGE[$businessModule] * $totalRate / 100) < 0.001;
            })
            ->assertViewHas('totalBase', fn (float $total): bool => abs($total - self::ADMIN_COMMISSION[$businessModule] - self::DELIVERY_CHARGE[$businessModule]) < 0.001);
    }

    #[DataProvider('modulePages')]
    public function test_individual_sources_apply_their_own_rates(string $businessModule): void
    {
        [$commissionTax, $deliveryTax] = $this->moduleTaxes(businessModule: $businessModule, count: 2)->all();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', [
                'date_range_type' => 'custom',
                'dates' => self::REPORT_DATES,
                'calculate_tax_on' => 'individual_source',
                'tax_on_order_commission' => [$commissionTax->id],
                'tax_on_delivery_charge_commission' => [$deliveryTax->id],
            ]))
            ->assertOk()
            ->assertViewHas('reportList', function (array $reportList) use ($businessModule, $commissionTax, $deliveryTax): bool {
                [$commission, $delivery] = $reportList;

                return (float) $commission['total_tax_percentage'] === (float) $commissionTax->tax_rate
                    && abs($commission['total_tax_amount'] - self::ADMIN_COMMISSION[$businessModule] * $commissionTax->tax_rate / 100) < 0.001
                    && (float) $delivery['total_tax_percentage'] === (float) $deliveryTax->tax_rate
                    && abs($delivery['total_tax_amount'] - self::DELIVERY_CHARGE[$businessModule] * $deliveryTax->tax_rate / 100) < 0.001;
            });
    }

    #[DataProvider('modulePages')]
    public function test_tax_options_and_rates_come_from_its_own_module_only(string $businessModule): void
    {
        $otherModule = $businessModule === BusinessModule::RETAIL ? BusinessModule::WHOLESALE : BusinessModule::RETAIL;
        $otherTaxes = $this->moduleTaxes(businessModule: $otherModule, count: 1);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', $this->allSourceFilters(taxes: $otherTaxes)))
            ->assertOk()
            ->assertViewHas('taxOptions', fn (Collection $taxes): bool => $taxes->isNotEmpty() && $taxes->every(fn (Tax $tax): bool => $tax->business_module === $businessModule))
            ->assertViewHas('totalTax', 0);
    }

    #[DataProvider('modulePages')]
    public function test_details_list_its_own_source_transactions_with_totals(string $businessModule): void
    {
        $taxes = $this->moduleTaxes(businessModule: $businessModule, count: 1);
        $transactionId = $this->transactionIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-details', [...$this->allSourceFilters(taxes: $taxes), 'source' => 'admin_commission']))
            ->assertOk()
            ->assertViewHas('transactions', fn (LengthAwarePaginator $transactions): bool => $transactions->total() === 1 && $transactions->first()->id === $transactionId)
            ->assertViewHas('totalOrderCount', 1)
            ->assertViewHas('totalOrderAmount', fn (float $amount): bool => abs($amount - self::ORDER_AMOUNT[$businessModule]) < 0.001)
            ->assertViewHas('totalAmount', fn (float $amount): bool => abs($amount - self::ADMIN_COMMISSION[$businessModule]) < 0.001)
            ->assertViewHas('taxRates', fn (Collection $rates): bool => $rates->pluck('id')->all() === $taxes->pluck('id')->all());
    }

    #[DataProvider('modulePages')]
    public function test_search_does_not_leak_rows_outside_the_report_filters(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', [
                ...$this->allSourceFilters(taxes: $this->moduleTaxes(businessModule: $businessModule, count: 1)),
                'search' => (string) $this->heldOrderIds[$businessModule],
            ]))
            ->assertOk()
            ->assertViewHas('totalBase', 0);
    }

    #[DataProvider('modulePages')]
    public function test_report_is_empty_until_a_calculation_is_chosen(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', ['date_range_type' => 'custom', 'dates' => self::REPORT_DATES]))
            ->assertOk()
            ->assertViewHas('reportList', []);
    }

    #[DataProvider('modulePages')]
    public function test_calculation_without_date_range_type_is_rejected(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-report', ['calculate_tax_on' => 'all_source']))
            ->assertRedirect()
            ->assertSessionHasErrors('date_range_type');
    }

    #[DataProvider('modulePages')]
    public function test_details_reject_an_unknown_source(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-details', [...$this->allSourceFilters(taxes: $this->moduleTaxes(businessModule: $businessModule, count: 1)), 'source' => 'entry_fee']))
            ->assertRedirect()
            ->assertSessionHasErrors('source');
    }

    #[DataProvider('modulePages')]
    public function test_details_are_not_found_without_a_generated_report(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-details', ['source' => 'admin_commission']))
            ->assertNotFound();
    }

    #[DataProvider('modulePages')]
    public function test_exports_download_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $filters = $this->allSourceFilters(taxes: $this->moduleTaxes(businessModule: $businessModule, count: 1));
        $transactionId = $this->transactionIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.admin-tax-report-export', [...$filters, 'export_type' => 'csv']))
            ->assertOk();

        Excel::assertDownloaded('AdminTaxExport.csv', fn (AdminTaxReportExport $export): bool => $this->exportData($export)['total_amount'] == self::ADMIN_COMMISSION[$businessModule] + self::DELIVERY_CHARGE[$businessModule]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.get-tax-details-export', [...$filters, 'source' => 'delivery_charge', 'export_type' => 'excel']))
            ->assertOk();

        Excel::assertDownloaded('delivery_charge TaxExport.xlsx', fn (AdminTaxReportDetailsExport $export): bool => $this->exportData($export)['transactions']->pluck('id')->all() === [$transactionId]);
    }

    public function test_auction_page_lists_only_auction_taxes(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.get-tax-report'))
            ->assertOk()
            ->assertViewHas('taxOptions', fn (Collection $taxes): bool => $taxes->every(fn (Tax $tax): bool => $tax->business_module === BusinessModule::AUCTION));
    }

    public function test_auction_calculation_without_date_range_type_is_rejected(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.get-tax-report', ['calculate_tax_on' => 'all_source']))
            ->assertRedirect()
            ->assertSessionHasErrors('date_range_type');
    }

    private function allSourceFilters(Collection $taxes): array
    {
        return [
            'date_range_type' => 'custom',
            'dates' => self::REPORT_DATES,
            'calculate_tax_on' => 'all_source',
            'tax_rate' => $taxes->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
        ];
    }

    private function exportData(object $export): array
    {
        return (fn (): array => $this->data)->call($export);
    }

    private function moduleTaxes(string $businessModule, int $count): Collection
    {
        $taxes = Tax::query()->where('business_module', $businessModule)->where('tax_rate', '>', 0)->orderBy('id')->limit($count)->get();

        if ($taxes->count() < $count) {
            $this->markTestSkipped("The snapshot database has fewer than {$count} {$businessModule} taxes.");
        }

        return $taxes;
    }

    private function createTransaction(
        string $businessModule,
        float $adminCommission,
        float $deliveryCharge,
        float $orderAmount,
        string $updatedAt = self::REPORT_DATE,
        string $status = 'disburse',
    ): int {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'order_type' => 'default_type',
            'order_status' => 'delivered',
            'shipping_responsibility' => 'inhouse_shipping',
            'seller_is' => 'seller',
            'order_group_id' => 'tax-report-'.Str::lower(Str::random(16)),
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();

        $transaction->forceFill([
            'order_id' => $order->id,
            'transaction_id' => 'tax-'.Str::lower(Str::random(12)),
            'status' => $status,
            'delivered_by' => 'admin',
            'seller_is' => 'seller',
            'admin_commission' => $adminCommission,
            'delivery_charge' => $deliveryCharge,
            'order_amount' => $orderAmount,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ])->save();

        return $transaction->id;
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
