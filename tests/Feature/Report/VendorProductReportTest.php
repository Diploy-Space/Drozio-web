<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\AllProductReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\OrderDetail;
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

class VendorProductReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/vendor-product-report-views';

    private const STUB_LAYOUT = 'layouts/vendor/app';

    private const REPORT_DATE = '2031-05-10 12:00:00';

    private const REPORT_FILTERS = ['date_type' => 'custom_date', 'from' => '2031-05-01', 'to' => '2031-05-31'];

    private const UNIT_PRICES = [
        BusinessModule::RETAIL => 100.0,
        BusinessModule::WHOLESALE => 250.0,
    ];

    private Seller $seller;

    private Seller $otherSeller;

    private array $soldProductIds = [];

    private int $otherSellerProductId;

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

        foreach (self::UNIT_PRICES as $businessModule => $unitPrice) {
            $soldProduct = $this->createProduct(seller: $this->seller, businessModule: $businessModule, name: 'Vendor sold report product', requestStatus: 1);
            $this->createOrderDetail(product: $soldProduct, quantity: 2, price: $unitPrice, discount: 5.0, deliveryStatus: 'delivered');
            $this->createOrderDetail(product: $soldProduct, quantity: 4, price: $unitPrice, discount: 10.0, deliveryStatus: 'pending');
            $this->soldProductIds[$businessModule] = $soldProduct->id;

            $this->createProduct(seller: $this->seller, businessModule: $businessModule, name: 'Vendor pending report product', requestStatus: 0);
        }

        $this->otherSellerProductId = $this->createProduct(seller: $this->otherSeller, businessModule: BusinessModule::RETAIL, name: 'Vendor sold report product', requestStatus: 1)->id;
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
    public function test_page_lists_only_the_vendors_own_module_products(string $businessModule): void
    {
        $unitPrice = self::UNIT_PRICES[$businessModule];
        $soldProductId = $this->soldProductIds[$businessModule];

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.all-product', self::REPORT_FILTERS + ['seller_id' => $this->otherSeller->id]))
            ->assertOk()
            ->assertViewHas('products', function (LengthAwarePaginator $products) use ($soldProductId, $unitPrice): bool {
                $soldRow = $products->getCollection()->firstWhere('id', $soldProductId);

                return $products->total() === 2
                    && $soldRow !== null
                    && $soldRow['total_sold_quantity'] === 2
                    && abs($soldRow['total_sold_amount'] - 2 * $unitPrice) < 0.0001
                    && $products->getCollection()->firstWhere('id', $this->otherSellerProductId) === null;
            })
            ->assertViewHas('productStatusCounts', fn (array $counts): bool => $counts['active_product_count'] === 1 && $counts['pending_product_count'] === 1 && $counts['total_product_count'] === 2)
            ->assertViewHas('productSaleSummary', fn (array $summary): bool => $summary['total_sold_quantity'] === 6 && abs($summary['total_discount'] - 15.0) < 0.0001)
            ->assertViewHas('productCountChart', fn (array $chart): bool => count($chart) === 31 && $chart[9]['product_count'] === 2)
            ->assertSee(route('vendor.report.'.$businessModule.'.all-product-excel', ['date_type' => self::REPORT_FILTERS['date_type'], 'from' => self::REPORT_FILTERS['from'], 'to' => self::REPORT_FILTERS['to']]));
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_the_vendors_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.'.$businessModule.'.all-product-excel', self::REPORT_FILTERS))
            ->assertOk();

        Excel::assertDownloaded('Product-Report-List.xlsx', function (AllProductReportExport $export) use ($businessModule): bool {
            $data = $export->view()->getData()['data'];

            return $data['products']->count() === 2
                && $data['products']->contains('id', $this->soldProductIds[$businessModule])
                && $data['storeName'] === $this->seller->shop->name;
        });
    }

    public function test_search_does_not_reach_another_vendors_products(): void
    {
        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.retail.all-product', self::REPORT_FILTERS + ['search' => 'Vendor sold report product']))
            ->assertOk()
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->total() === 1
                && $products->getCollection()->first()['id'] === $this->soldProductIds[BusinessModule::RETAIL]);
    }

    public function test_custom_date_requires_a_start_and_end_date(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->seller, 'seller')
            ->get(route('vendor.report.wholesale.all-product', ['date_type' => 'custom_date']))
            ->assertRedirect()
            ->assertSessionHasErrors(['from', 'to']);
    }

    private function createProduct(Seller $seller, string $businessModule, string $name, int $requestStatus): Product
    {
        $product = $this->requireFixture(Product::query()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(12)),
            'added_by' => 'seller',
            'user_id' => $seller->id,
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
