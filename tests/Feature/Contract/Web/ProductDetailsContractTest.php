<?php

namespace Tests\Feature\Contract\Web;

use App\Http\Controllers\Web\v6\ProductDetailsController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
use Tests\Feature\Contract\Support\SeedsCatalog;

class ProductDetailsContractTest extends ContractTestCase
{
    use SeedsCatalog;

    #[Test]
    public function related_and_more_from_seller_cards_have_no_seller_shop_n_plus_one(): void
    {
        $data = $this->seedSellerCatalog(products: 8, reviewsPerProduct: 2);
        $this->actingAs(User::find($data['customerId']), 'customer');

        $view = app(ProductDetailsController::class)->show($data['slugs'][0]);
        $viewData = $view->getData();
        $related = $viewData['relatedProducts'];
        $moreFromSeller = $viewData['moreProductFromSeller'];

        $this->assertGreaterThan(0, $related->count(), 'No related products surfaced.');

        DB::flushQueryLog();
        DB::enableQueryLog();
        foreach ($related as $product) {
            $shopName = optional(optional($product->seller)->shop)->name;
            $reviewCount = count($product->reviews);
        }
        foreach ($moreFromSeller as $product) {
            $shopName = optional(optional($product->seller)->shop)->name;
        }
        $renderQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        fwrite(STDERR, "\n[PDP] related={$related->count()} more={$moreFromSeller->count()} "
            ."render-time queries={$renderQueries}\n");

        $this->assertLessThanOrEqual(
            2,
            $renderQueries,
            "Product-detail cards ran {$renderQueries} queries at render — seller.shop/reviews N+1."
        );
    }
}
