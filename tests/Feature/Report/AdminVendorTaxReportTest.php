<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Shop;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\TaxModule\app\Exports\VendorTaxExport;
use Modules\TaxModule\app\Exports\VendorWiseTaxExport;
use Modules\TaxModule\app\Models\OrderTax;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class AdminVendorTaxReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/admin-vendor-tax-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const OUT_OF_RANGE_DATE = '2031-07-10 12:00:00';

    private const REPORT_DATES = '05/01/2031 - 05/31/2031';

    private const ORDER_AMOUNT = [BusinessModule::RETAIL => 800.0, BusinessModule::WHOLESALE => 2600.0];

    private const ORDER_TAX = [BusinessModule::RETAIL => 64.0, BusinessModule::WHOLESALE => 208.0];

    private const DELIVERY_TAX = [BusinessModule::RETAIL => 4.0, BusinessModule::WHOLESALE => 6.0];

    private const TAX_NAME = 'Report Test Tax';

    private int $nextOrderId = 0;

    private Shop $shop;

    private array $transactionIds = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        $this->shop = $this->requireFixture(Shop::query()->where('author_type', 'vendor')->orderBy('id')->first(), 'vendor shop');

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $this->transactionIds[$businessModule] = $this->createTransaction(businessModule: $businessModule, orderAmount: self::ORDER_AMOUNT[$businessModule], orderTax: self::ORDER_TAX[$businessModule], deliveryTax: self::DELIVERY_TAX[$businessModule]);
            $this->createTransaction(businessModule: $businessModule, orderAmount: 5000.0, orderTax: 500.0, deliveryTax: 50.0, updatedAt: self::OUT_OF_RANGE_DATE);
            $this->createTransaction(businessModule: $businessModule, orderAmount: 7000.0, orderTax: 700.0, deliveryTax: 70.0, status: 'hold');
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
    public function test_list_reports_only_its_own_module_per_shop(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-wise-taxes', ['dates' => self::REPORT_DATES, 'shop_id' => $this->shop->id]))
            ->assertOk()
            ->assertViewHas('totalOrders', 1)
            ->assertViewHas('totalOrderAmount', fn (float $amount): bool => abs($amount - self::ORDER_AMOUNT[$businessModule]) < 0.001)
            ->assertViewHas('totalTax', fn (float $tax): bool => abs($tax - self::ORDER_TAX[$businessModule] - self::DELIVERY_TAX[$businessModule]) < 0.001)
            ->assertViewHas('shopTaxList', function (LengthAwarePaginator $shopTaxList) use ($businessModule): bool {
                $row = $shopTaxList->first();

                return $shopTaxList->total() === 1
                    && $row['shop']->id === $this->shop->id
                    && $row['total_orders'] === 1
                    && abs($row['taxes']['basic'][self::TAX_NAME] - self::ORDER_TAX[$businessModule]) < 0.001
                    && abs($row['taxes']['tax_on_delivery_charge'][self::TAX_NAME] - self::DELIVERY_TAX[$businessModule]) < 0.001;
            });
    }

    #[DataProvider('modulePages')]
    public function test_search_keeps_the_date_range_and_status_filters(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-wise-taxes', ['dates' => self::REPORT_DATES, 'shop_id' => $this->shop->id, 'search' => self::TAX_NAME]))
            ->assertOk()
            ->assertViewHas('totalOrders', 1)
            ->assertViewHas('totalOrderAmount', fn (float $amount): bool => abs($amount - self::ORDER_AMOUNT[$businessModule]) < 0.001);
    }

    #[DataProvider('modulePages')]
    public function test_details_list_its_own_module_orders_for_the_shop(string $businessModule): void
    {
        $transactionId = $this->transactionIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-tax', ['dates' => self::REPORT_DATES, 'shop_id' => $this->shop->id]))
            ->assertOk()
            ->assertViewHas('shop', fn (Shop $shop): bool => $shop->id === $this->shop->id)
            ->assertViewHas('totalOrders', 1)
            ->assertViewHas('orderTransactions', fn (LengthAwarePaginator $transactions): bool => $transactions->total() === 1 && $transactions->first()->id === $transactionId);
    }

    #[DataProvider('modulePages')]
    public function test_details_require_an_existing_shop(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-tax', ['dates' => self::REPORT_DATES]))
            ->assertRedirect()
            ->assertSessionHasErrors('shop_id');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-tax', ['dates' => self::REPORT_DATES, 'shop_id' => (int) Shop::query()->max('id') + 1000]))
            ->assertNotFound();
    }

    #[DataProvider('modulePages')]
    public function test_invalid_shop_filter_is_rejected(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-wise-taxes', ['shop_id' => 'none']))
            ->assertRedirect()
            ->assertSessionHasErrors('shop_id');
    }

    #[DataProvider('modulePages')]
    public function test_exports_download_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $filters = ['dates' => self::REPORT_DATES, 'shop_id' => $this->shop->id];
        $transactionId = $this->transactionIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-wise-tax-export', [...$filters, 'export_type' => 'csv']))
            ->assertOk();

        Excel::assertDownloaded('VendorWiseTaxExport.csv', fn (VendorWiseTaxExport $export): bool => $this->exportData($export)['orderTransactions']->pluck('id')->all() === [$transactionId]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.vendor-tax-export', [...$filters, 'export_type' => 'excel']))
            ->assertOk();

        Excel::assertDownloaded($this->shop->name.'s TaxExport.xlsx', fn (VendorTaxExport $export): bool => $this->exportData($export)['orderTransactions']->pluck('id')->all() === [$transactionId]);
    }

    public function test_auction_list_rejects_an_invalid_shop_filter(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.vendor-wise-taxes', ['shop_id' => 'none']))
            ->assertRedirect()
            ->assertSessionHasErrors('shop_id');
    }

    public function test_auction_customer_list_renders_and_rejects_an_invalid_customer_filter(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.customer-wise-taxes', ['dates' => self::REPORT_DATES]))
            ->assertOk()
            ->assertViewHas('customerTaxList', fn (LengthAwarePaginator $list): bool => $list->total() === 0);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.customer-wise-taxes', ['customer_id' => 'none']))
            ->assertRedirect()
            ->assertSessionHasErrors('customer_id');
    }

    public function test_auction_customer_details_treat_all_as_every_customer(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.auction.customer-tax', ['customer_id' => 'all']))
            ->assertOk()
            ->assertViewHas('customer', null);
    }

    private function exportData(object $export): array
    {
        return (fn (): array => $this->data)->call($export);
    }

    private function createTransaction(
        string $businessModule,
        float $orderAmount,
        float $orderTax,
        float $deliveryTax,
        string $updatedAt = self::REPORT_DATE,
        string $status = 'disburse',
    ): int {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $businessModule,
            'order_type' => 'default_type',
            'order_status' => 'delivered',
            'seller_is' => 'seller',
            'seller_id' => $this->shop->seller_id,
            'order_group_id' => 'vendor-tax-'.Str::lower(Str::random(16)),
        ])->save();

        $transaction = $this->requireFixture(OrderTransaction::query()->orderBy('id')->first(), 'order transaction')->replicate();

        $transaction->forceFill([
            'order_id' => $order->id,
            'shop_id' => $this->shop->id,
            'transaction_id' => 'vendor-tax-'.Str::lower(Str::random(12)),
            'status' => $status,
            'order_amount' => $orderAmount,
            'tax' => $orderTax + $deliveryTax,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ])->save();

        foreach (['basic' => $orderTax, 'tax_on_delivery_charge' => $deliveryTax] as $taxOn => $amount) {
            OrderTax::query()->create([
                'tax_name' => self::TAX_NAME,
                'tax_type' => 'order_wise',
                'tax_on' => $taxOn,
                'tax_rate' => 8,
                'tax_amount' => $amount,
                'order_id' => $order->id,
                'order_type' => 'order',
                'tax_for' => 'tax_for_orders',
            ]);
        }

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
