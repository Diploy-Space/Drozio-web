<?php

namespace Tests\Feature\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\Seller;
use App\Services\New\ColorService;
use App\Services\New\ProductRetailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailProductBarcodeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const LABEL_SHOP_NAME = 'Barcode label shop';

    private ProductRetailService $service;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->service = app(ProductRetailService::class);
    }

    public function test_the_default_label_keeps_the_shop_name_price_and_printed_code(): void
    {
        $product = $this->createProduct('BARDEF1');

        foreach (['admin-views', 'vendor-views'] as $panel) {
            $label = $this->renderLabel($panel, ProductRetailService::BARCODE_LAYOUT_DEFAULT, $product);

            $this->assertStringContainsString('style24', $label);
            $this->assertStringContainsString('barcode_site', $label);
            $this->assertStringContainsString('barcode_price', $label);
            $this->assertStringContainsString('barcode_code', $label);
        }

        $this->assertStringContainsString(self::LABEL_SHOP_NAME, $this->renderLabel('admin-views', ProductRetailService::BARCODE_LAYOUT_DEFAULT, $product));
    }

    public function test_the_compact_label_renders_bars_and_code_only(): void
    {
        $product = $this->createProduct('BARCOMP1');

        foreach (['admin-views', 'vendor-views'] as $panel) {
            $label = $this->renderLabel($panel, ProductRetailService::BARCODE_LAYOUT_COMPACT, $product);

            $this->assertStringContainsString('style-compact', $label);
            $this->assertStringNotContainsString('style24', $label);
            $this->assertStringNotContainsString('barcode_site', $label);
            $this->assertStringNotContainsString('barcode_price', $label);
            $this->assertStringNotContainsString('barcode_code', $label);
            $this->assertStringContainsString($product->code, $label);
        }
    }

    public function test_both_layouts_have_a_label_partial_in_both_panels(): void
    {
        foreach (['admin-views', 'vendor-views'] as $panel) {
            foreach ([ProductRetailService::BARCODE_LAYOUT_DEFAULT, ProductRetailService::BARCODE_LAYOUT_COMPACT] as $layout) {
                $this->assertTrue(
                    View::exists($panel.'.product.partials._barcode-label-'.$layout),
                    $panel.' is missing the '.$layout.' barcode label',
                );
            }
        }
    }

    public function test_an_unknown_layout_falls_back_to_the_default_instead_of_naming_a_view(): void
    {
        foreach (['../../../admin-views.product.list', 'compact ', 'COMPACT', '', null] as $submitted) {
            $this->assertSame(
                ProductRetailService::BARCODE_LAYOUT_DEFAULT,
                $this->service->getBarcodeSheet(quantity: 4, layout: $submitted)['layout'],
            );
        }

        $this->assertSame(
            ProductRetailService::BARCODE_LAYOUT_COMPACT,
            $this->service->getBarcodeSheet(quantity: 4, layout: 'compact')['layout'],
        );
    }

    public function test_each_layout_fills_its_own_number_of_labels_per_printed_page(): void
    {
        $this->assertCount(1, $this->service->getBarcodeSheet(quantity: 21)['pages']);
        $this->assertCount(2, $this->service->getBarcodeSheet(quantity: 22)['pages']);

        $compact = ProductRetailService::BARCODE_LAYOUT_COMPACT;

        $this->assertCount(1, $this->service->getBarcodeSheet(quantity: 1, layout: $compact)['pages']);
        $this->assertCount(2, $this->service->getBarcodeSheet(quantity: 2, layout: $compact)['pages']);
        $this->assertCount(30, $this->service->getBarcodeSheet(quantity: 30, layout: $compact)['pages']);
    }

    public function test_the_sheet_stays_between_one_label_and_the_maximum_quantity(): void
    {
        $this->assertSame(1, $this->labelCount($this->service->getBarcodeSheet(quantity: 0)));
        $this->assertSame(1, $this->labelCount($this->service->getBarcodeSheet(quantity: -5)));

        $this->assertSame(
            ProductRetailService::BARCODE_MAX_QUANTITY,
            $this->labelCount($this->service->getBarcodeSheet(
                quantity: 5000,
                layout: ProductRetailService::BARCODE_LAYOUT_COMPACT,
            )),
        );
    }

    public function test_a_quantity_above_the_maximum_is_refused_before_rendering(): void
    {
        $product = $this->createProduct('BARLIMIT1');

        $this->from('admin/retail/products/list/all')
            ->actingAs($this->admin(), 'admin')
            ->get('admin/retail/products/barcode/'.$product->id.'?limit=271')
            ->assertRedirect('admin/retail/products/list/all');
    }

    public function test_every_layout_stylesheet_is_published_for_both_retail_panels(): void
    {
        foreach (['assets/backend/admin/css', 'assets/back-end/css'] as $directory) {
            foreach (['barcode.css', 'barcode-a4.css', 'barcode-sticker.css'] as $stylesheet) {
                $this->assertFileExists(public_path($directory.'/'.$stylesheet));
            }
        }
    }

    public function test_the_seller_api_prints_one_compact_sticker_per_page_when_asked(): void
    {
        $seller = $this->approvedSeller();
        $product = $this->createSellerProduct('BARAPICMP1', $seller);

        $this->getJson('api/v6/seller/products/'.$product->id.'/barcode?quantity=3&layout=compact', $this->sellerHeaders($seller))
            ->assertOk();

        $pdf = $this->generatedBarcodePdf();

        $this->assertSame(3, $this->pageCount($pdf));
        $this->assertMatchesRegularExpression('#/MediaBox \[0\.000 0\.000 111\.600 61\.200\]#', $pdf);
        $this->assertStringNotContainsString('Barcode product BARAPICMP1', $pdf);
    }

    public function test_the_seller_api_keeps_the_a4_sheet_without_a_layout_or_with_the_default_one(): void
    {
        $seller = $this->approvedSeller();
        $product = $this->createSellerProduct('BARAPIDEF1', $seller);

        foreach (['', '&layout=default'] as $layoutQuery) {
            $this->getJson('api/v6/seller/products/'.$product->id.'/barcode?quantity=3'.$layoutQuery, $this->sellerHeaders($seller))
                ->assertOk();

            $pdf = $this->generatedBarcodePdf();

            $this->assertSame(1, $this->pageCount($pdf));
            $this->assertMatchesRegularExpression('#/MediaBox \[0\.000 0\.000 595\.280 841\.890\]#', $pdf);
        }
    }

    public function test_the_seller_api_refuses_an_unknown_layout(): void
    {
        $seller = $this->approvedSeller();
        $product = $this->createSellerProduct('BARAPIBAD1', $seller);

        $this->getJson('api/v6/seller/products/'.$product->id.'/barcode?quantity=3&layout=sticker', $this->sellerHeaders($seller))
            ->assertStatus(422);
    }

    private function renderLabel(string $panel, string $layout, Product $product): string
    {
        return View::make($panel.'.product.partials._barcode-label-'.$layout, ['product' => $product, 'shopName' => self::LABEL_SHOP_NAME])->render();
    }

    private function labelCount(array $sheet): int
    {
        return array_sum(array_map('count', $sheet['pages']));
    }

    private function approvedSeller(): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'an approved seller',
        );

        $seller->forceFill(['auth_token' => Str::random(50)])->save();

        return $seller->fresh();
    }

    private function createSellerProduct(string $code, Seller $seller): Product
    {
        $product = $this->createProduct($code);

        $product->forceFill(['added_by' => ProductRetailService::ADDED_BY_SELLER, 'user_id' => $seller->id])->save();

        return $product;
    }

    private function generatedBarcodePdf(): string
    {
        return (string) file_get_contents(storage_path('app/public/product/barcode.pdf'));
    }

    private function pageCount(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    private function admin(): Admin
    {
        return Admin::query()->orderBy('id')->firstOrFail();
    }

    private function createProduct(string $code): Product
    {
        $this->service->create(
            data: [
                'lang' => ['en'],
                'name' => ['Barcode product '.$code],
                'description' => ['<p>Barcode description '.$code.'</p>'],
                'code' => $code,
                'category_id' => (int) DB::table('categories')->where('position', 0)->value('id'),
                'sub_category_id' => null,
                'sub_sub_category_id' => null,
                'brand_id' => null,
                'unit' => 'pc',
                'product_type' => 'physical',
                'unit_price' => 200,
                'discount' => 0,
                'discount_type' => 'flat',
                'current_stock' => 10,
                'minimum_order_qty' => 1,
                'shipping_cost' => 0,
                'video_url' => null,
                'meta_title' => 'Barcode product '.$code,
                'meta_description' => 'Barcode description '.$code,
                'choice_attributes' => [],
            ],
            files: [],
            addedBy: ProductRetailService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        return Product::query()->where('code', $code)->firstOrFail();
    }
}
