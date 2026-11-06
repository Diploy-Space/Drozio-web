<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\ListRequest;
use App\Http\Requests\Customer\Shop\VacationCheckRequest;
use App\Services\New\AuthorService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\Clearance\StockClearanceSetupService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use App\Utils\CartManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    private const THEME_ASTER = 'theme_aster';

    private const BUSINESS_MODE_SETTING = 'business_mode';

    private const BUSINESS_MODE_SINGLE = 'single';

    private const AUTHOR_TYPE_ADMIN = 'admin';

    private const CLEARANCE_OFFER = 'clearance_sale';

    private const PAGE_SIZE = 20;

    private const FEATURED_PRIORITY_SORT = 'featured_product_priority';

    private const PRODUCT_VIEW_STYLES = ['grid-view', 'list-view'];

    private const PRODUCT_VIEW_SESSION_KEY = 'product_view_style';

    private const COUPON_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'coupon_seller_id',
    ];

    /** @var array<int, string> */
    private const LIST_REQUEST_KEYS = [
        'id',
        'name',
        'brand_id',
        'category_id',
        'data_from',
        'offer_type',
        'sort_by',
        'min_price',
        'max_price',
        'product_type',
        'shop_id',
        'author_id',
        'publishing_house_id',
        'search_category_value',
        'product_name',
    ];

    public function __construct(
        private readonly ShopService $shopService,
        private readonly VendorService $vendorService,
        private readonly ProductRetailService $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly PublishingHouseService $publishingHouseService,
        private readonly AuthorService $authorService,
        private readonly ReviewService $reviewService,
        private readonly OrderService $orderService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly StockClearanceSetupService $stockClearanceSetupService,
    ) {}

    public function show(ListRequest $request, string $slug): View|JsonResponse|RedirectResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug], relations: ['seller']);

        if (! $shop) {
            ToastMagic::error(translate('Shop does not exist'));

            return redirect()->route('home');
        }

        if (getWebConfig(name: self::BUSINESS_MODE_SETTING) == self::BUSINESS_MODE_SINGLE && $shop['author_type'] != self::AUTHOR_TYPE_ADMIN) {
            ToastMagic::error(translate('Access denied!!'));

            return redirect()->route('home');
        }

        $this->flashUnapprovedSellerWarning($shop);
        $this->normalizeListRequest($request);

        return theme_root_path() === self::THEME_ASTER
            ? $this->asterShopPage($request, $shop)
            : $this->defaultShopPage($request, $shop);
    }

    public function vacationCheck(VacationCheckRequest $request): JsonResponse
    {
        $currentDate = date('Y-m-d');

        if ($request->isSellerShop()) {
            $shop = $this->shopService->findBy(params: ['seller_id' => $request['user_id']]);

            if ($shop === null) {
                return response()->json(['status' => 0, 'message' => translate('Shop is unavailable')]);
            }

            $snapshot = $this->shopService->getVacationSnapshot(shop: $shop);
        } else {
            $snapshot = $this->shopService->getInHouseVacationSnapshot();
        }

        if ($this->shopService->isClosedOn(snapshot: $snapshot, date: $currentDate)) {
            return response()->json(['status' => 'inactive']);
        }

        $product = $this->productService->find(id: $request['id']);

        if ($product === null) {
            return response()->json(['status' => 0, 'message' => translate('Product is unavailable')]);
        }

        unset($request['added_by']);
        $request['quantity'] = $product->minimum_order_qty;

        $cart = CartManager::add_to_cart($request);

        foreach (self::COUPON_SESSION_KEYS as $key) {
            session()->forget($key);
        }

        return response()->json($cart);
    }

    private function defaultShopPage(ListRequest $request, Model $shop): View|JsonResponse
    {
        $scope = $this->shopScope($request, $shop);
        $shopAllProducts = $this->getShopAllProducts($scope);
        $categories = $this->getShopCategories($scope, $shopAllProducts);
        $products = $this->getShopProducts($request, $scope);

        if ($request->ajax()) {
            return response()->json([
                'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                    'products' => $products,
                    'categories' => $categories,
                ])->render(),
            ], 200);
        }

        return view(themeViewName(key: 'shop_view_page'), [
            'products' => $products,
            'categories' => $categories,
            'seller_id' => $shop['seller_id'],
            'activeBrands' => $this->getShopBrands($scope, $shopAllProducts),
            'shopInfoArray' => $this->getShopInfo($scope, $shop, $this->getShopReviews($shopAllProducts)),
            'shopPublishingHouses' => $this->getShopPublishingHouses($scope, $shopAllProducts),
            'digitalProductAuthors' => $this->getShopAuthors($scope, $shopAllProducts),
            'stockClearanceProducts' => $this->getClearanceProductCount($scope['shop_id']),
            'stockClearanceSetup' => $this->getClearanceSetupStatus($scope['shop_id']),
            'data' => $this->getListRequestData($request),
        ]);
    }

    private function asterShopPage(ListRequest $request, Model $shop): View|JsonResponse
    {
        $scope = $this->shopScope($request, $shop);
        $shopAllProducts = $this->getShopAllProducts($scope, ['reviews:id,product_id,rating']);
        $products = $this->getShopProducts($request, $scope);
        $reviews = $this->getShopReviews($shopAllProducts);
        $totalReviews = $reviews->count();
        $isInHouseShop = $shop['author_type'] === self::AUTHOR_TYPE_ADMIN;
        $seller = $isInHouseShop ? null : $this->vendorService->find(id: $shop['seller_id']);

        if ($request->ajax()) {
            return response()->json([
                'total_product' => $products->total(),
                'html_products' => view(themeViewName(key: 'products__ajax_partials'), [
                    'products' => $products,
                    'product_ids' => $products->pluck('id')->toArray(),
                    'singlePageProductCount' => self::PAGE_SIZE,
                    'page' => $request['page'] ?? 1,
                ])->render(),
            ], 200);
        }

        return view(themeViewName(key: 'shop_view_page'), [
            'products' => $products,
            'categories' => $this->getShopCategories($scope, $shopAllProducts),
            'products_for_review' => $this->getReviewableProductCount($shop, $seller),
            'featuredProductsList' => $this->getFeaturedProducts($shop, $seller),
            'activeBrands' => $this->getShopBrands($scope, $shopAllProducts),
            'selectedRatings' => $request['rating'] ?? [],
            'data' => $this->getListRequestData($request),
            'ratings' => $this->getRatingDistribution($shopAllProducts),
            'rattingStatusArray' => $this->getRatingShares($reviews, $totalReviews),
            'stockClearanceProducts' => $this->getClearanceProductCount($scope['shop_id']),
            'stockClearanceSetup' => $this->getClearanceSetupStatus($scope['shop_id']),
            'slug' => $shop['slug'],
            'total_review' => $totalReviews,
            'avg_rating' => $reviews->avg('rating'),
            'shopInfoArray' => $this->getShopInfo($scope, $shop, $reviews),
            'shopPublishingHouses' => $this->getShopPublishingHouses($scope, $shopAllProducts),
            'digitalProductAuthors' => $this->getShopAuthors($scope, $shopAllProducts),
            'singlePageProductCount' => self::PAGE_SIZE,
            'page' => $request['page'] ?? 1,
            'isInHouseShop' => $isInHouseShop,
            'isShopVerified' => $isInHouseShop ? isInHouseShopVerified() : (bool) $seller?->hasVerifiedBadge(),
        ]);
    }

    /** @return array<string, mixed> */
    private function shopScope(ListRequest $request, Model $shop): array
    {
        $isInHouse = $shop['author_type'] === self::AUTHOR_TYPE_ADMIN;
        $sellerId = $shop['seller_id'] == 0 ? 0 : $shop['seller_id'];
        $clearanceOnly = $request['offer_type'] === self::CLEARANCE_OFFER;

        return [
            'added_by' => $isInHouse ? ProductRetailService::ADDED_BY_ADMIN : ProductRetailService::ADDED_BY_SELLER,
            'seller_id' => $sellerId,
            'shop_id' => $isInHouse ? 0 : $shop['id'],
            'clearance_only' => $clearanceOnly,
            'clearance_product_ids' => $clearanceOnly
                ? $this->stockClearanceProductService->getProductIds(running: true)
                : null,
            'brand_clearance_product_ids' => $clearanceOnly
                ? $this->getBrandClearanceProductIds($sellerId)
                : null,
        ];
    }

    /** @return array<int, int> */
    private function getBrandClearanceProductIds(mixed $sellerId): array
    {
        return $sellerId
            ? $this->stockClearanceProductService->getProductIds(
                addedBy: ShopService::AUTHOR_TYPE_VENDOR,
                vendorId: (int) $sellerId,
                running: true,
            )
            : $this->stockClearanceProductService->getProductIds(running: true);
    }

    private function getShopAllProducts(array $scope, array $relations = []): Collection
    {
        return $this->productService->getList(
            dataLimit: 'all',
            relations: $relations,
            filters: $this->ownershipFilters($scope) + $this->clearanceIdFilter($scope),
        );
    }

    /** @return array<string, mixed> */
    private function ownershipFilters(array $scope): array
    {
        $filters = [ProductRetailService::FILTER_ACTIVE => true];

        if ($scope['added_by'] === ProductRetailService::ADDED_BY_ADMIN) {
            $filters['added_by_user'] = ProductRetailService::ADDED_BY_ADMIN;
        }

        if ($scope['seller_id'] && $scope['added_by'] === ProductRetailService::ADDED_BY_SELLER) {
            $filters['added_by_user'] = ProductRetailService::ADDED_BY_SELLER;
            $filters['seller_id'] = $scope['seller_id'];
        }

        return $filters;
    }

    /** @return array<string, mixed> */
    private function clearanceIdFilter(array $scope): array
    {
        return $scope['clearance_only'] ? ['id' => $scope['clearance_product_ids']] : [];
    }

    private function getShopProducts(ListRequest $request, array $scope): mixed
    {
        return $this->productService
            ->getShopProductList(
                requestData: $request->all(),
                sellerId: $scope['seller_id'],
                addedBy: $scope['added_by'],
            )
            ->paginate(self::PAGE_SIZE)
            ->appends($request->all());
    }

    private function getShopCategories(array $scope, Collection $shopAllProducts): Collection
    {
        return $this->categoryService->getShopCategoryList(
            categoryIds: $this->collectCategoryIds($shopAllProducts),
            addedBy: $scope['added_by'],
            sellerId: $scope['added_by'] === ProductRetailService::ADDED_BY_ADMIN ? null : (int) $scope['seller_id'],
            clearanceOnly: $scope['clearance_only'],
        );
    }

    private function getShopBrands(array $scope, Collection $shopAllProducts): Collection
    {
        return $this->brandService->getShopBrandList(
            brandIds: $shopAllProducts->pluck('brand_id')->toArray(),
            addedBy: $scope['added_by'],
            sellerId: $scope['seller_id'] ?: null,
            clearanceProductIds: $scope['brand_clearance_product_ids'],
        );
    }

    private function getShopPublishingHouses(array $scope, Collection $shopAllProducts): Collection
    {
        $productIds = $shopAllProducts->pluck('id')->toArray();

        return $this->publishingHouseService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                $scope,
                $productIds,
                $this->publishingHouseService->getAttachedProductIds(),
            ),
            productIds: $productIds,
            sellerId: $scope['seller_id'] ?: null,
            clearanceProductIds: $scope['clearance_product_ids'],
        );
    }

    private function getShopAuthors(array $scope, Collection $shopAllProducts): Collection
    {
        $productIds = $shopAllProducts->pluck('id')->toArray();

        return $this->authorService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(
                $scope,
                $productIds,
                $this->authorService->getAttachedProductIds(),
            ),
            productIds: $productIds,
            sellerId: $scope['seller_id'] ?: null,
            clearanceProductIds: $scope['clearance_product_ids'],
        );
    }

    private function getUnattachedDigitalCount(array $scope, array $productIds, array $attachedProductIds): int
    {
        $filters = [
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_EXCLUDE_IDS => $attachedProductIds,
            'product_type' => ProductRetailService::PRODUCT_TYPE_DIGITAL,
        ];

        $narrowedIds = $productIds !== [] ? $productIds : $scope['clearance_product_ids'];

        if ($narrowedIds !== null) {
            $filters['id'] = $narrowedIds;
        }

        if ($scope['seller_id']) {
            $filters['user_id'] = $scope['seller_id'];
            $filters['added_by'] = ProductRetailService::ADDED_BY_SELLER;
        }

        return $this->productService->count(filters: $filters);
    }

    /** @return array<string, mixed> */
    private function getShopInfo(array $scope, Model $shop, Collection $reviews): array
    {
        return $this->shopService->getShopInfo(
            shop: $shop,
            averageRating: $reviews->avg('rating'),
            totalReview: $reviews->count(),
            totalOrder: $this->orderService->count(filters: $this->shopOrderFilters($scope)),
        );
    }

    /** @return array<string, mixed> */
    private function shopOrderFilters(array $scope): array
    {
        $filters = ['seller_is' => $scope['added_by']];

        if ($scope['added_by'] === ProductRetailService::ADDED_BY_SELLER) {
            $filters['seller_id'] = $scope['seller_id'];
        }

        return $filters;
    }

    private function getShopReviews(Collection $shopAllProducts): Collection
    {
        return $this->reviewService->getList(
            dataLimit: 'all',
            filters: ['status' => 1, 'product_id' => $shopAllProducts->pluck('id')->toArray()],
        );
    }

    /** @return array<string, int> */
    private function getRatingDistribution(Collection $shopAllProducts): array
    {
        $ratings = ['rating_1' => 0, 'rating_2' => 0, 'rating_3' => 0, 'rating_4' => 0, 'rating_5' => 0];

        foreach ($shopAllProducts as $product) {
            $average = (int) floor($product->reviews?->avg('rating') ?? 0);

            if ($average >= 1 && $average <= 5) {
                $ratings['rating_'.$average]++;
            }
        }

        return $ratings;
    }

    /** @return array<string, float|int> */
    private function getRatingShares(Collection $reviews, int $totalReviews): array
    {
        $share = fn (int $count): float|int => $totalReviews != 0 ? ($count * 100) / $totalReviews : 0;

        return [
            'positive' => $share($reviews->where('rating', '>=', 4)->count()),
            'good' => $share($reviews->where('rating', 3)->count()),
            'neutral' => $share($reviews->where('rating', 2)->count()),
            'negative' => $share($reviews->where('rating', 1)->count()),
        ];
    }

    private function getFeaturedProducts(Model $shop, ?Model $seller): Collection
    {
        if ($shop['author_type'] !== self::AUTHOR_TYPE_ADMIN && $seller === null) {
            return Collection::make();
        }

        $filters = [ProductRetailService::FILTER_ACTIVE => true, 'featured' => 1];

        if ($shop['author_type'] === self::AUTHOR_TYPE_ADMIN) {
            $filters['added_by_user'] = ProductRetailService::ADDED_BY_ADMIN;
        } else {
            $filters['added_by_user'] = ProductRetailService::ADDED_BY_SELLER;
            $filters['seller_id'] = $seller->id;
        }

        return $this->productService->getList(
            dataLimit: 'all',
            relations: [
                'seller.shop',
                'reviews',
                'wishList' => fn ($relation) => $relation->where('customer_id', Auth::guard('customer')->id() ?? 0),
                'compareList' => fn ($relation) => $relation->where('user_id', Auth::guard('customer')->id() ?? 0),
                'clearanceSale' => fn ($relation) => $relation->active(),
            ],
            withCount: ['orderDetails', 'reviews', 'wishList'],
            filters: $filters,
            sortBy: self::FEATURED_PRIORITY_SORT,
        );
    }

    private function getReviewableProductCount(Model $shop, ?Model $seller): int
    {
        if ($shop['author_type'] === self::AUTHOR_TYPE_ADMIN) {
            return $this->productService->count(filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'added_by' => ProductRetailService::ADDED_BY_ADMIN,
            ]);
        }

        return $seller === null ? 0 : $this->productService->count(filters: [
            ProductRetailService::FILTER_ACTIVE => true,
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
            'user_id' => $seller->id,
        ]);
    }

    private function getClearanceProductCount(mixed $shopId): int
    {
        return $this->stockClearanceProductService->count(filters: [
            StockClearanceProductService::FILTER_ACTIVE => true,
            'shop_id' => $shopId,
        ]);
    }

    private function getClearanceSetupStatus(mixed $shopId): mixed
    {
        return $this->stockClearanceSetupService->findBy(params: ['shop_id' => $shopId], relations: [])?->is_active ?? 0;
    }

    /** @return array<int, int> */
    private function collectCategoryIds(Collection $shopAllProducts): array
    {
        $categoryIds = [];

        foreach ($shopAllProducts->pluck('category_ids')->toArray() as $encoded) {
            foreach (json_decode($encoded, true) ?: [] as $category) {
                $categoryIds[] = $category['id'];
            }
        }

        return $categoryIds;
    }

    private function normalizeListRequest(ListRequest $request): void
    {
        if ($request->has('category_ids')) {
            $request->merge([
                'category_ids' => array_filter(
                    (array) $request['category_ids'],
                    static fn ($value): bool => ! is_null($value) && $value != 'all',
                ),
            ]);
        }

        if ($request->has('search_category_value') && ! $request->has('category_ids')) {
            unset($request['search_category_value']);
        }
    }

    /** @return array<string, mixed> */
    private function getListRequestData(ListRequest $request): array
    {
        if ($request->has('product_view') && in_array($request['product_view'], self::PRODUCT_VIEW_STYLES, true)) {
            session()->put(self::PRODUCT_VIEW_SESSION_KEY, $request['product_view']);
        }

        $data = [];

        foreach (self::LIST_REQUEST_KEYS as $key) {
            $data[$key] = $request[$key];
        }

        $data['page_no'] = $request['page'];
        $data['page'] = $request['page'] ?? 1;

        return $data;
    }

    private function flashUnapprovedSellerWarning(Model $shop): void
    {
        if ($shop['author_type'] === self::AUTHOR_TYPE_ADMIN) {
            return;
        }

        $seller = $this->vendorService->findBy(params: [
            'id' => $shop['seller_id'],
            'status' => VendorService::STATUS_APPROVED,
        ]);

        if ($seller === null) {
            ToastMagic::warning(translate('Not found'));
        }
    }
}
