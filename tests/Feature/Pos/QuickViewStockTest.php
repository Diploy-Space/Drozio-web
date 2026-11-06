<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Seller;
use App\Services\New\Pos\PosCartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\TaxModule\app\Models\Tax;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class QuickViewStockTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_product_without_variation_rows_reports_its_own_stock(): void
    {
        $product = $this->colourProduct('POSSTOCK1');

        DB::table('products_retail')->where('product_id', $product->id)->update(['variation' => '[]']);
        DB::table('products')->where('id', $product->id)->update(['current_stock' => 200]);

        $data = $this->variantData($product->fresh(['retailDetail']), $this->firstColourCode());

        $this->assertSame(200, (int) $data['quantity']);
        $this->assertFalse($data['allVariationsOutOfStock']);
    }

    public function test_a_selected_variation_reports_that_variation_stock(): void
    {
        $product = $this->colourProduct('POSSTOCK2');

        $variations = json_decode($product->retailDetail->variation, true);
        $this->assertNotEmpty($variations, 'the colour product stored no variation rows');

        $variations[0]['qty'] = 7;
        DB::table('products_retail')->where('product_id', $product->id)->update(['variation' => json_encode($variations)]);

        $data = $this->variantData($product->fresh(['retailDetail']), $this->firstColourCode());

        $this->assertSame(7, (int) $data['quantity']);
        $this->assertFalse($data['allVariationsOutOfStock']);
    }

    public function test_a_product_whose_every_variation_is_empty_reports_no_stock(): void
    {
        $product = $this->colourProduct('POSSTOCK3');

        $variations = collect(json_decode($product->retailDetail->variation, true))
            ->map(function (array $variation): array {
                $variation['qty'] = 0;

                return $variation;
            })->all();

        DB::table('products_retail')->where('product_id', $product->id)->update(['variation' => json_encode($variations)]);

        $data = $this->variantData($product->fresh(['retailDetail']), $this->firstColourCode());

        $this->assertSame(0, (int) $data['quantity']);
        $this->assertTrue($data['allVariationsOutOfStock']);
    }

    public function test_adding_a_product_without_variation_rows_checks_its_own_stock(): void
    {
        $product = $this->colourProduct('POSSTOCK4');

        DB::table('products_retail')->where('product_id', $product->id)->update(['variation' => '[]']);
        DB::table('products')->where('id', $product->id)->update(['current_stock' => 1200]);

        $remaining = app(PosCartService::class)->getRemainingStock(
            variant: Color::query()->orderBy('id')->value('name'),
            variation: [],
            productQty: 1200,
            quantity: 3,
        );

        $this->assertSame(1197, $remaining);
    }

    public function test_adding_a_selected_variation_checks_that_variation_stock(): void
    {
        $variant = Color::query()->orderBy('id')->value('name');

        $remaining = app(PosCartService::class)->getRemainingStock(
            variant: $variant,
            variation: json_decode(json_encode([['type' => $variant, 'price' => 300, 'qty' => 2]])),
            productQty: 1200,
            quantity: 3,
        );

        $this->assertSame(-1, $remaining);
    }

    public function test_a_variation_price_falls_back_to_the_product_price_without_variation_rows(): void
    {
        $price = app(PosCartService::class)->getVariationPrice(
            variation: [],
            variant: Color::query()->orderBy('id')->value('name'),
            productPrice: 300,
        );

        $this->assertSame(300.0, $price);
    }

    private function variantData(Product $product, string $colourCode): array
    {
        $colourName = Color::query()->where('code', $colourCode)->value('name');

        return app(PosCartService::class)->getVariantData(
            input: ['id' => $product->id, 'quantity' => 1, 'color' => $colourCode],
            product: $product,
            colorName: $colourName,
        );
    }

    private function firstColourCode(): string
    {
        return Color::query()->orderBy('id')->value('code');
    }

    private function colourProduct(string $code): Product
    {
        $colour = Color::query()->orderBy('id')->firstOrFail();

        $payload = [
            'lang' => ['en'],
            'name' => ['Pos stock product '.$code],
            'description' => ['<p>Pos stock description</p>'],
            'code' => $code,
            'category_id' => Category::query()->where('position', 0)->value('id'),
            'unit' => 'pc',
            'product_type' => 'physical',
            'unit_price' => 300,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 0,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'colors_active' => 'on',
            'colors' => [$colour->code],
            'tax_ids' => Tax::query()->where('is_active', 1)->limit(1)->pluck('id')->toArray(),
            'image' => UploadedFile::fake()->image('thumb.png'),
            'images' => [UploadedFile::fake()->image('a.png')],
            'color_image_'.ltrim($colour->code, '#') => UploadedFile::fake()->image('colour.png'),
            'price_'.str_replace([' ', '.'], ['', '_'], $colour->name) => 300,
            'sku_'.str_replace([' ', '.'], ['', '_'], $colour->name) => strtolower($code),
            'qty_'.str_replace([' ', '.'], ['', '_'], $colour->name) => 5,
        ];

        $this->actingAs($this->seller(), 'seller')
            ->post('vendor/retail/products/add', $payload)
            ->assertRedirect();

        return Product::query()->with('retailDetail')->where('code', $code)->firstOrFail();
    }

    private function seller(): Seller
    {
        return Seller::query()->where('status', 'approved')->firstOrFail();
    }
}
