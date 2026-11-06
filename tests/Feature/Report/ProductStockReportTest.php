<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\StockProductReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
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

class ProductStockReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/product-stock-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const STOCK_LIMIT = 10;

    private const CATEGORY_ID = 987654;

    private const STOCK_STATUSES = [
        0 => 'out_of_stock',
        4 => 'soon_stock_out',
        25 => 'in_stock',
    ];

    private Seller $seller;

    private array $productIds = [];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
        $this->stubAdminLayout();
        $this->withoutMiddleware([CollapseResponseWhitespaceMiddleware::class, CompressResponseMiddleware::class]);

        BusinessSetting::query()->updateOrInsert(['type' => 'stock_limit'], ['value' => self::STOCK_LIMIT]);

        $this->seller = $this->requireFixture(Seller::query()->where('status', 'approved')->whereHas('shop')->orderBy('id')->first(), 'approved vendor');

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            foreach (array_keys(self::STOCK_STATUSES) as $currentStock) {
                $this->productIds[$businessModule][$currentStock] = $this->createVendorProduct(businessModule: $businessModule, currentStock: $currentStock, productType: 'physical')->id;
            }
            $this->createVendorProduct(businessModule: $businessModule, currentStock: 1, productType: 'digital');
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
    public function test_page_lists_its_own_module_physical_products_by_stock(string $businessModule): void
    {
        $expectedIds = array_values($this->productIds[$businessModule]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.stock.product-stock', ['seller_id' => $this->seller->id, 'category_id' => self::CATEGORY_ID, 'sort' => 'ASC']))
            ->assertOk()
            ->assertViewHas('products', function (LengthAwarePaginator $products) use ($expectedIds): bool {
                $rows = $products->getCollection();

                return $products->total() === 3
                    && $rows->pluck('id')->all() === $expectedIds
                    && $rows->pluck('stock_status')->all() === array_values(self::STOCK_STATUSES);
            })
            ->assertSee(route('admin.report.'.$businessModule.'.stock.product-stock-export', ['sort' => 'ASC', 'category_id' => self::CATEGORY_ID, 'seller_id' => $this->seller->id]));
    }

    public function test_high_to_low_sort_reverses_the_stock_order(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.stock.product-stock', ['seller_id' => $this->seller->id, 'category_id' => self::CATEGORY_ID, 'sort' => 'DESC']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->getCollection()->pluck('current_stock')->all() === [25, 4, 0]);
    }

    public function test_in_house_filter_excludes_vendor_products(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.stock.product-stock', ['seller_id' => 'in_house', 'category_id' => self::CATEGORY_ID]))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->total() === 0);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.stock.product-stock-export', ['seller_id' => $this->seller->id, 'category_id' => self::CATEGORY_ID]))
            ->assertOk();

        Excel::assertDownloaded('Product-stock-report.xlsx', function (StockProductReportExport $export) use ($businessModule): bool {
            $data = $export->view()->getData()['data'];

            return $data['products']->pluck('id')->all() === array_values($this->productIds[$businessModule])
                && $data['storeName'] === $this->seller->shop->name;
        });
    }

    public function test_invalid_sort_and_vendor_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.stock.product-stock', ['seller_id' => 'abc', 'sort' => 'name']))
            ->assertRedirect()
            ->assertSessionHasErrors(['seller_id', 'sort']);
    }

    private function createVendorProduct(string $businessModule, int $currentStock, string $productType): Product
    {
        $product = $this->requireFixture(Product::query()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'name' => 'Stock report product '.$currentStock,
            'slug' => 'stock-report-product-'.Str::lower(Str::random(12)),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'product_type' => $productType,
            'current_stock' => $currentStock,
            'category_ids' => json_encode([['id' => (string) self::CATEGORY_ID, 'position' => 1]]),
        ])->save();

        return $product;
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
