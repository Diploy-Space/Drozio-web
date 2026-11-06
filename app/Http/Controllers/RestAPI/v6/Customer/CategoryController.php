<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Category\ListRequest;
use App\Http\Requests\Customer\Category\ProductListRequest;
use App\Http\Resources\V6\Customer\CategoryResource;
use App\Http\Resources\V6\Customer\ProductResource;
use App\Services\New\AuthorService;
use App\Services\New\CategoryService;
use App\Services\New\GuestUserService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ShopService;
use App\Services\New\WishlistService;
use App\Traits\CustomerOwnerTrait;
use App\Traits\ShopProductScopeTrait;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CategoryController extends BaseApiController
{
    use CustomerOwnerTrait;
    use ShopProductScopeTrait;

    private const SORT_KEYS = [
        CategoryService::SORT_PRIORITY,
        CategoryService::SORT_LATEST,
        CategoryService::SORT_OLDEST,
        CategoryService::SORT_NAME_ASC,
        CategoryService::SORT_NAME_DESC,
        CategoryService::SORT_NEWEST_ID,
    ];

    private const BASE_LOCALE = 'en';

    private const KEYWORD_MATCH_LIMIT = 10;

    private const HOME_CATEGORY_PRODUCT_LIMIT = 8;

    private const HOME_CATEGORIES_CACHE_KEY_PREFIX = 'cache_home_categories_api_v6_list_';

    private const LIST_QUERY = [
        'limit' => 'nullable|string',
        'offset' => 'nullable|integer',
        'search' => 'nullable|string',
        'sort_by' => 'nullable|string',
        'shop_slug' => 'nullable|string',
    ];

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $productRetailService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly GuestUserService $guestUserService,
        private readonly ShopService $shopService,
    ) {}

    #[ApiDoc(
        summary: 'Category list',
        description: 'Categories with their active product counts. Send shop_slug to receive only the categories the shop has active products in, counted for that shop alone.',
        tag: 'Category',
        query: self::LIST_QUERY,
        status: [404, 422],
    )]
    public function getList(ListRequest $request): JsonResponse
    {
        $shopScope = $this->resolveShopProductIds(shopSlug: $request->shopSlug());

        if (! $shopScope['found']) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = $this->listArgsWithShop($request);
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $categories = $this->categoryList(
            args: $args,
            limit: $limit,
            offset: $offset,
            position: CategoryService::POSITION_CATEGORY,
            countRelation: CategoryService::PRODUCT_RELATION,
            productIds: $shopScope['product_ids'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CategoryResource::collection($categories),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($categories),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Sub category list of a category',
        description: 'Sub categories with their active product counts. Send shop_slug to receive only the sub categories the shop has active products in, counted for that shop alone.',
        tag: 'Category',
        query: self::LIST_QUERY,
        status: [404, 422],
    )]
    public function getSubCategories(ListRequest $request, int|string $categoryId): JsonResponse
    {
        $shopScope = $this->resolveShopProductIds(shopSlug: $request->shopSlug());

        if (! $shopScope['found']) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = $this->listArgsWithShop($request);
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $categories = $this->categoryList(
            args: $args,
            limit: $limit,
            offset: $offset,
            position: CategoryService::POSITION_SUB_CATEGORY,
            parentIds: [(int) $categoryId],
            countRelation: CategoryService::SUB_CATEGORY_PRODUCT_RELATION,
            productIds: $shopScope['product_ids'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CategoryResource::collection($categories),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($categories),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Sub sub category list of a sub category',
        description: 'Sub sub categories with their active product counts. Send shop_slug to receive only the sub sub categories the shop has active products in, counted for that shop alone.',
        tag: 'Category',
        query: self::LIST_QUERY,
        status: [404, 422],
    )]
    public function getSubSubCategories(ListRequest $request, int|string $subCategoryId): JsonResponse
    {
        $shopScope = $this->resolveShopProductIds(shopSlug: $request->shopSlug());

        if (! $shopScope['found']) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = $this->listArgsWithShop($request);
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $categories = $this->categoryList(
            args: $args,
            limit: $limit,
            offset: $offset,
            position: CategoryService::POSITION_SUB_SUB_CATEGORY,
            parentIds: [(int) $subCategoryId],
            countRelation: CategoryService::SUB_SUB_CATEGORY_PRODUCT_RELATION,
            productIds: $shopScope['product_ids'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CategoryResource::collection($categories),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($categories),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Home categories with their products',
        description: 'Categories marked for the home page that have active products, each with its first 8 products. Cached for 3 hours per language. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Category',
        auth: false,
        guard: 'api',
    )]
    public function getHomeCategories(Request $request): JsonResponse
    {
        $categories = Cache::remember(
            $this->getHomeCategoriesCacheKey(),
            CACHE_FOR_3_HOURS,
            fn (): Collection => $this->categoryService->getHomeListWithActiveProducts()
                ->each(fn (Model $category) => $category->setRelation('products', $this->productRetailService->getSectionList(
                    section: ProductRetailService::SECTION_CATEGORY,
                    filters: [ProductRetailService::FILTER_CATEGORY_ID => $category->id],
                    dataLimit: self::HOME_CATEGORY_PRODUCT_LIMIT,
                )->getCollection())),
        );

        $this->stampOwnerFlags(request: $request, products: $categories->flatMap(fn (Model $category) => $category->products));

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CategoryResource::collection($categories),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Find what you need categories',
        description: 'Root categories that have sub categories, each with its first 4 sub categories and their active product counts.',
        tag: 'Category',
        auth: false,
    )]
    public function getFindWhatYouNeed(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CategoryResource::collection($this->categoryService->getFindWhatYouNeedList()),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Products of a category',
        description: 'Active products whose category, sub category or sub sub category is this one. Omit limit to receive every product without pagination. brand_ids, publishing_houses and product_authors accept an array or a JSON array string; 0 in publishing_houses or product_authors selects digital products without one. Send a bearer token to receive the customer\'s wishlist and compare flags.',
        tag: 'Category',
        query: [
            'limit' => 'nullable|integer|min:0',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'product_type' => 'nullable|string',
            'brand_ids' => 'nullable|string',
            'publishing_houses' => 'nullable|string',
            'product_authors' => 'nullable|string',
        ],
        status: [404, 422],
        auth: false,
        guard: 'api',
    )]
    public function getProducts(ProductListRequest $request, int|string $categoryId): JsonResponse
    {
        if ($this->categoryService->find(id: (int) $categoryId, relations: []) === null) {
            return $this->responseFormatter(config: config('response.category_not_found'));
        }

        $args = [
            'search' => $request['search'],
            'product_type' => $request['product_type'],
            'brand_ids' => $request->facetIds(ProductListRequest::FACET_BRAND),
            'publishing_houses' => $request->facetIds(ProductListRequest::FACET_PUBLISHING_HOUSE),
            'product_authors' => $request->facetIds(ProductListRequest::FACET_AUTHOR),
            'limit' => $request->dataLimit(),
            'offset' => $request->page(),
        ];

        $products = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_CATEGORY,
            filters: $this->getCategorySectionFilters(categoryId: (int) $categoryId, args: $args),
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

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    protected function shopService(): ShopService
    {
        return $this->shopService;
    }

    protected function productRetailService(): ProductRetailService
    {
        return $this->productRetailService;
    }

    /**
     * @return array<string, mixed>
     */
    private function listArgsWithShop(ListRequest $request): array
    {
        return [
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by'),
            'shop_slug' => $request->shopSlug(),
        ];
    }

    private function categoryList(
        array $args,
        int|string $limit,
        int $offset,
        int $position,
        array $parentIds = [],
        ?string $countRelation = null,
        ?array $productIds = null,
    ): Collection|LengthAwarePaginator {
        return $this->categoryService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['translations' => fn ($query) => $query->where('locale', app()->getLocale())],
            withCount: $countRelation === null ? [] : [$countRelation.' as product_count' => $this->activeProductCount($productIds)],
            filters: $productIds === null ? [] : [CategoryService::FILTER_PRODUCT_IDS => $productIds],
            sortBy: $this->sortKey($args['sort_by']),
            position: $position,
            parentIds: $parentIds,
        );
    }

    private function activeProductCount(?array $productIds): Closure
    {
        return fn (Builder $query) => $productIds === null
            ? $query->active()
            : $query->active()->whereIn('id', $productIds);
    }

    private function sortKey(?string $sortBy): string
    {
        return in_array($sortBy, self::SORT_KEYS, true) ? $sortBy : CategoryService::SORT_PRIORITY;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCategorySectionFilters(int $categoryId, array $args): array
    {
        $filters = [
            ProductRetailService::FILTER_CATEGORY_ID => $categoryId,
            ProductRetailService::FILTER_PRODUCT_TYPE => $args['product_type'],
            ProductRetailService::FILTER_BRAND_IDS => $args['brand_ids'],
        ];

        if (! empty($args['search'])) {
            $filters[ProductRetailService::FILTER_SEARCH] = $args['search'];
            $filters[ProductRetailService::FILTER_SEARCH_PRODUCT_IDS] = $this->getKeywordProductIds(keyword: (string) $args['search']);
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

    /**
     * @return array<int, int>
     */
    private function getKeywordProductIds(string $keyword): array
    {
        $matches = $this->productRetailService->getSectionList(
            section: ProductRetailService::SECTION_SEARCH,
            filters: [
                ProductRetailService::FILTER_SEARCH => $keyword,
                ProductRetailService::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS => $this->authorService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS => $this->publishingHouseService->getMatchingProductIds(keyword: $keyword),
                ProductRetailService::FILTER_SEARCH_TRANSLATED_ONLY => getDefaultLanguage() !== self::BASE_LOCALE,
            ],
            dataLimit: self::KEYWORD_MATCH_LIMIT,
            page: 1,
        );

        return collect($matches->items())->pluck('id')->all();
    }

    private function getHomeCategoriesCacheKey(): string
    {
        $cacheKey = self::HOME_CATEGORIES_CACHE_KEY_PREFIX.str_replace(' ', '_', strtolower(app()->getLocale()));
        $cacheKeys = Cache::get(CACHE_CONTAINER_FOR_LANGUAGE_WISE_CACHE_KEYS, []);

        if (! in_array($cacheKey, $cacheKeys, true)) {
            $cacheKeys[] = $cacheKey;
            Cache::put(CACHE_CONTAINER_FOR_LANGUAGE_WISE_CACHE_KEYS, $cacheKeys, CACHE_FOR_3_HOURS);
        }

        return $cacheKey;
    }

    private function stampOwnerFlags(Request $request, iterable $products): void
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
