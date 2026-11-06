<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Web\v6\ShopController;
use App\Http\Requests\Customer\Product\ListRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class ShopViewContractTest extends ContractTestCase
{
    use SeedsCatalog;

    #[Test]
    public function shop_page_rating_loop_has_no_review_n_plus_one(): void
    {
        $shop = $this->seedAdminShop();
        $this->seedCatalog(products: 6, reviewsPerProduct: 3);
        $this->actingAs(User::find($this->lastCustomerId), 'customer');

        $queriesFor6 = $this->countShopQueries($shop);

        $this->seedCatalog(products: 6, reviewsPerProduct: 3);
        $queriesFor12 = $this->countShopQueries($shop);

        fwrite(STDERR, "\n[SHOP] 6 products => {$queriesFor6} queries; 12 products => {$queriesFor12} queries\n");

        $this->assertLessThanOrEqual(
            4,
            $queriesFor12 - $queriesFor6,
            'Shop page grew by '.($queriesFor12 - $queriesFor6).' queries when products doubled — N+1 regression.'
        );
    }

    private function countShopQueries(object $shop): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request = ListRequest::createFrom(Request::create('/', 'GET'), app(ListRequest::class));
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();

        app(ShopController::class)->show($request, $shop->slug);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
