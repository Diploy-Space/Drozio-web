<?php

namespace Tests\Feature\Api\V6;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Enums\ManufacturerFilter;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Services\RecentViewService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class WholesaleRecommendedShopsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private ProductWholesaleService $productService;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->productService = app(ProductWholesaleService::class);
    }

    public function test_recommended_is_an_accepted_list_type(): void
    {
        $this->assertContains(ManufacturerFilter::RECOMMENDED, ManufacturerFilter::LIST_TYPES);

        $this->getJson('api/v6/wholesale/shops?type=recommended&limit=5')
            ->assertOk()
            ->assertJsonPath('identical_code', 'data_fetched_200')
            ->assertJsonPath('content.params.type', ManufacturerFilter::RECOMMENDED)
            ->assertJsonStructure(['content' => ['data', 'params', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]]);
    }

    public function test_a_guest_gets_the_top_wholesaler_ranking(): void
    {
        $recommendedIds = array_column($this->getJson('api/v6/wholesale/shops?type=recommended&limit=50')->json('content.data'), 'id');
        $topWholesalerIds = array_column($this->getJson('api/v6/wholesale/shops?type=top-wholesaler&limit=50')->json('content.data'), 'id');

        $this->assertSame($topWholesalerIds, $recommendedIds);
    }

    public function test_a_customer_is_recommended_wholesalers_from_recently_viewed_categories(): void
    {
        $categoryIds = Category::query()->where('position', 0)->limit(2)->pluck('id')->all();

        $this->assertCount(2, $categoryIds, 'The test database needs two top-level categories.');

        [$viewedCategoryId, $otherCategoryId] = $categoryIds;

        [$viewedShop, $viewedProduct] = $this->createWholesaler(categoryId: $viewedCategoryId);
        [$otherShop] = $this->createWholesaler(categoryId: $otherCategoryId);

        $customer = $this->requireFixture(User::query()->where('id', '>', 0)->where('is_active', 1)->first(), 'active customer');

        app(RecentViewService::class)->trackView(productId: $viewedProduct->id, userId: $customer->id);

        $shopIds = array_column(
            $this->getJson('api/v6/wholesale/shops?type=recommended&limit=200', $this->actingAsCustomer($customer))->assertOk()->json('content.data'),
            'id',
        );

        $this->assertContains((int) $viewedShop->id, $shopIds);

        $otherShopSellsInViewedCategory = Product::query()
            ->where('user_id', $otherShop->seller_id)
            ->where('category_id', $viewedCategoryId)
            ->exists();

        if (! $otherShopSellsInViewedCategory) {
            $this->assertNotContains((int) $otherShop->id, $shopIds);
        }
    }

    /** @return array{0: Model, 1: Model} */
    private function createWholesaler(int $categoryId): array
    {
        $seller = Seller::query()->create([
            'f_name' => 'Recommended',
            'l_name' => 'Wholesaler',
            'phone' => '+1'.random_int(1000000000, 1999999999),
            'email' => 'recommended-'.random_int(100000, 999999).'@example.test',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $shop = Shop::query()->create([
            'seller_id' => $seller->id,
            'author_type' => 'vendor',
            'name' => 'Recommended Wholesaler '.$seller->id,
            'slug' => 'recommended-wholesaler-'.$seller->id,
            'address' => 'Test address',
            'contact' => '+1'.random_int(1000000000, 1999999999),
            'banner' => 'def.png',
        ]);

        $product = $this->productService->create(
            data: $this->productPayload(categoryId: $categoryId),
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: $seller->id,
            shopId: $shop->id,
        );

        $this->productService->updateStatus($product->id, true);

        Product::query()->whereKey($product->id)->update([
            'request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED,
        ]);

        return [$shop, $product];
    }

    /** @return array<string, mixed> */
    private function productPayload(int $categoryId): array
    {
        return [
            'name' => ['Recommended wholesale product'],
            'lang' => ['en'],
            'description' => ['Recommended wholesale product'],
            'code' => 'RECO'.random_int(100000, 999999),
            'category_id' => $categoryId,
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
