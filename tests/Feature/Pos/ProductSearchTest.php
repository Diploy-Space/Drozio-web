<?php

namespace Tests\Feature\Pos;

use App\Http\Controllers\Admin\POS\POSController;
use App\Models\Admin;
use App\Services\New\ProductRetailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const SEARCH_CODE = 'POSSEARCHLIMIT';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();

        $this->actingAs(Admin::query()->findOrFail(1), 'admin');
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_search_returns_at_most_the_result_limit(): void
    {
        DB::table('products')->where('added_by', 'admin')->update(['code' => DB::raw("CONCAT('".self::SEARCH_CODE."-', id)")]);

        $matchingProductCount = app(ProductRetailService::class)->getList(
            searchValue: self::SEARCH_CODE,
            dataLimit: 'all',
            filters: [
                'added_by' => ProductRetailService::ADDED_BY_IN_HOUSE,
                ProductRetailService::FILTER_ACTIVE => true,
            ],
        )->count();

        if ($matchingProductCount <= POSController::SEARCH_RESULT_LIMIT) {
            $this->markTestSkipped('The test database has too few active in-house products to exceed the search limit.');
        }

        $response = $this->get(route('admin.retail.pos.search-product', ['name' => self::SEARCH_CODE]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJsonPath('count', POSController::SEARCH_RESULT_LIMIT);
        $this->assertSame(POSController::SEARCH_RESULT_LIMIT, substr_count($response->json('result'), 'action-select-search-product'));
    }

    public function test_search_with_few_matches_returns_all_of_them(): void
    {
        $productId = (int) DB::table('products')->where('added_by', 'admin')->where('status', 1)->value('id');
        DB::table('products')->where('id', $productId)->update(['code' => self::SEARCH_CODE.'-ONLY']);

        $response = $this->get(route('admin.retail.pos.search-product', ['name' => self::SEARCH_CODE.'-ONLY']), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('id', $productId);
    }
}
