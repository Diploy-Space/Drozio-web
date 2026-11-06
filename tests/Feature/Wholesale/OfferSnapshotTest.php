<?php

namespace Tests\Feature\Wholesale;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class OfferSnapshotTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresWholesaleAddon;

    private ProductWholesaleService $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = app(ProductWholesaleService::class);
    }

    public function test_percent_discount_is_taken_as_the_percentage(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 15,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            pricesByVariation: false,
            priceSetup: [],
            lowestPrice: 400.0,
        );

        $this->assertSame(15.0, $percentage);
    }

    public function test_flat_discount_is_converted_against_the_lowest_price(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 40,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            pricesByVariation: false,
            priceSetup: [],
            lowestPrice: 400.0,
        );

        $this->assertSame(10.0, $percentage);
    }

    public function test_flat_discount_on_an_unpriced_product_is_zero(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 40,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            pricesByVariation: false,
            priceSetup: [],
            lowestPrice: 0.0,
        );

        $this->assertSame(0.0, $percentage);
    }

    public function test_variation_pricing_takes_the_highest_tier_percentage_and_ignores_the_product_discount(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 90,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            pricesByVariation: true,
            priceSetup: ['rows' => [
                ['variation_value' => 'Red', 'unit_price' => 100, 'discount' => 5, 'tiers' => [
                    ['min_qty' => 10, 'max_qty' => 50, 'unit_price' => 100, 'discount' => 12],
                ]],
                ['variation_value' => 'Blue', 'unit_price' => 100, 'discount' => 8, 'tiers' => []],
            ]],
            lowestPrice: 100.0,
        );

        $this->assertSame(12.0, $percentage);
    }

    public function test_a_discount_above_one_hundred_percent_is_capped(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 500,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            pricesByVariation: false,
            priceSetup: [],
            lowestPrice: 100.0,
        );

        $this->assertSame(100.0, $percentage);
    }

    public function test_no_discount_is_zero(): void
    {
        $percentage = $this->productService->getEffectiveDiscountPercentage(
            discount: 0,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            pricesByVariation: false,
            priceSetup: [],
            lowestPrice: 100.0,
        );

        $this->assertSame(0.0, $percentage);
    }

    public function test_offer_window_start_is_seven_days_back(): void
    {
        $this->assertSame(7, ProductWholesaleService::OFFER_WINDOW_DAYS);
        $this->assertSame(
            now()->subDays(7)->toDateString(),
            $this->productService->offerWindowStart()->toDateString(),
        );
    }

    public function test_creating_a_product_stores_its_discount_percentage(): void
    {
        $product = $this->createWholesaleProduct(discount: 20, discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT);

        $this->assertSame(20.0, (float) $product->wholesaleDetail->discount_percentage);
        $this->assertNull($product->wholesaleDetail->price_dropped_at);
        $this->assertNull($product->wholesaleDetail->moq_reduced_at);
    }

    public function test_lowering_the_price_records_the_previous_range(): void
    {
        $product = $this->createWholesaleProduct();

        $updated = $this->productService->update(
            id: $product->id,
            data: $this->productPayload(tiers: [[10, 50, 30], [51, null, 20]]),
        );

        $detail = $updated->wholesaleDetail->refresh();

        $this->assertSame(40.0, $detail->previous_min_price);
        $this->assertSame(60.0, $detail->previous_max_price);
        $this->assertNotNull($detail->price_dropped_at);
    }

    public function test_raising_the_price_clears_the_previous_range(): void
    {
        $product = $this->createWholesaleProduct();

        $this->productService->update(id: $product->id, data: $this->productPayload(tiers: [[10, 50, 30], [51, null, 20]]));
        $raised = $this->productService->update(id: $product->id, data: $this->productPayload(tiers: [[10, 50, 90], [51, null, 80]]));

        $detail = $raised->wholesaleDetail->refresh();

        $this->assertNull($detail->previous_min_price);
        $this->assertNull($detail->previous_max_price);
        $this->assertNull($detail->price_dropped_at);
    }

    public function test_lowering_the_minimum_order_quantity_records_the_previous_value(): void
    {
        $product = $this->createWholesaleProduct();

        $updated = $this->productService->update(
            id: $product->id,
            data: $this->productPayload(minimumOrderQty: 10),
        );

        $detail = $updated->wholesaleDetail->refresh();

        $this->assertSame(50, $detail->previous_minimum_order_qty);
        $this->assertNotNull($detail->moq_reduced_at);
    }

    public function test_raising_the_minimum_order_quantity_clears_the_previous_value(): void
    {
        $product = $this->createWholesaleProduct();

        $this->productService->update(id: $product->id, data: $this->productPayload(minimumOrderQty: 10));
        $raised = $this->productService->update(id: $product->id, data: $this->productPayload(minimumOrderQty: 80));

        $detail = $raised->wholesaleDetail->refresh();

        $this->assertNull($detail->previous_minimum_order_qty);
        $this->assertNull($detail->moq_reduced_at);
    }

    public function test_recompute_backfills_a_stale_discount_percentage(): void
    {
        $product = $this->createWholesaleProduct(discount: 25, discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT);

        $product->wholesaleDetail->forceFill(['discount_percentage' => 0])->save();

        $this->assertGreaterThanOrEqual(1, $this->productService->recomputeDiscountPercentages());
        $this->assertSame(25.0, (float) $product->wholesaleDetail->refresh()->discount_percentage);
    }

    private function createWholesaleProduct(mixed $discount = 0, string $discountType = ProductWholesaleService::DISCOUNT_TYPE_FLAT): Model
    {
        return $this->productService->create(
            data: $this->productPayload(discount: $discount, discountType: $discountType),
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
        );
    }

    /** @return array<string, mixed> */
    private function productPayload(
        array $tiers = [[10, 50, 60], [51, null, 40]],
        int $minimumOrderQty = 50,
        mixed $discount = 0,
        string $discountType = ProductWholesaleService::DISCOUNT_TYPE_FLAT,
    ): array {
        return [
            'name' => ['Offer signal product'],
            'lang' => ['en'],
            'description' => ['Offer signal description'],
            'code' => 'OFFR'.random_int(100000, 999999),
            'category_id' => Category::query()->where('position', 0)->value('id'),
            'unit' => 'pc',
            'minimum_order_qty' => $minimumOrderQty,
            'current_stock' => 5000,
            'discount' => $discount,
            'discount_type' => $discountType,
            'quantity_based_price' => 1,
            'tier_min_qty' => array_column($tiers, 0),
            'tier_max_qty' => array_column($tiers, 1),
            'tier_unit_price' => array_column($tiers, 2),
        ];
    }
}
