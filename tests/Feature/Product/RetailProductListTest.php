<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Exports\ProductListExport;
use App\Models\Product;
use App\Models\Seller;
use App\Services\New\ProductRetailService;
use App\Utils\Helpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailProductListTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const CODE_PREFIX = 'RETAILLIST';

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

    public function test_each_vendor_tab_exports_exactly_what_it_lists(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        foreach ($this->vendorTabs() as $tab => $expectedCode) {
            $listed = $this->vendorListedCodes($seller, $tab);
            $exported = $this->vendorExportedCodes($seller, $tab);

            $this->assertSame([$expectedCode], $listed, "the {$tab} tab does not list its own product alone");
            $this->assertSame($listed, $exported, "the {$tab} export does not match the {$tab} list");
        }
    }

    public function test_the_vendor_all_tab_lists_and_exports_every_request_state(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $expected = ['DENIED', 'APPROVED', 'PENDING'];

        $this->assertSame($expected, $this->vendorListedCodes($seller, 'all'));
        $this->assertSame($expected, $this->vendorExportedCodes($seller, 'all'));
    }

    public function test_the_vendor_list_and_export_are_scoped_to_the_authenticated_seller(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);
        $foreignCode = $this->foreignProduct($seller)->code;

        $this->assertNotContains($foreignCode, $this->vendorListedCodes($seller, 'all'));
        $this->assertNotContains($foreignCode, $this->vendorExportedCodes($seller, 'all'));
    }

    public function test_the_status_facet_narrows_both_the_vendor_list_and_the_vendor_export(): void
    {
        $seller = $this->seller();
        $products = $this->sellerProducts($seller);
        $products['APPROVED']->forceFill(['status' => 0])->save();

        $query = ['product_status' => ['0']];

        $this->assertSame(['APPROVED'], $this->vendorListedCodes($seller, 'all', $query));
        $this->assertSame(['APPROVED'], $this->vendorExportedCodes($seller, 'all', $query));
    }

    public function test_the_search_term_survives_into_the_vendor_export(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $query = ['searchValue' => self::CODE_PREFIX.'DENIED'];

        $this->assertSame(['DENIED'], $this->vendorListedCodes($seller, 'all', $query));
        $this->assertSame(['DENIED'], $this->vendorExportedCodes($seller, 'all', $query));
    }

    public function test_the_category_facet_narrows_both_the_vendor_list_and_the_vendor_export(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $query = ['filter_category_ids' => [$this->otherCategoryId()]];

        $this->assertSame([], $this->vendorListedCodes($seller, 'all', $query));
        $this->assertSame([], $this->vendorExportedCodes($seller, 'all', $query));
    }

    public function test_the_admin_request_state_filters_narrow_the_shared_list_query(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        foreach ($this->adminRequestStates() as $requestStatus => $expectedCode) {
            $codes = $this->listedCodesFor(
                state: $this->productService()->resolveRequestState($requestStatus),
                addedBy: ProductRetailService::ADDED_BY_SELLER,
                sellerId: $seller->id,
            );

            $this->assertSame([$expectedCode], $codes, "the admin request_status={$requestStatus} filter is wrong");
        }
    }

    public function test_the_admin_in_house_filters_exclude_every_vendor_product(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $codes = $this->listedCodesFor(
            state: ProductRetailService::REQUEST_STATE_ALL,
            addedBy: ProductRetailService::ADDED_BY_IN_HOUSE,
        );

        $this->assertSame([], $codes);
    }

    public function test_the_admin_gallery_state_keeps_unapproved_products_out_when_no_vendor_is_selected(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $codes = $this->listedCodesFor(state: ProductRetailService::REQUEST_STATE_APPROVED);

        $this->assertContains('APPROVED', $codes);
        $this->assertNotContains('PENDING', $codes);
        $this->assertNotContains('DENIED', $codes);
    }

    public function test_the_status_facet_is_ignored_on_the_states_that_hide_the_control(): void
    {
        $service = $this->productService();

        $this->assertArrayNotHasKey('status', $service->listFilters(
            state: ProductRetailService::REQUEST_STATE_NEW,
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            statuses: ['0'],
        ));

        $this->assertSame(['0'], $service->listFilters(
            state: ProductRetailService::REQUEST_STATE_APPROVED,
            addedBy: ProductRetailService::ADDED_BY_SELLER,
            statuses: ['0'],
        )['status']);
    }

    public function test_the_seller_api_list_uses_the_same_states_as_the_vendor_panel(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $expected = [
            'all' => ['DENIED', 'APPROVED', 'PENDING'],
            'new' => ['PENDING'],
            'approved' => ['APPROVED'],
            'denied' => ['DENIED'],
        ];

        foreach ($expected as $state => $codes) {
            $this->assertSame($codes, $this->apiListedCodes($seller, ['state' => $state]), "api/v6 state={$state} is wrong");
        }
    }

    public function test_the_seller_api_list_is_scoped_to_the_token_holder(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);
        $foreign = $this->foreignProduct($seller);

        $response = $this->getJson('api/v6/seller/products?limit=200', $this->sellerHeaders($seller));

        $response->assertSuccessful();

        $this->assertNotContains($foreign->id, collect($response->json('content.data'))->pluck('id')->all());
    }

    public function test_the_seller_api_list_searches_by_code_and_reports_the_applied_params(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $response = $this->getJson(
            'api/v6/seller/products?search='.self::CODE_PREFIX.'APPROVED&filter_sort_by=oldest',
            $this->sellerHeaders($seller),
        );

        $response->assertSuccessful();

        $this->assertSame(['APPROVED'], $this->suffixes(collect($response->json('content.data'))->pluck('code')));
        $this->assertSame(self::CODE_PREFIX.'APPROVED', $response->json('content.params.search'));
        $this->assertSame('oldest', $response->json('content.params.filter_sort_by'));
    }

    public function test_the_seller_api_rejects_an_unknown_state_and_falls_back_to_all(): void
    {
        $seller = $this->seller();
        $this->sellerProducts($seller);

        $response = $this->getJson('api/v6/seller/products?state=whatever', $this->sellerHeaders($seller));

        $response->assertSuccessful();

        $this->assertSame(ProductRetailService::REQUEST_STATE_ALL, $response->json('content.params.state'));
        $this->assertSame(['DENIED', 'APPROVED', 'PENDING'], $this->suffixes(collect($response->json('content.data'))->pluck('code')));
    }

    public function test_the_seller_api_status_facet_matches_the_vendor_panel(): void
    {
        $seller = $this->seller();
        $products = $this->sellerProducts($seller);
        $products['APPROVED']->forceFill(['status' => 0])->save();

        $this->assertSame(['APPROVED'], $this->apiListedCodes($seller, ['product_status' => ['0']]));
    }

    public function test_the_status_facet_keeps_pending_and_denied_products_out_of_active_and_inactive(): void
    {
        $seller = $this->seller();
        $products = $this->sellerProducts($seller);

        $this->assertSame(['APPROVED'], $this->apiListedCodes($seller, ['product_status' => ['1']]));
        $this->assertSame(['APPROVED'], $this->vendorListedCodes($seller, 'all', ['product_status' => ['1']]));

        foreach ($products as $product) {
            $product->forceFill(['status' => 0])->save();
        }

        $this->assertSame(['APPROVED'], $this->apiListedCodes($seller, ['product_status' => ['0']]));
        $this->assertSame(['APPROVED'], $this->vendorListedCodes($seller, 'all', ['product_status' => ['0']]));
    }

    public function test_a_parent_category_still_matches_its_own_products_when_a_child_is_also_selected(): void
    {
        $seller = $this->seller();
        $parentId = $this->parentCategoryWithChildren();
        $childId = $this->childCategoryId($parentId);

        $this->product($seller->id, ProductRetailService::REQUEST_STATUS_APPROVED, 'PARENTONLY', $parentId);

        $parentOnly = ['filter_category_ids' => [$parentId]];
        $parentAndChild = ['filter_category_ids' => [$parentId], 'filter_sub_category_ids' => [$childId]];

        $this->assertSame(['PARENTONLY'], $this->vendorListedCodes($seller, 'all', $parentOnly));
        $this->assertSame(['PARENTONLY'], $this->vendorListedCodes($seller, 'all', $parentAndChild));
        $this->assertSame(['PARENTONLY'], $this->vendorExportedCodes($seller, 'all', $parentAndChild));
        $this->assertSame(['PARENTONLY'], $this->apiListedCodes($seller, $parentAndChild));
    }

    public function test_a_selected_sub_sub_category_narrows_its_selected_ancestors_to_their_own_products(): void
    {
        $seller = $this->seller();
        $tree = $this->categoryTreeWithSiblingSubCategory();

        $this->product($seller->id, ProductRetailService::REQUEST_STATUS_APPROVED, 'DIRECT', $tree['category_id']);
        $this->product($seller->id, ProductRetailService::REQUEST_STATUS_APPROVED, 'SUBSUB', $tree['category_id'])
            ->update(['sub_category_id' => $tree['sub_category_id'], 'sub_sub_category_id' => $tree['sub_sub_category_id']]);
        $this->product($seller->id, ProductRetailService::REQUEST_STATUS_APPROVED, 'SIBLING', $tree['category_id'])
            ->update(['sub_category_id' => $tree['sibling_sub_category_id']]);

        $query = [
            'filter_category_ids' => [$tree['category_id']],
            'filter_sub_category_ids' => [$tree['sub_category_id']],
            'filter_sub_sub_category_ids' => [$tree['sub_sub_category_id']],
        ];

        $this->assertEqualsCanonicalizing(['DIRECT', 'SUBSUB'], $this->vendorListedCodes($seller, 'all', $query));
        $this->assertEqualsCanonicalizing(['DIRECT', 'SUBSUB'], $this->apiListedCodes($seller, $query));
    }

    /** @return array{category_id: int, sub_category_id: int, sub_sub_category_id: int, sibling_sub_category_id: int} */
    private function categoryTreeWithSiblingSubCategory(): array
    {
        $subSubCategory = $this->requireFixture(
            DB::table('categories as sub_sub')
                ->join('categories as sub', 'sub.id', '=', 'sub_sub.parent_id')
                ->join('categories as sibling', fn ($join) => $join->on('sibling.parent_id', '=', 'sub.parent_id')->whereColumn('sibling.id', '!=', 'sub.id'))
                ->where('sub_sub.position', 2)
                ->where('sub_sub.business_module', BusinessModule::RETAIL)
                ->select('sub_sub.id as sub_sub_category_id', 'sub.id as sub_category_id', 'sub.parent_id as category_id', 'sibling.id as sibling_sub_category_id')
                ->first(),
            'a sub-sub-category whose sub-category has a sibling',
        );

        return array_map('intval', (array) $subSubCategory);
    }

    private function parentCategoryWithChildren(): int
    {
        return (int) $this->requireFixture(
            DB::table('categories as parent')
                ->where('parent.position', 0)
                ->whereExists(fn ($query) => $query->select(DB::raw(1))->from('categories as child')->whereColumn('child.parent_id', 'parent.id'))
                ->value('parent.id'),
            'a category with sub-categories',
        );
    }

    private function childCategoryId(int $parentId): int
    {
        return (int) DB::table('categories')->where('parent_id', $parentId)->value('id');
    }

    private function vendorTabs(): array
    {
        return ['new-request' => 'PENDING', 'approved' => 'APPROVED', 'denied' => 'DENIED'];
    }

    private function adminRequestStates(): array
    {
        return ['0' => 'PENDING', '1' => 'APPROVED', '2' => 'DENIED'];
    }

    private function vendorListedCodes(Seller $seller, string $tab, array $query = []): array
    {
        $listed = null;

        View::composer('vendor-views.product.list', function ($view) use (&$listed): void {
            $listed = collect($view->getData()['products']->items())->pluck('code');
        });

        $this->actingAs($seller, 'seller')
            ->get('vendor/retail/products/list/'.$tab.'?'.http_build_query($query))
            ->assertSuccessful();

        $this->assertNotNull($listed, 'the vendor product list view never rendered');

        return $this->suffixes($listed);
    }

    private function vendorExportedCodes(Seller $seller, string $tab, array $query = []): array
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->actingAs($seller, 'seller')
            ->get('vendor/retail/products/export-excel/'.$tab.'?'.http_build_query($query))
            ->assertSuccessful();

        $codes = null;

        Excel::assertDownloaded('/product-list\.xlsx$/', function (ProductListExport $export) use (&$codes): bool {
            $codes = collect((fn () => $this->data)->call($export)['products'])->pluck('code');

            return true;
        });

        return $this->suffixes($codes ?? collect());
    }

    private function apiListedCodes(Seller $seller, array $query): array
    {
        $response = $this->getJson('api/v6/seller/products?'.http_build_query($query), $this->sellerHeaders($seller));

        $response->assertSuccessful();

        return $this->suffixes(collect($response->json('content.data'))->pluck('code'));
    }

    private function listedCodesFor(string $state, ?string $addedBy = null, mixed $sellerId = null): array
    {
        $service = $this->productService();

        $products = $service->getList(
            dataLimit: 'all',
            filters: $service->listFilters(state: $state, addedBy: $addedBy, sellerId: $sellerId),
        );

        return $this->suffixes($products->pluck('code'));
    }

    private function productService(): ProductRetailService
    {
        return app(ProductRetailService::class);
    }

    private function suffixes(Collection $codes): array
    {
        return $codes
            ->filter(fn (?string $code): bool => $code !== null && str_starts_with($code, self::CODE_PREFIX))
            ->map(fn (string $code): string => substr($code, strlen(self::CODE_PREFIX)))
            ->values()
            ->all();
    }

    private function sellerProducts(Seller $seller): array
    {
        return [
            'PENDING' => $this->product($seller->id, ProductRetailService::REQUEST_STATUS_PENDING, 'PENDING'),
            'APPROVED' => $this->product($seller->id, ProductRetailService::REQUEST_STATUS_APPROVED, 'APPROVED'),
            'DENIED' => $this->product($seller->id, ProductRetailService::REQUEST_STATUS_DENIED, 'DENIED'),
        ];
    }

    private function foreignProduct(Seller $seller): Product
    {
        $otherSellerId = (int) Seller::query()->whereKeyNot($seller->id)->value('id');

        return $this->product($otherSellerId ?: $seller->id + 1000, ProductRetailService::REQUEST_STATUS_APPROVED, 'FOREIGN');
    }

    private function product(int $sellerId, int $requestStatus, string $suffix, ?int $categoryId = null): Product
    {
        return Product::query()->create([
            'business_module' => BusinessModule::RETAIL,
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
            'user_id' => $sellerId,
            'shop_id' => 0,
            'name' => 'Retail list '.$suffix,
            'slug' => 'retail-list-'.strtolower($suffix).'-'.uniqid(),
            'code' => self::CODE_PREFIX.$suffix,
            'category_id' => $categoryId ?? $this->categoryId(),
            'category_ids' => json_encode([]),
            'unit' => 'pc',
            'product_type' => ProductRetailService::PRODUCT_TYPE_PHYSICAL,
            'details' => 'Retail list '.$suffix,
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
            'request_status' => $requestStatus,
        ]);
    }

    private function seller(): Seller
    {
        $seller = $this->requireFixture(
            Seller::query()->where('status', 'approved')->first(),
            'an approved seller',
        );

        Product::query()
            ->where('added_by', ProductRetailService::ADDED_BY_SELLER)
            ->delete();

        Product::query()
            ->where('added_by', ProductRetailService::ADDED_BY_ADMIN)
            ->delete();

        return $seller;
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('position', 0)->value('id');
    }

    private function otherCategoryId(): int
    {
        return (int) DB::table('categories')
            ->where('position', 0)
            ->where('id', '!=', $this->categoryId())
            ->value('id');
    }
}
