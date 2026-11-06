<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Web\v6\RestockRequestController;
use App\Models\User;
use App\Services\New\ProductCompareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class CompareRestockContractTest extends ContractTestCase
{
    use SeedsCatalog;

    private const COMPARE_RELATIONS = [
        'product',
        'product.digitalVariation',
        'product.reviews',
        'product.brand',
        'product.category',
    ];

    #[Test]
    public function compare_list_rows_have_no_product_relation_n_plus_one(): void
    {
        $data = $this->seedCompareAndRestock(items: 6);
        $this->actingAs(User::find($data['customerId']), 'customer');

        $compareLists = app(ProductCompareService::class)->getList(
            dataLimit: 'all',
            relations: self::COMPARE_RELATIONS,
            filters: ['user_id' => $data['customerId'], ProductCompareService::FILTER_HAS_PRODUCT => true],
            sortBy: ProductCompareService::SORT_NEWEST_ID,
        );
        $this->assertGreaterThan(0, $compareLists->count());

        DB::flushQueryLog();
        DB::enableQueryLog();
        foreach ($compareLists as $compareList) {
            $avg = $compareList->product?->reviews?->avg('rating');
            $brand = $compareList->product?->brand?->name;
            $category = $compareList->product?->category?->name;
        }
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        fwrite(STDERR, "\n[COMPARE] rows={$compareLists->count()} render-time queries={$queries}\n");
        $this->assertLessThanOrEqual(2, $queries, "Compare list ran {$queries} render queries — product relation N+1.");
    }

    #[Test]
    public function restock_rows_have_no_product_relation_n_plus_one(): void
    {
        $data = $this->seedCompareAndRestock(items: 6);
        $this->actingAs(User::find($data['customerId']), 'customer');

        $restockProducts = app(RestockRequestController::class)
            ->index(Request::create('/', 'GET'))
            ->getData()['restockProducts'];
        $this->assertGreaterThan(0, $restockProducts->count());

        DB::flushQueryLog();
        DB::enableQueryLog();
        foreach ($restockProducts as $restockProduct) {
            $reviewCount = count($restockProduct?->product?->reviews ?? []);
            $brand = $restockProduct?->product?->brand?->name;
        }
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        fwrite(STDERR, "\n[RESTOCK] rows={$restockProducts->count()} render-time queries={$queries}\n");
        $this->assertLessThanOrEqual(2, $queries, "Restock list ran {$queries} render queries — product relation N+1.");
    }
}
