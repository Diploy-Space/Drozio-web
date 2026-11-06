<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\StockProductReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\BusinessSetting;
use App\Models\Product;
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

class VendorProductStockReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-product-stock-report-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const VENDOR_STOCK_LIMIT = 10;

    private const SYSTEM_STOCK_LIMIT = 30;

    private const CATEGORY_ID = 987654;

    private const STOCK_STATUSES = [
        0 => 'out_of_stock',
        4 => 'soon_stock_out',
        25 => 'in_stock',
    ];

    private Seller $seller;

    private Seller $otherSeller;

    private array $productIds = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubVendorLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        BusinessSetting::query()->updateOrInsert(['type' => 'stock_limit'], ['value' => self::SYSTEM_STOCK_LIMIT]);

        $sellers = Seller::query()->where('status', 'approved')->whereHas('shop')->orderBy('id')->limit(2)->get();
        $this->seller = $this->requireFixture($sellers->get(0), 'approved vendor');
        $this->otherSeller = $this->requireFixture($sellers->get(1), 'second approved vendor');
        $this->seller->forceFill(['stock_limit' => self::VENDOR_STOCK_LIMIT])->save();

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            foreach (array_keys(self::STOCK_STATUSES) as $currentStock) {
                $this->productIds[$businessModule][$currentStock] = $this->createProduct(seller: $this->seller, businessModule: $businessModule, currentStock: $currentStock, productType: 'physical')->id;
            }
            $this->createProduct(seller: $this->seller, businessModule: $businessModule, currentStock: 1, productType: 'digital');
        }

        $this->createProduct(seller: $this->otherSeller, businessModule: BusinessModule::RETAIL, currentStock: 2, productType: 'physical');
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
    public function test_page_lists_the_vendors_own_module_physical_products_by_stock(string $businessModule): void
    {
        $expectedIds = array_values($this->productIds[$businessModule]);

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.stock-product-report', ['category_id' => self::CATEGORY_ID, 'sort' => 'ASC', 'seller_id' => $this->otherSeller->id]))
            ->assertOk()
            ->assertViewHas('stockLimit', self::VENDOR_STOCK_LIMIT)
            ->assertViewHas('products', function (LengthAwarePaginator $products) use ($expectedIds): bool {
                $rows = $products->getCollection();

                return $products->total() === 3
                    && $rows->pluck('id')->all() === $expectedIds
                    && $rows->pluck('stock_status')->all() === array_values(self::STOCK_STATUSES);
            });
    }

    public function test_system_stock_limit_applies_when_the_vendor_has_none(): void
    {
        $this->seller->forceFill(['stock_limit' => 0])->save();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.stock-product-report', ['category_id' => self::CATEGORY_ID]))
            ->assertOk()
            ->assertViewHas('stockLimit', self::SYSTEM_STOCK_LIMIT)
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->getCollection()->pluck('stock_status')->all() === ['out_of_stock', 'soon_stock_out', 'soon_stock_out']);
    }

    public function test_high_to_low_sort_reverses_the_stock_order(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.stock-product-report', ['category_id' => self::CATEGORY_ID, 'sort' => 'DESC']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->getCollection()->pluck('current_stock')->all() === [25, 4, 0]);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_the_vendors_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.product-stock-export', ['category_id' => self::CATEGORY_ID]))
            ->assertOk();

        Excel::assertDownloaded('Product-stock-report.xlsx', function (StockProductReportExport $export) use ($businessModule): bool {
            $data = $export->view()->getData()['data'];

            return $data['products']->pluck('id')->all() === array_values($this->productIds[$businessModule])
                && $data['storeName'] === $this->seller->shop->name;
        });
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.stock-product-report', ['sort' => 'name']))
            ->assertRedirect()
            ->assertSessionHasErrors(['sort']);
    }

    private function createProduct(Seller $seller, string $businessModule, int $currentStock, string $productType): Product
    {
        $product = $this->requireFixture(Product::query()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'name' => 'Vendor stock report product '.$currentStock,
            'slug' => 'vendor-stock-report-product-'.Str::lower(Str::random(12)),
            'added_by' => 'seller',
            'user_id' => $seller->id,
            'product_type' => $productType,
            'current_stock' => $currentStock,
            'category_ids' => json_encode([['id' => (string) self::CATEGORY_ID, 'position' => 1]]),
        ])->save();

        return $product;
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
