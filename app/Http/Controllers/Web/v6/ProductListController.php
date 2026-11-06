<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\FilterRequest;
use App\Http\Requests\Customer\Product\ListRequest;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\Deal\FeaturedDealService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\RobotsMetaContentService;
use App\Services\New\ShopService;
use App\Services\ProductRecommendationService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductListController extends Controller
{
    private const THEME_ASTER = 'theme_aster';

    private const PAGE_SIZE = 20;

    private const FILTER_PAGE_SIZE = 25;

    private const PER_PAGE_PRODUCT_KEY = 'per_page_product';

    private const PRODUCT_VIEW_SESSION_KEY = 'product_view_style';

    private const DEFAULT_AJAX_PARTIAL = 'web-views.products._ajax-products';

    private const ASTER_FILTER_TAGS_PARTIAL = 'theme-views.product._selected_filter_tags';

    private const FLASH_DEAL_PRODUCTS_VIEW_DATA_KEYS = [
        'id',
        'name',
        'data_from',
        'sort_by',
        'page_no' => 'page',
        'min_price',
        'max_price',
        'product_type',
        'search_category_value',
    ];

    private const SHOP_ID_KEY = 'shop_id';

    private const CATEGORY_POSITION_FILTERS = [
        CategoryService::POSITION_CATEGORY => ProductRetailService::FILTER_CATEGORY_ID,
        CategoryService::POSITION_SUB_CATEGORY => ProductRetailService::FILTER_SUB_CATEGORY_ID,
        CategoryService::POSITION_SUB_SUB_CATEGORY => ProductRetailService::FILTER_SUB_SUB_CATEGORY_ID,
    ];

    private ?Model $runningFeaturedDeal = null;

    private bool $isRunningFeaturedDealResolved = false;

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly StockClearanceSetupService $stockClearanceSetupService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly FlashDealService $flashDealService,
        private readonly FeaturedDealService $featuredDealService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly ShopService $shopService,
    ) {}

    public function index(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $searchKeyword = $request->filled('name') ? $request['name'] : $request->input('product_name');

        if ($searchKeyword && auth('customer')->check()) {
            dispatch(fn () => app(ProductRecommendationService::class)->recordSearch($searchKeyword))->afterResponse();
        }

        return $this->renderList(request: $request, pageTitle: $this->getSearchPageTitle($request));
    }

    public function brandProducts(ListRequest $request, string $slug): View|JsonResponse|RedirectResponse
    {
        $brand = $this->brandService->findBy(params: ['slug' => $slug, 'status' => 1], relations: ['seo']);

        if (! $brand) {
            ToastMagic::warning(translate('Not found'));

            return redirect()->route('home');
        }

        $this->mergeRouteFilters($request, [
            ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_BRAND,
            ProductRetailService::FILTER_BRAND_ID => $brand['id'],
        ]);

        return $this->renderList(
            request: $request,
            pageTitle: $this->getEntityPageTitle($brand['name']),
            metaData: $brand?->seo,
        );
    }

    public function categoryProducts(ListRequest $request, string $slug): View|JsonResponse|RedirectResponse
    {
        $category = $this->categoryService->findBySlug(slug: $slug);

        if (! $category) {
            ToastMagic::warning(translate('Not found'));

            return redirect()->route('home');
        }

        $this->mergeRouteFilters($request, [
            ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_CATEGORY,
            self::CATEGORY_POSITION_FILTERS[$this->categoryService->getFilterPosition(category: $category)] => $category['id'],
        ]);

        return $this->renderList(
            request: $request,
            pageTitle: $this->getEntityPageTitle($category['name']),
            metaData: $category?->seo,
        );
    }

    public function featuredProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_FEATURED]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Featured Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_FEATURED_PRODUCTS]),
        );
    }

    public function featuredDealProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_OFFER_TYPE => ProductRetailService::OFFER_FEATURED_DEAL]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Featured Deal Products'),
            metaData: $this->getRunningFeaturedDeal()?->seo,
        );
    }

    public function latestProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_LATEST]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Latest Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_LATEST_PRODUCTS]),
        );
    }

    public function bestSellingProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_BEST_SELLING]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Best Selling Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_BEST_SELLING_PRODUCTS]),
        );
    }

    public function topRatedProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_TOP_RATED]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Top Rated Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_TOP_RATED_PRODUCTS]),
        );
    }

    public function mostFavoriteProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_MOST_FAVORITE]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Most Favorite Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_MOST_FAVORITE_PRODUCTS]),
        );
    }

    public function discountedProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_OFFER_TYPE => ProductRetailService::OFFER_DISCOUNTED]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Discounted Products'),
            metaData: $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::PAGE_NAME_DISCOUNTED_PRODUCTS]),
        );
    }

    public function clearanceSaleProducts(ListRequest $request): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_OFFER_TYPE => ProductRetailService::OFFER_CLEARANCE_SALE]);

        return $this->renderList(
            request: $request,
            pageTitle: translate('Clearance Sale Products'),
            metaData: $this->stockClearanceSetupService
                ->findBy(params: ['setup_by' => StockClearanceSetupService::SETUP_BY_ADMIN])?->seo,
        );
    }

    public function flashDealProducts(ListRequest $request, string $id): View|JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [
            ProductRetailService::FILTER_OFFER_TYPE => ProductRetailService::OFFER_FLASH_DEALS,
            ProductRetailService::FILTER_FLASH_DEALS_ID => $id,
        ]);

        $this->mergeSearchFromProductName($request);

        $deal = $this->getRunningFlashDeal($id);

        if ($deal === null) {
            ToastMagic::warning(translate('Not found'));

            return back();
        }

        return $this->renderFlashDealList($request, $deal);
    }

    public function filterFlashDealProducts(ListRequest $request, string $id): JsonResponse|RedirectResponse
    {
        $this->mergeRouteFilters($request, [ProductRetailService::FILTER_FLASH_DEALS_ID => $id]);

        $priceRangeResponse = $this->rejectInvalidPriceRange($request);

        if ($priceRangeResponse !== null) {
            return $priceRangeResponse;
        }

        $this->mergeSearchFromProductName($request);

        $type = $request->filled(ProductRetailService::FILTER_FLASH_DEALS_ID)
            ? ProductRetailService::OFFER_FLASH_DEALS
            : null;

        $this->normalizeListRequest($request);

        $products = $this->productService->getStorefrontList(
            filters: $this->resolveFilters($request, $type),
            sortBy: $request['sort_by'],
            currencyCode: session('currency_code'),
            type: $type,
        );

        $productsCount = $products->count();
        $viewData = $this->getFlashDealProductsViewData($request);
        $paginatedProducts = $products->paginate(self::PAGE_SIZE)->appends($viewData);

        return response()->json([
            'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                'products' => $paginatedProducts,
                'paginate_count' => $paginatedProducts->lastPage(),
                'page' => $paginatedProducts->currentPage(),
                'request_data' => $viewData,
                'singlePageProductCount' => self::PAGE_SIZE,
                'data' => $viewData,
            ])->render(),
            'html_tags' => $this->renderSelectedFilterTags($request),
            'products_count' => $productsCount,
            'products' => $paginatedProducts,
            'singlePageProductCount' => self::PAGE_SIZE,
        ]);
    }

    public function filterProducts(FilterRequest $request): JsonResponse|RedirectResponse
    {
        $priceRangeResponse = $this->rejectInvalidPriceRange($request);

        if ($priceRangeResponse !== null) {
            return $priceRangeResponse;
        }

        $this->rememberProductViewStyle($request);

        $singlePageProductCount = (int) ($request[self::PER_PAGE_PRODUCT_KEY] ?? self::FILTER_PAGE_SIZE);
        $shopScope = $this->getShopScope($request);
        $viewData = $request->viewData();

        $this->normalizeListRequest($request);

        $products = $this->productService->getStorefrontList(
            filters: $this->resolveFilters($request, null),
            sortBy: $request['sort_by'],
            sellerId: $shopScope['seller_id'],
            addedBy: $shopScope['added_by'],
            currencyCode: session('currency_code'),
        );

        $productsCount = $products->count();
        $paginatedProducts = $products->paginate($singlePageProductCount)->appends($viewData);

        return response()->json([
            'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                'products' => $paginatedProducts,
                'paginate_count' => $paginatedProducts->lastPage(),
                'page' => $paginatedProducts->currentPage(),
                'request_data' => $viewData,
                'singlePageProductCount' => $singlePageProductCount,
                'data' => $viewData,
            ])->render(),
            'html_tags' => $this->renderSelectedFilterTags($request),
            'products_count' => $productsCount,
            'products' => $paginatedProducts,
            'singlePageProductCount' => $singlePageProductCount,
        ]);
    }

    /**
     * @return array{seller_id: ?int, added_by: ?string}
     */
    private function getShopScope(FilterRequest $request): array
    {
        if (! $request->has(self::SHOP_ID_KEY)) {
            return ['seller_id' => null, 'added_by' => null];
        }

        $shopId = (int) $request[self::SHOP_ID_KEY];

        return $shopId === ShopService::IN_HOUSE_ID
            ? ['seller_id' => ShopService::IN_HOUSE_ID, 'added_by' => ProductRetailService::ADDED_BY_ADMIN]
            : [
                'seller_id' => (int) $this->shopService->find(id: $shopId)?->seller_id,
                'added_by' => ProductRetailService::ADDED_BY_SELLER,
            ];
    }

    private function renderSelectedFilterTags(ListRequest $request): string
    {
        if (theme_root_path() !== self::THEME_ASTER) {
            return '';
        }

        return view(self::ASTER_FILTER_TAGS_PARTIAL, [
            ...$this->getSelectedFilterTags($request),
            'selectedRatings' => $request[ProductRetailService::FILTER_RATING] ?? [],
            'sort_by' => $request['sort_by'],
        ])->render();
    }

    private function mergeSearchFromProductName(ListRequest $request): void
    {
        if (! $request->filled(ProductRetailService::FILTER_PRODUCT_NAME)) {
            return;
        }

        $this->mergeRouteFilters($request, [
            ProductRetailService::FILTER_DATA_FROM => ProductRetailService::LIST_SEARCH,
            ProductRetailService::FILTER_SEARCH => $request[ProductRetailService::FILTER_PRODUCT_NAME],
        ]);
    }

    private function getRunningFlashDeal(string $id): ?Model
    {
        return $this->flashDealService
            ->getRunningDeals(relations: ['seo'], withCount: ['products'], locale: getDefaultLanguage())
            ->firstWhere('id', (int) $id);
    }

    private function renderFlashDealList(ListRequest $request, Model $deal): View|JsonResponse
    {
        $this->rememberProductViewStyle($request);

        $viewData = $request->viewData();

        $this->normalizeListRequest($request);

        $products = $this->productService->getStorefrontList(
            filters: $this->resolveFilters($request, ProductRetailService::OFFER_FLASH_DEALS),
            sortBy: $request['sort_by'],
            currencyCode: session('currency_code'),
            type: ProductRetailService::OFFER_FLASH_DEALS,
        );

        $ratings = $this->productService->getRatingCounts($products);
        $paginatedProducts = $this->paginate($this->applyRatingBand($products, $request), $viewData);
        $productIds = $paginatedProducts->pluck('id')->toArray();
        $dealProductIds = $this->flashDealProductService->getProductIds(dealId: $deal->id);

        if ($request->ajax()) {
            return response()->json([
                'total_product' => $paginatedProducts->total(),
                'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                    'products' => $paginatedProducts,
                    'product_ids' => $productIds,
                    'singlePageProductCount' => self::PAGE_SIZE,
                    'page' => $paginatedProducts->currentPage(),
                ])->render(),
            ], 200);
        }

        return view(themeViewName(key: 'flash_deals'), [
            'pageTitleContent' => translate('Flash Deal Products'),
            'products' => $paginatedProducts,
            'paginate_count' => $paginatedProducts->lastPage(),
            'data' => $viewData,
            'ratings' => $ratings,
            'selectedRatings' => $request[ProductRetailService::FILTER_RATING] ?? [],
            'product_ids' => $productIds,
            'activeBrands' => $this->getActiveBrands($request),
            'productCategories' => $this->getCategoryTree($request, ProductRetailService::OFFER_FLASH_DEALS),
            'publishingHouses' => $this->getFlashDealPublishingHouses($dealProductIds),
            'digitalProductAuthors' => $this->getFlashDealAuthors($dealProductIds),
            'deal' => $deal,
            'singlePageProductCount' => self::PAGE_SIZE,
            'page' => $paginatedProducts->currentPage(),
            'robotsMetaContentData' => $deal?->seo,
        ]);
    }

    private function applyRatingBand(Collection $products, ListRequest $request): Collection
    {
        $ratingBand = $request[ProductRetailService::FILTER_RATINGS];

        if (! is_numeric($ratingBand)) {
            return $products;
        }

        $lowerBound = (float) $ratingBand;

        return $products->filter(function (Model $product) use ($lowerBound): bool {
            $average = (float) ($product->rating->pluck('average')[0] ?? 0);

            return $average >= $lowerBound && $average < $lowerBound + 1;
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function getFlashDealProductsViewData(ListRequest $request): array
    {
        $data = [];

        foreach (self::FLASH_DEAL_PRODUCTS_VIEW_DATA_KEYS as $dataKey => $requestKey) {
            $data[is_int($dataKey) ? $requestKey : $dataKey] = $request[$requestKey];
        }

        if ($request->has(self::SHOP_ID_KEY)) {
            $data[self::SHOP_ID_KEY] = $request[self::SHOP_ID_KEY];
        }

        return $data;
    }

    private function mergeRouteFilters(ListRequest $request, array $filters): void
    {
        $request->merge($filters);
        request()->merge($filters);
    }

    private function renderList(
        ListRequest $request,
        string $pageTitle,
        ?Model $metaData = null,
        ?string $type = null,
    ): View|JsonResponse|RedirectResponse {
        $priceRangeResponse = $this->rejectInvalidPriceRange($request);

        if ($priceRangeResponse !== null) {
            return $priceRangeResponse;
        }

        $this->rememberProductViewStyle($request);

        $viewData = $request->viewData();

        $this->normalizeListRequest($request);

        $products = $this->productService->getStorefrontList(
            filters: $this->resolveFilters($request, $type),
            sortBy: $request['sort_by'],
            currencyCode: session('currency_code'),
            type: $type,
        );

        return theme_root_path() === self::THEME_ASTER
            ? $this->getAsterPayload($request, $products, $viewData, $pageTitle, $metaData)
            : $this->getDefaultPayload($request, $products, $viewData, $pageTitle, $metaData);
    }

    private function getDefaultPayload(
        ListRequest $request,
        Collection $products,
        array $viewData,
        string $pageTitle,
        ?Model $metaData,
    ): View|JsonResponse {
        $paginatedProducts = $this->paginate($products, $viewData);

        if ($request->ajax()) {
            return response()->json([
                'total_product' => $paginatedProducts->total(),
                'html_products' => view(self::DEFAULT_AJAX_PARTIAL, ['products' => $paginatedProducts])->render(),
            ], 200);
        }

        return view(themeViewName(key: 'products_view_page'), [
            'pageTitleContent' => $pageTitle,
            'products' => $paginatedProducts,
            'data' => $viewData,
            'activeBrands' => $this->getActiveBrands($request),
            'categories' => $this->getCategoryTree($request),
            'robotsMetaContentData' => $metaData,
        ]);
    }

    private function getAsterPayload(
        ListRequest $request,
        Collection $products,
        array $viewData,
        string $pageTitle,
        ?Model $metaData,
    ): View|JsonResponse {
        $ratings = $this->productService->getRatingCounts($products);
        $paginatedProducts = $this->paginate($products, $viewData);
        $productIds = $paginatedProducts->pluck('id')->toArray();
        $selectedRatings = $request['rating'] ?? [];

        if ($request->ajax()) {
            return response()->json([
                'total_product' => $paginatedProducts->total(),
                'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                    'products' => $paginatedProducts,
                    'product_ids' => $productIds,
                    'singlePageProductCount' => self::PAGE_SIZE,
                    'page' => $request['page'] ?? 1,
                ])->render(),
                'html_tags' => view(self::ASTER_FILTER_TAGS_PARTIAL, [
                    ...$this->getSelectedFilterTags($request),
                    'selectedRatings' => $selectedRatings,
                    'sort_by' => $request['sort_by'],
                ])->render(),
            ], 200);
        }

        return view(themeViewName(key: 'products_view_page'), [
            'pageTitleContent' => $pageTitle,
            'products' => $paginatedProducts,
            'data' => $viewData,
            'ratings' => $ratings,
            'selectedRatings' => $selectedRatings,
            'product_ids' => $productIds,
            'activeBrands' => $this->getActiveBrands($request),
            'categories' => $this->getCategoryTree($request),
            'singlePageProductCount' => self::PAGE_SIZE,
            'page' => $request['page'] ?? 1,
            ...$this->getSelectedFilterTags($request),
            'sort_by' => $request['sort_by'],
            'robotsMetaContentData' => $metaData,
        ]);
    }

    private function paginate(Collection $products, array $appends): LengthAwarePaginator
    {
        return $products->paginate(self::PAGE_SIZE)->appends($appends);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSelectedFilterTags(ListRequest $request): array
    {
        return [
            'tags_category' => $request[ProductRetailService::FILTER_CATEGORY_IDS]
                ? $this->categoryService->getList(
                    dataLimit: 'all',
                    filters: ['id' => $request[ProductRetailService::FILTER_CATEGORY_IDS]],
                    sortBy: CategoryService::SORT_OLDEST_ID,
                    position: null,
                )
                : [],
            'tags_brands' => $request[ProductRetailService::FILTER_BRAND_IDS]
                ? $this->brandService->getList(
                    dataLimit: 'all',
                    filters: ['id' => $request[ProductRetailService::FILTER_BRAND_IDS]],
                    sortBy: BrandService::SORT_OLDEST_ID,
                )
                : [],
            'publishingHouse' => $request[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS]
                ? $this->publishingHouseService->getList(
                    dataLimit: 'all',
                    filters: ['id' => $request[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS]],
                )
                : [],
            'productAuthors' => $request[ProductRetailService::FILTER_AUTHOR_IDS]
                ? $this->authorService->getList(
                    dataLimit: 'all',
                    filters: ['id' => $request[ProductRetailService::FILTER_AUTHOR_IDS]],
                )
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(ListRequest $request, ?string $type): array
    {
        $filters = $request->filters();
        $offerType = $filters[ProductRetailService::FILTER_OFFER_TYPE] ?? null;

        if (in_array($offerType, [ProductRetailService::OFFER_CLEARANCE_SALE, ProductRetailService::OFFER_DISCOUNTED], true)) {
            $filters[ProductRetailService::FILTER_CLEARANCE_PRODUCT_IDS] = $this->stockClearanceProductService->getProductIds(running: true);
        }

        if ($offerType === ProductRetailService::OFFER_FEATURED_DEAL) {
            $filters[ProductRetailService::FILTER_FEATURED_DEAL_PRODUCT_IDS] = $this->getFeaturedDealProductIds();
        }

        if ($type === ProductRetailService::OFFER_FLASH_DEALS && ! empty($filters[ProductRetailService::FILTER_FLASH_DEALS_ID])) {
            $filters[ProductRetailService::FILTER_FLASH_DEAL_PRODUCT_IDS] = $this->flashDealProductService
                ->getProductIds(dealId: $filters[ProductRetailService::FILTER_FLASH_DEALS_ID]);
        }

        return [
            ...$filters,
            ...$this->resolvePublisherFilters($filters),
            ...$this->resolveAuthorFilters($filters),
            ...$this->resolveCategoryTreeFilters($filters),
            ...$this->resolveKeywordFilters($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePublisherFilters(array $filters): array
    {
        $publishingHouseId = $filters[ProductRetailService::FILTER_PUBLISHING_HOUSE_ID] ?? null;
        $publishingHouseIds = $filters[ProductRetailService::FILTER_PUBLISHING_HOUSE_IDS] ?? [];
        $resolved = [];

        if (($publishingHouseId != '' && $publishingHouseId == 0) || in_array(0, (array) $publishingHouseIds)) {
            $resolved[ProductRetailService::FILTER_ATTACHED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService->getAttachedProductIds();
        }

        if (! empty($publishingHouseIds)) {
            $resolved[ProductRetailService::FILTER_SELECTED_PUBLISHER_PRODUCT_IDS] = $this->publishingHouseService
                ->getAttachedProductIds(ownerIds: (array) $publishingHouseIds);
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAuthorFilters(array $filters): array
    {
        $authorId = $filters[ProductRetailService::FILTER_AUTHOR_ID] ?? null;
        $authorIds = $filters[ProductRetailService::FILTER_AUTHOR_IDS] ?? [];
        $resolved = [];

        if (($authorId != '' && $authorId == 0) || in_array(0, (array) $authorIds)) {
            $resolved[ProductRetailService::FILTER_ATTACHED_AUTHOR_PRODUCT_IDS] = $this->authorService->getAttachedProductIds();
        }

        if (! empty($authorIds) && is_array($authorIds)) {
            $resolved[ProductRetailService::FILTER_SELECTED_AUTHOR_PRODUCT_IDS] = $this->authorService
                ->getAttachedProductIds(ownerIds: $authorIds);
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCategoryTreeFilters(array $filters): array
    {
        if (! $this->productService->hasCategoryTreeFilter($filters)) {
            return [];
        }

        $categoryIds = $filters[ProductRetailService::FILTER_CATEGORY_IDS] ?? [];

        $filterCategoryIds = ! empty($categoryIds) && is_array($categoryIds)
            ? $this->categoryService->getCategoryIdsForFilter(
                categoryIds: $categoryIds,
                subCategoryIds: (array) ($filters[ProductRetailService::FILTER_SUB_CATEGORY_IDS] ?? []),
                subSubCategoryIds: (array) ($filters[ProductRetailService::FILTER_SUB_SUB_CATEGORY_IDS] ?? []),
            )
            : $this->categoryService->getCategoryIdsForFilter(
                categoryIds: [$filters[ProductRetailService::FILTER_CATEGORY_ID] ?? null],
                subCategoryIds: [$filters[ProductRetailService::FILTER_SUB_CATEGORY_ID] ?? null],
                subSubCategoryIds: [$filters[ProductRetailService::FILTER_SUB_SUB_CATEGORY_ID] ?? null],
            );

        return [
            ProductRetailService::FILTER_TREE_CATEGORY_IDS => $filterCategoryIds['category_ids'],
            ProductRetailService::FILTER_TREE_SUB_CATEGORY_IDS => $filterCategoryIds['sub_category_ids'],
            ProductRetailService::FILTER_TREE_SUB_SUB_CATEGORY_IDS => $filterCategoryIds['sub_sub_category_ids'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveKeywordFilters(array $filters): array
    {
        $keyword = $this->productService->getStorefrontSearchKeyword($filters);

        if ($keyword === null) {
            return [];
        }

        return [
            ProductRetailService::FILTER_KEYWORD_AUTHOR_PRODUCT_IDS => $this->authorService->getMatchingProductIds($keyword),
            ProductRetailService::FILTER_KEYWORD_PUBLISHER_PRODUCT_IDS => $this->publishingHouseService->getMatchingProductIds($keyword),
        ];
    }

    private function rejectInvalidPriceRange(ListRequest $request): JsonResponse|RedirectResponse|null
    {
        $hasRange = $request->has(ProductRetailService::FILTER_MIN_PRICE)
            && $request[ProductRetailService::FILTER_MIN_PRICE] != ''
            && $request->has(ProductRetailService::FILTER_MAX_PRICE)
            && $request[ProductRetailService::FILTER_MAX_PRICE] != '';

        if (! $hasRange || $request[ProductRetailService::FILTER_MIN_PRICE] <= $request[ProductRetailService::FILTER_MAX_PRICE]) {
            return null;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => 0,
                'message' => translate('Minimum price should be less than or equal to maximum price.'),
            ]);
        }

        ToastMagic::error(translate('Minimum price should be less than or equal to maximum price.'));

        return back();
    }

    private function normalizeListRequest(ListRequest $request): void
    {
        if ($request->has(ProductRetailService::FILTER_CATEGORY_IDS)) {
            $request->merge([
                ProductRetailService::FILTER_CATEGORY_IDS => array_filter(
                    (array) $request[ProductRetailService::FILTER_CATEGORY_IDS],
                    static fn ($value): bool => ! is_null($value) && $value != ProductRetailService::CATEGORY_ALL,
                ),
            ]);

            return;
        }

        if ($request->has(ProductRetailService::FILTER_SEARCH_CATEGORY_VALUE)) {
            unset($request[ProductRetailService::FILTER_SEARCH_CATEGORY_VALUE]);
        }
    }

    private function rememberProductViewStyle(ListRequest $request): void
    {
        $viewStyle = $request->productViewStyle();

        if ($viewStyle !== null) {
            session()->put(self::PRODUCT_VIEW_SESSION_KEY, $viewStyle);
        }
    }

    private function getSearchPageTitle(ListRequest $request): string
    {
        if ($request->filled(ProductRetailService::FILTER_AUTHOR_ID)) {
            return $this->getEntityPageTitle($this->authorService->find(id: $request[ProductRetailService::FILTER_AUTHOR_ID])?->name);
        }

        if ($request->filled(ProductRetailService::FILTER_PUBLISHING_HOUSE_ID)) {
            return $this->getEntityPageTitle($this->publishingHouseService->find(id: $request[ProductRetailService::FILTER_PUBLISHING_HOUSE_ID])?->name);
        }

        return translate('Products');
    }

    private function getEntityPageTitle(?string $name): string
    {
        return trim(ucwords(str_replace(['-', '_'], ' ', (string) $name)).' '.translate('Products'));
    }

    /**
     * @return array<int, int>
     */
    private function getFeaturedDealProductIds(): array
    {
        $featuredDeal = $this->getRunningFeaturedDeal();

        return $featuredDeal === null ? [] : $this->flashDealProductService->getProductIds(dealId: $featuredDeal->id);
    }

    private function getRunningFeaturedDeal(): ?Model
    {
        if (! $this->isRunningFeaturedDealResolved) {
            $this->runningFeaturedDeal = $this->featuredDealService->getList(
                dataLimit: 'all',
                relations: ['seo'],
                filters: ['status' => 1, FeaturedDealService::FILTER_RUNNING => true],
            )->first();

            $this->isRunningFeaturedDealResolved = true;
        }

        return $this->runningFeaturedDeal;
    }

    private function getCategoryTree(ListRequest $request, ?string $type = null): Collection
    {
        $isFlashDealList = $type === ProductRetailService::OFFER_FLASH_DEALS;
        $flashDealId = $this->getRequestedFlashDealId($request);

        $filters = match (true) {
            $isFlashDealList && $flashDealId !== null => [CategoryService::FILTER_FLASH_DEAL_ID => $flashDealId],
            $isFlashDealList => [CategoryService::FILTER_HAS_FLASH_DEAL_PRODUCTS => true],
            default => [],
        };

        return $this->categoryService->getList(
            dataLimit: 'all',
            filters: $filters,
            sortBy: CategoryService::SORT_LIST_PRIORITY,
            withProductCounts: true,
            productCountFilters: $this->resolveCategoryCountFilters($request, $type),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCategoryCountFilters(ListRequest $request, ?string $type = null): array
    {
        $offerType = $request[ProductRetailService::FILTER_OFFER_TYPE] ?? null;
        $countFilters = [];

        if ($type === ProductRetailService::OFFER_FLASH_DEALS) {
            $flashDealId = $this->getRequestedFlashDealId($request);

            if ($flashDealId === null) {
                $countFilters[CategoryService::COUNT_FILTER_FLASH_DEAL_ONLY] = true;
            } else {
                $countFilters[CategoryService::COUNT_FILTER_FLASH_DEAL_ID] = $flashDealId;
            }
        }

        if ($offerType === ProductRetailService::OFFER_CLEARANCE_SALE) {
            $countFilters[CategoryService::COUNT_FILTER_CLEARANCE_ONLY] = true;
        }

        if ($offerType === ProductRetailService::OFFER_DISCOUNTED) {
            $countFilters[CategoryService::COUNT_FILTER_DISCOUNTED_ONLY] = true;
        }

        if ($offerType === ProductRetailService::OFFER_FEATURED_DEAL) {
            $countFilters[CategoryService::COUNT_FILTER_PRODUCT_IDS] = $this->getFeaturedDealProductIds();
        }

        if (($request[ProductRetailService::FILTER_DATA_FROM] ?? null) === ProductRetailService::LIST_PUBLISHING_HOUSE) {
            $countFilters[CategoryService::COUNT_FILTER_DIGITAL_ONLY] = true;
        }

        return $countFilters;
    }

    private function getActiveBrands(ListRequest $request): Collection
    {
        $isFlashDealList = ($request[ProductRetailService::FILTER_OFFER_TYPE] ?? null) === ProductRetailService::OFFER_FLASH_DEALS;
        $flashDealId = $this->getRequestedFlashDealId($request);

        $filters = match (true) {
            $isFlashDealList && $flashDealId !== null => ['status' => 1, BrandService::FILTER_FLASH_DEAL_ID => $flashDealId],
            $isFlashDealList => ['status' => 1, BrandService::FILTER_HAS_FLASH_DEAL_PRODUCTS => true],
            default => ['status' => 1],
        };

        return $this->brandService->getList(
            dataLimit: 'all',
            withCount: [BrandService::COUNT_RELATION_PRODUCTS],
            filters: $filters,
            sortBy: BrandService::SORT_PRIORITY,
            productCountFilters: $this->resolveBrandCountFilters($request),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBrandCountFilters(ListRequest $request): array
    {
        $flashDealId = $this->getRequestedFlashDealId($request);

        return match ($request[ProductRetailService::FILTER_OFFER_TYPE] ?? null) {
            ProductRetailService::OFFER_CLEARANCE_SALE => [BrandService::COUNT_FILTER_CLEARANCE_ONLY => true],
            ProductRetailService::OFFER_FLASH_DEALS => $flashDealId === null
                ? [BrandService::COUNT_FILTER_FLASH_DEAL_ONLY => true]
                : [BrandService::COUNT_FILTER_FLASH_DEAL_ID => $flashDealId],
            default => [],
        };
    }

    private function getFlashDealPublishingHouses(array $dealProductIds): Collection
    {
        if ($dealProductIds === []) {
            return new Collection;
        }

        return $this->publishingHouseService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                $dealProductIds,
                $this->publishingHouseService->getAttachedProductIds(),
            ),
            productIds: $dealProductIds,
        );
    }

    private function getFlashDealAuthors(array $dealProductIds): Collection
    {
        if ($dealProductIds === []) {
            return new Collection;
        }

        return $this->authorService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                $dealProductIds,
                $this->authorService->getAttachedProductIds(),
            ),
            productIds: $dealProductIds,
        );
    }

    private function getUnattachedDigitalCount(array $productIds, array $attachedProductIds): int
    {
        return $this->productService->count(filters: [
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_EXCLUDE_IDS => $attachedProductIds,
            'product_type' => ProductRetailService::PRODUCT_TYPE_DIGITAL,
            'id' => $productIds,
        ]);
    }

    private function getRequestedFlashDealId(ListRequest $request): ?int
    {
        $flashDealId = $request[ProductRetailService::FILTER_FLASH_DEALS_ID] ?? null;

        return is_numeric($flashDealId) ? (int) $flashDealId : null;
    }
}
