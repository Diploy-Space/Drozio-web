<?php

namespace Tests\Feature\Api\V6;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Enums\ManufacturerFilter;
use Modules\Wholesale\app\Models\ProductWholesale;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleTopDiscountedShopsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private ProductWholesaleService $productService;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->productService = app(ProductWholesaleService::class);
    }

    public function test_top_discounted_is_an_accepted_list_type(): void
    {
        $this->assertContains(ManufacturerFilter::TOP_DISCOUNTED, ManufacturerFilter::LIST_TYPES);
    }

    public function test_top_discounted_answers_the_shop_card_contract(): void
    {
        $this->getJson('api/v6/wholesale/shops?type=top-discounted&limit=5')
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonPath('content.params.type', ManufacturerFilter::TOP_DISCOUNTED)
            ->assertJsonStructure([
                'content' => [
                    'data' => [[
                        'id', 'seller_id', 'name', 'slug', 'image_full_url', 'is_verified',
                        'average_rating', 'review_count', 'total_product', 'minimum_order_amount',
                        'products' => [['id', 'slug', 'name', 'price_range', 'minimum_order_qty', 'discount', 'discount_type']],
                    ]],
                    'params',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                ],
            ]);

        $this->assertNotEmpty(
            $this->getJson('api/v6/wholesale/shops?type=top-discounted&limit=5')->json('content.data'),
            'No shop qualified for type=top-discounted in the test database.',
        );
    }

    public function test_top_discounted_is_ordered_by_the_deepest_discount(): void
    {
        $deeperShop = $this->createDeeperDiscountWholesaler(discountPercentage: 45.0);
        $shallowerShop = $this->createDeeperDiscountWholesaler(discountPercentage: 15.0);

        $shops = $this->getJson('api/v6/wholesale/shops?type=top-discounted&limit=10')->json('content.data');

        $this->assertNotEmpty($shops, 'No shop qualified for type=top-discounted in the test database.');
        $this->assertGreaterThanOrEqual(
            2,
            count($shops),
            'Fewer than two shops qualified for type=top-discounted, so the ordering assertion cannot distinguish correct ordering from no ordering at all.',
        );

        $shopIds = array_column($shops, 'id');

        $this->assertContains((int) $deeperShop->id, $shopIds, 'The 45%-discount fixture shop dropped out of the ranked list.');
        $this->assertContains((int) $shallowerShop->id, $shopIds, 'The 15%-discount fixture shop dropped out of the ranked list.');
        $this->assertLessThan(
            array_search((int) $shallowerShop->id, $shopIds, true),
            array_search((int) $deeperShop->id, $shopIds, true),
            'The 45%-discount fixture shop was not ranked ahead of the 15%-discount fixture shop.',
        );

        $deepest = array_map(
            static fn (array $shop): float => max([0.0, ...array_map(
                static fn (array $product): float => (float) ($product['discount_percentage'] ?? 0),
                $shop['products'],
            )]),
            $shops,
        );

        $this->assertSame($deepest, collect($deepest)->sortDesc()->values()->all());
    }

    public function test_an_explicit_sort_by_overrides_the_discount_ranking(): void
    {
        $overrideShop = $this->createDeeperDiscountWholesaler(discountPercentage: 45.0, name: 'Zzyx Wholesale Override');

        $discountRankedFirstId = $this->getJson('api/v6/wholesale/shops?type=top-discounted&limit=10')->json('content.data.0.id');

        $this->assertSame(
            (int) $overrideShop->id,
            (int) $discountRankedFirstId,
            'Fixture did not become the deepest-discount shop, so the override cannot be shown against it.',
        );

        $shops = $this->getJson('api/v6/wholesale/shops?'.http_build_query([
            'type' => 'top-discounted',
            'sort_by' => 'a-z',
            'limit' => 10,
        ]))->json('content.data');

        $this->assertGreaterThanOrEqual(
            2,
            count($shops),
            'Only one shop qualified for type=top-discounted, so an explicit sort_by cannot be shown to override the discount ranking.',
        );

        $names = array_column($shops, 'name');

        $this->assertSame($names, collect($names)->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all());
        $this->assertNotSame(
            (int) $overrideShop->id,
            (int) $shops[0]['id'],
            'sort_by=a-z still ranked the deepest-discount shop first, so the explicit sort was not honored.',
        );
    }

    public function test_top_discounted_previews_the_shops_most_discounted_products_first(): void
    {
        $shops = $this->getJson('api/v6/wholesale/shops?type=top-discounted&limit=3')->json('content.data');

        $this->assertNotEmpty($shops, 'No shop qualified for type=top-discounted in the test database.');

        $shopWithMultipleProducts = false;

        foreach ($shops as $shop) {
            $percentages = array_map(
                static fn (array $product): float => (float) ($product['discount_percentage'] ?? 0),
                $shop['products'],
            );

            if (count($percentages) > 1) {
                $shopWithMultipleProducts = true;
            }

            $this->assertSame($percentages, collect($percentages)->sortDesc()->values()->all());
        }

        $this->assertTrue(
            $shopWithMultipleProducts,
            'No shop in the test database previewed more than one product, so the ordering was never exercised.',
        );
    }

    public function test_top_discounted_accepts_a_search_keyword(): void
    {
        $shop = $this->createDeeperDiscountWholesaler(
            discountPercentage: 45.0,
            name: 'Search Keyword Wholesaler '.random_int(100000, 999999),
        );

        $response = $this->getJson('api/v6/wholesale/shops?'.http_build_query([
            'type' => 'top-discounted',
            'search' => $shop->name,
            'limit' => 10,
        ]))
            ->assertOk()
            ->assertJsonPath('content.params.search', $shop->name);

        $shops = $response->json('content.data');

        $this->assertNotEmpty($shops, 'The search keyword matched no shop.');

        $names = array_column($shops, 'name');

        $this->assertContains($shop->name, $names, 'The fixture shop matching the search keyword did not appear in the results.');

        foreach ($names as $name) {
            $this->assertStringContainsStringIgnoringCase(
                $shop->name,
                $name,
                "Shop \"{$name}\" was returned for a search that should only match \"{$shop->name}\".",
            );
        }
    }

    public function test_an_unknown_list_type_is_rejected(): void
    {
        $this->getJson('api/v6/wholesale/shops?type=cheapest')->assertStatus(422);
    }

    private function createDeeperDiscountWholesaler(float $discountPercentage, ?string $name = null): Model
    {
        $seller = Seller::query()->create([
            'f_name' => 'Deepest',
            'l_name' => 'Discounter',
            'phone' => '+1'.random_int(1000000000, 1999999999),
            'email' => 'deepest-discount-'.random_int(100000, 999999).'@example.test',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $shop = Shop::query()->create([
            'seller_id' => $seller->id,
            'author_type' => 'vendor',
            'name' => $name ?? 'Deepest Discount Wholesaler',
            'slug' => 'deepest-discount-wholesaler-'.$seller->id,
            'address' => 'Test address',
            'contact' => '+1'.random_int(1000000000, 1999999999),
            'banner' => 'def.png',
        ]);

        $product = $this->productService->create(
            data: $this->deeperDiscountProductPayload(),
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: $seller->id,
            shopId: $shop->id,
        );

        $this->productService->updateStatus($product->id, true);

        Product::query()->whereKey($product->id)->update([
            'request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED,
        ]);

        ProductWholesale::query()->where('product_id', $product->id)->update([
            'discount_percentage' => $discountPercentage,
        ]);

        return $shop;
    }

    /** @return array<string, mixed> */
    private function deeperDiscountProductPayload(): array
    {
        return [
            'name' => ['Deepest discount wholesale product'],
            'lang' => ['en'],
            'description' => ['Deepest discount wholesale product'],
            'code' => 'TOPD'.random_int(100000, 999999),
            'category_id' => Category::query()->where('position', 0)->value('id'),
            'unit' => 'pc',
            'minimum_order_qty' => 10,
            'current_stock' => 500,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 51],
            'tier_max_qty' => [50, null],
            'tier_unit_price' => [60, 40],
        ];
    }
}
