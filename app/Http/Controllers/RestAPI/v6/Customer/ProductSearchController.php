<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Product\FilterRequest;
use App\Http\Requests\Customer\Product\SearchRequest;
use App\Http\Requests\Customer\Product\SuggestionRequest;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Http\Resources\V6\Customer\ProductSuggestionResource;
use App\Services\New\AuthorService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\GuestUserService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductSearchController extends BaseApiController
{
    use CustomerOwnerTrait;

    private const BASE_LOCALE = 'en';

    private const KEYWORD_QUERY = [
        'name' => 'required|string',
        'limit' => 'nullable|integer|min:0',
        'offset' => 'nullable|integer',
    ];

    public function __construct(
        private readonly ProductRetailService $productRetailService,
        private readonly CategoryService $categoryService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Search products by keyword',
        description: 'Matches product names, tags, author and publishing house names, falling back to translated names when the requested page is empty. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Product search',
        query: self::KEYWORD_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function search(SearchRequest $request): JsonResponse
    {
        $args = [
            'name' => $request['name'],
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->getKeywordMatches(
            keyword: (string) $args['name'],
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        $this->stampOwnerFlags(request: $request, products: $products);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Filter products by facets',
        description: 'search is base64 encoded. category, brand, publishing_houses and product_authors accept an array or a JSON array string; 0 in publishing_houses or product_authors selects digital products without one. min_price and max_price are taken from the returned page.',
        tag: 'Product search',
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function filter(FilterRequest $request): JsonResponse
    {
        $args = [
            'search' => $request['search'],
            'category' => $request->facetIds(FilterRequest::FACET_CATEGORY),
            'brand' => $request->facetIds(FilterRequest::FACET_BRAND),
            'publishing_houses' => $request->facetIds(FilterRequest::FACET_PUBLISHING_HOUSE),
            'product_authors' => $request->facetIds(FilterRequest::FACET_AUTHOR),
            'product_type' => $request['product_type'],
            'sort_by' => $request['sort_by'],
            'offer_type' => $request['offer_type'],
            'price_min' => $request['price_min'],
            'price_max' => $request['price_max'],
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_FILTER,
            filters: $this->getFilterSectionFilters(request: $request, args: $args),
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        $this->stampOwnerFlags(request: $request, products: $products);

        $pageRows = collect($products->items());

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
                'min_price' => $pageRows->min('unit_price'),
                'max_price' => $pageRows->max('unit_price'),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Product name suggestions for a keyword',
        description: 'Returns id, slug and name of the products the keyword search matches.',
        tag: 'Product search',
        query: self::KEYWORD_QUERY,
        status: [422],
        auth: false,
        guard: 'api',
    )]
    public function getSuggestions(SuggestionRequest $request): JsonResponse
    {
        $args = [
            'name' => $request['name'],
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->getKeywordMatches(
            keyword: (string) $args['name'],
            dataLimit: $args['limit'],
            page: $args['offset'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ProductSuggestionResource::collection($products),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($products),
            ],
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function getKeywordMatches(string $keyword, int $dataLimit, int $page, bool $translatedOnly = false): LengthAwarePaginator
    {
        return $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SEARCH,
            filters: [
                ProductRetailService::FILTER_SEARCH => $keyword,
                ProductRetailService::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS => $this->authorService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS => $this->publishingHouseService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_SEARCH_TRANSLATED_ONLY => $translatedOnly,
            ],
            dataLimit: $dataLimit,
            page: $page,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getFilterSectionFilters(FilterRequest $request, array $args): array
    {
        $keyword = $request->searchKeyword();
        $categoryIds = $this->categoryService->getIdsByPosition(
            categoryIds: $args['category'],
            dropSubCategoriesWithSelectedChildren: true,
        );

        $filters = [
            ProductRetailService::FILTER_SEARCH => $keyword,
            ProductRetailService::FILTER_PRODUCT_TYPE => $args['product_type'],
            ProductRetailService::FILTER_BRAND_IDS => $args['brand'],
            ProductRetailService::FILTER_CATEGORY_IDS => $categoryIds['category_ids'],
            ProductRetailService::FILTER_SUB_CATEGORY_IDS => $categoryIds['sub_category_ids'],
            ProductRetailService::FILTER_SUB_SUB_CATEGORY_IDS => $categoryIds['sub_sub_category_ids'],
            ProductRetailService::FILTER_SORT_BY => $args['sort_by'],
            ProductRetailService::FILTER_OFFER_TYPE => $args['offer_type'],
            ProductRetailService::FILTER_PRICE_MIN => $args['price_min'],
            ProductRetailService::FILTER_PRICE_MAX => $args['price_max'],
        ];

        if ($request->hasSearchKeyword()) {
            $filters[ProductRetailService::FILTER_SEARCH_PRODUCT_IDS] = collect($this->getKeywordMatches(
                keyword: $keyword,
                dataLimit: $args['limit'],
                page: $args['offset'],
                translatedOnly: app()->getLocale() !== self::BASE_LOCALE,
            )->items())->pluck('id')->all();
        }

        if ($args['offer_type'] === ProductRetailService::OFFER_CLEARANCE_SALE) {
            $filters[ProductRetailService::FILTER_CLEARANCE_PRODUCT_IDS] = $this->stockClearanceProductService->getProductIds(running: true);
        }

        if ($args['publishing_houses'] !== []) {
            $filters[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS] = $args['publishing_houses'];
            $filters[ProductRetailService::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds(ownerIds: $args['publishing_houses']);
            $filters[ProductRetailService::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds();
        }

        if ($args['product_authors'] !== []) {
            $filters[ProductRetailService::FILTER_AUTHOR_IDS] = $args['product_authors'];
            $filters[ProductRetailService::FILTER_SELECTED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds(ownerIds: $args['product_authors']);
            $filters[ProductRetailService::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds();
        }

        return $filters;
    }

    private function stampOwnerFlags(Request $request, LengthAwarePaginator|Collection $products): void
    {
        $owner = $this->resolveCustomerOwner($request);
        $customerId = $owner !== null && ! $owner['is_guest'] ? (int) $owner['id'] : null;

        $this->productRetailService->applyOwnerFlags(
            products: $products,
            wishlistProductIds: $customerId === null ? [] : $this->wishlistService->getProductIds(customerId: $customerId),
            compareProductIds: $customerId === null ? [] : $this->productCompareService->getProductIds(customerId: $customerId),
        );
    }
}
