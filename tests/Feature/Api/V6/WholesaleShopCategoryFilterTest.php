<?php

namespace Tests\Feature\Api\V6;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleShopCategoryFilterTest extends TestCase
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

    public function test_category_id_filters_shops_to_wholesalers_selling_in_that_category(): void
    {
        $categoryA = $this->createWholesaleCategory();
        $categoryB = $this->createWholesaleCategory();

        $shopA = $this->createWholesalerWithProduct(['category_id' => $categoryA->id]);
        $shopB = $this->createWholesalerWithProduct(['category_id' => $categoryB->id]);

        $shopIds = $this->fetchShopIds(['category_id' => $categoryA->id]);

        $this->assertContains((int) $shopA->id, $shopIds);
        $this->assertNotContains((int) $shopB->id, $shopIds);
    }

    public function test_sub_category_id_filters_shops_to_wholesalers_selling_in_that_sub_category(): void
    {
        $category = $this->createWholesaleCategory();
        $subCategoryA = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);
        $subCategoryB = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);

        $shopA = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategoryA->id,
        ]);
        $shopB = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategoryB->id,
        ]);

        $shopIds = $this->fetchShopIds(['sub_category_id' => $subCategoryA->id]);

        $this->assertContains((int) $shopA->id, $shopIds);
        $this->assertNotContains((int) $shopB->id, $shopIds);
    }

    public function test_sub_sub_category_id_filters_shops_to_wholesalers_selling_in_that_sub_sub_category(): void
    {
        $category = $this->createWholesaleCategory();
        $subCategory = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);
        $subSubCategoryA = $this->createWholesaleCategory(['parent_id' => $subCategory->id, 'position' => 2]);
        $subSubCategoryB = $this->createWholesaleCategory(['parent_id' => $subCategory->id, 'position' => 2]);

        $shopA = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'sub_sub_category_id' => $subSubCategoryA->id,
        ]);
        $shopB = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'sub_sub_category_id' => $subSubCategoryB->id,
        ]);

        $shopIds = $this->fetchShopIds(['sub_sub_category_id' => $subSubCategoryA->id]);

        $this->assertContains((int) $shopA->id, $shopIds);
        $this->assertNotContains((int) $shopB->id, $shopIds);
    }

    public function test_a_sub_category_selection_narrows_a_broader_category_selection(): void
    {
        $category = $this->createWholesaleCategory();
        $subCategoryA = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);
        $subCategoryB = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);

        $shopA = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategoryA->id,
        ]);
        $shopB = $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategoryB->id,
        ]);

        $shopIds = $this->fetchShopIds([
            'category_id' => $category->id,
            'sub_category_id' => $subCategoryA->id,
        ]);

        $this->assertContains((int) $shopA->id, $shopIds);
        $this->assertNotContains(
            (int) $shopB->id,
            $shopIds,
            'Sending category_id alongside sub_category_id should narrow to the sub-category, not widen back to the whole parent category.',
        );
    }

    public function test_an_unknown_category_id_returns_no_shops_instead_of_ignoring_the_filter(): void
    {
        $category = $this->createWholesaleCategory();

        $this->createWholesalerWithProduct(['category_id' => $category->id]);

        $shopIds = $this->fetchShopIds(['category_id' => 999999999]);

        $this->assertSame([], $shopIds, 'An unresolvable category id must not fall back to the unfiltered list.');
    }

    public function test_an_unknown_sub_category_id_beside_a_valid_category_id_returns_no_shops(): void
    {
        $category = $this->createWholesaleCategory();

        $this->createWholesalerWithProduct(['category_id' => $category->id]);

        $shopIds = $this->fetchShopIds(['category_id' => $category->id, 'sub_category_id' => 999999999]);

        $this->assertSame([], $shopIds, 'An unresolvable sub-category must not widen back to its parent category.');
    }

    public function test_sub_sub_category_id_is_honored_for_the_verified_seller_and_top_discounted_types(): void
    {
        $category = $this->createWholesaleCategory();
        $subCategory = $this->createWholesaleCategory(['parent_id' => $category->id, 'position' => 1]);
        $subSubCategory = $this->createWholesaleCategory(['parent_id' => $subCategory->id, 'position' => 2]);
        $otherCategory = $this->createWholesaleCategory();

        $this->createWholesalerWithProduct([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'sub_sub_category_id' => $subSubCategory->id,
        ]);
        $outsideShop = $this->createWholesalerWithProduct(['category_id' => $otherCategory->id]);

        foreach (['verified-seller', 'top-discounted'] as $type) {
            $this->assertNotContains(
                (int) $outsideShop->id,
                $this->fetchShopIds(['type' => $type, 'sub_sub_category_id' => $subSubCategory->id]),
            );
        }
    }

    public function test_category_id_is_honored_for_the_top_wholesaler_type(): void
    {
        $categoryA = $this->createWholesaleCategory();
        $categoryB = $this->createWholesaleCategory();

        $shopA = $this->createWholesalerWithProduct(['category_id' => $categoryA->id]);
        $shopB = $this->createWholesalerWithProduct(['category_id' => $categoryB->id]);

        $shopIds = $this->fetchShopIds(['type' => 'top-wholesaler', 'category_id' => $categoryA->id]);

        $this->assertContains((int) $shopA->id, $shopIds);
        $this->assertNotContains((int) $shopB->id, $shopIds);
    }

    /** @param array<string, mixed> $query */
    private function fetchShopIds(array $query): array
    {
        $shops = $this->getJson('api/v6/wholesale/shops?'.http_build_query([...$query, 'limit' => 50]))
            ->assertOk()
            ->json('content.data');

        return array_map('intval', array_column($shops, 'id'));
    }

    /** @param array<string, mixed> $overrides */
    private function createWholesaleCategory(array $overrides = []): Category
    {
        $unique = random_int(100000, 999999);

        return Category::query()->create([
            'name' => 'Filter Test Category '.$unique,
            'slug' => 'filter-test-category-'.$unique,
            'parent_id' => 0,
            'position' => 0,
            'business_module' => BusinessModule::WHOLESALE,
            ...$overrides,
        ]);
    }

    /** @param array<string, mixed> $categoryOverrides */
    private function createWholesalerWithProduct(array $categoryOverrides): Model
    {
        $unique = random_int(100000, 999999);

        $seller = Seller::query()->create([
            'f_name' => 'Category',
            'l_name' => 'Filter',
            'phone' => '+1'.random_int(1000000000, 1999999999),
            'email' => 'category-filter-'.$unique.'@example.test',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $shop = Shop::query()->create([
            'seller_id' => $seller->id,
            'author_type' => 'vendor',
            'name' => 'Category Filter Wholesaler '.$unique,
            'slug' => 'category-filter-wholesaler-'.$unique,
            'address' => 'Test address',
            'contact' => '+1'.random_int(1000000000, 1999999999),
            'banner' => 'def.png',
        ]);

        $product = $this->productService->create(
            data: [
                'name' => ['Category filter wholesale product '.$unique],
                'lang' => ['en'],
                'description' => ['Category filter wholesale product'],
                'code' => 'CATF'.$unique,
                'unit' => 'pc',
                'minimum_order_qty' => 10,
                'current_stock' => 500,
                'discount' => 0,
                'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
                'quantity_based_price' => 1,
                'tier_min_qty' => [10],
                'tier_max_qty' => [null],
                'tier_unit_price' => [60],
                ...$categoryOverrides,
            ],
            addedBy: ProductWholesaleService::ADDED_BY_SELLER,
            userId: $seller->id,
            shopId: $shop->id,
        );

        $this->productService->updateStatus($product->id, true);

        Product::query()->whereKey($product->id)->update([
            'request_status' => ProductWholesaleService::REQUEST_STATUS_APPROVED,
        ]);

        return $shop;
    }
}
