<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Utils\CartManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Modules\TaxModule\app\Models\SystemTaxSetup;
use Modules\TaxModule\app\Models\Tax;
use Modules\TaxModule\app\Services\New\SystemTaxSetupService;
use Modules\TaxModule\app\Services\SystemTaxSetupService as TaxSetupCache;
use Modules\TaxModule\app\Traits\VatTaxManagement;
use Modules\Wholesale\app\Http\Requests\Shared\Product\PriceQuoteRequest;
use Modules\Wholesale\app\Services\AttributeService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductPriceQuoteTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;
    use VatTaxManagement;

    private const COLOR_NAMES = ['#FF0000' => 'Red', '#000080' => 'Navy'];

    private SystemTaxSetup $taxSetup;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
        $this->taxSetup = $this->activeWholesaleTaxSetup();
        $this->configureTax(isIncluded: false);
    }

    protected function tearDown(): void
    {
        TaxSetupCache::clearTaxSystemTypeCache();
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_quantity_tiers_pick_the_tier_containing_the_quantity(): void
    {
        $product = $this->createProduct([
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            ...$this->quantityTiers([[10, 49, 50], [50, 99, 40], [100, null, 30]]),
        ]);

        foreach ([10 => [0, 50.0], 49 => [0, 50.0], 50 => [1, 40.0], 99 => [1, 40.0], 100 => [2, 30.0]] as $quantity => [$tierIndex, $unitPrice]) {
            $quote = $this->service()->getPriceQuote($product, [], $quantity);

            $this->assertSame($tierIndex, $quote['tier_index'], 'quantity '.$quantity);
            $this->assertSame($unitPrice, $quote['unit_price'], 'quantity '.$quantity);
            $this->assertSame($unitPrice * $quantity, $quote['subtotal']);
            $this->assertNull($quote['reason']);
            $this->assertSame('', $quote['variant_key']);
            $this->assertNull($quote['variation_value']);
        }
    }

    public function test_quantity_below_the_first_tier_is_priced_at_it_and_flagged(): void
    {
        $product = $this->createProduct([
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            ...$this->quantityTiers([[50, 99, 40], [100, null, 30]]),
        ]);

        $quote = $this->service()->getPriceQuote($product, [], 30);

        $this->assertSame(0, $quote['tier_index']);
        $this->assertSame(40.0, $quote['unit_price']);
        $this->assertSame(50, $quote['minimum_quantity']);
        $this->assertSame(ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER, $quote['reason']);

        $raisedMinimum = $this->createProduct([
            'minimum_order_qty' => 60,
            'current_stock' => 1000,
            ...$this->quantityTiers([[50, 99, 40], [100, null, 30]]),
        ]);

        $quote = $this->service()->getPriceQuote($raisedMinimum, [], 55);

        $this->assertSame(0, $quote['tier_index']);
        $this->assertSame(60, $quote['minimum_quantity']);
        $this->assertSame(ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER, $quote['reason']);
    }

    public function test_quantity_above_a_bounded_last_tier_is_flagged(): void
    {
        $product = $this->createProduct([
            'current_stock' => 1000,
            ...$this->quantityTiers([[10, 49, 50], [50, 99, 40]]),
        ]);

        $quote = $this->service()->getPriceQuote($product, [], 150);

        $this->assertSame(1, $quote['tier_index']);
        $this->assertSame(40.0, $quote['unit_price']);
        $this->assertSame(99, $quote['maximum_quantity']);
        $this->assertSame(ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY, $quote['reason']);
    }

    public function test_open_ended_last_tier_accepts_any_larger_quantity(): void
    {
        $product = $this->createProduct([
            'current_stock' => 10000,
            ...$this->quantityTiers([[10, 49, 50], [50, null, 40]]),
        ]);

        $quote = $this->service()->getPriceQuote($product, [], 5000);

        $this->assertSame(1, $quote['tier_index']);
        $this->assertNull($quote['maximum_quantity']);
        $this->assertNull($quote['reason']);
        $this->assertSame(200000.0, $quote['total']);
    }

    public function test_variation_price_uses_the_selected_price_axis_row(): void
    {
        $product = $this->createProduct([
            ...$this->colors(['Red' => 50, 'Navy' => 50]),
            'variation_based_price' => 1,
            'variation_type' => ProductWholesaleService::AXIS_COLOR,
            'variation_price' => ['Red' => 120, 'Navy' => 140],
        ]);

        $quote = $this->service()->getPriceQuote($product, ['color' => '#000080'], 2, self::COLOR_NAMES);

        $this->assertSame('Navy', $quote['variant_key']);
        $this->assertSame('Navy', $quote['variation_value']);
        $this->assertNull($quote['tier_index']);
        $this->assertNull($quote['maximum_quantity']);
        $this->assertSame(140.0, $quote['unit_price']);
        $this->assertSame(280.0, $quote['subtotal']);
        $this->assertSame(280.0, $quote['total']);
        $this->assertSame(50, $quote['available_stock']);
        $this->assertNull($quote['reason']);
    }

    public function test_variation_tier_discount_is_a_percentage_of_the_tier_price(): void
    {
        $attributeId = $this->attributeIds(1)[0];
        $product = $this->createProduct([
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
            'qty_S' => 100,
            'qty_M' => 100,
            'variation_based_price' => 1,
            'variation_type' => (string) $attributeId,
            ...$this->quantityTiers([[1, 9, null], [10, null, null]]),
            'variation_tier_price' => ['S' => [100, 90], 'M' => [110, 95]],
            'variation_tier_discount' => ['M' => [1 => 10]],
        ]);

        $quote = $this->service()->getPriceQuote($product, ['choice_'.$attributeId => 'M'], 20);

        $this->assertSame('M', $quote['variant_key']);
        $this->assertSame(1, $quote['tier_index']);
        $this->assertSame(95.0, $quote['unit_price']);
        $this->assertSame(9.5, $quote['discount_per_unit']);
        $this->assertSame(85.5, $quote['discounted_unit_price']);
        $this->assertSame(1900.0, $quote['subtotal']);
        $this->assertSame(190.0, $quote['discount_amount']);
        $this->assertSame(1710.0, $quote['total']);

        $undiscounted = $this->service()->getPriceQuote($product, ['choice_'.$attributeId => 'S'], 5);

        $this->assertSame(0, $undiscounted['tier_index']);
        $this->assertSame(0.0, $undiscounted['discount_amount']);
        $this->assertSame(500.0, $undiscounted['total']);
    }

    public function test_card_range_of_a_variation_discounted_product_is_up_to_its_highest_discount(): void
    {
        $attributeId = $this->attributeIds(1)[0];
        $product = $this->createProduct([
            ...$this->attribute($attributeId, 'Size', ['S', 'M']),
            'qty_S' => 100,
            'qty_M' => 100,
            'variation_based_price' => 1,
            'variation_type' => (string) $attributeId,
            ...$this->quantityTiers([[1, 9, null], [10, null, null]]),
            'variation_tier_price' => ['S' => [100, 90], 'M' => [110, 95]],
            'variation_tier_discount' => ['S' => [0 => 5], 'M' => [1 => 20]],
        ]);

        $range = $this->service()->getPriceRanges([$product])[$product->id];

        $this->assertSame(90.0, $range['min']);
        $this->assertSame(110.0, $range['max']);
        $this->assertSame(76.0, $range['discounted_min']);
        $this->assertSame(110.0, $range['discounted_max']);
        $this->assertTrue($range['has_discount']);
        $this->assertTrue($range['is_up_to']);
        $this->assertSame(20.0, $range['discount_percent']);

        $quote = $this->service()->getPriceQuote($product, ['choice_'.$attributeId => 'M'], 20);

        $this->assertSame($range['discounted_min'], $quote['discounted_unit_price']);
    }

    public function test_card_range_keeps_flat_discount_and_marks_undiscounted_products(): void
    {
        $tiers = $this->quantityTiers([[1, 9, 100], [10, null, 80]]);

        $flat = $this->service()->getPriceRanges([$flatProduct = $this->createProduct([...$tiers, 'discount' => 15, 'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT])])[$flatProduct->id];
        $plain = $this->service()->getPriceRanges([$plainProduct = $this->createProduct($tiers)])[$plainProduct->id];

        $this->assertTrue($flat['has_discount']);
        $this->assertFalse($flat['is_up_to']);
        $this->assertSame(65.0, $flat['discounted_min']);
        $this->assertSame(85.0, $flat['discounted_max']);

        $this->assertFalse($plain['has_discount']);
        $this->assertFalse($plain['is_up_to']);
        $this->assertSame($plain['min'], $plain['discounted_min']);
        $this->assertSame($plain['max'], $plain['discounted_max']);
    }

    public function test_product_level_flat_and_percent_discount_apply_to_quantity_pricing(): void
    {
        $tiers = $this->quantityTiers([[1, null, 100]]);

        $percent = $this->createProduct([...$tiers, 'discount' => 10, 'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT]);
        $flat = $this->createProduct([...$tiers, 'discount' => 15, 'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT]);

        $percentQuote = $this->service()->getPriceQuote($percent, [], 3);
        $flatQuote = $this->service()->getPriceQuote($flat, [], 3);

        $this->assertSame(10.0, $percentQuote['discount_per_unit']);
        $this->assertSame(300.0, $percentQuote['subtotal']);
        $this->assertSame(30.0, $percentQuote['discount_amount']);
        $this->assertSame(270.0, $percentQuote['total']);

        $this->assertSame(15.0, $flatQuote['discount_per_unit']);
        $this->assertSame(45.0, $flatQuote['discount_amount']);
        $this->assertSame(255.0, $flatQuote['total']);
        $this->assertEqualsWithDelta(
            (float) getProductPriceByType(product: $flat, type: 'discounted_amount', result: 'value', price: 100),
            $flatQuote['discount_per_unit'],
            0.0001,
        );
    }

    public function test_excluded_tax_is_added_and_included_tax_is_zero(): void
    {
        $tax = $this->requireFixture(
            Tax::query()->where('is_active', 1)->where('business_module', BusinessModule::WHOLESALE)->where('tax_rate', '>', 0)->first(),
            'active wholesale vat and tax rate',
        );
        $created = $this->createProduct([
            ...$this->quantityTiers([[1, null, 100]]),
            'discount' => 10,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);

        $this->getAddTaxData(
            taxableType: $created::class,
            taxableId: $created->id,
            taxIds: [$tax->id],
            businessModule: BusinessModule::WHOLESALE,
        );

        $product = $this->reload($created);
        $excluded = $this->service()->getPriceQuote($product, [], 4);
        $expectedTax = CartManager::getAppliedTaxAmount(
            $product,
            getTaxModuleSystemTypesConfig(businessModule: BusinessModule::WHOLESALE),
            400.0,
            40.0,
        );

        $this->assertGreaterThan(0, $expectedTax);
        $this->assertEqualsWithDelta(360.0 * (float) $tax->tax_rate / 100, $expectedTax, 0.0001);
        $this->assertEqualsWithDelta($expectedTax, $excluded['tax_amount'], 0.0001);
        $this->assertEqualsWithDelta(360.0 + $expectedTax, $excluded['total'], 0.0001);
        $this->assertSame(ProductWholesaleService::TAX_MODEL_EXCLUDE, $excluded['tax_model']);

        $this->configureTax(isIncluded: true);

        $included = $this->service()->getPriceQuote($product, [], 4);

        $this->assertSame(0.0, $included['tax_amount']);
        $this->assertSame(360.0, $included['total']);
        $this->assertSame(ProductWholesaleService::TAX_MODEL_INCLUDE, $included['tax_model']);
    }

    public function test_stock_is_read_from_the_full_combination(): void
    {
        [$sizeId, $fitId] = $this->attributeIds(2);
        $product = $this->createProduct([
            ...$this->colors([]),
            ...$this->attribute($sizeId, 'Size', ['S', 'M']),
            ...$this->attribute($fitId, 'Fit', ['A', 'B']),
            'choice_attributes' => [$sizeId, $fitId],
            'choice_no' => [$sizeId, $fitId],
            'choice' => ['Size', 'Fit'],
            ...$this->stockFor(['Red', 'Navy'], ['S', 'M'], ['A', 'B'], ['Red-S-A' => 5, 'Navy-M-B' => 0]),
            'variation_based_price' => 1,
            'variation_type' => (string) $sizeId,
            'variation_price' => ['S' => 10, 'M' => 12],
        ]);

        $sizeAxis = 'choice_'.$sizeId;
        $fitAxis = 'choice_'.$fitId;

        $exceeding = $this->service()->getPriceQuote($product, ['color' => '#FF0000', $sizeAxis => 'S', $fitAxis => 'A'], 6, self::COLOR_NAMES);

        $this->assertSame('Red-S-A', $exceeding['variant_key']);
        $this->assertSame('S', $exceeding['variation_value']);
        $this->assertSame(5, $exceeding['available_stock']);
        $this->assertSame(ProductWholesaleService::REASON_EXCEEDS_STOCK, $exceeding['reason']);

        $empty = $this->service()->getPriceQuote($product, ['color' => '#000080', $sizeAxis => 'M', $fitAxis => 'B'], 1, self::COLOR_NAMES);

        $this->assertSame('Navy-M-B', $empty['variant_key']);
        $this->assertSame(12.0, $empty['unit_price']);
        $this->assertSame(ProductWholesaleService::REASON_OUT_OF_STOCK, $empty['reason']);

        $unknown = $this->service()->getPriceQuote($product, ['color' => '#FF0000', $sizeAxis => 'S', $fitAxis => 'Z'], 1, self::COLOR_NAMES);

        $this->assertSame('Red-S-Z', $unknown['variant_key']);
        $this->assertSame(0, $unknown['available_stock']);
        $this->assertSame(ProductWholesaleService::REASON_VARIATION_UNAVAILABLE, $unknown['reason']);

        $available = $this->service()->getPriceQuote($product, ['color' => '#000080', $sizeAxis => 'S', $fitAxis => 'B'], 50, self::COLOR_NAMES);

        $this->assertSame(50, $available['available_stock']);
        $this->assertNull($available['reason']);
    }

    public function test_reason_precedence(): void
    {
        $product = $this->createProduct([
            ...$this->colors(['Red' => 0, 'Navy' => 15]),
            ...$this->quantityTiers([[10, 19, 5]]),
            'minimum_order_qty' => 10,
        ]);

        $reasons = [
            [['color' => '#FFFFFF'], 1, ProductWholesaleService::REASON_VARIATION_UNAVAILABLE],
            [['color' => '#FF0000'], 1, ProductWholesaleService::REASON_OUT_OF_STOCK],
            [['color' => '#000080'], 5, ProductWholesaleService::REASON_BELOW_MINIMUM_ORDER],
            [['color' => '#000080'], 25, ProductWholesaleService::REASON_ABOVE_MAXIMUM_QUANTITY],
            [['color' => '#000080'], 16, ProductWholesaleService::REASON_EXCEEDS_STOCK],
            [['color' => '#000080'], 12, null],
        ];

        foreach ($reasons as [$selection, $quantity, $reason]) {
            $this->assertSame(
                $reason,
                $this->service()->getPriceQuote($product, $selection, $quantity, self::COLOR_NAMES)['reason'],
                json_encode($selection).' x '.$quantity,
            );
        }
    }

    public function test_price_quote_request_rejects_missing_or_non_positive_quantity(): void
    {
        $request = new PriceQuoteRequest;

        $failures = [
            [[], 'quantity', translate('Please enter the quantity you want to order.')],
            [['quantity' => 0], 'quantity', translate('The quantity must be at least 1.')],
            [['quantity' => -3], 'quantity', translate('The quantity must be at least 1.')],
            [['quantity' => 'many'], 'quantity', translate('The quantity must be a whole number.')],
            [['quantity' => 2, 'variation' => 'Red'], 'variation', translate('Please select a valid variation.')],
            [['quantity' => 2, 'variation' => ['color' => str_repeat('a', 192)]], 'variation.color', translate('Please select a valid variation.')],
        ];

        foreach ($failures as [$input, $field, $message]) {
            $validator = Validator::make($input, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), json_encode($input));
            $this->assertSame($message, $validator->errors()->first($field));
        }

        $this->assertFalse(Validator::make(
            ['quantity' => 1, 'variation' => ['color' => '#FF0000', 'choice_6' => null]],
            $request->rules(),
            $request->messages(),
        )->fails());
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

        return $this->reload($product);
    }

    private function reload(Model $product): Model
    {
        return $this->service()->findUnscoped($product->id, ['wholesaleDetail', 'stocks', 'taxVats.tax']);
    }

    private function basePayload(): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale quote fixture'],
            'description' => ['Wholesale quote fixture'],
            'code' => 'WSQTE'.random_int(10000, 99999),
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

    private function quantityTiers(array $tiers): array
    {
        return [
            'quantity_based_price' => 1,
            'tier_min_qty' => array_column($tiers, 0),
            'tier_max_qty' => array_map(static fn (?int $maximum): string => $maximum === null ? '' : (string) $maximum, array_column($tiers, 1)),
            'tier_unit_price' => array_column($tiers, 2),
        ];
    }

    private function colors(array $stockByName): array
    {
        $fields = ['colors_active' => 'on', 'colors' => array_keys(self::COLOR_NAMES)];

        foreach ($stockByName as $name => $quantity) {
            $fields['qty_'.$name] = $quantity;
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

    private function stockFor(array $colors, array $sizes, array $fits, array $quantities): array
    {
        $fields = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                foreach ($fits as $fit) {
                    $variant = $color.'-'.$size.'-'.$fit;
                    $fields['qty_'.$variant] = $quantities[$variant] ?? 50;
                }
            }
        }

        return $fields;
    }

    private function attributeIds(int $count): array
    {
        $ids = app(AttributeService::class)->getIds();

        if (count($ids) < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' wholesale attributes.');
        }

        return array_map(intval(...), array_slice($ids, 0, $count));
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function activeWholesaleTaxSetup(): SystemTaxSetup
    {
        return $this->requireFixture(
            SystemTaxSetup::query()
                ->where('business_module', BusinessModule::WHOLESALE)
                ->where('tax_payer', SystemTaxSetupService::TAX_PAYER_VENDOR)
                ->where('is_default', 1)
                ->where('is_active', 1)
                ->first(),
            'active default wholesale tax setup',
        );
    }

    private function configureTax(bool $isIncluded): void
    {
        $this->taxSetup->update(['tax_type' => 'product_wise', 'is_included' => $isIncluded ? 1 : 0]);

        TaxSetupCache::clearTaxSystemTypeCache();
    }
}
