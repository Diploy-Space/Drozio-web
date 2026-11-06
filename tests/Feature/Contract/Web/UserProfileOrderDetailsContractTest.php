<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Web\v6\CustomerOrderController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class UserProfileOrderDetailsContractTest extends ContractTestCase
{
    use SeedsCatalog;

    #[Test]
    public function account_order_details_review_lookup_is_scale_invariant(): void
    {
        $productIds = $this->seedCatalog(products: 8, reviewsPerProduct: 0);
        $customer = User::find($this->lastCustomerId);
        $this->actingAs($customer, 'customer');

        $smallOrder = $this->seedOrderWithDetails($customer->id, array_slice($productIds, 0, 4));
        $largeOrder = $this->seedOrderWithDetails($customer->id, $productIds);

        $queriesSmall = $this->countDetailQueries($smallOrder);
        $queriesLarge = $this->countDetailQueries($largeOrder);

        fwrite(STDERR, "\n[ACCOUNT-ORDER] 4 lines => {$queriesSmall} queries; 8 lines => {$queriesLarge} queries\n");

        $this->assertLessThanOrEqual(
            2,
            $queriesLarge - $queriesSmall,
            'Order details grew by '.($queriesLarge - $queriesSmall).' queries when lines doubled — review N+1 regression.'
        );
    }

    private function countDetailQueries(int $orderId): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $view = app(CustomerOrderController::class)->details(
            Request::create('/', 'GET', ['id' => $orderId])
        );

        foreach ($view->getData()['order']->details as $detail) {
            $x = $detail['reviewData'];
        }

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
