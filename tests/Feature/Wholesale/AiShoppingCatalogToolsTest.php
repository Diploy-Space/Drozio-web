<?php

namespace Tests\Feature\Wholesale;

use App\Enums\BusinessModule;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AI\app\Tools\SearchProductsTool as RetailSearchProductsTool;
use Modules\Wholesale\app\Enums\CampaignShopStatus;
use Modules\Wholesale\app\Models\Campaign;
use Modules\Wholesale\app\Models\CampaignShop;
use Modules\Wholesale\app\Models\CampaignShopProduct;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Storefronts\ShoppingStorefront;
use Modules\Wholesale\app\Tools\GetSimilarProductsTool;
use Modules\Wholesale\app\Tools\ListBrandsTool;
use Modules\Wholesale\app\Tools\ListVendorsTool;
use Modules\Wholesale\app\Tools\SearchOffersTool;
use Modules\Wholesale\app\Tools\SearchProductsTool;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class AiShoppingCatalogToolsTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresWholesaleAddon;

    private const CATALOG_TOOL_NAMES = [
        'search_products',
        'get_similar_products',
        'add_to_cart',
        'update_cart_quantity',
        'go_to_checkout',
        'recall_shown_products',
        'search_offers',
        'list_vendors',
        'list_brands',
    ];

    private Seller $seller;

    private Shop $shop;

    private Category $category;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $user = User::create([
            'name' => 'Catalog Tools Seller',
            'email' => 'catalog-tools-seller@example.test',
            'phone' => '10000000098',
            'password' => bcrypt('password'),
            'user_type' => 'seller',
        ]);

        $this->seller = Seller::create([
            'id' => $user->id,
            'email' => 'catalog-tools-seller@example.test',
            'f_name' => 'Catalog',
            'l_name' => 'Seller',
            'phone' => '10000000098',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $this->shop = Shop::create([
            'seller_id' => $this->seller->id,
            'name' => 'Catalog Tools Shop',
            'address' => 'Catalog Tools Address',
            'contact' => '10000000098',
            'banner' => 'banner.png',
            'image' => 'shop.png',
        ]);

        $this->category = Category::create([
            'name' => 'Catalog Tools Chairs',
            'slug' => 'catalog-tools-chairs-'.uniqid(),
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);

        $this->brand = Brand::create([
            'name' => 'CatalogToolsBrand',
            'slug' => 'catalog-tools-brand-'.uniqid(),
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);
    }

    public function test_storefront_reports_wholesale_module_and_its_tools_in_the_retail_order(): void
    {
        $storefront = app(ShoppingStorefront::class);

        $this->assertSame(BusinessModule::WHOLESALE, $storefront->businessModule());
        $this->assertSame(self::CATALOG_TOOL_NAMES, array_map(fn ($tool) => $tool->name(), $storefront->tools()));
    }

    public function test_search_products_returns_only_wholesale_matches_priced_at_the_moq(): void
    {
        $wholesale = $this->createTieredProduct(name: 'Isolation Bulk Chair', tiers: [[1, 9, 100.0], [10, null, 80.0]], minimumOrderQty: 1);
        $retail = $this->createRetailProduct(name: 'Isolation Bulk Chair Retail');

        $result = json_decode($this->app->make(SearchProductsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Bulk Chair',
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');
        $this->assertContains($wholesale->id, $ids);
        $this->assertNotContains($retail->id, $ids);

        $card = collect($result['products'])->firstWhere('id', $wholesale->id);
        $this->assertEquals(100.0, $card['unit_price']);
        $this->assertSame(1, $card['minimum_order_qty']);
        $this->assertSame('physical', $card['product_type']);
        $this->assertSame([], $card['digital_variations']);
        $this->assertSame([], $card['digital_product_extensions']);
        $this->assertArrayHasKey('color_axis_key', $card);
        $this->assertNull($card['color_axis_key']);
        $this->assertNotEmpty($card['price_tiers']);
        $this->assertSame(1, $card['price_tiers'][0]['min_qty']);
        $this->assertSame(9, $card['price_tiers'][0]['max_qty']);
    }

    public function test_search_products_prices_a_higher_quantity_moq_at_its_own_tier(): void
    {
        $product = $this->createTieredProduct(name: 'Isolation Volume Table', tiers: [[1, 49, 100.0], [50, null, 60.0]], minimumOrderQty: 50);

        $result = json_decode($this->app->make(SearchProductsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Volume Table',
            'limit' => 10,
        ])), associative: true);

        $card = collect($result['products'])->firstWhere('id', $product->id);

        $this->assertEquals(60.0, $card['unit_price']);
        $this->assertSame(50, $card['minimum_order_qty']);
    }

    public function test_wholesale_and_retail_searches_cache_separately_for_the_same_keyword(): void
    {
        $wholesale = $this->createTieredProduct(name: 'Isolation Shared Keyword Item');
        $retail = $this->createRetailProduct(name: 'Isolation Shared Keyword Item');

        $wholesaleResult = json_decode($this->app->make(SearchProductsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Shared Keyword Item',
            'limit' => 10,
        ])), associative: true);

        $retailResult = json_decode($this->app->make(RetailSearchProductsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Shared Keyword Item',
            'limit' => 10,
        ])), associative: true);

        $this->assertSame([$wholesale->id], array_column($wholesaleResult['products'], 'id'));
        $this->assertSame([$retail->id], array_column($retailResult['products'], 'id'));
    }

    public function test_get_similar_products_stays_within_wholesale(): void
    {
        $anchor = $this->createTieredProduct(name: 'Isolation Similar Anchor');
        $sameCategory = $this->createTieredProduct(name: 'Isolation Similar Sibling');
        $retailSameCategory = $this->createRetailProduct(name: 'Isolation Similar Sibling Retail');

        $result = json_decode($this->app->make(GetSimilarProductsTool::class)->handle(new ToolRequest([
            'product_id' => $anchor->id,
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');
        $this->assertContains($sameCategory->id, $ids);
        $this->assertNotContains($retailSameCategory->id, $ids);
    }

    public function test_list_brands_counts_wholesale_products_only(): void
    {
        $this->createTieredProduct(name: 'Isolation Brand Count Item');
        $this->createRetailProduct(name: 'Isolation Brand Count Item Retail');

        $result = json_decode($this->app->make(ListBrandsTool::class)->handle(new ToolRequest([
            'query' => 'CatalogToolsBrand',
            'limit' => 10,
        ])), associative: true);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['brands'][0]['product_count']);
    }

    public function test_list_vendors_counts_wholesale_products_only(): void
    {
        $this->createTieredProduct(name: 'Isolation Vendor Count Item');
        $this->createRetailProduct(name: 'Isolation Vendor Count Item Retail');

        $result = json_decode($this->app->make(ListVendorsTool::class)->handle(new ToolRequest([
            'query' => 'Catalog Tools Shop',
            'limit' => 10,
        ])), associative: true);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['vendors'][0]['product_count']);
    }

    public function test_search_offers_returns_only_products_in_a_running_campaign(): void
    {
        $inCampaign = $this->createTieredProduct(name: 'Isolation Campaign Item');
        $notInCampaign = $this->createTieredProduct(name: 'Isolation Non Campaign Item');
        $retailInCampaign = $this->createRetailProduct(name: 'Isolation Campaign Item Retail');

        $campaign = Campaign::create([
            'business_module' => BusinessModule::WHOLESALE,
            'title' => 'Isolation Campaign',
            'slug' => 'isolation-campaign-'.uniqid(),
            'min_discount' => 0,
            'max_discount' => 100,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
            'status' => true,
        ]);

        $campaignShop = CampaignShop::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => CampaignShopStatus::APPROVED,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        CampaignShopProduct::create([
            'campaign_shop_id' => $campaignShop->id,
            'product_id' => $inCampaign->id,
            'discount' => 20,
        ]);

        $result = json_decode($this->app->make(SearchOffersTool::class)->handle(new ToolRequest([
            'limit' => 10,
        ])), associative: true);

        $ids = array_column($result['products'], 'id');
        $this->assertContains($inCampaign->id, $ids);
        $this->assertNotContains($notInCampaign->id, $ids);
        $this->assertNotContains($retailInCampaign->id, $ids);
    }

    public function test_search_offers_returns_nothing_for_an_expired_campaign(): void
    {
        $product = $this->createTieredProduct(name: 'Isolation Expired Campaign Item');

        $campaign = Campaign::create([
            'business_module' => BusinessModule::WHOLESALE,
            'title' => 'Isolation Expired Campaign',
            'slug' => 'isolation-expired-campaign-'.uniqid(),
            'min_discount' => 0,
            'max_discount' => 100,
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->subWeek(),
            'status' => true,
        ]);

        $campaignShop = CampaignShop::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => CampaignShopStatus::APPROVED,
            'is_active' => true,
            'joined_at' => now()->subWeeks(2),
        ]);

        CampaignShopProduct::create([
            'campaign_shop_id' => $campaignShop->id,
            'product_id' => $product->id,
            'discount' => 20,
        ]);

        $result = json_decode($this->app->make(SearchOffersTool::class)->handle(new ToolRequest([
            'limit' => 10,
        ])), associative: true);

        $this->assertSame(0, $result['total']);
    }

    public function test_wholesale_module_boots_without_the_ai_module(): void
    {
        $this->assertTrue(class_exists(ShoppingStorefront::class));
        $this->assertFalse(str_contains(
            file_get_contents(module_path('Wholesale', 'app/Providers/WholesaleServiceProvider.php')),
            'Modules\AI',
        ));
    }

    private function createTieredProduct(string $name, array $tiers = [[1, null, 100.0]], int $minimumOrderQty = 1): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'category_ids' => json_encode([['id' => (string) $this->category->id, 'position' => 1]]),
            'brand_id' => $this->brand->id,
            'unit_price' => $tiers[0][2],
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'current_stock' => 500,
            'minimum_order_qty' => $minimumOrderQty,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);

        $product->wholesaleDetail()->create([
            'variation_based_price' => false,
            'quantity_based_price' => true,
            'variation_type' => null,
            'price_setup' => [
                'rows' => [[
                    'variation_value' => null,
                    'unit_price' => null,
                    'discount' => null,
                    'tiers' => array_map(fn (array $tier): array => [
                        'min_qty' => (string) $tier[0],
                        'max_qty' => $tier[1] === null ? '' : (string) $tier[1],
                        'unit_price' => $tier[2],
                        'discount' => $tier[3] ?? 0,
                    ], $tiers),
                ]],
            ],
        ]);

        return $product->fresh();
    }

    private function createRetailProduct(string $name): Product
    {
        $categoryRetail = Category::create([
            'name' => $this->category->name,
            'slug' => Str::slug($name).'-retail-category-'.uniqid(),
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $brandRetail = Brand::create([
            'name' => $this->brand->name,
            'slug' => Str::slug($name).'-retail-brand-'.uniqid(),
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        return Product::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $categoryRetail->id,
            'category_ids' => json_encode([['id' => (string) $categoryRetail->id, 'position' => 1]]),
            'brand_id' => $brandRetail->id,
            'unit_price' => 25.0,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 100,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::RETAIL,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);
    }
}
