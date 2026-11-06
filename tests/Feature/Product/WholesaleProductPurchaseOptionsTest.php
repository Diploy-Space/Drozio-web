<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\Product\AttributeImageService;
use Modules\Wholesale\app\Services\Product\FaqService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductPurchaseOptionsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const COLOR_NAMES = ['#FF0000' => 'Red', '#000080' => 'Navy'];

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

    public function test_combination_highlights_list_value_and_tier_ranges(): void
    {
        $attributeId = $this->attributeId();
        $axisKey = 'choice_'.$attributeId;
        $product = $this->createProduct([
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
            'qty_S' => 100,
            'qty_M' => 100,
            'variation_based_price' => 1,
            'variation_type' => (string) $attributeId,
            'quantity_based_price' => 1,
            'tier_min_qty' => [1, 10],
            'tier_max_qty' => ['9', ''],
            'variation_tier_price' => ['S' => [100, 90], 'M' => [110, 95]],
            'variation_tier_discount' => ['S' => [0 => 5], 'M' => [1 => 10]],
        ]);

        $options = $this->service()->getPurchaseOptions($product, self::COLOR_NAMES);

        $this->assertSame(ProductWholesaleService::PRICING_VARIATION_QUANTITY, $options['pricing_mode']);
        $this->assertSame($axisKey, $options['price_axis']);
        $this->assertSame([
            'kind' => ProductWholesaleService::HIGHLIGHT_COMBINATION,
            'axis_key' => $axisKey,
            'items' => [
                ['value' => 'S', 'variation_value' => 'S', 'min_qty' => 1, 'max_qty' => 9, 'discount' => 5.0],
                ['value' => 'M', 'variation_value' => 'M', 'min_qty' => 10, 'max_qty' => null, 'discount' => 10.0],
            ],
        ], $options['discount_highlights']);

        $axis = $options['axes'][0];

        $this->assertSame($axisKey, $axis['key']);
        $this->assertSame('Size', $axis['title']);
        $this->assertTrue($axis['is_price_axis']);
        $this->assertSame([true, true], array_column($axis['options'], 'has_discount'));
        $this->assertSame(85.5, $options['price_rows'][1]['tiers'][1]['discounted_unit_price']);
        $this->assertSame(1, $options['minimum_order_qty']);
    }

    public function test_variation_highlights_list_discounted_values_and_flag_options(): void
    {
        $product = $this->createProduct([
            ...$this->colors(['Red' => 20, 'Navy' => 20]),
            'variation_based_price' => 1,
            'variation_type' => ProductWholesaleService::AXIS_COLOR,
            'variation_price' => ['Red' => 100, 'Navy' => 120],
            'variation_discount' => ['Navy' => 15],
        ]);

        $options = $this->service()->getPurchaseOptions($product, self::COLOR_NAMES);

        $this->assertSame(ProductWholesaleService::PRICING_VARIATION, $options['pricing_mode']);
        $this->assertSame([
            'kind' => ProductWholesaleService::HIGHLIGHT_VARIATION,
            'axis_key' => ProductWholesaleService::AXIS_COLOR,
            'items' => [
                ['value' => '#000080', 'variation_value' => 'Navy', 'min_qty' => null, 'max_qty' => null, 'discount' => 15.0],
            ],
        ], $options['discount_highlights']);
        $this->assertSame([
            ['value' => '#FF0000', 'label' => 'Red', 'has_discount' => false],
            ['value' => '#000080', 'label' => 'Navy', 'has_discount' => true],
        ], $options['axes'][0]['options']);
        $this->assertSame('color', $options['axes'][0]['type']);
        $this->assertNull($options['axes'][0]['title']);
        $this->assertSame(['Red' => 20, 'Navy' => 20], $options['stock']);

        $plain = $this->createProduct(['quantity_based_price' => 1, 'tier_min_qty' => [1], 'tier_max_qty' => [''], 'tier_unit_price' => [10]]);
        $plainOptions = $this->service()->getPurchaseOptions($plain);

        $this->assertSame(['kind' => null, 'axis_key' => null, 'items' => []], $plainOptions['discount_highlights']);
        $this->assertSame(ProductWholesaleService::PRICING_QUANTITY, $plainOptions['pricing_mode']);
        $this->assertNull($plainOptions['price_axis']);
        $this->assertSame(['' => 100], $plainOptions['stock']);
        $this->assertSame([], $plainOptions['axes']);
    }

    public function test_images_are_grouped_by_axis_and_value(): void
    {
        $attributeId = $this->attributeId();
        $axisKey = 'choice_'.$attributeId;
        $product = $this->createProduct([
            ...$this->colors(['Red-S' => 5, 'Red-M' => 5, 'Navy-S' => 5, 'Navy-M' => 5]),
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
        ]);

        Storage::fake('public');

        $prefix = 'wsopt-'.$product->id.'-';

        foreach (['navy-front.webp', 'small-front.webp', 'small-back.webp', 'medium.webp'] as $imageName) {
            Storage::disk('public')->put(AttributeImageService::IMAGE_DIRECTORY.$prefix.$imageName, 'image');
        }

        $attributeImageService = app(AttributeImageService::class);
        $attributeImageService->replaceProductImageSets($product->id, [
            ['type' => AttributeImageService::TYPE_COLOR, 'attribute_id' => null, 'value' => 'Navy', 'existing' => [$prefix.'navy-front.webp']],
            ['type' => AttributeImageService::TYPE_ATTRIBUTE, 'attribute_id' => $attributeId, 'value' => 'S', 'existing' => [$prefix.'small-front.webp', $prefix.'small-back.webp']],
            ['type' => AttributeImageService::TYPE_ATTRIBUTE, 'attribute_id' => $attributeId, 'value' => 'M', 'existing' => [$prefix.'missing-file.webp']],
        ], []);

        $images = $this->service()->getPurchaseOptions(
            $product,
            self::COLOR_NAMES,
            $attributeImageService->getForProduct($product->id),
        )['images'];

        $this->assertSame([ProductWholesaleService::AXIS_COLOR, $axisKey], array_keys($images));
        $this->assertSame(['#000080'], array_keys($images[ProductWholesaleService::AXIS_COLOR]));
        $this->assertStringEndsWith('navy-front.webp', $images[ProductWholesaleService::AXIS_COLOR]['#000080'][0]);
        $this->assertSame(['S'], array_keys($images[$axisKey]));
        $this->assertCount(2, $images[$axisKey]['S']);
        $this->assertStringEndsWith('small-back.webp', $images[$axisKey]['S'][1]);

        $attributeImageService->replaceProductImageSets($product->id, [
            ['type' => AttributeImageService::TYPE_ATTRIBUTE, 'attribute_id' => $attributeId, 'value' => 'M', 'existing' => [$prefix.'medium.webp']],
        ], []);

        $singleAxis = $this->service()->getPurchaseOptions($product, self::COLOR_NAMES, $attributeImageService->getForProduct($product->id));

        $this->assertSame([$axisKey], array_keys($singleAxis['images']));
        $this->assertSame([], $this->service()->getPurchaseOptions($product, self::COLOR_NAMES)['images']);
    }

    public function test_default_selection_prefers_the_first_in_stock_combination(): void
    {
        $attributeId = $this->attributeId();
        $axisKey = 'choice_'.$attributeId;
        $product = $this->createProduct([
            ...$this->colors(['Red-S' => 0, 'Red-M' => 0, 'Navy-S' => 7, 'Navy-M' => 3]),
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
            'quantity_based_price' => 1,
            'tier_min_qty' => [1],
            'tier_max_qty' => [''],
            'tier_unit_price' => [10],
        ]);

        $selection = $this->service()->getDefaultSelection($product, self::COLOR_NAMES);

        $this->assertSame(['color' => '#000080', $axisKey => 'S'], $selection);
        $this->assertSame('Navy-S', $this->service()->getVariantKey($product, $selection, self::COLOR_NAMES));
        $this->assertSame($selection, $this->service()->getPurchaseOptions($product, self::COLOR_NAMES)['default_selection']);

        $soldOut = $this->createProduct([
            ...$this->colors(['Red-S' => 0, 'Red-M' => 0, 'Navy-S' => 0, 'Navy-M' => 0]),
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
        ]);

        $this->assertSame(['color' => '#FF0000', $axisKey => 'S'], $this->service()->getDefaultSelection($soldOut, self::COLOR_NAMES));

        $simple = $this->createProduct(['quantity_based_price' => 1, 'tier_min_qty' => [1], 'tier_max_qty' => [''], 'tier_unit_price' => [10]]);

        $this->assertSame([], $this->service()->getDefaultSelection($simple));
        $this->assertSame('', $this->service()->getVariantKey($simple, []));
    }

    public function test_storefront_faq_list_excludes_inactive(): void
    {
        $product = $this->createProduct(['quantity_based_price' => 1, 'tier_min_qty' => [1], 'tier_max_qty' => [''], 'tier_unit_price' => [10]]);
        $faqService = app(FaqService::class);

        $faqService->replaceProductFaqs($product->id, [
            ['question' => 'Active second', 'answer' => 'Shown', 'priority' => 2, 'status' => 1],
            ['question' => 'Inactive first', 'answer' => 'Hidden', 'priority' => 1, 'status' => 0],
            ['question' => 'Active first', 'answer' => 'Shown', 'priority' => 1, 'status' => 1],
        ]);

        $this->assertSame(
            ['Active first', 'Active second'],
            $faqService->getForProduct($product->id, onlyActive: true)->pluck('question')->all(),
        );
        $this->assertCount(3, $faqService->getForProduct($product->id));
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function createProduct(array $overrides): Model
    {
        $product = $this->service()->create(
            data: [...$this->basePayload(), ...$overrides],
            colorNames: self::COLOR_NAMES,
        );

        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax']);
    }

    private function basePayload(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale purchase options fixture'],
            'description' => ['Wholesale purchase options fixture'],
            'code' => 'WSOPT'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'shipping_cost' => 0,
            'minimum_order_qty' => 1,
            'current_stock' => 100,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
        ];
    }

    private function colors(array $stockByVariant): array
    {
        $fields = ['colors_active' => 'on', 'colors' => array_keys(self::COLOR_NAMES)];

        foreach ($stockByVariant as $variant => $quantity) {
            $fields['qty_'.$variant] = $quantity;
        }

        return $fields;
    }

    private function attribute(int $attributeId, string $title, array $values): array
    {
        return [
            'choice_attributes' => [$attributeId],
            'choice_no' => [$attributeId],
            'choice' => [$title],
            'choice_options_'.$attributeId => [implode(',', $values)],
        ];
    }

    private function attributeId(): int
    {
        return (int) $this->requireFixture(app(AttributeService::class)->getIds()[0] ?? null, 'wholesale attribute');
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }
}
