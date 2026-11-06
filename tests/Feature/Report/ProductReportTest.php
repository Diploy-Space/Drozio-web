<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\AllProductReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
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

class ProductReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/product-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const UNIT_PRICES = [
        BusinessModule::RETAIL => 100.0,
        BusinessModule::WHOLESALE => 250.0,
    ];

    private Seller $seller;

    private array $soldProductIds = [];

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

        foreach (self::UNIT_PRICES as $businessModule => $unitPrice) {
            $soldProduct = $this->createVendorProduct(businessModule: $businessModule, name: 'Sold report product', requestStatus: 1);
            $this->createOrderDetail(product: $soldProduct, quantity: 2, price: $unitPrice, discount: 5.0, deliveryStatus: 'delivered');
            $this->createOrderDetail(product: $soldProduct, quantity: 1, price: $unitPrice, discount: 0.0, deliveryStatus: 'delivered');
            $this->createOrderDetail(product: $soldProduct, quantity: 4, price: $unitPrice, discount: 10.0, deliveryStatus: 'pending');
            $this->createReview(product: $soldProduct, rating: 4, deliveryManId: null);
            $this->createReview(product: $soldProduct, rating: 5, deliveryManId: null);
            $this->createReview(product: $soldProduct, rating: 1, deliveryManId: 1);
            $this->soldProductIds[$businessModule] = $soldProduct->id;

            $this->createVendorProduct(businessModule: $businessModule, name: 'Pending report product', requestStatus: 0);

            $rejectedProduct = $this->createVendorProduct(businessModule: $businessModule, name: 'Rejected report product', requestStatus: 2);
            $this->createOrderDetail(product: $rejectedProduct, quantity: 3, price: $unitPrice, discount: 1.0, deliveryStatus: 'pending');
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
    public function test_page_lists_its_own_module_products_with_delivered_sales(string $businessModule): void
    {
        $unitPrice = self::UNIT_PRICES[$businessModule];
        $soldProductId = $this->soldProductIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.all-product', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('products', function (LengthAwarePaginator $products) use ($soldProductId, $unitPrice): bool {
                $soldRow = $products->getCollection()->firstWhere('id', $soldProductId);

                return $products->total() === 3
                    && $soldRow !== null
                    && $soldRow['total_sold_quantity'] === 3
                    && abs($soldRow['total_sold_amount'] - 3 * $unitPrice) < 0.0001
                    && abs($soldRow['average_sold_price'] - $unitPrice) < 0.0001
                    && abs($soldRow['average_rating'] - 4.5) < 0.0001
                    && $soldRow['review_count'] === 3;
            })
            ->assertViewHas('productCountChart', fn (array $chart): bool => count($chart) === 31 && $chart[9]['product_count'] === 3)
            ->assertSee(route('admin.report.'.$businessModule.'.all-product-excel', ['seller_id' => $this->seller->id, 'date_type' => self::REPORT_FILTERS['date_type'], 'from' => self::REPORT_FILTERS['from'], 'to' => self::REPORT_FILTERS['to']]));
    }

    #[DataProvider('modulePages')]
    public function test_summary_follows_the_legacy_product_report_formula(string $businessModule): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.all-product', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk()
            ->assertViewHas('productStatusCounts', [
                'total_product_count' => 3,
                'active_product_count' => 1,
                'pending_product_count' => 1,
                'rejected_product_count' => 1,
            ])
            ->assertViewHas('productSaleSummary', fn (array $summary): bool => $summary['total_sold_quantity'] === 7
                && abs($summary['total_discount'] - 15.0) < 0.0001);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $soldProductId = $this->soldProductIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.all-product-excel', self::REPORT_FILTERS + ['seller_id' => $this->seller->id]))
            ->assertOk();

        Excel::assertDownloaded('Product-Report-List.xlsx', function (AllProductReportExport $export) use ($soldProductId): bool {
            $data = $export->view()->getData()['data'];

            return $data['products']->count() === 3
                && $data['products']->contains(fn (array $row): bool => $row['id'] === $soldProductId)
                && $data['storeName'] === $this->seller->shop->name;
        });
    }

    public function test_search_stays_within_the_selected_vendor(): void
    {
        $otherVendorProduct = $this->createVendorProduct(businessModule: BusinessModule::RETAIL, name: 'Sold report product elsewhere', requestStatus: 1);
        $otherVendorProduct->forceFill(['user_id' => $this->seller->id + 100000])->save();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.all-product', self::REPORT_FILTERS + ['seller_id' => $this->seller->id, 'search' => 'Sold report']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->total() === 1
                && $products->first()['id'] === $this->soldProductIds[BusinessModule::RETAIL]);
    }

    public function test_in_house_filter_excludes_vendor_products(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.all-product', self::REPORT_FILTERS + ['seller_id' => 'inhouse']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => ! $products->getCollection()->contains(fn (array $row): bool => $row['id'] === $this->soldProductIds[BusinessModule::WHOLESALE]));
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.all-product', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_non_numeric_vendor_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.all-product', ['seller_id' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors(['seller_id']);
    }

    private function createVendorProduct(string $businessModule, string $name, int $requestStatus): Product
    {
        $product = $this->requireFixture(Product::query()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(12)),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'request_status' => $requestStatus,
            'product_type' => 'physical',
            'unit_price' => self::UNIT_PRICES[$businessModule],
            'current_stock' => 50,
            'created_at' => self::REPORT_DATE,
        ])->save();

        return $product;
    }

    private function createOrderDetail(Product $product, int $quantity, float $price, float $discount, string $deliveryStatus): void
    {
        $orderDetail = $this->requireFixture(OrderDetail::query()->orderBy('id')->first(), 'order detail')->replicate();

        $orderDetail->forceFill([
            'product_id' => $product->id,
            'qty' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'delivery_status' => $deliveryStatus,
        ])->save();
    }

    private function createReview(Product $product, int $rating, ?int $deliveryManId): void
    {
        $review = $this->requireFixture(Review::query()->withoutGlobalScopes()->orderBy('id')->first(), 'review')->replicate();

        $review->forceFill([
            'product_id' => $product->id,
            'rating' => $rating,
            'delivery_man_id' => $deliveryManId,
        ])->save();
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
