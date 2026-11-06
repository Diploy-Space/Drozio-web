<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductBarcodeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const PANELS = ['admin-views', 'vendor-views'];

    private ProductWholesaleService $service;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->service = app(ProductWholesaleService::class);
    }

    public function test_both_layouts_have_a_label_partial_in_both_panels(): void
    {
        foreach (self::PANELS as $panel) {
            foreach ([ProductWholesaleService::BARCODE_LAYOUT_DEFAULT, ProductWholesaleService::BARCODE_LAYOUT_COMPACT] as $layout) {
                $this->assertTrue(
                    View::exists('wholesale::'.$panel.'.products.partials._barcode-label-'.$layout),
                    $panel.' is missing the '.$layout.' wholesale barcode label',
                );
            }
        }
    }

    public function test_the_default_label_keeps_the_shop_name_price_and_printed_code(): void
    {
        foreach (self::PANELS as $panel) {
            $label = $this->renderLabel($panel, ProductWholesaleService::BARCODE_LAYOUT_DEFAULT);

            $this->assertStringContainsString('style24', $label);
            $this->assertStringContainsString('barcode_site', $label);
            $this->assertStringContainsString('barcode_price', $label);
            $this->assertStringContainsString('barcode_code', $label);
        }
    }

    public function test_the_compact_label_renders_bars_and_code_only(): void
    {
        foreach (self::PANELS as $panel) {
            $label = $this->renderLabel($panel, ProductWholesaleService::BARCODE_LAYOUT_COMPACT);

            $this->assertStringContainsString('style-compact', $label);
            $this->assertStringNotContainsString('style24', $label);
            $this->assertStringNotContainsString('barcode_site', $label);
            $this->assertStringNotContainsString('barcode_price', $label);
            $this->assertStringNotContainsString('barcode_code', $label);
            $this->assertStringContainsString('WHOLESALEBAR1', $label);
        }
    }

    public function test_an_unknown_layout_falls_back_to_the_default_instead_of_naming_a_view(): void
    {
        foreach (['../../../admin-views.products.list', 'compact ', 'COMPACT', '', null] as $submitted) {
            $this->assertSame(
                ProductWholesaleService::BARCODE_LAYOUT_DEFAULT,
                $this->service->getBarcodeSheet(quantity: 4, layout: $submitted)['layout'],
            );
        }

        $this->assertSame(
            ProductWholesaleService::BARCODE_LAYOUT_COMPACT,
            $this->service->getBarcodeSheet(quantity: 4, layout: 'compact')['layout'],
        );
    }

    public function test_each_layout_fills_its_own_number_of_labels_per_printed_page(): void
    {
        $this->assertCount(1, $this->service->getBarcodeSheet(quantity: 21)['pages']);
        $this->assertCount(2, $this->service->getBarcodeSheet(quantity: 22)['pages']);

        $compact = ProductWholesaleService::BARCODE_LAYOUT_COMPACT;

        $this->assertCount(1, $this->service->getBarcodeSheet(quantity: 1, layout: $compact)['pages']);
        $this->assertCount(2, $this->service->getBarcodeSheet(quantity: 2, layout: $compact)['pages']);
        $this->assertCount(30, $this->service->getBarcodeSheet(quantity: 30, layout: $compact)['pages']);
    }

    public function test_the_sheet_stays_between_one_label_and_the_maximum_quantity(): void
    {
        $this->assertSame(1, $this->labelCount($this->service->getBarcodeSheet(quantity: 0)));
        $this->assertSame(1, $this->labelCount($this->service->getBarcodeSheet(quantity: -5)));

        $this->assertSame(
            ProductWholesaleService::BARCODE_MAX_QUANTITY,
            $this->labelCount($this->service->getBarcodeSheet(
                quantity: 5000,
                layout: ProductWholesaleService::BARCODE_LAYOUT_COMPACT,
            )),
        );
    }

    public function test_every_layout_stylesheet_is_published_into_the_module_assets(): void
    {
        foreach (['barcode.css', 'barcode-a4.css', 'barcode-sticker.css'] as $stylesheet) {
            $this->assertFileExists(public_path('Modules/Wholesale/module-assets/css/'.$stylesheet));
        }
    }

    private function renderLabel(string $panel, string $layout): string
    {
        return View::make('wholesale::'.$panel.'.products.partials._barcode-label-'.$layout, [
            'product' => (new ProductWholesale)->forceFill(['name' => 'Wholesale barcode product', 'code' => 'WHOLESALEBAR1']),
            'shopName' => 'Wholesale shop',
            'unitPrice' => '$200.00',
        ])->render();
    }

    private function labelCount(array $sheet): int
    {
        return array_sum(array_map('count', $sheet['pages']));
    }
}
