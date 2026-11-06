<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Web\v6\HomeController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class HomeContractTest extends ContractTestCase
{
    use SeedsCatalog;

    private const CARD_LISTS = [
        'latestProductsList',
        'featuredProductsList',
        'newArrivalProducts',
        'topRatedProducts',
        'bestSellProduct',
        'clearanceSaleProducts',
        'dealOfTheDay',
    ];

    #[Test]
    public function home_product_sections_do_not_n_plus_one_on_reviews_or_seller(): void
    {
        $this->seedCatalog(products: 6, reviewsPerProduct: 3);
        [$queriesFor6, $rendered6] = $this->renderHomeCards();

        $this->seedCatalog(products: 6, reviewsPerProduct: 3);
        [$queriesFor12, $rendered12] = $this->renderHomeCards();

        fwrite(STDERR, "\n[HOME] 6 products => {$queriesFor6} queries ({$rendered6} cards); "
            ."12 products => {$queriesFor12} queries ({$rendered12} cards)\n");

        $this->assertGreaterThan($rendered6, $rendered12, 'Catalog did not grow as expected.');

        $this->assertLessThanOrEqual(
            6,
            $queriesFor12 - $queriesFor6,
            'Query count grew by '.($queriesFor12 - $queriesFor6).' when products doubled — N+1 regression.'
        );
    }

    private function renderHomeCards(): array
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $viewData = app(HomeController::class)->index()->getData();
        $rendered = 0;

        foreach (self::CARD_LISTS as $list) {
            foreach ($this->toIterable($viewData[$list] ?? []) as $product) {
                $reviewCount = count($product->reviews);
                $shopName = optional(optional($product->seller)->shop)->name;
                $this->assertIsInt($reviewCount);
                $rendered++;
            }
        }

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$count, $rendered];
    }

    private function toIterable($list): iterable
    {
        if (is_iterable($list)) {
            return $list;
        }
        if (is_object($list) && method_exists($list, 'get')) {
            return $list->get();
        }

        return [];
    }
}
