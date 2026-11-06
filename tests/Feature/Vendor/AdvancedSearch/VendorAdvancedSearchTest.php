<?php

namespace Tests\Feature\Vendor\AdvancedSearch;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Seller;
use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\New\ProductRetailService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

function courierSetupUnavailable(): ?string
{
    return null;
}

function courierSetupAvailable(): ?string
{
    return BusinessModule::RETAIL;
}

class VendorAdvancedSearchTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const PROBE = 'Zqxvendorprobe';

    private const IN_HOUSE_SELLER_ID = 1;

    private const SUPER_ADMIN_ID = 1;

    private const VENDOR_URI_PREFIX = 'vendor/';

    private const POS_URI = 'vendor/retail/pos';

    private const AVAILABLE_AFTER_VERSION_CONFIG = 'advanced_search.vendor.available_after_version';

    private const RELEASED_VERSION = '0';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
        Config::set(self::AVAILABLE_AFTER_VERSION_CONFIG, self::RELEASED_VERSION);

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_a_vendor_finds_only_their_own_products(): void
    {
        [$owner, $otherVendor] = $this->approvedSellers(count: 2);
        $ownProduct = $this->createSellerProduct(seller: $owner, businessModule: BusinessModule::RETAIL);
        $foreignProduct = $this->createSellerProduct(seller: $otherVendor, businessModule: BusinessModule::RETAIL);

        $uris = $this->resultUris($this->search(seller: $owner, query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertContains($this->retailProductUri(product: $ownProduct), $uris);
        $this->assertNotContains($this->retailProductUri(product: $foreignProduct), $uris);
    }

    public function test_a_vendor_never_sees_another_vendors_coupons_or_delivery_men(): void
    {
        [$owner, $otherVendor] = $this->approvedSellers(count: 2);
        $ownCouponId = $this->createSellerCoupon(seller: $owner);
        $foreignCouponId = $this->createSellerCoupon(seller: $otherVendor);
        $ownDeliveryManId = $this->createSellerDeliveryMan(seller: $owner);
        $foreignDeliveryManId = $this->createSellerDeliveryMan(seller: $otherVendor);

        $uris = $this->resultUris($this->search(seller: $owner, query: ['keyword' => self::PROBE, 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertContains($this->relativeRoute(name: 'vendor.retail.coupon.update', parameters: ['id' => $ownCouponId]), $uris);
        $this->assertNotContains($this->relativeRoute(name: 'vendor.retail.coupon.update', parameters: ['id' => $foreignCouponId]), $uris);
        $this->assertContains($this->relativeRoute(name: 'vendor.delivery-man.update', parameters: ['id' => $ownDeliveryManId]), $uris);
        $this->assertNotContains($this->relativeRoute(name: 'vendor.delivery-man.update', parameters: ['id' => $foreignDeliveryManId]), $uris);
    }

    public function test_a_vendor_cannot_find_another_vendors_order_by_its_id(): void
    {
        [$owner, $otherVendor] = $this->approvedSellers(count: 2);
        $foreignOrderId = $this->requireFixture(
            DB::table('orders')->where('seller_is', 'seller')->where('seller_id', $otherVendor->id)->value('id'),
            'order belonging to the second vendor',
        );
        $foreignOrderUri = $this->relativeRoute(name: 'vendor.retail.orders.details', parameters: ['id' => $foreignOrderId]);

        $ownerUris = $this->resultUris($this->search(seller: $owner, query: ['keyword' => (string) $foreignOrderId, 'business_module' => BusinessModule::RETAIL])->assertOk());
        $otherVendorUris = $this->resultUris($this->search(seller: $otherVendor, query: ['keyword' => (string) $foreignOrderId, 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertNotContains($foreignOrderUri, $ownerUris);
        $this->assertContains($foreignOrderUri, $otherVendorUris);
    }

    public function test_refund_requests_match_only_when_every_word_matches(): void
    {
        [$seller] = $this->approvedSellers(count: 1);

        $refundItems = $this->resultItems($this->search(seller: $seller, query: ['keyword' => 'all orders', 'business_module' => BusinessModule::RETAIL])->assertOk())
            ->where('type', 'refund_requests')
            ->all();

        $this->assertSame([], $refundItems);
    }

    public function test_every_result_links_into_the_vendor_panel(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $this->createSellerProduct(seller: $seller, businessModule: BusinessModule::RETAIL);

        foreach (['all orders', 'coupon', 'product', 'withdraw', 'report', 'dashboard', self::PROBE] as $keyword) {
            $uris = $this->resultUris($this->search(seller: $seller, query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL])->assertOk());

            $this->assertNotEmpty($uris, "no vendor result for {$keyword}");

            foreach ($uris as $uri) {
                $this->assertStringStartsWith(self::VENDOR_URI_PREFIX, (string) $uri, "{$keyword} returned {$uri}");
            }
        }
    }

    public function test_module_results_carry_their_module_label(): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Wholesale')) {
            $this->markTestSkipped('Wholesale addon is not published.');
        }

        [$seller] = $this->approvedSellers(count: 1);

        $menus = $this->resultItems($this->search(seller: $seller, query: ['keyword' => 'All orders', 'business_module' => BusinessModule::RETAIL])->assertOk())
            ->where('type', 'menu')
            ->pluck('page_title_value', 'uri');

        $this->assertSame('Retail › Orders › All', $menus[$this->relativeRoute(name: 'vendor.retail.orders.list', parameters: ['status' => 'all'])] ?? null);
        $this->assertSame('Wholesale › Orders › All', $menus[$this->relativeRoute(name: 'vendor.wholesale.orders.list', parameters: ['status' => 'all'])] ?? null);
    }

    public function test_module_menus_in_the_settings_area_show_with_the_current_workspace_first(): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Wholesale')) {
            $this->markTestSkipped('Wholesale addon is not published.');
        }

        [$seller] = $this->approvedSellers(count: 1);

        $wholesaleFirst = $this->resultItems($this->search(seller: $seller, query: ['keyword' => 'Shipping methods', 'business_module' => BusinessModule::WHOLESALE])->assertOk())
            ->where('type', 'menu')
            ->pluck('page_title_value')
            ->values()
            ->all();

        $this->assertSame('Wholesale › Shipping Methods', $wholesaleFirst[0] ?? null);
        $this->assertContains('Retail › Shipping Methods', $wholesaleFirst);
    }

    public function test_naming_a_module_limits_results_to_that_module(): void
    {
        if (! getCheckAddonPublishedStatus(moduleName: 'Wholesale')) {
            $this->markTestSkipped('Wholesale addon is not published.');
        }

        [$seller] = $this->approvedSellers(count: 1);

        $uris = $this->menuUris($this->search(seller: $seller, query: ['keyword' => 'Wholesale all orders', 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertContains($this->relativeRoute(name: 'vendor.wholesale.orders.list', parameters: ['status' => 'all']), $uris);
        $this->assertNotContains($this->relativeRoute(name: 'vendor.retail.orders.list', parameters: ['status' => 'all']), $uris);
    }

    public function test_pos_is_found_only_when_the_vendor_has_pos_enabled(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $isPosSettingOn = (int) getWebConfig(name: 'seller_pos') === 1;

        $this->setSellerPosStatus(seller: $seller, isEnabled: false);
        $this->assertNotContains(self::POS_URI, $this->resultUris($this->search(seller: $seller->fresh(), query: ['keyword' => 'POS', 'business_module' => BusinessModule::RETAIL])->assertOk()));

        $this->setSellerPosStatus(seller: $seller, isEnabled: true);
        $enabledUris = $this->resultUris($this->search(seller: $seller->fresh(), query: ['keyword' => 'POS', 'business_module' => BusinessModule::RETAIL])->assertOk());

        $isPosSettingOn
            ? $this->assertContains(self::POS_URI, $enabledUris)
            : $this->assertNotContains(self::POS_URI, $enabledUris);
    }

    public function test_courier_menu_and_page_follow_the_delivery_partner_setup_rule(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $courierUri = $this->relativeRoute(name: 'vendor.courier.config.index');

        $this->useCourierSetupHelper(helper: __NAMESPACE__.'\\courierSetupAvailable');
        $availableItems = $this->resultItems($this->search(seller: $seller, query: ['keyword' => 'Delivery partner', 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertContains($courierUri, $availableItems->where('type', 'menu')->pluck('uri')->all());

        $this->useCourierSetupHelper(helper: __NAMESPACE__.'\\courierSetupUnavailable');
        $unavailableUris = $this->resultUris($this->search(seller: $seller, query: ['keyword' => 'Delivery partner', 'business_module' => BusinessModule::RETAIL])->assertOk());

        $this->assertNotContains($courierUri, $unavailableUris);
    }

    public function test_inactive_module_contributes_nothing(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        $this->createSellerProduct(seller: $seller, businessModule: BusinessModule::WHOLESALE);
        $this->makeModulesUnavailable(keys: [BusinessModule::WHOLESALE]);

        foreach (['All orders', 'Coupon', 'Limited stock', self::PROBE] as $keyword) {
            $uris = $this->resultUris($this->search(seller: $seller, query: ['keyword' => $keyword, 'business_module' => BusinessModule::RETAIL])->assertOk());

            $this->assertSame([], array_values(array_filter($uris, fn (string $uri): bool => str_contains($uri, 'wholesale'))), "{$keyword} returned an inactive module's result");
        }
    }

    public function test_recent_search_rejects_a_link_outside_the_vendor_panel(): void
    {
        [$seller] = $this->approvedSellers(count: 1);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.advanced-search-recent'), [
                'routeName' => 'Customer list',
                'routeUri' => 'admin/people/customer/list',
                'searchKeyword' => '',
                'business_module' => BusinessModule::COMMON,
                'type' => 'menu',
                'image' => '',
            ])
            ->assertUnprocessable();
    }

    public function test_guests_and_admins_cannot_use_the_vendor_search(): void
    {
        $guestStatus = $this->getJson(route('vendor.advanced-search', ['keyword' => 'orders']))->status();
        $admin = $this->requireFixture(Admin::query()->find(self::SUPER_ADMIN_ID), 'super admin');
        $adminStatus = $this->actingAs($admin, 'admin')->getJson(route('vendor.advanced-search', ['keyword' => 'orders']))->status();

        $this->assertNotSame(200, $guestStatus);
        $this->assertNotSame(200, $adminStatus);
    }

    public function test_vendor_search_stays_hidden_until_a_release_after_the_configured_version(): void
    {
        [$seller] = $this->approvedSellers(count: 1);
        Config::set(self::AVAILABLE_AFTER_VERSION_CONFIG, SOFTWARE_VERSION);

        $this->search(seller: $seller, query: ['keyword' => 'orders', 'business_module' => BusinessModule::RETAIL])->assertNotFound();
        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.advanced-search-recent'), [
                'routeName' => 'Dashboard',
                'routeUri' => 'vendor/dashboard',
                'searchKeyword' => '',
                'business_module' => BusinessModule::COMMON,
                'type' => 'menu',
                'image' => '',
            ])
            ->assertNotFound();

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.index'))
            ->assertOk()
            ->assertDontSee('v2-header-search')
            ->assertDontSee('advanceSearchModal')
            ->assertDontSee('keyword-highlight.js');
    }

    public function test_vendor_search_shows_once_the_release_is_past_the_configured_version(): void
    {
        [$seller] = $this->approvedSellers(count: 1);

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.messages.index'))
            ->assertOk()
            ->assertSee('v2-header-search')
            ->assertSee('advanceSearchModal')
            ->assertSee('keyword-highlight.js');
    }

    private function search(Seller $seller, array $query): TestResponse
    {
        return $this->actingAs($seller, 'seller')->getJson(route('vendor.advanced-search', $query));
    }

    private function resultItems(TestResponse $response): Collection
    {
        return collect($response->json('result'))->flatten(depth: 1);
    }

    private function resultUris(TestResponse $response): array
    {
        return $this->resultItems($response)->pluck('uri')->all();
    }

    private function menuUris(TestResponse $response): array
    {
        return $this->resultItems($response)->where('type', 'menu')->pluck('uri')->values()->all();
    }

    private function relativeRoute(string $name, array $parameters = []): string
    {
        return ltrim(route($name, $parameters, false), '/');
    }

    private function retailProductUri(Product $product): string
    {
        return $this->relativeRoute(name: 'vendor.retail.products.view', parameters: ['id' => $product->id]);
    }

    private function approvedSellers(int $count): array
    {
        $sellers = Seller::query()
            ->where('status', 'approved')
            ->where('id', '!=', self::IN_HOUSE_SELLER_ID)
            ->whereHas('shops', fn ($shops) => $shops->where('author_type', 'vendor'))
            ->orderBy('id')
            ->limit($count)
            ->get()
            ->all();

        if (count($sellers) < $count) {
            $this->markTestSkipped('Test database has fewer than '.$count.' approved sellers with a vendor shop.');
        }

        return $sellers;
    }

    private function createSellerProduct(Seller $seller, string $businessModule): Product
    {
        return Product::query()->create([
            'business_module' => $businessModule,
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
            'user_id' => $seller->id,
            'shop_id' => 0,
            'name' => self::PROBE.' '.$businessModule.' '.$seller->id,
            'slug' => Str::slug(self::PROBE.'-'.$businessModule.'-'.$seller->id).'-'.uniqid(),
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

    private function createSellerCoupon(Seller $seller): int
    {
        return DB::table('coupons')->insertGetId([
            'added_by' => 'seller',
            'coupon_bearer' => 'seller',
            'seller_id' => $seller->id,
            'business_module' => BusinessModule::RETAIL,
            'coupon_type' => 'discount_on_purchase',
            'title' => self::PROBE.' coupon '.$seller->id,
            'code' => Str::upper(Str::random(8)),
            'discount' => 5,
            'discount_type' => 'amount',
            'status' => 1,
            'start_date' => now()->subDay(),
            'expire_date' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSellerDeliveryMan(Seller $seller): int
    {
        return DB::table('delivery_men')->insertGetId([
            'seller_id' => $seller->id,
            'f_name' => self::PROBE,
            'l_name' => 'Rider '.$seller->id,
            'phone' => '+8801'.random_int(100000000, 999999999),
            'email' => Str::lower(self::PROBE).'-'.Str::lower(Str::random(6)).'@example.test',
            'password' => bcrypt('vendor-search-probe'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setSellerPosStatus(Seller $seller, bool $isEnabled): void
    {
        DB::table('sellers')->where('id', $seller->id)->update(['pos_status' => $isEnabled ? 1 : 0]);
    }

    private function useCourierSetupHelper(string $helper): void
    {
        Config::set('advanced_search.vendor.menus', array_map(
            fn (array $menu): array => ($menu['route'] ?? null) === 'vendor.courier.config.index'
                ? [...$menu, 'visibility' => [...$menu['visibility'], 'helper' => $helper]]
                : $menu,
            config('advanced_search.vendor.menus'),
        ));

        app('router')->getRoutes()->getByName('vendor.advanced-search')->controller = null;
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

        app('router')->getRoutes()->getByName('vendor.advanced-search')->controller = null;
    }
}
