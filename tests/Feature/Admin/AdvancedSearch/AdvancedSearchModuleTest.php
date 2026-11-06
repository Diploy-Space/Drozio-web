<?php

namespace Tests\Feature\Admin\AdvancedSearch;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Product;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\ProductRetailService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Auction\app\Enums\ApprovalStatus;
use Modules\Auction\app\Enums\ItemCondition;
use Modules\Auction\app\Enums\OwnerType;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class AdvancedSearchModuleTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PROBE = 'Zqxsearchprobe';

    private const SUPER_ADMIN_ID = 1;

    private const FOREIGN_PREFIXES = [
        BusinessModule::RETAIL => ['admin/wholesale/', 'admin/auction/'],
        BusinessModule::WHOLESALE => ['admin/retail/', 'admin/auction/'],
        BusinessModule::AUCTION => ['admin/retail/', 'admin/wholesale/'],
    ];

    private const NON_ENGLISH_LOCALE = 'bd';

    private const NON_ENGLISH_MENU_KEYWORD = 'ইমেইল টেমপ্লেট';

    private const NON_ENGLISH_MENU_TITLE = 'Email template';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_retail_active_lists_retail_products_first_then_other_modules_products(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk();

        $this->assertSame(
            [$this->retailProductUri(product: $retailProduct), $this->wholesaleProductUri(product: $wholesaleProduct)],
            $this->productUris($response),
        );
        $this->assertNoForeignUris(uris: $this->recordUris($response), businessModule: BusinessModule::RETAIL);
        $this->assertSame(self::PROBE, $response->json('keyword'));
        $this->assertIsString($response->json('htmlView'));
    }

    public function test_menus_come_from_every_active_module_current_first(): void
    {
        $retailUri = $this->relativeRoute(name: 'admin.retail.products.stock-limit-list');
        $wholesaleUri = $this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list');

        $retailFirst = $this->menuUris($this->search(query: ['keyword' => 'Limited stock', 'business_module' => BusinessModule::RETAIL])->assertOk());
        $wholesaleFirst = $this->menuUris($this->search(query: ['keyword' => 'Limited stock', 'business_module' => BusinessModule::WHOLESALE])->assertOk());

        $this->assertLessThan(array_search($wholesaleUri, $retailFirst, true), array_search($retailUri, $retailFirst, true));
        $this->assertLessThan(array_search($retailUri, $wholesaleFirst, true), array_search($wholesaleUri, $wholesaleFirst, true));

        foreach (['Products', 'Category', 'Report', 'Coupon'] as $keyword) {
            $response = $this->search(query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL])->assertOk();

            $this->assertNotEmpty($this->resultUris($response), "no result for {$keyword}");
            $this->assertNoForeignUris(uris: $this->recordUris($response), businessModule: BusinessModule::RETAIL);
        }
    }

    public function test_naming_a_module_limits_menus_to_that_module(): void
    {
        $uris = $this->menuUris($this->search(query: ['keyword' => 'Wholesale limited stock', 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertContains($this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list'), $uris);
        $this->assertNotContains($this->relativeRoute(name: 'admin.retail.products.stock-limit-list'), $uris);
    }

    public function test_module_results_carry_their_module_label_and_common_ones_do_not(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $orderMenus = $this->titlesByUri($this->search(query: ['keyword' => 'All orders', 'business_module' => BusinessModule::RETAIL])->assertOk(), type: 'menu');
        $earningMenus = $this->titlesByUri($this->search(query: ['keyword' => 'Earning', 'business_module' => BusinessModule::RETAIL])->assertOk(), type: 'menu');
        $products = $this->titlesByUri($this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk(), type: 'products');
        $commonMenus = $this->titlesByUri($this->search(query: ['keyword' => 'Support ticket', 'business_module' => BusinessModule::RETAIL])->assertOk(), type: 'menu');

        $this->assertSame('Retail › Orders › All', $orderMenus[$this->orderListUri(businessModule: BusinessModule::RETAIL)]);
        $this->assertSame('Wholesale › Orders › All', $orderMenus[$this->orderListUri(businessModule: BusinessModule::WHOLESALE)]);
        $this->assertStringStartsWith('Retail › ', $earningMenus[$this->relativeRoute(name: 'admin.report.retail.admin-earning')]);
        $this->assertStringStartsWith('Wholesale › ', $earningMenus[$this->relativeRoute(name: 'admin.report.wholesale.admin-earning')]);
        $this->assertStringStartsWith('Retail › ', $products[$this->retailProductUri(product: $retailProduct)]);
        $this->assertStringStartsWith('Wholesale › ', $products[$this->wholesaleProductUri(product: $wholesaleProduct)]);
        $this->assertSame('Support ticket', $commonMenus[$this->relativeRoute(name: 'admin.people.support-ticket.view')]);
    }

    public function test_pages_match_the_title_of_the_menu_that_opens_them(): void
    {
        $pages = $this->resultItems($this->search(query: ['keyword' => 'All orders', 'business_module' => BusinessModule::RETAIL])->assertOk())
            ->where('type', 'page')
            ->pluck('uri')
            ->all();

        $this->assertContains($this->orderListUri(businessModule: BusinessModule::RETAIL), $pages);
        $this->assertContains($this->orderListUri(businessModule: BusinessModule::WHOLESALE), $pages);
    }

    public function test_inactive_module_contributes_no_menu_page_or_product(): void
    {
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);
        $this->makeModulesUnavailable(keys: [BusinessModule::WHOLESALE]);

        foreach (['All orders', 'Limited stock', 'Coupon', self::PROBE] as $keyword) {
            $uris = $this->resultUris($this->search(query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL])->assertOk());

            $this->assertSame([], array_values(array_filter($uris, fn (string $uri): bool => str_contains($uri, 'wholesale'))), "{$keyword} returned an inactive module's result");
        }

        $this->assertNotContains($this->wholesaleProductUri(product: $wholesaleProduct), $this->resultUris($this->search(query: ['keyword' => self::PROBE])->assertOk()));
    }

    public function test_employee_sees_another_modules_menu_when_the_role_allows_that_feature(): void
    {
        $employee = $this->employeeHolding(permissions: ['retail.orders', 'wholesale.orders']);

        $uris = $this->menuUris($this->search(query: ['keyword' => 'All orders', 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk());

        $this->assertContains($this->orderListUri(businessModule: BusinessModule::RETAIL), $uris);
        $this->assertContains($this->orderListUri(businessModule: BusinessModule::WHOLESALE), $uris);
    }

    public function test_employee_without_the_feature_permission_never_sees_that_modules_menu(): void
    {
        $employee = $this->employeeHolding(permissions: ['retail.dashboard', 'retail.orders', 'wholesale.dashboard', 'wholesale.catalog']);

        $uris = $this->resultUris($this->search(query: ['keyword' => 'All orders', 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk());

        $this->assertContains($this->orderListUri(businessModule: BusinessModule::RETAIL), $uris);
        $this->assertSame([], array_values(array_filter($uris, fn (string $uri): bool => str_starts_with($uri, 'admin/wholesale/orders/'))));

        $catalogUris = $this->menuUris($this->search(query: ['keyword' => 'Limited stock', 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk());

        $this->assertContains($this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list'), $catalogUris);
        $this->assertNotContains($this->relativeRoute(name: 'admin.retail.products.stock-limit-list'), $catalogUris);
    }

    public function test_wholesale_active_lists_wholesale_products_first_and_its_own_menu_pages(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::WHOLESALE])->assertOk();

        $this->assertSame(
            [$this->wholesaleProductUri(product: $wholesaleProduct), $this->retailProductUri(product: $retailProduct)],
            $this->productUris($response),
        );
        $this->assertNoForeignUris(uris: $this->recordUris($response), businessModule: BusinessModule::WHOLESALE);

        $menuResponse = $this->search(query: ['keyword' => 'Limited stock', 'business_module' => BusinessModule::WHOLESALE])->assertOk();

        $this->assertSame($this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list'), $this->menuUris($menuResponse)[0]);
        $this->assertNoForeignUris(uris: $this->recordUris($menuResponse), businessModule: BusinessModule::WHOLESALE);
        $this->assertSame(
            [BusinessModule::WHOLESALE],
            $this->resultItems($menuResponse)->where('type', 'menu')->where('uri', $this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list'))->pluck('business_module')->unique()->values()->all(),
        );
    }

    public function test_auction_active_finds_auction_products_categories_and_brands(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }

        $auction = $this->createAuctionFixtures();
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::AUCTION])->assertOk();
        $uris = $this->resultUris($response);

        $this->assertContains($this->relativeRoute(name: 'admin.auction.products.view', parameters: ['id' => $auction['product_id']]), $uris);
        $this->assertContains($this->relativeRoute(name: 'admin.auction.category.view', parameters: ['searchValue' => self::PROBE]), $uris);
        $this->assertContains($this->relativeRoute(name: 'admin.auction.brand.update', parameters: ['id' => $auction['brand_id']]), $uris);
        $this->assertContains($this->retailProductUri(product: $retailProduct), $uris);
        $this->assertContains($this->wholesaleProductUri(product: $wholesaleProduct), $uris);
        $this->assertSame($this->relativeRoute(name: 'admin.auction.products.view', parameters: ['id' => $auction['product_id']]), $this->productUris($response)[0]);
        $this->assertNoForeignUris(uris: $this->recordUris($response), businessModule: BusinessModule::AUCTION);
    }

    public function test_unpublished_wholesale_addon_falls_back_to_the_default_module(): void
    {
        $this->makeModulesUnavailable(keys: [BusinessModule::WHOLESALE]);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);

        $uris = $this->resultUris($this->search(query: ['keyword' => self::PROBE], session: [$this->sessionKey() => BusinessModule::WHOLESALE])->assertOk());

        $this->assertContains($this->retailProductUri(product: $retailProduct), $uris);
        $this->assertNotContains($this->wholesaleProductUri(product: $wholesaleProduct), $uris);
        $this->assertNoForeignUris(uris: $uris, businessModule: BusinessModule::RETAIL);

        $postedUris = $this->resultUris($this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::WHOLESALE])->assertOk());

        $this->assertNotContains($this->wholesaleProductUri(product: $wholesaleProduct), $postedUris);
        $this->assertNoForeignUris(uris: $postedUris, businessModule: BusinessModule::RETAIL);
    }

    public function test_unavailable_auction_addon_falls_back_to_the_default_module(): void
    {
        $this->makeModulesUnavailable(keys: [BusinessModule::AUCTION]);
        $auction = $this->createAuctionFixtures();
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);

        $uris = $this->resultUris($this->search(query: ['keyword' => self::PROBE], session: [$this->sessionKey() => BusinessModule::AUCTION])->assertOk());

        $this->assertContains($this->retailProductUri(product: $retailProduct), $uris);
        $this->assertNotContains($this->relativeRoute(name: 'admin.auction.products.view', parameters: ['id' => $auction['product_id']]), $uris);
        $this->assertNoForeignUris(uris: $uris, businessModule: BusinessModule::RETAIL);
    }

    public function test_common_menus_link_into_an_available_addon(): void
    {
        $uris = $this->resultUris($this->search(query: ['keyword' => 'pages and media', 'business_module' => BusinessModule::RETAIL])->assertOk());

        foreach ($this->addonContentPages() as $uri) {
            $this->assertContains($uri, $uris);
        }
    }

    public function test_common_menus_never_link_into_an_unavailable_addon(): void
    {
        $this->makeModulesUnavailable(keys: array_keys($this->addonContentPages()));

        $uris = $this->resultUris($this->search(query: ['keyword' => 'pages and media', 'business_module' => BusinessModule::RETAIL])->assertOk());

        foreach ($this->addonContentPages() as $uri) {
            $this->assertNotContains($uri, $uris);
        }
    }

    public function test_recent_search_into_an_unavailable_addon_is_hidden_and_cannot_be_reopened(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();
        $addonPages = $this->addonContentPages();

        foreach ($addonPages as $uri) {
            $this->saveRecent(uri: $uri, title: 'Pages & Media', businessModule: BusinessModule::COMMON)->assertRedirect(url($uri));
        }

        $this->makeModulesUnavailable(keys: array_keys($addonPages));

        $recentUris = $this->resultUris($this->search(query: ['business_module' => BusinessModule::RETAIL])->assertOk());

        foreach ($addonPages as $uri) {
            $this->assertNotContains($uri, $recentUris);
            $this->assertValidationErrorOn(
                response: $this->actingAs($this->superAdmin(), 'admin')
                    ->postJson(route('admin.advanced-search-recent'), $this->recentPayload(uri: $uri, title: 'Pages & Media', businessModule: BusinessModule::COMMON)),
                field: 'routeUri',
            );
        }
    }

    public function test_unavailable_auction_addon_admin_pages_return_not_found(): void
    {
        $this->makeModulesUnavailable(keys: [BusinessModule::AUCTION]);

        $this->actingAs($this->superAdmin(), 'admin')
            ->get(route('admin.settings.auction.hero-section'))
            ->assertNotFound();
    }

    public function test_session_module_is_used_when_nothing_is_posted(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => self::PROBE], session: [$this->sessionKey() => BusinessModule::WHOLESALE])->assertOk();

        $this->assertSame(
            [$this->wholesaleProductUri(product: $wholesaleProduct), $this->retailProductUri(product: $retailProduct)],
            $this->productUris($response),
        );
    }

    public function test_posted_available_module_wins_over_the_session(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(
            query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::WHOLESALE],
            session: [$this->sessionKey() => BusinessModule::RETAIL],
        )->assertOk();

        $this->assertSame(
            [$this->wholesaleProductUri(product: $wholesaleProduct), $this->retailProductUri(product: $retailProduct)],
            $this->productUris($response),
        );
        $this->assertNoForeignUris(uris: $this->recordUris($response), businessModule: BusinessModule::WHOLESALE);
    }

    public function test_naming_a_module_limits_products_to_that_module(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => 'Wholesale '.self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk();

        $this->assertSame([$this->wholesaleProductUri(product: $wholesaleProduct)], $this->productUris($response));
        $this->assertNotContains($this->retailProductUri(product: $retailProduct), $this->resultUris($response));
    }

    public function test_posted_module_the_admin_is_not_permitted_to_use_is_ignored(): void
    {
        $employee = $this->employeeHolding(permissions: ['people.customers']);
        $this->createCustomer();
        $wholesaleProduct = $this->createProduct(businessModule: BusinessModule::WHOLESALE);

        $response = $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::WHOLESALE], admin: $employee)->assertOk();
        $items = $this->resultItems($response);

        $this->assertNotContains($this->wholesaleProductUri(product: $wholesaleProduct), $items->pluck('uri')->all());
        $this->assertNotEmpty($items->where('type', 'customers')->all());
        $this->assertSame([BusinessModule::COMMON], $items->where('type', 'customers')->pluck('business_module')->unique()->values()->all());
    }

    public function test_common_source_results_are_tagged_common_and_their_recent_shows_in_every_module(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();
        $customerId = $this->createCustomer();
        $customerUri = $this->relativeRoute(name: 'admin.people.customer.view', parameters: ['user_id' => $customerId]);

        $customers = $this->resultItems($this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::WHOLESALE])->assertOk())
            ->where('type', 'customers');

        $this->assertContains($customerUri, $customers->pluck('uri')->all());
        $this->assertSame([BusinessModule::COMMON], $customers->pluck('business_module')->unique()->values()->all());

        $this->saveRecent(uri: $customerUri, title: self::PROBE.' Customer', businessModule: BusinessModule::COMMON, type: 'customers')->assertRedirect(url($customerUri));

        foreach ([BusinessModule::RETAIL, BusinessModule::WHOLESALE] as $businessModule) {
            $recentCustomers = $this->resultItems($this->search(query: ['business_module' => $businessModule])->assertOk())->where('type', 'customers');

            $this->assertContains($customerUri, $recentCustomers->pluck('uri')->all(), "common recent missing from {$businessModule}");
        }
    }

    public function test_unknown_posted_module_is_rejected(): void
    {
        $this->assertValidationErrorOn(
            response: $this->search(query: ['keyword' => self::PROBE, 'business_module' => 'marketplace']),
            field: 'business_module',
        );
    }

    public function test_customers_only_role_sees_customers_and_nothing_from_catalog_or_orders(): void
    {
        $employee = $this->employeeHolding(permissions: ['people.customers']);
        $customerId = $this->createCustomer();
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);

        $items = $this->resultItems($this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk());

        $this->assertSame(['customers'], $items->pluck('type')->unique()->values()->all());
        $this->assertContains($this->relativeRoute(name: 'admin.people.customer.view', parameters: ['user_id' => $customerId]), $items->pluck('uri')->all());
        $this->assertNotContains($this->retailProductUri(product: $retailProduct), $items->pluck('uri')->all());

        foreach (['Products', 'Orders', 'Category'] as $keyword) {
            $types = $this->resultItems($this->search(query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk())
                ->pluck('type')->unique()->all();

            $this->assertEmpty(array_intersect($types, ['products', 'categories', 'sub_categories', 'sub_sub_categories', 'brands', 'orders', 'refund_requests', 'coupons']), "{$keyword} leaked catalog or order results");
        }
    }

    public function test_content_only_role_finds_a_content_settings_page(): void
    {
        $employee = $this->employeeHolding(permissions: ['settings.content']);
        $contentPageUri = $this->relativeRoute(name: 'admin.settings.pages-and-media.social-media');

        $pageUris = $this->resultItems($this->search(query: ['keyword' => 'Social Media', 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk())
            ->where('type', 'page')->pluck('uri')->all();

        $this->assertContains($contentPageUri, $pageUris);
    }

    public function test_pos_only_role_gets_no_retail_catalog_pages(): void
    {
        $employee = $this->employeeHolding(permissions: ['retail.pos']);
        $posUri = $this->relativeRoute(name: 'admin.retail.pos.index');

        foreach (['Category', 'Brand List', 'Product Gallery'] as $keyword) {
            $retailUris = $this->resultItems($this->search(query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk())
                ->pluck('uri')
                ->filter(fn (string $uri): bool => str_starts_with($uri, 'admin/retail/') && $uri !== $posUri)
                ->all();

            $this->assertEmpty($retailUris, "{$keyword} returned retail pages the role cannot open");
        }

        $posUris = $this->resultItems($this->search(query: ['keyword' => 'POS', 'business_module' => BusinessModule::RETAIL], admin: $employee)->assertOk())
            ->where('type', 'page')->pluck('uri')->all();

        $this->assertContains($posUri, $posUris);
    }

    public function test_recent_search_rejects_an_unknown_type(): void
    {
        $uri = $this->relativeRoute(name: 'admin.people.customer.list');

        $this->assertValidationErrorOn(
            response: $this->actingAs($this->superAdmin(), 'admin')
                ->postJson(route('admin.advanced-search-recent'), $this->recentPayload(uri: $uri, title: 'Customer list', businessModule: BusinessModule::COMMON, type: 'not_a_source')),
            field: 'type',
        );
    }

    public function test_recent_search_stores_a_server_built_response(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();
        $uri = $this->relativeRoute(name: 'admin.people.customer.list');

        $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.advanced-search-recent'), [
                ...$this->recentPayload(uri: $uri, title: 'Customer list', businessModule: BusinessModule::COMMON),
                'response' => json_encode(['type' => '<script>', 'priority' => 0, 'injected' => true]),
            ])
            ->assertRedirect(url($uri));

        $stored = json_decode((string) DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->where('route_uri', $uri)->value('response'), true);

        $this->assertSame('menu', $stored['type']);
        $this->assertSame(1, $stored['priority']);
        $this->assertArrayNotHasKey('injected', $stored);
    }

    public function test_stored_recent_with_an_unknown_type_is_dropped(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();
        $uri = $this->relativeRoute(name: 'admin.people.customer.list');

        DB::table('recent_searches')->insert([
            'user_id' => self::SUPER_ADMIN_ID,
            'user_type' => 'admin',
            'business_module' => BusinessModule::COMMON,
            'route_uri' => $uri,
            'title' => 'Customer list',
            'response' => json_encode(['page_title_value' => 'Customer list', 'uri' => $uri, 'type' => '<b>injected</b>', 'priority' => 1]),
            'route_full_url' => url($uri),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->search(query: ['business_module' => BusinessModule::RETAIL])->assertOk();

        $this->assertSame([], $response->json('result'));
        $this->assertStringNotContainsString('injected', (string) $response->json('htmlView'));
    }

    public function test_group_headers_come_from_the_registry_labels(): void
    {
        $this->createCustomer();

        $html = (string) $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk()->json('htmlView');

        $this->assertStringContainsString(translate('Customers'), $html);
        $this->assertStringNotContainsString('>customers<', preg_replace('/\s+/', '', $html));
    }

    public function test_registry_source_with_a_missing_route_is_skipped(): void
    {
        $retailProduct = $this->createProduct(businessModule: BusinessModule::RETAIL);

        config()->set('advanced_search.sources.'.BusinessModule::RETAIL.'.probe', [
            'service' => ProductRetailService::class,
            'permission' => 'retail.catalog',
            'title' => ['name'],
            'image' => null,
            'image_type' => 'product',
            'route' => 'admin.does-not-exist',
            'route_parameters' => ['id' => 'id'],
            'filters' => [],
            'data_limit' => 5,
        ]);

        $response = $this->search(query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk();

        $this->assertEmpty($this->resultItems($response)->where('type', 'probe')->all());
        $this->assertContains($this->retailProductUri(product: $retailProduct), $this->resultUris($response));
    }

    public function test_recent_searches_show_every_active_modules_items_in_any_workspace(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();

        $wholesaleUri = $this->relativeRoute(name: 'admin.wholesale.products.stock-limit-list');
        $commonUri = $this->relativeRoute(name: 'admin.people.customer.list');

        $this->insertRecent(uri: $wholesaleUri, title: 'Wholesale › Limited stock', businessModule: BusinessModule::WHOLESALE);
        $this->insertRecent(uri: $commonUri, title: 'Customer list', businessModule: BusinessModule::COMMON);

        $retailResponse = $this->search(query: ['keyword' => '', 'business_module' => BusinessModule::RETAIL])->assertOk();
        $retailUris = $this->resultUris($retailResponse);

        $this->assertSame('', $retailResponse->json('keyword'));
        $this->assertContains($commonUri, $retailUris);
        $this->assertContains($wholesaleUri, $retailUris);

        $wholesaleUris = $this->resultUris($this->search(query: ['business_module' => BusinessModule::WHOLESALE])->assertOk());

        $this->assertContains($commonUri, $wholesaleUris);
        $this->assertContains($wholesaleUri, $wholesaleUris);

        $this->makeModulesUnavailable(keys: [BusinessModule::WHOLESALE]);

        $this->assertNotContains($wholesaleUri, $this->resultUris($this->search(query: ['keyword' => '', 'business_module' => BusinessModule::RETAIL])->assertOk()));
    }

    public function test_recent_search_rejects_absolute_urls_and_never_redirects_off_site(): void
    {
        DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->delete();
        $previousUrl = route('admin.retail.dashboard.index');

        foreach (['https://evil.example/phish', '//evil.example/phish', 'javascript:alert(1)', '\\\\evil.example'] as $uri) {
            $response = $this->actingAs($this->superAdmin(), 'admin')
                ->from($previousUrl)
                ->post(route('admin.advanced-search-recent'), $this->recentPayload(uri: $uri, title: 'Phish', businessModule: BusinessModule::RETAIL));

            $response->assertRedirect($previousUrl)->assertSessionHasErrors(['routeUri']);
            $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));

            $this->assertValidationErrorOn(
                response: $this->actingAs($this->superAdmin(), 'admin')
                    ->postJson(route('admin.advanced-search-recent'), $this->recentPayload(uri: $uri, title: 'Phish', businessModule: BusinessModule::RETAIL)),
                field: 'routeUri',
            );
        }

        $this->assertSame(0, DB::table('recent_searches')->where('user_id', self::SUPER_ADMIN_ID)->where('user_type', 'admin')->count());
    }

    public function test_keyword_without_a_match_returns_the_empty_state(): void
    {
        $response = $this->search(query: ['keyword' => 'Qzvxnomatchprobe', 'business_module' => BusinessModule::RETAIL])->assertOk();

        $this->assertSame([], $response->json('result'));
        $this->assertIsString($response->json('htmlView'));
    }

    public function test_keywords_with_wildcard_path_and_regex_characters_run_without_error(): void
    {
        foreach (['%', '_', '/', '50%_off/', '(.*', '[a-z]+', '\\', '^$|?', "O'Brien", '/admin/../'] as $keyword) {
            foreach (BusinessModule::values() as $businessModule) {
                $this->search(query: ['keyword' => $keyword, 'business_module' => $businessModule])->assertOk()->assertJsonStructure(['keyword', 'result', 'htmlView']);
            }
        }
    }

    public function test_keyword_longer_than_255_characters_is_rejected(): void
    {
        $this->assertValidationErrorOn(response: $this->search(query: ['keyword' => str_repeat('a', 256)]), field: 'keyword');

        $this->search(query: ['keyword' => str_repeat('a', 255)])->assertOk();
    }

    public function test_non_english_default_language_still_matches_menus(): void
    {
        $response = $this->search(
            query: ['keyword' => self::NON_ENGLISH_MENU_KEYWORD, 'business_module' => BusinessModule::RETAIL],
            session: ['local' => self::NON_ENGLISH_LOCALE],
        )->assertOk();

        $this->assertContains(self::NON_ENGLISH_MENU_TITLE, $this->resultItems($response)->where('type', 'menu')->pluck('page_title')->all());
    }

    private function search(array $query, array $session = [], ?Admin $admin = null): TestResponse
    {
        return $this->actingAs($admin ?? $this->superAdmin(), 'admin')
            ->withSession($session)
            ->getJson(route('admin.v2.advanced-search', $query));
    }

    private function saveRecent(string $uri, string $title, string $businessModule, string $type = 'menu'): TestResponse
    {
        return $this->actingAs($this->superAdmin(), 'admin')
            ->post(route('admin.advanced-search-recent'), $this->recentPayload(uri: $uri, title: $title, businessModule: $businessModule, type: $type));
    }

    private function recentPayload(string $uri, string $title, string $businessModule, string $type = 'menu'): array
    {
        return [
            'routeName' => $title,
            'routeUri' => $uri,
            'searchKeyword' => '',
            'business_module' => $businessModule,
            'type' => $type,
            'image' => '',
        ];
    }

    private function resultItems(TestResponse $response): Collection
    {
        return collect($response->json('result'))->flatten(depth: 1);
    }

    private function resultUris(TestResponse $response): array
    {
        return $this->resultItems($response)->pluck('uri')->all();
    }

    private function productUris(TestResponse $response): array
    {
        return $this->resultItems($response)->where('type', 'products')->pluck('uri')->values()->all();
    }

    private function titlesByUri(TestResponse $response, string $type): array
    {
        return $this->resultItems($response)->where('type', $type)->pluck('page_title_value', 'uri')->all();
    }

    private function insertRecent(string $uri, string $title, string $businessModule, string $type = 'menu'): void
    {
        DB::table('recent_searches')->insert([
            'user_id' => self::SUPER_ADMIN_ID,
            'user_type' => 'admin',
            'business_module' => $businessModule,
            'title' => $title,
            'keyword' => null,
            'route_uri' => $uri,
            'route_full_url' => url($uri),
            'response' => json_encode([
                'page_title' => $title,
                'page_title_value' => $title,
                'uri' => $uri,
                'type' => $type,
                'priority' => 1,
                'image' => null,
                'business_module' => $businessModule,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function orderListUri(string $businessModule): string
    {
        return $this->relativeRoute(name: 'admin.'.$businessModule.'.orders.list', parameters: ['status' => 'all']);
    }

    private function menuUris(TestResponse $response): array
    {
        return $this->resultItems($response)->where('type', 'menu')->pluck('uri')->values()->all();
    }

    private function recordUris(TestResponse $response): array
    {
        return $this->resultItems($response)->whereNotIn('type', ['products', 'menu', 'page'])->pluck('uri')->values()->all();
    }

    private function assertValidationErrorOn(TestResponse $response, string $field): void
    {
        $response->assertUnprocessable();

        $this->assertContains($field, collect($response->json('errors'))->pluck('error_code')->all());
    }

    private function assertNoForeignUris(array $uris, string $businessModule): void
    {
        foreach ($uris as $uri) {
            foreach (self::FOREIGN_PREFIXES[$businessModule] as $prefix) {
                $this->assertStringStartsNotWith($prefix, (string) $uri, "{$businessModule} search returned {$uri}");
            }
        }
    }

    private function addonContentPages(): array
    {
        return [
            BusinessModule::WHOLESALE => $this->relativeRoute(name: 'admin.settings.wholesale.pages-and-media.index'),
            BusinessModule::AUCTION => $this->relativeRoute(name: 'admin.settings.auction.hero-section'),
        ];
    }

    private function relativeRoute(string $name, array $parameters = []): string
    {
        return ltrim(route($name, $parameters, false), '/');
    }

    private function retailProductUri(Product $product): string
    {
        return $this->relativeRoute(name: 'admin.retail.products.view', parameters: [
            'addedBy' => $product->added_by === 'seller' ? 'vendor' : 'in-house',
            'id' => $product->id,
        ]);
    }

    private function wholesaleProductUri(Product $product): string
    {
        return $this->relativeRoute(name: 'admin.wholesale.products.view.overview', parameters: ['id' => $product->id]);
    }

    private function sessionKey(): string
    {
        return BusinessModuleManager::SESSION_KEY;
    }

    private function superAdmin(): Admin
    {
        return $this->requireFixture(Admin::query()->find(self::SUPER_ADMIN_ID), 'super admin');
    }

    private function employeeHolding(array $permissions): Admin
    {
        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => 'Advanced search probe role',
            'module_access' => json_encode($permissions),
            'status' => 1,
        ]);

        $employeeId = DB::table('admins')->insertGetId([
            'name' => 'Advanced search probe employee',
            'email' => 'advanced-search-probe-'.Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('advanced-search-probe'),
            'admin_role_id' => $roleId,
            'status' => 1,
        ]);

        return Admin::query()->findOrFail($employeeId)->load('role');
    }

    private function createCustomer(): int
    {
        return DB::table('users')->insertGetId([
            'f_name' => self::PROBE,
            'l_name' => 'Customer',
            'email' => Str::lower(self::PROBE).'-'.Str::lower(Str::random(6)).'@example.test',
            'phone' => '+8801'.random_int(100000000, 999999999),
            'password' => bcrypt('advanced-search-probe'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProduct(string $businessModule): Product
    {
        return Product::query()->create([
            'business_module' => $businessModule,
            'added_by' => ProductRetailService::ADDED_BY_ADMIN,
            'user_id' => self::SUPER_ADMIN_ID,
            'shop_id' => 0,
            'name' => self::PROBE.' '.$businessModule,
            'slug' => Str::slug(self::PROBE.'-'.$businessModule).'-'.uniqid(),
            'code' => Str::upper(Str::random(10)),
            'category_id' => (int) DB::table('categories')->where('position', 0)->where('business_module', $businessModule)->value('id'),
            'category_ids' => json_encode([]),
            'unit' => 'pc',
            'product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL,
            'details' => self::PROBE,
            'colors' => json_encode([]),
            'choice_options' => json_encode([]),
            'attributes' => json_encode([]),
            'images' => json_encode([]),
            'unit_price' => 100,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 5,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'status' => 1,
            'request_status' => ProductRetailService::REQUEST_STATUS_APPROVED,
        ]);
    }

    private function createAuctionFixtures(): array
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => self::PROBE,
            'slug' => Str::slug(self::PROBE).'-'.uniqid(),
            'parent_id' => 0,
            'position' => 0,
            'business_module' => BusinessModule::AUCTION,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $brandId = DB::table('brands')->insertGetId([
            'name' => self::PROBE,
            'status' => 1,
            'business_module' => BusinessModule::AUCTION,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('auction_products')->insertGetId([
            'owner_type' => OwnerType::ADMIN,
            'owner_id' => self::SUPER_ADMIN_ID,
            'name' => self::PROBE.' auction',
            'slug' => Str::slug(self::PROBE.'-auction').'-'.uniqid(),
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'item_condition' => ItemCondition::NEW,
            'approval_status' => ApprovalStatus::APPROVED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['category_id' => $categoryId, 'brand_id' => $brandId, 'product_id' => $productId];
    }

    private function makeModulesUnavailable(array $keys): void
    {
        $this->app->instance(BusinessModuleManager::class, new class($keys) extends BusinessModuleManager
        {
            public function __construct(private readonly array $unavailableKeys) {}

            public function isInstalled(string $key): bool
            {
                return ! in_array($key, $this->unavailableKeys, true) && parent::isInstalled($key);
            }
        });

        app('router')->getRoutes()->getByName('admin.v2.advanced-search')->controller = null;
    }
}
