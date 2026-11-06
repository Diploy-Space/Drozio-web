<?php

namespace Tests\Feature\AI\ShoppingAssistant;

use App\Enums\BusinessModule;
use App\Enums\DealType;
use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\FlashDealProduct;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use App\Services\New\CartService as RetailCartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Models\AiChatSession;
use Modules\AI\app\Services\ShoppingAssistant\AgentLoopService;
use Modules\AI\app\Services\ShoppingAssistant\CartActionCollector;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\AI\app\Services\ShoppingAssistant\ShownProductStore;
use Modules\AI\app\Tools\AddToCartTool as RetailAddToCartTool;
use Modules\AI\app\Tools\CheckoutTool as RetailCheckoutTool;
use Modules\AI\app\Tools\GetSimilarProductsTool as RetailGetSimilarProductsTool;
use Modules\AI\app\Tools\ListBrandsTool as RetailListBrandsTool;
use Modules\AI\app\Tools\ListVendorsTool as RetailListVendorsTool;
use Modules\AI\app\Tools\RecallShownProductsTool;
use Modules\AI\app\Tools\SearchOffersTool as RetailSearchOffersTool;
use Modules\AI\app\Tools\SearchProductsTool as RetailSearchProductsTool;
use Modules\AI\app\Tools\UpdateCartQuantityTool as RetailUpdateCartQuantityTool;
use Modules\Wholesale\app\Enums\CampaignShopStatus;
use Modules\Wholesale\app\Models\Campaign;
use Modules\Wholesale\app\Models\CampaignShop;
use Modules\Wholesale\app\Models\CampaignShopProduct;
use Modules\Wholesale\app\Services\CartService as WholesaleCartService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Storefronts\ShoppingStorefront as WholesaleShoppingStorefront;
use Modules\Wholesale\app\Tools\AddToCartTool as WholesaleAddToCartTool;
use Modules\Wholesale\app\Tools\CheckoutTool as WholesaleCheckoutTool;
use Modules\Wholesale\app\Tools\GetSimilarProductsTool as WholesaleGetSimilarProductsTool;
use Modules\Wholesale\app\Tools\ListBrandsTool as WholesaleListBrandsTool;
use Modules\Wholesale\app\Tools\ListVendorsTool as WholesaleListVendorsTool;
use Modules\Wholesale\app\Tools\SearchOffersTool as WholesaleSearchOffersTool;
use Modules\Wholesale\app\Tools\SearchProductsTool as WholesaleSearchProductsTool;
use Modules\Wholesale\app\Tools\UpdateCartQuantityTool as WholesaleUpdateCartQuantityTool;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionFunction;
use Tests\TestCase;

class CrossModuleIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private const SHARED_KEYWORD = 'Isolation Crosscheck Item';

    private const SHARED_BRAND_NAME = 'IsolationCrosscheckBrand';

    private const SHARED_CATEGORY_NAME = 'Isolation Crosscheck Category';

    private Seller $seller;

    private Shop $shop;

    private Product $retailProduct;

    private Product $wholesaleProduct;

    private array $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (glob(base_path('resources/lang/*/{new-messages,lang,messages}.php'), GLOB_BRACE) ?: [] as $path) {
            $this->languageFiles[$path] = (string) file_get_contents($path);
        }

        $user = User::create([
            'name' => 'Crosscheck Seller',
            'email' => 'crosscheck-seller@example.test',
            'phone' => '10000000097',
            'password' => bcrypt('password'),
            'user_type' => 'seller',
        ]);

        $this->seller = Seller::create([
            'id' => $user->id,
            'email' => 'crosscheck-seller@example.test',
            'f_name' => 'Crosscheck',
            'l_name' => 'Seller',
            'phone' => '10000000097',
            'password' => bcrypt('password'),
            'status' => 'approved',
        ]);

        $this->shop = Shop::create([
            'seller_id' => $this->seller->id,
            'name' => 'Isolation Crosscheck Shop',
            'address' => 'Isolation Crosscheck Address',
            'contact' => '10000000097',
            'banner' => 'banner.png',
            'image' => 'shop.png',
        ]);

        $categoryRetail = Category::create([
            'name' => self::SHARED_CATEGORY_NAME,
            'slug' => 'isolation-crosscheck-category-retail',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $categoryWholesale = Category::create([
            'name' => self::SHARED_CATEGORY_NAME,
            'slug' => 'isolation-crosscheck-category-wholesale',
            'parent_id' => 0,
            'position' => 0,
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);

        $brandRetail = Brand::create([
            'name' => self::SHARED_BRAND_NAME,
            'slug' => 'isolation-crosscheck-brand-retail',
            'status' => 1,
            'business_module' => BusinessModule::RETAIL,
        ]);

        $brandWholesale = Brand::create([
            'name' => self::SHARED_BRAND_NAME,
            'slug' => 'isolation-crosscheck-brand-wholesale',
            'status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
        ]);

        $this->retailProduct = Product::create([
            'name' => self::SHARED_KEYWORD.' Retail',
            'slug' => 'isolation-crosscheck-retail',
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $categoryRetail->id,
            'category_ids' => json_encode([['id' => (string) $categoryRetail->id, 'position' => 1]]),
            'brand_id' => $brandRetail->id,
            'unit_price' => 25.0,
            'discount' => 15,
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
            'name' => self::SHARED_KEYWORD.' Wholesale',
            'slug' => 'isolation-crosscheck-wholesale',
            'added_by' => 'seller',
            'user_id' => $this->seller->id,
            'category_id' => $categoryWholesale->id,
            'category_ids' => json_encode([['id' => (string) $categoryWholesale->id, 'position' => 1]]),
            'brand_id' => $brandWholesale->id,
            'unit_price' => 80.0,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'current_stock' => 500,
            'minimum_order_qty' => 10,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::WHOLESALE,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);

        $this->wholesaleProduct->wholesaleDetail()->create([
            'variation_based_price' => false,
            'quantity_based_price' => true,
            'variation_type' => null,
            'price_setup' => [
                'rows' => [[
                    'variation_value' => null,
                    'unit_price' => null,
                    'discount' => null,
                    'tiers' => [
                        ['min_qty' => '10', 'max_qty' => '49', 'unit_price' => 80.0, 'discount' => 0],
                        ['min_qty' => '50', 'max_qty' => '', 'unit_price' => 60.0, 'discount' => 0],
                    ],
                ]],
            ],
        ]);
        $this->wholesaleProduct = $this->wholesaleProduct->fresh();

        $flashDeal = FlashDeal::create([
            'title' => 'Isolation Crosscheck Flash Deal',
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
            'status' => 1,
            'featured' => 0,
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'banner' => 'def.webp',
            'slug' => 'isolation-crosscheck-flash-deal',
            'product_id' => $this->retailProduct->id,
            'deal_type' => DealType::FLASH,
        ]);

        FlashDealProduct::create([
            'flash_deal_id' => $flashDeal->id,
            'product_id' => $this->retailProduct->id,
            'discount' => 15,
            'discount_type' => 'percent',
        ]);

        $campaign = Campaign::create([
            'business_module' => BusinessModule::WHOLESALE,
            'title' => 'Isolation Crosscheck Campaign',
            'slug' => 'isolation-crosscheck-campaign',
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
            'product_id' => $this->wholesaleProduct->id,
            'discount' => 10,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->languageFiles as $path => $contents) {
            if (file_get_contents($path) !== $contents) {
                file_put_contents($path, $contents);
            }
        }

        parent::tearDown();
    }

    public function test_read_tools_return_only_their_own_module_products(): void
    {
        $retailSearch = $this->decode($this->app->make(RetailSearchProductsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_KEYWORD,
            'limit' => 10,
        ])));
        $wholesaleSearch = $this->decode($this->app->make(WholesaleSearchProductsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_KEYWORD,
            'limit' => 10,
        ])));

        $this->assertContains($this->retailProduct->id, array_column($retailSearch['products'], 'id'));
        $this->assertNotContains($this->wholesaleProduct->id, array_column($retailSearch['products'], 'id'));
        $this->assertContains($this->wholesaleProduct->id, array_column($wholesaleSearch['products'], 'id'));
        $this->assertNotContains($this->retailProduct->id, array_column($wholesaleSearch['products'], 'id'));

        $retailSimilar = $this->decode($this->app->make(RetailGetSimilarProductsTool::class)->handle(new ToolRequest([
            'product_id' => $this->retailProduct->id,
            'limit' => 10,
        ])));
        $wholesaleSimilar = $this->decode($this->app->make(WholesaleGetSimilarProductsTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'limit' => 10,
        ])));

        $this->assertNotContains($this->wholesaleProduct->id, array_column($retailSimilar['products'], 'id'));
        $this->assertNotContains($this->retailProduct->id, array_column($wholesaleSimilar['products'], 'id'));

        $retailOffers = $this->decode($this->app->make(RetailSearchOffersTool::class)->handle(new ToolRequest([
            'deal_type' => 'flash_deal',
            'limit' => 10,
        ])));
        $wholesaleOffers = $this->decode($this->app->make(WholesaleSearchOffersTool::class)->handle(new ToolRequest([
            'deal_type' => 'campaign',
            'limit' => 10,
        ])));

        $this->assertContains($this->retailProduct->id, array_column($retailOffers['products'], 'id'));
        $this->assertNotContains($this->wholesaleProduct->id, array_column($retailOffers['products'], 'id'));
        $this->assertContains($this->wholesaleProduct->id, array_column($wholesaleOffers['products'], 'id'));
        $this->assertNotContains($this->retailProduct->id, array_column($wholesaleOffers['products'], 'id'));
    }

    public function test_brands_categories_and_vendor_counts_are_module_scoped(): void
    {
        $retailBrands = $this->decode($this->app->make(RetailListBrandsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_BRAND_NAME,
            'limit' => 10,
        ])));
        $wholesaleBrands = $this->decode($this->app->make(WholesaleListBrandsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_BRAND_NAME,
            'limit' => 10,
        ])));

        $this->assertSame(1, $retailBrands['total']);
        $this->assertSame(1, $retailBrands['brands'][0]['product_count']);
        $this->assertSame(1, $wholesaleBrands['total']);
        $this->assertSame(1, $wholesaleBrands['brands'][0]['product_count']);

        $retailVendors = $this->decode($this->app->make(RetailListVendorsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Crosscheck Shop',
            'limit' => 10,
        ])));
        $wholesaleVendors = $this->decode($this->app->make(WholesaleListVendorsTool::class)->handle(new ToolRequest([
            'query' => 'Isolation Crosscheck Shop',
            'limit' => 10,
        ])));

        $this->assertSame(1, $retailVendors['total']);
        $this->assertSame(1, $retailVendors['vendors'][0]['product_count']);
        $this->assertSame(1, $wholesaleVendors['total']);
        $this->assertSame(1, $wholesaleVendors['vendors'][0]['product_count']);
    }

    public function test_cart_tools_see_only_their_own_module_lines(): void
    {
        $customer = $this->customer();

        $this->context($customer, null);
        $retailAdd = $this->decode($this->app->make(RetailAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->retailProduct->id,
            'quantity' => 1,
        ])));
        $this->assertSame('added', $retailAdd['status']);

        $this->context($customer, null);
        $wholesaleAdd = $this->decode($this->app->make(WholesaleAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'quantity' => 10,
        ])));
        $this->assertSame('added', $wholesaleAdd['status']);

        $this->assertSame(2, Cart::query()->where('customer_id', $customer->id)->count());

        $retailCartService = $this->app->make(RetailCartService::class);
        $wholesaleCartService = $this->app->make(WholesaleCartService::class);

        $this->assertSame(1, $retailCartService->count(filters: ['customer_id' => $customer->id, 'is_guest' => RetailCartService::CUSTOMER]));
        $this->assertSame(1, $wholesaleCartService->count(filters: ['customer_id' => $customer->id, 'is_guest' => RetailCartService::CUSTOMER]));

        $this->context($customer, null);
        $retailCheckout = $this->decode($this->app->make(RetailCheckoutTool::class)->handle(new ToolRequest([])));
        $this->assertSame('checkout', $retailCheckout['status']);

        $this->context($customer, null);
        $wholesaleCheckout = $this->decode($this->app->make(WholesaleCheckoutTool::class)->handle(new ToolRequest([])));
        $this->assertSame('checkout', $wholesaleCheckout['status']);

        $this->context($customer, null);
        $crossUpdate = $this->decode($this->app->make(RetailUpdateCartQuantityTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'quantity' => 20,
        ])));
        $this->assertSame('not_found', $crossUpdate['status']);

        $this->context($customer, null);
        $crossUpdateReverse = $this->decode($this->app->make(WholesaleUpdateCartQuantityTool::class)->handle(new ToolRequest([
            'product_id' => $this->retailProduct->id,
            'quantity' => 2,
        ])));
        $this->assertSame('not_found', $crossUpdateReverse['status']);
    }

    #[RunInSeparateProcess]
    public function test_minimum_order_enforcement_does_not_cross_modules(): void
    {
        $this->setBusinessSetting('minimum_order_amount_status', 1);
        $this->setBusinessSetting('minimum_order_amount', 999999);
        $this->setBusinessSetting('minimum_order_amount_by_seller', 0);

        $inHouseRetailProduct = Product::create([
            'name' => self::SHARED_KEYWORD.' In House',
            'slug' => 'isolation-crosscheck-inhouse',
            'added_by' => 'admin',
            'user_id' => 1,
            'category_id' => null,
            'category_ids' => json_encode([]),
            'brand_id' => null,
            'unit_price' => 25.0,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 40,
            'minimum_order_qty' => 1,
            'status' => 1,
            'request_status' => 1,
            'business_module' => BusinessModule::RETAIL,
            'product_type' => 'physical',
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
        ]);

        $customer = $this->customer();

        $this->context($customer, null);
        $this->app->make(RetailAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $inHouseRetailProduct->id,
            'quantity' => 1,
        ]));

        $this->context($customer, null);
        $this->app->make(WholesaleAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'quantity' => 10,
        ]));

        $this->context($customer, null);
        $retailCheckout = $this->decode($this->app->make(RetailCheckoutTool::class)->handle(new ToolRequest([])));
        $this->assertSame('needs_minimum', $retailCheckout['status']);

        $this->context($customer, null);
        $wholesaleCheckout = $this->decode($this->app->make(WholesaleCheckoutTool::class)->handle(new ToolRequest([])));
        $this->assertSame('checkout', $wholesaleCheckout['status']);
    }

    public function test_add_to_cart_cross_module_id_attack_returns_not_found_and_writes_no_row(): void
    {
        $guestId = 'guest-crosscheck-attack';
        $this->context(null, $guestId);
        $retailAttempt = $this->decode($this->app->make(RetailAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->wholesaleProduct->id,
            'quantity' => 1,
        ])));
        $this->assertSame('not_found', $retailAttempt['status']);
        $this->assertDatabaseMissing('carts', ['product_id' => $this->wholesaleProduct->id, 'customer_id' => $guestId]);

        $this->context(null, $guestId);
        $wholesaleAttempt = $this->decode($this->app->make(WholesaleAddToCartTool::class)->handle(new ToolRequest([
            'product_id' => $this->retailProduct->id,
            'quantity' => 1,
        ])));
        $this->assertSame('not_found', $wholesaleAttempt['status']);
        $this->assertDatabaseMissing('carts', ['product_id' => $this->retailProduct->id, 'customer_id' => $guestId]);

        $this->assertSame(0, Cart::query()->where('customer_id', $guestId)->count());
    }

    public function test_sessions_are_isolated_by_module_across_all_surfaces(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->mock(AgentLoopService::class)->shouldNotReceive('run');

        $guestId = 'guest-crosscheck-session';

        $retailSession = AiChatSession::query()->create([
            'guest_id' => $guestId,
            'business_module' => BusinessModule::RETAIL,
            'locale' => 'en',
            'last_activity_at' => now(),
        ]);
        $retailSession->messages()->create(['role' => 'user', 'content' => 'hi']);

        $wholesaleSession = AiChatSession::query()->create([
            'guest_id' => $guestId,
            'business_module' => BusinessModule::WHOLESALE,
            'locale' => 'en',
            'last_activity_at' => now(),
        ]);
        $wholesaleSession->messages()->create(['role' => 'user', 'content' => 'hi']);

        $query = '?guest_id='.$guestId;

        foreach (['ai/assistant', 'api/v6/ai/assistant', 'api/v1/ai/assistant'] as $retailPrefix) {
            $this->getJson($retailPrefix.'/sessions/'.$wholesaleSession->id.$query)->assertNotFound();
            $this->postJson($retailPrefix.'/sessions/'.$wholesaleSession->id.'/message', ['guest_id' => $guestId, 'message' => 'hi'])->assertNotFound();
            $this->deleteJson($retailPrefix.'/sessions/'.$wholesaleSession->id.$query)->assertNotFound();

            $listed = $this->getJson($retailPrefix.'/sessions'.$query)->assertOk()->json();
            $sessions = $listed['sessions'] ?? $listed['content']['sessions'];
            $this->assertSame([$retailSession->id], array_column($sessions, 'id'));
        }

        foreach (['wholesale/ai/assistant', 'api/v6/wholesale/ai/assistant'] as $wholesalePrefix) {
            $this->getJson($wholesalePrefix.'/sessions/'.$retailSession->id.$query)->assertNotFound();
            $this->postJson($wholesalePrefix.'/sessions/'.$retailSession->id.'/message', ['guest_id' => $guestId, 'message' => 'hi'])->assertNotFound();
            $this->deleteJson($wholesalePrefix.'/sessions/'.$retailSession->id.$query)->assertNotFound();

            $listed = $this->getJson($wholesalePrefix.'/sessions'.$query)->assertOk()->json();
            $sessions = $listed['sessions'] ?? $listed['content']['sessions'];
            $this->assertSame([$wholesaleSession->id], array_column($sessions, 'id'));
        }

        $this->assertNotNull(AiChatSession::query()->find($retailSession->id));
        $this->assertNotNull(AiChatSession::query()->find($wholesaleSession->id));
    }

    public function test_response_and_tool_caches_are_keyed_by_module(): void
    {
        $fingerprint = md5(app()->getLocale().'||show me isolation items|');
        Cache::put('ai_response_global:'.BusinessModule::RETAIL.':'.$fingerprint, ['reply' => 'retail-cached'], 60);
        Cache::put('ai_response_global:'.BusinessModule::WHOLESALE.':'.$fingerprint, ['reply' => 'wholesale-cached'], 60);

        $agentLoop = $this->app->make(AgentLoopService::class);
        $retailResult = $agentLoop->run(history: [], newMessage: 'show me isolation items', systemPrompt: '', tools: [], businessModule: BusinessModule::RETAIL);
        $wholesaleResult = $agentLoop->run(history: [], newMessage: 'show me isolation items', systemPrompt: '', tools: [], businessModule: BusinessModule::WHOLESALE);

        $this->assertSame('retail-cached', $retailResult['reply']);
        $this->assertSame('wholesale-cached', $wholesaleResult['reply']);

        $retailFingerprint = $this->searchFingerprint();
        $retailSearch = $this->decode($this->app->make(RetailSearchProductsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_KEYWORD,
            'limit' => 10,
        ])));
        $wholesaleFingerprint = $this->searchFingerprint();
        $wholesaleSearch = $this->decode($this->app->make(WholesaleSearchProductsTool::class)->handle(new ToolRequest([
            'query' => self::SHARED_KEYWORD,
            'limit' => 10,
        ])));

        $this->assertNotSame(
            array_column($retailSearch['products'], 'id'),
            array_column($wholesaleSearch['products'], 'id'),
        );

        $this->assertNotNull(Cache::get('ai_search:'.BusinessModule::RETAIL.':'.$retailFingerprint));
        $this->assertNotNull(Cache::get('ai_search:'.BusinessModule::WHOLESALE.':'.$wholesaleFingerprint));
        $this->assertNull(Cache::get('ai_search:'.BusinessModule::WHOLESALE.':'.$retailFingerprint));
    }

    public function test_recall_cannot_cross_modules(): void
    {
        $this->mock(AgentLoopService::class)
            ->shouldReceive('run')
            ->once()
            ->andReturn(['reply' => 'ok', 'products' => [], 'tools_used' => [], 'actions' => []]);

        $session = AiChatSession::query()->create([
            'guest_id' => 'guest-crosscheck-recall',
            'business_module' => BusinessModule::WHOLESALE,
            'locale' => 'en',
            'last_activity_at' => now(),
        ]);

        $session->messages()->create([
            'role' => 'assistant',
            'content' => 'Here you go',
            'meta' => ['products' => [
                ['id' => $this->retailProduct->id, 'name' => $this->retailProduct->name],
                ['id' => $this->wholesaleProduct->id, 'name' => $this->wholesaleProduct->name],
            ]],
        ]);

        $storefront = $this->app->make(WholesaleShoppingStorefront::class);
        $turn = new ShoppingAssistantTurnRequestedEvent(sessionId: $session->id, content: 'show the others', imageUrl: null, storefront: $storefront);
        event($turn);

        $shown = $this->app->make(ShownProductStore::class)->all();
        $this->assertSame([$this->wholesaleProduct->id], array_column($shown, 'id'));

        $recall = $this->decode($this->app->make(RecallShownProductsTool::class)->handle(new ToolRequest([
            'product_ids' => [$this->retailProduct->id],
        ])));
        $this->assertSame('none', $recall['status']);

        $recallWholesale = $this->decode($this->app->make(RecallShownProductsTool::class)->handle(new ToolRequest([
            'product_ids' => [$this->wholesaleProduct->id],
        ])));
        $this->assertSame('shown', $recallWholesale['status']);
        $this->assertSame([$this->wholesaleProduct->id], array_column($recallWholesale['products'], 'id'));
    }

    private function searchFingerprint(): string
    {
        $locale = app()->getLocale();
        $currency = (string) session('currency_code', '');
        $query = strtolower(self::SHARED_KEYWORD);

        return md5($locale.'|'.$currency.'|'.$query.'||10|||||');
    }

    private function decode(string $json): array
    {
        return json_decode($json, associative: true);
    }

    private function context(?User $customer, ?string $guestId): void
    {
        $this->app->make(CartSelectionContext::class)->set($customer, $guestId, null);
        $this->app->make(CartActionCollector::class)->reset();
    }

    private function customer(): User
    {
        static $sequence = 0;
        $sequence++;

        return User::query()->create([
            'name' => 'Crosscheck Customer '.$sequence,
            'email' => 'crosscheck-customer-'.$sequence.'-'.uniqid().'@example.test',
            'phone' => '1000000'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);
    }

    private function setBusinessSetting(string $type, mixed $value): void
    {
        $setting = BusinessSetting::query()->updateOrCreate(['type' => $type], ['value' => $value]);

        getWebConfig(name: 'company_name');

        $collection = (new ReflectionFunction('getWebConfig'))->getStaticVariables()['businessSettings'];
        $existing = $collection?->firstWhere('type', $type);
        if ($existing) {
            $existing->value = $value;
        } else {
            $collection?->push($setting);
        }
    }
}
