<?php

namespace App\Http\Controllers\Web\v6;

use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Services\New\BannerService;
use App\Services\New\BrandService;
use App\Services\New\CategoryService;
use App\Services\New\Clearance\StockClearanceProductService;
use App\Services\New\CouponService;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\Deal\FeaturedDealService;
use App\Services\New\Deal\FlashDealProductService;
use App\Services\New\Deal\FlashDealService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\RobotsMetaContentService;
use App\Services\New\ShopService;
use App\Services\New\VendorService;
use App\Services\ProductRecommendationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Modules\Auction\app\Enums\AuctionStatus;
use Modules\Auction\app\Services\New\ProductService;

class HomeController extends Controller
{
    private const FEATURED_LIMIT = 12;

    private const NEW_ARRIVAL_LIMIT = 8;

    private const LATEST_LIMIT = 8;

    private const RATED_LIMIT = 10;

    private const CLEARANCE_LIMIT = 10;

    private const FOOTER_BANNER_LIMIT = 10;

    private const VENDOR_PRODUCT_SAMPLE = 3;

    private const AUCTION_LIMIT = 8;

    private const FLASH_DEAL_LIMIT = 10;

    private const COUPON_LIMIT = 3;

    private const JUST_FOR_YOU_LIMIT = 8;

    private const MORE_VENDORS_LIMIT = 8;

    private const ORDER_AGAIN_LIMIT = 8;

    private const CATEGORY_CHILD_LIMIT = 4;

    private const MENU_CATEGORY_LIMIT = 12;

    private const CATEGORY_SLIDER_CHUNK = 4;

    private const PRODUCT_RELATIONS = ['reviews', 'seller.shop'];

    private const BANNER_MAIN = 'Main Banner';

    private const BANNER_MAIN_SECTION = 'Main Section Banner';

    private const BANNER_FOOTER = 'Footer Banner';

    private const BANNER_SIDEBAR = 'Sidebar Banner';

    private const BANNER_TOP_SIDE = 'Top Side Banner';

    public function __construct(
        private readonly BrandService $brandService,
        private readonly CategoryService $categoryService,
        private readonly ProductRetailService $productService,
        private readonly BannerService $bannerService,
        private readonly ShopService $shopService,
        private readonly VendorService $vendorService,
        private readonly ReviewService $reviewService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly FlashDealService $flashDealService,
        private readonly FlashDealProductService $flashDealProductService,
        private readonly FeaturedDealService $featuredDealService,
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly StockClearanceProductService $stockClearanceProductService,
        private readonly RobotsMetaContentService $robotsMetaContentService,
        private readonly ProductRecommendationService $recommendationService,
    ) {}

    public function index(): View
    {
        $homeCategories = $this->getHomeCategories();
        $flashDeal = $this->getFlashDeal();
        $featuredProductsList = $this->getFeaturedProducts();
        $latestProductsList = $this->getLatestProducts();
        $newArrivalProducts = $this->getNewArrivalProducts();
        $clearanceSaleProducts = $this->getClearanceProducts();

        $bestSellProduct = $this->getBestSellingProducts();
        $bestSellProduct = $bestSellProduct->isEmpty() ? $latestProductsList : $bestSellProduct;

        $topRatedProducts = $this->getTopRatedProducts();
        $topRatedProducts = $topRatedProducts->isEmpty() ? $bestSellProduct : $topRatedProducts;

        $this->personalize(
            homeCategories: $homeCategories,
            lists: [&$featuredProductsList, &$newArrivalProducts, &$latestProductsList, &$clearanceSaleProducts],
            flashDeal: $flashDeal,
        );

        $categories = $this->getCategoryTree();
        $topVendorsList = $this->getTopVendors();
        $findWhatYouNeedCategories = $this->getFindWhatYouNeedCategories($categories);
        $mainSectionBanners = $this->getBanners(type: self::BANNER_MAIN_SECTION);

        return view(themeViewName(key: 'home'), [
            'flashDeal' => $flashDeal,
            'featuredProductsList' => $featuredProductsList,
            'topRatedProducts' => $topRatedProducts,
            'bestSellProduct' => $bestSellProduct,
            'latestProductsList' => $latestProductsList,
            'newArrivalProducts' => $newArrivalProducts,
            'clearanceSaleProducts' => $clearanceSaleProducts,
            'recommendedProduct' => $this->productService->getRandomDiscountedProduct(relations: $this->activeClearanceSale()),
            'homeCategories' => $homeCategories,
            'categories' => $categories,
            'menuCategories' => $categories->take(self::MENU_CATEGORY_LIMIT),
            'brands' => $this->getBrands(),
            'dealOfTheDay' => $this->dealOfTheDayService->getRunningDeal(relations: ['product'], locale: getDefaultLanguage()),
            'topVendorsList' => $topVendorsList,
            'bannerTypeMainBanner' => $this->getBanners(type: self::BANNER_MAIN),
            'bannerTypeMainSectionBanner' => $mainSectionBanners->first(),
            'bannerTypeMainSectionBanners' => $mainSectionBanners,
            'bannerTypeFooterBanner' => $this->getBanners(type: self::BANNER_FOOTER)->take(self::FOOTER_BANNER_LIMIT),
            'random_coupon' => $this->getRandomCoupons(),
            'bannerTypeSidebarBanner' => $this->getSingleBanner(type: self::BANNER_SIDEBAR),
            'bannerTypeTopSideBanner' => $this->getSingleBanner(type: self::BANNER_TOP_SIDE),
            'final_category' => $findWhatYouNeedCategories,
            'category_slider' => array_chunk($findWhatYouNeedCategories, self::CATEGORY_SLIDER_CHUNK),
            'justForYouProducts' => $this->getJustForYouProducts(),
            'moreVendors' => $this->getMoreVendors(),
            'order_again' => $this->getOrderAgainList(),
            'topVendorsListSectionShowingStatus' => $topVendorsList->contains(fn (Model $shop): bool => (int) ($shop->products_count ?? 0) > 0),
            'robotsMetaContentData' => $this->robotsMetaContentService->findBy(params: ['page_name' => RobotsMetaContentService::DEFAULT_PAGE_NAME]),
            'auctionProducts' => $this->getAuctionProducts(),
            'current_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function getBrands(): Collection
    {
        return $this->brandService->getList(
            dataLimit: 'all',
            withCount: ['brandProducts'],
            filters: ['status' => 1],
            sortBy: BrandService::SORT_PRIORITY,
        );
    }

    private function getHomeCategories(): Collection
    {
        return $this->categoryService->getList(
            dataLimit: 'all',
            filters: [
                CategoryService::FILTER_HAS_ACTIVE_PRODUCTS => true,
                CategoryService::STATUS_TYPE_HOME => true,
            ],
            sortBy: CategoryService::SORT_OLDEST_ID,
            position: null,
            productLimit: CategoryService::HOME_PRODUCT_LIMIT,
        );
    }

    private function getCategoryTree(): Collection
    {
        return $this->categoryService->getList(
            dataLimit: 'all',
            filters: [CategoryService::FILTER_HAS_ACTIVE_PRODUCTS => true],
            sortBy: CategoryService::SORT_LIST_PRIORITY,
            withProductCounts: true,
        );
    }

    /** @return array{flashDeal: ?Model, flashDealProducts: Collection|LengthAwarePaginator|null} */
    private function getFlashDeal(): array
    {
        $deal = $this->flashDealService->getRunningDeals(relations: ['seo'], withCount: ['products'], locale: getDefaultLanguage())->first();

        return [
            'flashDeal' => $deal,
            'flashDealProducts' => $deal === null ? null : $this->productService->getList(
                dataLimit: self::FLASH_DEAL_LIMIT,
                relations: ['compareList', 'seller.shop', 'rating', 'reviews', ...$this->activeClearanceSale()],
                withCount: ['orderDetails', 'reviews'],
                filters: [ProductRetailService::FILTER_ACTIVE => true, ProductRetailService::FILTER_FLASH_DEAL => $deal->id],
                sortBy: FlashDealService::PRIORITY_TYPE,
            ),
        ];
    }

    private function getFeaturedProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::FEATURED_LIMIT,
            relations: [...self::PRODUCT_RELATIONS, ...$this->activeClearanceSale()],
            filters: [ProductRetailService::FILTER_ACTIVE => true, 'featured' => 1],
            sortBy: 'featured_product_priority',
        );
    }

    private function getTopRatedProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::RATED_LIMIT,
            relations: [...self::PRODUCT_RELATIONS, ...$this->activeClearanceSale()],
            filters: [ProductRetailService::FILTER_ACTIVE => true, 'has_reviews' => true],
            sortBy: 'top_rated_product_list_priority',
        );
    }

    private function getBestSellingProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::RATED_LIMIT,
            relations: [...self::PRODUCT_RELATIONS, ...$this->activeClearanceSale()],
            filters: [ProductRetailService::FILTER_ACTIVE => true, 'has_orders' => true],
            sortBy: 'best_selling_product_list_priority',
        );
    }

    private function getLatestProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::LATEST_LIMIT,
            relations: ['reviews', 'seller.shop', 'flashDealProducts.flashDeal', ...$this->activeClearanceSale()],
            filters: [ProductRetailService::FILTER_ACTIVE => true],
        );
    }

    private function getNewArrivalProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::NEW_ARRIVAL_LIMIT,
            relations: [...self::PRODUCT_RELATIONS, ...$this->activeClearanceSale()],
            filters: [ProductRetailService::FILTER_ACTIVE => true],
            sortBy: 'new_arrival_product_list_priority',
        );
    }

    private function activeClearanceSale(): array
    {
        return ['clearanceSale' => fn (HasOne $sale) => $sale->active()];
    }

    private function getClearanceProducts(): Collection|LengthAwarePaginator
    {
        return $this->productService->getList(
            dataLimit: self::CLEARANCE_LIMIT,
            relations: ['reviews', 'rating', 'seller.shop', ...$this->activeClearanceSale()],
            withCount: ['reviews'],
            filters: [
                ProductRetailService::FILTER_ACTIVE => true,
                'id' => $this->stockClearanceProductService->getProductIds(shownInHomepage: true),
            ],
            sortBy: StockClearanceProductService::PRIORITY_TYPE,
        );
    }

    private function getRandomCoupons(): Collection
    {
        return $this->couponService->getList(
            dataLimit: 'all',
            relations: ['seller'],
            filters: ['status' => 1, CouponService::FILTER_RUNNING => true],
            sortBy: CouponService::SORT_RANDOM,
        )->take(self::COUPON_LIMIT);
    }

    /**
     * @param  Collection<int, Model>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function getFindWhatYouNeedCategories(Collection $categories): array
    {
        return $categories
            ->map(function (Model $category): array {
                $data = $category->toArray();
                $data['childes'] = collect($data['childes'] ?? [])
                    ->take(self::CATEGORY_CHILD_LIMIT)
                    ->map(fn (array $child): array => $child + [
                        'subCategoryProduct_count' => $child['sub_category_product_count'] ?? 0,
                    ])
                    ->values()
                    ->all();

                return $data;
            })
            ->filter(fn (array $category): bool => $category['childes'] !== [])
            ->values()
            ->all();
    }

    private function getJustForYouProducts(): Collection|LengthAwarePaginator
    {
        $products = $this->productService->getList(
            dataLimit: self::JUST_FOR_YOU_LIMIT,
            relations: ['reviews', ...$this->activeClearanceSale()],
            withCount: ['reviews'],
            filters: [ProductRetailService::FILTER_ACTIVE => true],
            sortBy: ProductRetailService::SORT_RANDOM,
        );

        $products->each(function (Model $product): void {
            $product->average_rating = $product->reviews->avg('rating');
            $product->total_rating = $product->reviews->sum('rating');
        });

        return $products;
    }

    private function getMoreVendors(): Collection
    {
        return $this->vendorService->getRandomApprovedVendors(limit: self::MORE_VENDORS_LIMIT);
    }

    private function getOrderAgainList(): Collection|LengthAwarePaginator
    {
        $customerId = Auth::guard('customer')->id();

        if ($customerId === null) {
            return Collection::make();
        }

        return $this->orderService->getList(
            dataLimit: self::ORDER_AGAIN_LIMIT,
            relations: ['details.product'],
            filters: ['customer_id' => $customerId, 'order_status' => OrderService::STATUS_DELIVERED],
            sortBy: OrderService::SORT_NEWEST,
        );
    }

    private function getBanners(string $type): Collection
    {
        return $this->bannerService->getList(
            dataLimit: 'all',
            relations: ['storage'],
            filters: $this->bannerFilters(type: $type),
        );
    }

    private function getSingleBanner(string $type): ?Model
    {
        return $this->bannerService->findBy(params: $this->bannerFilters(type: $type), relations: ['storage']);
    }

    /** @return array<string, mixed> */
    private function bannerFilters(string $type): array
    {
        return [
            'banner_type' => $type,
            'published' => 1,
            'theme' => theme_root_path(),
            'business_module' => BusinessModule::RETAIL,
        ];
    }

    private function getTopVendors(): Collection
    {
        $shops = $this->shopService->getList(
            dataLimit: 'all',
            relations: [
                'products' => fn ($relation) => $relation->active()->businessModule(BusinessModule::RETAIL)->with('reviews'),
                'seller.coupon' => fn ($relation) => $relation->businessModule(BusinessModule::RETAIL),
            ],
            withCount: ['products' => fn ($relation) => $relation->active()->businessModule(BusinessModule::RETAIL)],
            filters: [
                ShopService::FILTER_SELLER_APPROVED => true,
                ShopService::FILTER_HAS_ACTIVE_PRODUCTS_IN_MODULE => BusinessModule::RETAIL,
            ],
            sortBy: ShopService::SORT_OLDEST,
        );

        $this->shopService->attachRatings($shops);

        $shops->each(fn (Model $shop) => $shop->setRelation(
            'products',
            $shop->products->shuffle()->take(self::VENDOR_PRODUCT_SAMPLE)->values(),
        ));

        return $this->shopService->applyTopVendorPriority($shops)->prepend($this->getInHouseShop());
    }

    private function getInHouseShop(): Model
    {
        $shop = getInHouseShopConfig();
        $productIds = $this->productService->getIds(filters: [
            ProductRetailService::FILTER_ACTIVE => true,
            'added_by_user' => ProductRetailService::ADDED_BY_ADMIN,
        ]);

        $reviews = $this->reviewService->getList(dataLimit: 'all', filters: ['status' => 1, 'product_id' => $productIds]);
        $reviewCount = $reviews->count();

        $shop->products_count = count($productIds);
        $shop->coupon_list = $this->couponService->getList(dataLimit: 'all', filters: [
            'added_by' => ProductRetailService::ADDED_BY_ADMIN,
            'coupon_bearer' => CouponService::BEARER_INHOUSE,
            'status' => 1,
            CouponService::FILTER_RUNNING => true,
        ]);
        $shop->review_count = $reviewCount;
        $shop->total_rating = $reviewCount;
        $shop->average_rating = $reviews->avg('rating');
        $shop->positive_review = $reviewCount === 0
            ? 0
            : ($reviews->where('rating', '>=', 4)->count() * 100) / $reviewCount;
        $shop->orders_count = $this->orderService->count(filters: ['seller_is' => ProductRetailService::ADDED_BY_ADMIN]);
        $shop->products = $productIds === []
            ? []
            : $this->productService->getList(
                dataLimit: 'all',
                filters: [
                    ProductRetailService::FILTER_ACTIVE => true,
                    'id' => collect($productIds)->shuffle()->take(self::VENDOR_PRODUCT_SAMPLE)->all(),
                ],
            )->toArray();

        return $shop;
    }

    private function getAuctionProducts(): ?Collection
    {
        if (! getWebConfig(name: 'auction_feature_status') || ! getCheckAddonPublishedStatus(moduleName: 'Auction')) {
            return null;
        }

        $auctionProductService = app(ProductService::class);

        $auctionProducts = $auctionProductService->getList(
            dataLimit: self::AUCTION_LIMIT,
            filters: [
                ProductService::FILTER_ACTIVE => true,
                ProductService::FILTER_AUCTION_STATUS => [
                    AuctionStatus::LIVE,
                    AuctionStatus::UPCOMING,
                ],
            ],
        )->getCollection();

        if (Auth::guard('customer')->check()) {
            $auctionProducts->load(['myBid', 'myParticipation', 'mySavedProduct']);
        }

        return $auctionProducts;
    }

    /**
     * @param  array<int, Collection|LengthAwarePaginator>  $lists
     * @param  array{flashDeal: ?Model, flashDealProducts: Collection|LengthAwarePaginator|null}  $flashDeal
     */
    private function personalize(Collection $homeCategories, array $lists, array &$flashDeal): void
    {
        if (! $this->recommendationService->isActive()) {
            return;
        }

        $flashDealProducts = $flashDeal['flashDealProducts'] ?? null;

        $this->recommendationService->prime(array_merge(
            ...array_map(static fn ($list): array => $list->pluck('id')->all(), $lists),
            ...[
                $flashDealProducts?->pluck('id')->all() ?? [],
                $this->getFeaturedDealProductIds(),
                $homeCategories->flatMap(fn (Model $category) => $category->product?->pluck('id') ?? collect())->all(),
            ],
        ));

        foreach ($lists as &$list) {
            $list = $this->recommendationService->reorder($list);
        }

        if ($flashDealProducts !== null && $flashDealProducts->isNotEmpty()) {
            $flashDeal['flashDealProducts'] = $this->recommendationService->reorder($flashDealProducts);
        }

        $homeCategories->each(function (Model $category): void {
            $products = $category->product;

            if ($products !== null) {
                $category->setRelation('product', $this->recommendationService->reorder($products));
            }
        });
    }

    /** @return array<int, int> */
    private function getFeaturedDealProductIds(): array
    {
        $deal = $this->featuredDealService->getRunningDeals()->first();

        return $deal === null ? [] : $this->flashDealProductService->getProductIds(dealId: $deal->id);
    }
}
