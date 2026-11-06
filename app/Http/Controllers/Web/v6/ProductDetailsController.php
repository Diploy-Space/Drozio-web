<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\Deal\DealOfTheDayService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductService;
use App\Services\New\ReviewService;
use App\Services\New\WishlistService;
use App\Services\ProductRecommendationService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\RedirectResponse;

class ProductDetailsController extends Controller
{
    private const RELATED_LIMIT = 12;

    private const SELLER_PRODUCT_LIMIT = 5;

    private const REVIEW_LIMIT = 2;

    private const DEAL_ACTIVE = 1;

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly WishlistService $wishlistService,
        private readonly ProductCompareService $productCompareService,
        private readonly ReviewService $reviewService,
        private readonly DealOfTheDayService $dealOfTheDayService,
        private readonly CartService $cartService,
        private readonly ColorService $colorService,
        private readonly RestockProductService $restockProductService,
        private readonly ProductRecommendationService $recommendationService,
    ) {}

    public function show(string $slug): View|RedirectResponse
    {
        $customerId = auth('customer')->id();
        $product = $this->resolveProduct(slug: $slug, customerId: (int) $customerId);

        if ($product === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $this->recommendationService->recordView((int) $product->id);

        $initialProductConfig = $this->buildInitialConfig(
            product: $product,
            customerId: $customerId,
            guestId: session('guest_id'),
        );

        $productsForReview = $this->getSellerProducts($product);
        $sellerRating = $this->reviewService->getRatingBreakdownForProducts(
            productIds: $this->productService->getActiveIds(filters: $this->sellerFilters($product)),
        );

        return view(themeViewName(key: 'products_details'), [
            'product' => $product,
            'initialProductConfig' => $initialProductConfig,
            'initialProductQuantity' => $initialProductConfig['quantity'],
            'initialProductPrice' => $initialProductConfig['price'],
            'productDetailsMeta' => $product->seoInfo,
            'productAuthorsInfo' => $this->productService->getAuthorsInfo(product: $product),
            'productPublishingHouseInfo' => $this->productService->getPublishingHouseInfo(product: $product),
            'overallRating' => getOverallRating(reviews: $product->reviews),
            'rating' => getRating(reviews: $product->reviews),
            'wishlistStatus' => $this->wishlistService->count(
                filters: $this->ownedFilters($product, 'customer_id', $customerId),
            ),
            'compareList' => $this->productCompareService->count(
                filters: $this->ownedFilters($product, 'user_id', $customerId),
            ),
            'countWishlist' => $product->wish_list_count,
            'countOrder' => $product->order_details_count,
            'productReviews' => $this->getProductReviews($product),
            'firstVariationQuantity' => $this->productService->getFirstVariationQuantity(product: $product),
            'moreProductFromSeller' => $this->getMoreFromSeller($product),
            'productsForReview' => $productsForReview,
            'totalReviews' => $sellerRating['rating_count'],
            'avgRating' => $sellerRating['average_rating'],
            'positiveReview' => $sellerRating['positive_percentage'],
            'relatedProducts' => $this->getRelatedProducts($product, (int) $customerId),
            'dealOfTheDay' => $this->dealOfTheDayService->findBy(
                params: ['product_id' => $product->id, 'status' => self::DEAL_ACTIVE],
            ),
            'currentDate' => date('Y-m-d'),
            'decimalPointSettings' => getWebConfig('decimal_point_settings'),
            'previewFileInfo' => getFileInfoFromURL(url: $product->preview_file_full_url['path'] ?? null),
        ]);
    }

    private function resolveProduct(string $slug, int $customerId): ?Model
    {
        return $this->productService->findActiveBy(
            filters: ['slug' => $slug],
            relations: [
                'seoInfo', 'digitalVariation', 'reviews', 'seller.shop',
                'digitalProductAuthors.author', 'digitalProductPublishingHouse.publishingHouse',
                'clearanceSale' => fn (HasOne $sale) => $sale->active(),
                'wishList' => fn (HasMany $wishlist) => $wishlist->where('customer_id', $customerId),
                'compareList' => fn (HasMany $compare) => $compare->where('user_id', $customerId),
            ],
            withCount: ['orderDetails', 'wishList'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInitialConfig(Model $product, mixed $customerId, mixed $guestId): array
    {
        $cartItems = $this->cartService->getList(
            dataLimit: 'all',
            filters: [
                'product_id' => $product->id,
                'customer_id' => $customerId ?? $guestId ?? 0,
                'is_guest' => $customerId ? CartService::CUSTOMER : CartService::GUEST,
                CartService::FILTER_ACTIVE_PRODUCT => true,
            ],
        );

        $colorNames = $this->colorService->getNameMap();

        $variant = $this->productService->getInitialVariant(
            product: $product,
            cartItems: $cartItems,
            colorNames: $colorNames,
        );

        $restockRequestStatus = $customerId === null ? 0 : (int) $this->restockProductService->hasCustomerRequest(
            productId: $product->id,
            variant: $variant,
            customerId: $customerId,
        );

        return $this->productService->getInitialConfig(
            product: $product,
            cartItems: $cartItems,
            colorNames: $colorNames,
            restockRequestStatus: $restockRequestStatus,
        );
    }

    private function getProductReviews(Model $product): LengthAwarePaginator
    {
        return $this->reviewService->getList(
            dataLimit: self::REVIEW_LIMIT,
            relations: ['reply'],
            filters: ['product_id' => $product->id],
            sortBy: ReviewService::SORT_NEWEST_ID,
        );
    }

    private function getMoreFromSeller(Model $product): LengthAwarePaginator
    {
        return $this->productService->getActiveList(
            filters: $this->sellerFilters($product),
            excludeIds: [$product->id],
            relations: [
                'clearanceSale' => fn (HasOne $sale) => $sale->active(),
                'reviews' => fn (HasMany $reviews) => $reviews->active(),
                'seller.shop',
            ],
            dataLimit: self::SELLER_PRODUCT_LIMIT,
            sortBy: ProductRetailService::SORT_LATEST,
        );
    }

    private function getSellerProducts(Model $product): LengthAwarePaginator
    {
        return $this->productService->getActiveList(
            filters: $this->sellerFilters($product),
        );
    }

    private function getRelatedProducts(Model $product, int $customerId): LengthAwarePaginator
    {
        $relatedProducts = $this->productService->getActiveList(
            filters: ['category_id' => $product->category_id],
            excludeIds: [$product->id],
            relations: [
                'reviews' => fn (HasMany $reviews) => $reviews->active(),
                'clearanceSale' => fn (HasOne $sale) => $sale->active(),
                'seller.shop',
                'flashDealProducts.flashDeal',
                'wishList' => fn (HasMany $wishlist) => $wishlist->where('customer_id', $customerId),
                'compareList' => fn (HasMany $compare) => $compare->where('user_id', $customerId),
            ],
            withCount: ['reviews'],
            dataLimit: self::RELATED_LIMIT,
        );

        $currentDate = date('Y-m-d H:i:s');

        foreach ($relatedProducts->items() as $related) {
            $flashDeal = $related->flashDealProducts->first()?->flashDeal;
            $isRunning = $flashDeal
                && $flashDeal->status == 1
                && date('Y-m-d H:i:s', strtotime($flashDeal->start_date)) <= $currentDate
                && date('Y-m-d H:i:s', strtotime($flashDeal->end_date)) >= $currentDate;

            $related['flash_deal_status'] = $isRunning ? 1 : 0;
            $related['flash_deal_end_date'] = $flashDeal ? $flashDeal->end_date : 0;
            $related['flash_deal_end_at'] = $flashDeal?->end_at;
        }

        return $relatedProducts;
    }

    /**
     * @return array<string, mixed>
     */
    private function sellerFilters(Model $product): array
    {
        return [
            'added_by' => $product->added_by === ProductRetailService::ADDED_BY_ADMIN
                ? ProductRetailService::ADDED_BY_IN_HOUSE
                : $product->added_by,
            'seller_id' => $product->user_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ownedFilters(Model $product, string $ownerColumn, mixed $customerId): array
    {
        $filters = ['product_id' => $product->id];

        return $customerId === null ? $filters : $filters + [$ownerColumn => $customerId];
    }
}
