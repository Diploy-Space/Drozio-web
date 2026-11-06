<?php

namespace Tests\Unit;

use App\Models\Product;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class CampaignDiscountEligibilityTest extends TestCase
{
    use RequiresWholesaleAddon;

    public function test_a_variation_priced_product_reports_its_lowest_discount_inside_the_campaign_range(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: true,
            priceSetup: $this->variationSetup([0, 4, 7, 12]),
            discount: 25,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(7.0, $discount);
    }

    public function test_a_variation_priced_product_ignores_the_primary_discount(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: true,
            priceSetup: $this->variationSetup([9]),
            discount: 30,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(9.0, $discount);
    }

    public function test_a_variation_priced_product_with_no_qualifying_variation_stays_outside_the_range(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: true,
            priceSetup: $this->variationSetup([0, 2, 18]),
            discount: 7,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(2.0, $discount);
    }

    public function test_a_variation_priced_product_reads_the_quantity_tier_discounts_when_both_pricing_modes_are_on(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: true,
            priceSetup: $this->variationTierSetup([[3, 8], [6, 15]]),
            discount: 40,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(6.0, $discount);
    }

    public function test_a_quantity_priced_product_uses_the_primary_percentage_discount(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: false,
            priceSetup: $this->quantityOnlySetup(),
            discount: 8,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(8.0, $discount);
    }

    public function test_a_quantity_priced_product_with_a_flat_discount_reports_nothing(): void
    {
        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: false,
            priceSetup: $this->quantityOnlySetup(),
            discount: 8,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(0.0, $discount);
    }

    public function test_a_blank_variation_discount_is_not_read_as_a_zero_percent_offer(): void
    {
        $setup = ['rows' => [
            ['variation_value' => 'Red', 'unit_price' => 100, 'discount' => null, 'tiers' => []],
            ['variation_value' => 'Blue', 'unit_price' => 100, 'discount' => 9, 'tiers' => []],
        ]];

        $discount = $this->service()->getCampaignDiscountPercentage(
            pricesByVariation: true,
            priceSetup: $setup,
            discount: 0,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
            minDiscount: 0.0,
            maxDiscount: 10.0,
        );

        $this->assertSame(9.0, $discount);
    }

    public function test_campaign_eligibility_still_answers_yes_for_any_discounted_variation(): void
    {
        $service = $this->service();

        $this->assertTrue($service->hasCampaignDiscount(
            pricesByVariation: true,
            priceSetup: $this->variationSetup([0, 12]),
            discount: 0,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
        ));

        $this->assertFalse($service->hasCampaignDiscount(
            pricesByVariation: true,
            priceSetup: $this->variationSetup([0, 0]),
            discount: 0,
            discountType: ProductWholesaleService::DISCOUNT_TYPE_FLAT,
        ));
    }

    public function test_the_product_keyed_map_applies_the_range_to_every_pricing_type(): void
    {
        $discounts = $this->service()->getCampaignDiscountsByProductId(
            products: [
                $this->product(id: 11, pricesByVariation: true, priceSetup: $this->variationSetup([0, 6, 14]), discount: 30),
                $this->product(id: 22, pricesByVariation: false, priceSetup: $this->quantityOnlySetup(), discount: 9),
                $this->product(id: 33, pricesByVariation: true, priceSetup: $this->variationSetup([18]), discount: 7),
            ],
            minDiscount: 5.0,
            maxDiscount: 10.0,
        );

        $this->assertSame([11 => 6.0, 22 => 9.0, 33 => 18.0], $discounts);
    }

    private function service(): ProductWholesaleService
    {
        return new ProductWholesaleService(new Product);
    }

    private function product(int $id, bool $pricesByVariation, array $priceSetup, float $discount): Product
    {
        $product = new Product([
            'discount' => $discount,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_PERCENT,
        ]);
        $product->id = $id;
        $product->setRelation('wholesaleDetail', new ProductWholesale([
            'variation_based_price' => $pricesByVariation,
            'quantity_based_price' => ! $pricesByVariation,
            'price_setup' => $priceSetup,
        ]));

        return $product;
    }

    private function variationSetup(array $discounts): array
    {
        $rows = [];

        foreach ($discounts as $index => $discount) {
            $rows[] = [
                'variation_value' => 'Variation '.($index + 1),
                'unit_price' => 100,
                'discount' => (float) $discount,
                'tiers' => [],
            ];
        }

        return ['rows' => $rows];
    }

    private function variationTierSetup(array $discountsPerVariation): array
    {
        $rows = [];

        foreach ($discountsPerVariation as $index => $discounts) {
            $rows[] = [
                'variation_value' => 'Variation '.($index + 1),
                'unit_price' => null,
                'discount' => null,
                'tiers' => array_map(static fn (float|int $discount, int $tier): array => [
                    'min_qty' => ($tier * 10) + 1,
                    'max_qty' => ($tier + 1) * 10,
                    'unit_price' => 100,
                    'discount' => (float) $discount,
                ], $discounts, array_keys($discounts)),
            ];
        }

        return ['rows' => $rows];
    }

    private function quantityOnlySetup(): array
    {
        return ['rows' => [[
            'variation_value' => null,
            'unit_price' => null,
            'discount' => null,
            'tiers' => [
                ['min_qty' => 1, 'max_qty' => 10, 'unit_price' => 100, 'discount' => null],
                ['min_qty' => 11, 'max_qty' => null, 'unit_price' => 90, 'discount' => null],
            ],
        ]]];
    }
}
