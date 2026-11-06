<?php

namespace Tests\Feature\Shipping;

use App\Enums\BusinessModule;
use App\Models\Admin;
use App\Models\Category;
use App\Models\CategoryShippingCost;
use App\Models\Seller;
use App\Models\ShippingMethod;
use App\Services\New\ShippingMethodService;
use App\Utils\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\InteractsWithWholesaleAddon;
use Tests\TestCase;

class ShippingOwnershipTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use InteractsWithWholesaleAddon;

    private const OTHER_SELLER_ID = 990301;

    private const FIXTURE_CATEGORY_ID = 990302;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();
    }

    public function test_wholesale_vendor_manages_only_its_own_wholesale_methods(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $ownMethod = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: 'Own wholesale');
        $otherSellerMethod = $this->createMethod(creatorId: self::OTHER_SELLER_ID, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: 'Other wholesale');
        $ownRetailMethod = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::RETAIL, title: 'Own retail');

        foreach ([$otherSellerMethod, $ownRetailMethod] as $foreignMethod) {
            $this->actingAs($seller, 'seller')
                ->post(route('vendor.wholesale.shipping-method.update', ['id' => $foreignMethod->id]), $this->methodPayload(title: 'Hijacked'));
            $this->actingAs($seller, 'seller')
                ->post(route('vendor.wholesale.shipping-method.update-status'), ['id' => $foreignMethod->id, 'status' => 0])
                ->assertJsonPath('success', 0);
            $this->actingAs($seller, 'seller')
                ->post(route('vendor.wholesale.shipping-method.delete'), ['id' => $foreignMethod->id])
                ->assertJsonPath('status', 0);

            $foreignMethod->refresh();
            $this->assertNotSame('Hijacked', $foreignMethod->title);
            $this->assertSame(1, (int) $foreignMethod->status);
        }

        $this->assertSame(BusinessModule::RETAIL, $ownRetailMethod->context);

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.shipping-method.update', ['id' => $ownMethod->id]), $this->methodPayload(title: 'Renamed wholesale'))
            ->assertSessionHasNoErrors();
        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.shipping-method.update-status'), ['id' => $ownMethod->id, 'status' => 0])
            ->assertJsonPath('success', 1);

        $this->assertSame('Renamed wholesale', $ownMethod->fresh()->title);
        $this->assertSame(0, (int) $ownMethod->fresh()->status);

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.shipping-method.delete'), ['id' => $ownMethod->id])
            ->assertJsonPath('status', 1);
        $this->assertNull($ownMethod->fresh());
    }

    public function test_wholesale_vendor_title_must_be_unique_among_its_wholesale_methods(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::WHOLESALE, title: 'Duplicate wholesale title');

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.shipping-method.store'), $this->methodPayload(title: 'Duplicate wholesale title'))
            ->assertSessionHasErrors('title');

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.wholesale.shipping-method.store'), $this->methodPayload(title: 'Fresh wholesale title'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ShippingMethod::query()->where([
            'creator_id' => $seller->id,
            'creator_type' => 'seller',
            'title' => 'Fresh wholesale title',
            'context' => BusinessModule::WHOLESALE,
        ])->count());
    }

    public function test_wholesale_vendor_updates_only_its_own_category_shipping_costs(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $ownCost = $this->createCategoryCost(sellerId: $seller->id, context: BusinessModule::WHOLESALE);
        $otherSellerCost = $this->createCategoryCost(sellerId: self::OTHER_SELLER_ID, context: BusinessModule::WHOLESALE);
        $ownRetailCost = $this->createCategoryCost(sellerId: $seller->id, context: BusinessModule::RETAIL);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.wholesale.shipping-method.category-shipping-cost'), [
                'ids' => [self::FIXTURE_CATEGORY_ID],
                'cost' => [15],
                'multiply_qty' => [1],
            ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertEquals(15, $ownCost->fresh()->cost);
        $this->assertSame(1, (int) $ownCost->fresh()->multiply_qty);
        $this->assertEquals(5, $otherSellerCost->fresh()->cost);
        $this->assertSame(0, (int) $otherSellerCost->fresh()->multiply_qty);
        $this->assertEquals(5, $ownRetailCost->fresh()->cost);
    }

    public function test_vendor_category_shipping_cost_rejects_a_negative_cost(): void
    {
        $seller = $this->seller();
        $ownCost = $this->createCategoryCost(sellerId: $seller->id, context: BusinessModule::RETAIL);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.business-settings.category-wise-shipping-cost.index'), [
                'ids' => [self::FIXTURE_CATEGORY_ID],
                'cost' => [-1],
                'multiply_qty' => [0],
            ])
            ->assertUnprocessable();

        $this->assertEquals(5, $ownCost->fresh()->cost);
    }

    public function test_vendor_category_shipping_cost_list_is_paginated(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.business-settings.shipping-method.index'))
            ->assertOk()
            ->assertViewHas('allCategoryShippingCost', fn ($costs): bool => $costs instanceof LengthAwarePaginator
                && $costs->getPageName() === 'category_page');

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.shipping-method.wholesale'))
            ->assertOk()
            ->assertViewHas('allCategoryShippingCost', fn ($costs): bool => $costs instanceof LengthAwarePaginator
                && $costs->getPageName() === 'category_page');
    }

    public function test_wholesale_vendor_page_seeds_wholesale_category_costs(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $seller = $this->seller();
        $wholesaleCategoryId = $this->requireFixture(
            Category::query()->withoutGlobalScopes()->where(['business_module' => BusinessModule::WHOLESALE, 'position' => 0])->value('id'),
            'wholesale main category',
        );
        CategoryShippingCost::query()->where(['seller_id' => $seller->id, 'context' => BusinessModule::WHOLESALE])->delete();

        $this->actingAs($seller, 'seller')
            ->get(route('vendor.wholesale.shipping-method.wholesale'))
            ->assertOk()
            ->assertSee(route('vendor.wholesale.shipping-method.category-shipping-cost'), escape: false);

        $this->assertTrue(CategoryShippingCost::query()->where([
            'seller_id' => $seller->id,
            'category_id' => $wholesaleCategoryId,
            'context' => BusinessModule::WHOLESALE,
        ])->exists());
    }

    public function test_retail_vendor_updates_only_its_own_category_shipping_costs(): void
    {
        $seller = $this->seller();
        $ownCost = $this->createCategoryCost(sellerId: $seller->id, context: BusinessModule::RETAIL);
        $otherSellerCost = $this->createCategoryCost(sellerId: self::OTHER_SELLER_ID, context: BusinessModule::RETAIL);
        $ownWholesaleCost = $this->createCategoryCost(sellerId: $seller->id, context: BusinessModule::WHOLESALE);

        $this->actingAs($seller, 'seller')
            ->postJson(route('vendor.business-settings.category-wise-shipping-cost.index'), [
                'ids' => [self::FIXTURE_CATEGORY_ID],
                'cost' => [15],
                'multiply_qty' => [1],
            ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertEquals(15, $ownCost->fresh()->cost);
        $this->assertSame(1, (int) $ownCost->fresh()->multiply_qty);
        $this->assertEquals(5, $otherSellerCost->fresh()->cost);
        $this->assertEquals(5, $ownWholesaleCost->fresh()->cost);
    }

    public function test_admin_updates_only_in_house_category_shipping_costs_of_its_own_tab(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $admin = Admin::query()->orderBy('id')->firstOrFail();
        $routes = [
            BusinessModule::RETAIL => 'admin.settings.business-settings.category-shipping-cost.store',
            BusinessModule::WHOLESALE => 'admin.settings.business-settings.shipping-method.wholesale.category-shipping-cost',
        ];

        foreach ($routes as $context => $routeName) {
            $inHouseCost = $this->createCategoryCost(sellerId: 0, context: $context);
            $vendorCost = $this->createCategoryCost(sellerId: self::OTHER_SELLER_ID, context: $context);
            $otherTabCost = $this->createCategoryCost(
                sellerId: 0,
                context: $context === BusinessModule::RETAIL ? BusinessModule::WHOLESALE : BusinessModule::RETAIL,
            );

            $this->actingAs($admin, 'admin')
                ->postJson(route($routeName), [
                    'ids' => [self::FIXTURE_CATEGORY_ID],
                    'cost' => [15],
                    'multiply_qty' => [1],
                ])
                ->assertOk()
                ->assertJsonPath('status', 1);

            $this->assertEquals(15, $inHouseCost->fresh()->cost);
            $this->assertSame(1, (int) $inHouseCost->fresh()->multiply_qty);
            $this->assertEquals(5, $vendorCost->fresh()->cost);
            $this->assertEquals(5, $otherTabCost->fresh()->cost);

            CategoryShippingCost::query()->whereKey([$inHouseCost->id, $vendorCost->id, $otherTabCost->id])->delete();
        }
    }

    public function test_admin_category_shipping_cost_list_is_paginated(): void
    {
        $admin = Admin::query()->orderBy('id')->firstOrFail();

        foreach (['admin.settings.business-settings.shipping-method.index', 'admin.settings.business-settings.shipping-method.wholesale'] as $routeName) {
            $this->actingAs($admin, 'admin')
                ->get(route($routeName))
                ->assertOk()
                ->assertViewHas('allCategoryShippingCost', fn ($costs): bool => $costs instanceof LengthAwarePaginator
                    && $costs->getPageName() === 'category_page');
        }
    }

    public function test_wholesale_admin_cannot_change_a_retail_method(): void
    {
        $this->skipUnlessWholesaleAddonPublished();

        $admin = Admin::query()->orderBy('id')->firstOrFail();
        $retailMethod = $this->createMethod(creatorId: $admin->id, creatorType: 'admin', context: BusinessModule::RETAIL, title: 'Admin retail');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.business-settings.shipping-method.wholesale.update', ['id' => $retailMethod->id]), $this->methodPayload(title: 'Converted'));
        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.business-settings.shipping-method.wholesale.update-status'), ['id' => $retailMethod->id, 'status' => 0])
            ->assertJsonPath('success', 0);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.business-settings.shipping-method.wholesale.delete'), ['id' => $retailMethod->id]);

        $retailMethod->refresh();
        $this->assertSame('Admin retail', $retailMethod->title);
        $this->assertSame(BusinessModule::RETAIL, $retailMethod->context);
        $this->assertSame(1, (int) $retailMethod->status);
    }

    public function test_vendor_cannot_delete_a_shipping_method_used_by_an_order(): void
    {
        $seller = $this->seller();
        $routes = ['vendor.business-settings.shipping-method.delete' => BusinessModule::RETAIL];

        if (addon_published_status(moduleName: 'Wholesale')) {
            $routes['vendor.wholesale.shipping-method.delete'] = BusinessModule::WHOLESALE;
        }

        foreach ($routes as $routeName => $context) {
            $usedMethod = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: $context, title: 'Used '.$context);
            $unusedMethod = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: $context, title: 'Unused '.$context);
            $this->createOrderUsingShippingMethod(sellerId: $seller->id, shippingMethodId: $usedMethod->id, businessModule: $context);

            $this->actingAs($seller, 'seller')
                ->post(route($routeName), ['id' => $usedMethod->id])
                ->assertJsonPath('status', 0);
            $this->assertNotNull($usedMethod->fresh());

            $this->actingAs($seller, 'seller')
                ->post(route($routeName), ['id' => $unusedMethod->id])
                ->assertJsonPath('status', 1);
            $this->assertNull($unusedMethod->fresh());
        }
    }

    public function test_vendor_shipping_method_title_is_limited_to_100_characters(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.business-settings.shipping-method.index'), $this->methodPayload(title: str_repeat('a', 101)))
            ->assertSessionHasErrors('title');

        $this->actingAs($seller, 'seller')
            ->post(route('vendor.business-settings.shipping-method.index'), $this->methodPayload(title: str_repeat('b', 100)))
            ->assertSessionHasNoErrors();
    }

    public function test_shipping_method_search_treats_wildcards_literally(): void
    {
        $seller = $this->seller();
        $matching = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::RETAIL, title: 'Express 100% sure');
        $other = $this->createMethod(creatorId: $seller->id, creatorType: 'seller', context: BusinessModule::RETAIL, title: 'Express 1000 sure');

        $foundIds = app(ShippingMethodService::class)->getList(
            searchValue: '100%',
            dataLimit: 'all',
            filters: ['creator_id' => $seller->id, 'creator_type' => ShippingMethodService::CREATOR_SELLER],
        )->pluck('id');

        $this->assertTrue($foundIds->contains($matching->id));
        $this->assertFalse($foundIds->contains($other->id));
    }

    private function createOrderUsingShippingMethod(int $sellerId, int $shippingMethodId, string $businessModule): void
    {
        DB::table('orders')->insert([
            'customer_id' => 1,
            'is_guest' => 0,
            'seller_id' => $sellerId,
            'seller_is' => 'seller',
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
            'order_type' => 'default_type',
            'order_amount' => 100,
            'shipping_method_id' => $shippingMethodId,
            'business_module' => $businessModule,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function methodPayload(string $title): array
    {
        return ['title' => $title, 'duration' => '2 Days', 'cost' => 12];
    }

    private function createMethod(int $creatorId, string $creatorType, string $context, string $title): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'creator_id' => $creatorId,
            'creator_type' => $creatorType,
            'title' => $title,
            'duration' => '3 Days',
            'cost' => 10,
            'status' => 1,
            'context' => $context,
        ]);
    }

    private function createCategoryCost(int $sellerId, string $context): CategoryShippingCost
    {
        return CategoryShippingCost::query()->create([
            'seller_id' => $sellerId,
            'category_id' => self::FIXTURE_CATEGORY_ID,
            'cost' => 5,
            'multiply_qty' => 0,
            'context' => $context,
        ]);
    }

    private function seller(): Seller
    {
        return $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'approved seller',
        );
    }
}
