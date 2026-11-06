<?php

namespace Tests\Feature\Wholesale;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class OfferQueryTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresWholesaleAddon;

    private ProductWholesaleService $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = app(ProductWholesaleService::class);
    }

    public function test_the_discount_sort_is_offered_to_the_storefront(): void
    {
        $this->assertContains(ProductWholesaleService::SORT_DISCOUNT_DESC, ProductWholesaleService::STOREFRONT_SORTS);
    }

    public function test_the_discounted_filter_keeps_only_products_with_a_stored_percentage(): void
    {
        $discounted = $this->markOffer(['discount_percentage' => 12]);
        $plain = $this->markOffer(['discount_percentage' => 0]);

        $ids = $this->listedIds([ProductWholesaleService::FILTER_DISCOUNTED => true]);

        $this->assertContains($discounted, $ids);
        $this->assertNotContains($plain, $ids);
    }

    public function test_the_price_drop_filter_keeps_only_recent_reductions(): void
    {
        $recent = $this->markOffer(['previous_min_price' => 90, 'previous_max_price' => 120, 'price_dropped_at' => now()->subDay()]);
        $stale = $this->markOffer(['previous_min_price' => 90, 'previous_max_price' => 120, 'price_dropped_at' => now()->subDays(30)]);
        $never = $this->markOffer(['previous_min_price' => null, 'price_dropped_at' => null]);

        $ids = $this->listedIds([ProductWholesaleService::FILTER_PRICE_DROPPED => true]);

        $this->assertContains($recent, $ids);
        $this->assertNotContains($stale, $ids);
        $this->assertNotContains($never, $ids);
    }

    public function test_the_reduced_moq_filter_keeps_only_recent_reductions(): void
    {
        $recent = $this->markOffer(['previous_minimum_order_qty' => 100, 'moq_reduced_at' => now()->subDay()]);
        $stale = $this->markOffer(['previous_minimum_order_qty' => 100, 'moq_reduced_at' => now()->subDays(30)]);

        $ids = $this->listedIds([ProductWholesaleService::FILTER_MOQ_REDUCED => true]);

        $this->assertContains($recent, $ids);
        $this->assertNotContains($stale, $ids);
    }

    public function test_the_discount_sort_puts_the_deepest_discount_first(): void
    {
        $shallow = $this->markOffer(['discount_percentage' => 5]);
        $deep = $this->markOffer(['discount_percentage' => 45]);

        $ids = $this->listedIds(
            filters: [ProductWholesaleService::FILTER_DISCOUNTED => true, 'id' => [$shallow, $deep]],
            sortBy: ProductWholesaleService::SORT_DISCOUNT_DESC,
        );

        $this->assertSame([$deep, $shallow], array_values($ids));
    }

    public function test_price_bounds_narrow_to_the_requested_bucket(): void
    {
        $cheapId = $this->markOffer(['previous_minimum_order_qty' => 100, 'moq_reduced_at' => now()->subDay()]);
        $this->createWholesaleProduct(tiers: [[10, 50, 9000], [51, null, 9000]]);

        $cheapPrice = (float) Product::query()->where('id', $cheapId)->value('unit_price');

        $catalogueBounds = $this->productService->getPriceBounds();
        $bucketBounds = $this->productService->getPriceBounds(filters: [ProductWholesaleService::FILTER_MOQ_REDUCED => true]);

        $this->assertGreaterThan($bucketBounds['max'], $catalogueBounds['max']);
        $this->assertGreaterThanOrEqual($cheapPrice, $bucketBounds['max']);
    }

    /** @param array<string, mixed> $columns */
    private function markOffer(array $columns): int
    {
        $product = $this->createWholesaleProduct();

        ProductWholesale::query()->where('product_id', $product->id)->update([
            'discount_percentage' => 0,
            'previous_min_price' => null,
            'previous_max_price' => null,
            'price_dropped_at' => null,
            'previous_minimum_order_qty' => null,
            'moq_reduced_at' => null,
            ...$columns,
        ]);

        return (int) $product->id;
    }

    /** @param array<int, array{0: int, 1: int|null, 2: int}> $tiers */
    private function createWholesaleProduct(array $tiers = [[10, 50, 60], [51, null, 40]]): Model
    {
        return $this->productService->create(
            data: $this->productPayload(tiers: $tiers),
            addedBy: ProductWholesaleService::ADDED_BY_ADMIN,
        );
    }

    /**
     * @param  array<int, array{0: int, 1: int|null, 2: int}>  $tiers
     * @return array<string, mixed>
     */
    private function productPayload(array $tiers = [[10, 50, 60], [51, null, 40]]): array
    {
        return [
            'name' => ['Offer query product'],
            'lang' => ['en'],
            'description' => ['Offer query description'],
            'code' => 'OFFR'.random_int(100000, 999999),
            'category_id' => Category::query()->where('position', 0)->value('id'),
            'unit' => 'pc',
            'minimum_order_qty' => 50,
            'current_stock' => 5000,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'quantity_based_price' => 1,
            'tier_min_qty' => array_column($tiers, 0),
            'tier_max_qty' => array_column($tiers, 1),
            'tier_unit_price' => array_column($tiers, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    private function listedIds(array $filters, ?string $sortBy = null): array
    {
        return $this->productService
            ->getList(dataLimit: 'all', relations: ['wholesaleDetail'], filters: $filters, sortBy: $sortBy)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
