<?php

namespace Tests\Feature\Contract\Web;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class ReviewPaginationContractTest extends ContractTestCase
{
    use SeedsCatalog;

    #[Test]
    public function review_list_uses_simple_pagination_and_keeps_shape(): void
    {
        $productIds = $this->seedCatalog(products: 1, reviewsPerProduct: 3);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->post(route('review.product-list'), ['product_id' => $productIds[0], 'offset' => 0]);
        $countStarQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains(strtolower($q['query']), 'count(*)') && str_contains($q['query'], '`reviews`'))
            ->count();
        DB::disableQueryLog();

        $payload = $response->assertOk()->json();

        foreach (['productReview', 'not_empty', 'checkReviews'] as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
        $this->assertIsInt($payload['not_empty']);
        $this->assertIsInt($payload['checkReviews']);

        fwrite(STDERR, "\n[REVIEW-PAGINATION] reviews count(*) queries={$countStarQueries}\n");
        $this->assertSame(0, $countStarQueries, 'simplePaginate should not run a COUNT(*) on reviews.');
    }
}
