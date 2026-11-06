<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Models\ProductWholesale;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleOfferProductsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const CARD_KEYS = ['id', 'name', 'slug', 'thumbnail_full_url', 'price_range', 'minimum_order_qty', 'current_stock', 'discount', 'discount_type', 'unit', 'discount_percentage'];

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();
    }

    public function test_discounted_answers_the_v6_envelope_with_price_bounds(): void
    {
        $this->markOffer(['discount_percentage' => 18]);

        $this->getJson('api/v6/wholesale/products/discounted?limit=5')
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonStructure([
                'identical_code',
                'message',
                'content' => [
                    'data' => [self::CARD_KEYS],
                    'params',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                    'price_bounds' => ['min', 'max'],
                ],
                'errors',
            ]);
    }

    public function test_discounted_omits_a_product_without_a_stored_percentage(): void
    {
        $plain = $this->markOffer(['discount_percentage' => 0]);

        $response = $this->getJson('api/v6/wholesale/products/discounted?limit=100');

        $this->assertNotContains($plain, $this->returnedIds($response->json('content.data')));
    }

    public function test_price_drop_carries_the_previous_price_range(): void
    {
        $product = $this->markOffer([
            'previous_min_price' => 90,
            'previous_max_price' => 130,
            'price_dropped_at' => now()->subDay(),
        ]);

        $card = $this->findCard($this->getJson('api/v6/wholesale/products/price-drop?limit=100')->json('content.data'), $product);

        $this->assertEquals(90.0, $card['previous_price_range']['min']);
        $this->assertEquals(130.0, $card['previous_price_range']['max']);
    }

    public function test_reduced_moq_carries_the_previous_minimum_order_quantity(): void
    {
        $product = $this->markOffer([
            'previous_minimum_order_qty' => 250,
            'moq_reduced_at' => now()->subDay(),
        ]);

        $card = $this->findCard($this->getJson('api/v6/wholesale/products/reduced-moq?limit=100')->json('content.data'), $product);

        $this->assertSame(250, $card['previous_minimum_order_qty']);
    }

    public function test_an_offer_bucket_accepts_the_catalogue_filter_contract(): void
    {
        $product = $this->markOffer(['discount_percentage' => 22]);
        $name = Product::query()->whereKey($product)->value('name');

        $response = $this->getJson('api/v6/wholesale/products/discounted?'.http_build_query([
            'name' => $name,
            'sort_by' => 'discount_desc',
            'category_ids' => [Product::query()->whereKey($product)->value('category_id')],
            'min_price' => 0,
            'limit' => 10,
        ]));

        $response->assertOk();

        $this->assertContains($product, $this->returnedIds($response->json('content.data')));
    }

    public function test_an_invalid_sort_is_rejected(): void
    {
        $this->getJson('api/v6/wholesale/products/discounted?sort_by=cheapest')->assertStatus(422);
    }

    public function test_offer_buckets_return_only_their_own_snapshot_fields(): void
    {
        $this->markOffer(['discount_percentage' => 15]);
        $this->markOffer([
            'previous_min_price' => 80,
            'previous_max_price' => 120,
            'price_dropped_at' => now()->subDay(),
        ]);
        $this->markOffer([
            'previous_minimum_order_qty' => 300,
            'moq_reduced_at' => now()->subDay(),
        ]);

        $discountedCards = $this->getJson('api/v6/wholesale/products/discounted?limit=100')->json('content.data');
        $priceDropCards = $this->getJson('api/v6/wholesale/products/price-drop?limit=100')->json('content.data');
        $reducedMoqCards = $this->getJson('api/v6/wholesale/products/reduced-moq?limit=100')->json('content.data');

        if ($discountedCards === []) {
            $this->fail('The discounted bucket returned no cards.');
        }

        if ($priceDropCards === []) {
            $this->fail('The price-drop bucket returned no cards.');
        }

        if ($reducedMoqCards === []) {
            $this->fail('The reduced-moq bucket returned no cards.');
        }

        foreach ($discountedCards as $card) {
            $this->assertArrayHasKey('discount_percentage', $card);
            $this->assertArrayNotHasKey('previous_price_range', $card);
            $this->assertArrayNotHasKey('previous_minimum_order_qty', $card);
        }

        foreach ($priceDropCards as $card) {
            $this->assertArrayHasKey('previous_price_range', $card);
            $this->assertArrayNotHasKey('previous_minimum_order_qty', $card);
        }

        foreach ($reducedMoqCards as $card) {
            $this->assertArrayHasKey('previous_minimum_order_qty', $card);
            $this->assertArrayNotHasKey('previous_price_range', $card);
        }
    }

    public function test_catalogue_listing_omits_offer_snapshot_fields(): void
    {
        $cards = $this->getJson('api/v6/wholesale/products?limit=100')->json('content.data');

        if ($cards === []) {
            $this->fail('The catalogue listing returned no cards.');
        }

        foreach ($cards as $card) {
            $this->assertArrayNotHasKey('discount_percentage', $card);
            $this->assertArrayNotHasKey('previous_price_range', $card);
            $this->assertArrayNotHasKey('previous_minimum_order_qty', $card);
        }
    }

    /** @param array<string, mixed> $columns */
    private function markOffer(array $columns): int
    {
        $product = Product::query()
            ->active()
            ->where('business_module', BusinessModule::WHOLESALE)
            ->whereHas('wholesaleDetail')
            ->whereNotIn('id', $this->claimedIds)
            ->firstOrFail();

        $this->claimedIds[] = (int) $product->id;

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

    /** @var array<int, int> */
    private array $claimedIds = [];

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, int>
     */
    private function returnedIds(array $cards): array
    {
        return array_map(static fn (array $card): int => (int) $card['id'], $cards);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<string, mixed>
     */
    private function findCard(array $cards, int $productId): array
    {
        foreach ($cards as $card) {
            if ((int) $card['id'] === $productId) {
                return $card;
            }
        }

        $this->fail('Product '.$productId.' was not returned by the offer listing.');
    }
}
