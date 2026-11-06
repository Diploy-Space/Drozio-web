<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\SearchRequest;
use App\Services\New\AuthorService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    private const SEARCH_LIMIT = 10;

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
    ) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $keyword = (string) $request['name'];

        $products = $this->productService->searchForStorefront(
            keyword: $keyword,
            categoryId: $request['category_id'] ?? ProductRetailService::CATEGORY_ALL,
            authorProductIds: $this->authorService->getMatchingProductIds(keyword: $keyword),
            publishingHouseProductIds: $this->publishingHouseService->getMatchingProductIds(keyword: $keyword),
            limit: self::SEARCH_LIMIT,
            page: 1,
        );

        $sellerProducts = $this->productService->getShopMatchedProducts(
            productIds: $this->getInHouseProductIds(keyword: $keyword),
            keyword: $keyword,
        );

        return response()->json([
            'result' => view(themeViewName(key: 'product_search_result'), [
                'products' => $products,
                'seller_products' => $sellerProducts,
            ])->render(),
            'seller_products' => $sellerProducts->count(),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function getInHouseProductIds(string $keyword): array
    {
        $inHouseShopName = getInHouseShopConfig(key: 'name');

        return $inHouseShopName !== null && str_contains($keyword, $inHouseShopName)
            ? $this->productService->getActiveInHouseIds()
            : [];
    }
}
