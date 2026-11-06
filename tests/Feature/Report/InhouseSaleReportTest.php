<?php

namespace Tests\Feature\Report;

use App\Enums\BusinessModule;
use App\Exports\InhouseProductSaleReportExport;
use App\Http\Middleware\CollapseResponseWhitespaceMiddleware;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
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

class InhouseSaleReportTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const STUB_VIEW_ROOT = __DIR__.'/../../../storage/framework/testing/inhouse-sale-report-views';

    private const STUB_LAYOUT = 'layouts/admin/app';

    private const REPORT_CATEGORY_ID = 987654321;

    private const UNIT_PRICE = 100.0;

    private const SOLD_QUANTITY = 3;

    private const LINE_DISCOUNT = 20.0;

    private int $nextOrderId = 0;

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

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $product = $this->createInHouseProduct(businessModule: $businessModule);
            $this->productIds[$businessModule] = $product->id;

            $this->createOrderLine(product: $product, customerId: 1, deliveryStatus: 'delivered');
            $this->createOrderLine(product: $product, customerId: 2, deliveryStatus: 'delivered');
            $this->createOrderLine(product: $product, customerId: 2, deliveryStatus: 'pending');
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
    public function test_page_lists_only_its_own_in_house_products_with_delivered_quantity(string $businessModule, string $otherModule): void
    {
        $productId = $this->productIds[$businessModule];
        $otherProductId = $this->productIds[$otherModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.inhouse-product-sale', ['category_id' => self::REPORT_CATEGORY_ID]))
            ->assertOk()
            ->assertViewHas('selectedCategoryId', self::REPORT_CATEGORY_ID)
            ->assertViewHas('products', function (LengthAwarePaginator $products) use ($productId, $otherProductId): bool {
                $row = $products->firstWhere('id', $productId);

                return $products->total() === 1
                    && $row !== null
                    && (int) $row['total_sold_quantity'] === self::SOLD_QUANTITY * 2
                    && $products->firstWhere('id', $otherProductId) === null;
            })
            ->assertSee(route('admin.report.'.$businessModule.'.inhouse-product-sale-excel-export', ['category_id' => self::REPORT_CATEGORY_ID]));
    }

    #[DataProvider('modulePages')]
    public function test_summary_counts_only_delivered_lines_of_its_own_module(string $businessModule): void
    {
        $expectedSaleAmount = 2 * (self::UNIT_PRICE * self::SOLD_QUANTITY - self::LINE_DISCOUNT);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.inhouse-product-sale', ['category_id' => self::REPORT_CATEGORY_ID]))
            ->assertOk()
            ->assertViewHas('saleSummary', fn (array $summary): bool => abs($summary['total_sale_amount'] - $expectedSaleAmount) < 0.0001
                && $summary['total_order_count'] === 2
                && abs($summary['average_order_value'] - $expectedSaleAmount / 2) < 0.0001
                && $summary['total_customer_count'] === 2);
    }

    #[DataProvider('modulePages')]
    public function test_export_downloads_its_own_module_rows(string $businessModule): void
    {
        Excel::fake();

        $productId = $this->productIds[$businessModule];

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.'.$businessModule.'.inhouse-product-sale-excel-export', ['category_id' => self::REPORT_CATEGORY_ID]))
            ->assertOk();

        Excel::assertDownloaded('inhouse-product-sale-report.xlsx', function (InhouseProductSaleReportExport $export) use ($productId): bool {
            $products = $export->view()->getData()['data']['products'];

            return $products->count() === 1
                && $products->first()->id === $productId
                && (int) $products->first()['total_sold_quantity'] === self::SOLD_QUANTITY * 2;
        });
    }

    public function test_all_category_lists_every_in_house_product(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.retail.inhouse-product-sale', ['category_id' => 'all']))
            ->assertOk()
            ->assertViewHas('selectedCategoryId', null)
            ->assertViewHas('products', fn (LengthAwarePaginator $products): bool => $products->total() === Product::query()
                ->where(['business_module' => BusinessModule::RETAIL, 'added_by' => 'admin'])
                ->count());
    }

    public function test_non_numeric_category_is_rejected(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.wholesale.inhouse-product-sale', ['category_id' => 'shoes']))
            ->assertRedirect()
            ->assertSessionHasErrors(['category_id']);
    }

    private function createInHouseProduct(string $businessModule): Product
    {
        $product = $this->requireFixture(Product::query()->withoutGlobalScopes()->orderBy('id')->first(), 'product')->replicate();

        $product->forceFill([
            'business_module' => $businessModule,
            'added_by' => 'admin',
            'user_id' => 1,
            'category_id' => (string) self::REPORT_CATEGORY_ID,
            'name' => 'Inhouse sale report '.Str::lower(Str::random(8)),
            'slug' => 'inhouse-sale-report-'.Str::lower(Str::random(16)),
            'code' => (string) random_int(10000000, 99999999),
        ])->save();

        return $product;
    }

    private function createOrderLine(Product $product, int $customerId, string $deliveryStatus): void
    {
        $order = $this->requireFixture(Order::query()->orderBy('id')->first(), 'order')->replicate();

        $order->forceFill([
            'id' => $this->nextOrderId(),
            'business_module' => $product->business_module,
            'customer_id' => (string) $customerId,
            'is_guest' => 0,
            'seller_is' => 'admin',
            'seller_id' => 1,
            'order_status' => $deliveryStatus,
            'order_group_id' => 'inhouse-sale-'.Str::lower(Str::random(16)),
        ])->save();

        $orderDetail = $this->requireFixture(OrderDetail::query()->orderBy('id')->first(), 'order detail')->replicate();

        $orderDetail->forceFill([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => 1,
            'qty' => self::SOLD_QUANTITY,
            'price' => self::UNIT_PRICE,
            'discount' => self::LINE_DISCOUNT,
            'delivery_status' => $deliveryStatus,
        ])->save();
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
