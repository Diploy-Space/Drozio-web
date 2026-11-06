<?php

namespace Tests\Feature\AI;

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AI\app\Tools\AddToCartTool;
use Modules\AI\app\Tools\GetSimilarProductsTool;
use Modules\AI\app\Tools\ListBrandsTool;
use Modules\AI\app\Tools\ListVendorsTool;
use Modules\AI\app\Tools\SearchOffersTool;
use Modules\AI\app\Tools\SearchProductsTool;
use Tests\TestCase;

class CatalogToolsIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private Product $retailProduct;

    private Product $wholesaleProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $user = User::create([
            'name' => 'Isolation Seller',
            'email' => 'isolation-seller@example.test',
            'phone' => '10000000099',
            'password' => bcrypt('password'),
            'user_type' => 'seller',
        ]);

        $seller = Seller::create([
            'id' => $user->id,
            'email' => 'isolation-seller@example.test',
            'f_name' => 'Isolation',
            'l_name' => 'Seller',
            'phone' => '10000000099',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        Shop::create([
            'seller_id' => $seller->id,
            'name' => 'Isolation Shop',
            'address' => 'Isolation Address',
            'contact' => '10000000099',
            'banner' => 'banner.png',
            'image' => 'shop.png',
        ]);

        $categoryRetail = Category::create([
            'name' => 'Isolation Shirts',
            'slug' => 'isolation-shirts-retail',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $categoryWholesale = Category::create([
            'name' => 'Isolation Shirts',
            'slug' => 'isolation-shirts-wholesale',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);

        $brandRetail = Brand::create([
            'name' => 'IsolationBrand',
            'slug' => 'isolation-brand-retail',
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $brandWholesale = Brand::create([
            'name' => 'IsolationBrand',
            'slug' => 'isolation-brand-wholesale',
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);

        $this->retailProduct = Product::create([
            'name' => 'Isolation Blue Shirt',
            'slug' => 'isolation-blue-shirt-retail',
            'added_by' => 'seller',
            'user_id' => $seller->id,
            'category_id' => $categoryRetail->id,
            'category_ids' => json_encode([['id' => (string) $categoryRetail->id, 'position' => 1]]),
            'brand_id' => $brandRetail->id,
            'unit_price' => 25.0,
            'discount' => 5,
            'discount_type' => 'percent',
            'current_stock' => 40,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::RETAIL,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
            'variation' => json_encode([]),
        ]);

        $this->wholesaleProduct = Product::create([
            'name' => 'Isolation Bulk Shirt',
            'slug' => 'isolation-bulk-shirt-wholesale',
            'added_by' => 'seller',
            'user_id' => $seller->id,
            'category_id' => $categoryWholesale->id,
            'category_ids' => json_encode([['id' => (string) $categoryWholesale->id, 'position' => 1]]),
            'brand_id' => $brandWholesale->id,
            'unit_price' => 15.0,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 500,
            'minimum_order_qty' => 10,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
            'variation' => json_encode([]),
        ]);
    }

    public function test_search_products_returns_only_retail_matches(): void
    {
        $result = json_decode($this->app->make(SearchProductsTool::class)->handle(new ToolRequest([
            'query' => 'blue shirt',
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');

        $this->assertContains($this->retailProduct->id, $ids);
        $this->assertNotContains($this->wholesaleProduct->id, $ids);
    }

    public function test_search_offers_returns_only_retail_matches(): void
    {
        $result = json_decode($this->app->make(SearchOffersTool::class)->handle(new ToolRequest([
            'query' => 'blue shirt',
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');

        $this->assertContains($this->retailProduct->id, $ids);
        $this->assertNotContains($this->wholesaleProduct->id, $ids);
    }

    public function test_similar_products_excludes_wholesale(): void
    {
        $result = json_decode($this->app->make(GetSimilarProductsTool::class)->handle(new ToolRequest([
            'product_id' => $this->retailProduct->id,
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');

        $this->assertNotContains($this->wholesaleProduct->id, $ids);
    }

    public function test_list_brands_excludes_wholesale_brand(): void
    {
        $result = json_decode($this->app->make(ListBrandsTool::class)->handle(new ToolRequest([
            'query' => 'IsolationBrand',
            'limit' => 10,
        ])), associative: true);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['brands'][0]['product_count']);
    }

    public function test_list_vendors_counts_retail_products_only(): void
    {
        $result = json_decode($this->app->make(ListVendorsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Shop',
            'limit' => 10,
        ])), associative: true);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['vendors'][0]['product_count']);
    }

    public function test_add_to_cart_rejects_wholesale_product(): void
    {
        $result = json_decode($this->app->make(AddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'quantity' => 1,
        ])), associative: true);

        $this->assertSame('not_found', $result['status']);
    }
}
