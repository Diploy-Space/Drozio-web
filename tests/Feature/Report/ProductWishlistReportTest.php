<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\WishlistProductReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Wishlist;
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

class ProductWishlistReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/product-wishlist-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const WISHLIST_COUNTS = [1, 3, 2];

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

        $this->seller = $this->requireFixture(Seller::query()->where('status', 'approved')->whereHas('shop')->orderBy('id')->first(), 'approved vendor');

        Product::query()->where(['added_by' => 'seller', 'user_id' => $this->seller->id])->update(['request_status' => 0]);

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            foreach (self::WISHLIST_COUNTS as $wishlistCount) {
                $product = $this->createVendorProduct(businessModule: $businessModule, requestStatus: 1);
                $this->createWishlists(product: $product, count: $wishlistCount);
                $this->productIds[$businessModule][$wishlistCount] = $product->id;
            }
            $this->createWishlists(product: $this->createVendorProduct(businessModule: $businessModule, requestStatus: 0), count: 5);
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
    public function test_page_lists_its_own_module_approved_products_by_wishlist_count(string $businessModule): void
    {
        $productIds = $this->productIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.stock.product-in-wishlist', ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->total() === 3
                && $products->getCollection()->pluck('id')->all() === [$productIds[3], $productIds[2], $productIds[1]]
                && $products->getCollection()->pluck('wishlist_count')->all() === [3, 2, 1])
            ->assertSee(route('admin.report.'.$businessModule.'.stock.wishlist-product-export', ['seller_id' => $this->seller->id, 'sort' => 'DESC']));
    }

    public function test_low_to_high_sort_reverses_the_wishlist_order(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.stock.product-in-wishlist', ['seller_id' => $this->seller->id, 'sort' => 'ASC']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->getCollection()->pluck('wishlist_count')->all() === [1, 2, 3]);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.stock.wishlist-product-export', ['seller_id' => $this->seller->id, 'sort' => 'DESC']))
            ->assertOk();

        Excel::assertDownloaded('Product-wishlisted-report.xlsx', function (WishlistProductReportExport $export) use ($businessModule): bool {
            $data = $export->view()->getData()['data'];

            return $data['products']->count() === 3
                && $data['products']->pluck('id')->sort()->values()->all() === collect($this->productIds[$businessModule])->sort()->values()->all()
                && $data['storeName'] === $this->seller->shop->name;
        });
    }

    public function test_invalid_sort_and_vendor_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.stock.product-in-wishlist', ['seller_id' => 'abc', 'sort' => 'name']))
            ->assertRedirect()
            ->assertSessionHasErrors(['seller_id', 'sort']);
    }

    private function createVendorProduct(string $businessModule, int $requestStatus): Product
    {
        $product = $this->requireFixture(Product::query()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'name' => 'Wishlist report product',
            'slug' => 'wishlist-report-product-'.Str::lower(Str::random(12)),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'request_status' => $requestStatus,
        ])->save();

        return $product;
    }

    private function createWishlists(Product $product, int $count): void
    {
        $fixture = $this->requireFixture(Wishlist::query()->orderBy('id')->first(), 'wishlist');

        for ($index = 0; $index < $count; $index++) {
            $fixture->replicate()->forceFill(['product_id' => $product->id])->save();
        }
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
