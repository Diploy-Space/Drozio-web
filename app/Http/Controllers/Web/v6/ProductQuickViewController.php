<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Product\QuickViewRequest;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\OrderDetailService;
use App\Services\New\ProductCompareService;
use App\Services\New\ProductRetailService;
use App\Services\New\RestockProductService;
use App\Services\New\WishlistService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;

class ProductQuickViewController extends Controller
{
    private const RELATED_LIMIT = 12;

    private const PRODUCT_RELATIONS = [
        'rating',
        'reviews',
        'retailDetail',
        'seller.shop',
        'tags',
        'seoInfo',
        'digitalVariation',
        'digitalProductAuthors.author',
        'digitalProductPublishingHouse.publishingHouse',
    ];

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly WishlistService $wishlistService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ProductCompareService $productCompareService,
        private readonly CartService $cartService,
        private readonly ColorService $colorService,
        private readonly RestockProductService $restockProductService,
    ) {}

    public function show(QuickViewRequest $request): JsonResponse
    {
        $product = $this->productService->findActiveBy(
            filters: ['id' => (int) $request['product_id']],
            relations: [...self::PRODUCT_RELATIONS, 'clearanceSale' => fn (HasOne $sale) => $sale->active()],
        );

        if ($product === null) {
            return response()->json(['success' => 0, 'message' => translate('Product not found')], 404);
        }

        $customerId = (int) auth('customer')->id();

        return response()->json([
            'success' => 1,
            'product' => $product,
            'view' => view(themeViewName(key: 'product_quick_view_partials'), [
                'product' => $product,
                'countWishlist' => $this->wishlistService->count(filters: ['product_id' => $product->id]),
                'countOrder' => $this->orderDetailService->count(filters: ['product_id' => $product->id]),
                'initialProductConfig' => $this->buildInitialConfig(
                    product: $product,
                    customerId: auth('customer')->id(),
                    guestId: session('guest_id'),
                ),
                'relatedProducts' => $this->productService->getRelatedByCategoryIds(
                    categoryIds: $product->category_ids,
                    excludeId: $product->id,
                    limit: self::RELATED_LIMIT,
                ),
                'currentDate' => date('Y-m-d'),
                'productAuthorsInfo' => $this->productService->getAuthorsInfo(product: $product),
                'productPublishingHouseInfo' => $this->productService->getPublishingHouseInfo(product: $product),
                'wishlist_status' => $this->wishlistService->count(filters: ['product_id' => $product->id, 'customer_id' => $customerId]),
                'overallRating' => getOverallRating($product->reviews),
                'rating' => getRating($product->reviews),
                'firstVariationQuantity' => $this->productService->getFirstVariationQuantity(product: $product),
                'compareList' => $this->productCompareService->count(filters: ['product_id' => $product->id, 'user_id' => $customerId]),
                'productShop' => $this->getProductShop(product: $product),
            ])->render(),
        ]);
    }

    /** @return array{name: string, slug: ?string, is_verified: bool} */
    private function getProductShop(Model $product): array
    {
        if ($product->added_by === ProductRetailService::ADDED_BY_SELLER) {
            return [
                'name' => (string) ($product->seller?->shop?->name ?? ''),
                'slug' => $product->seller?->shop?->slug,
                'is_verified' => (bool) $product->seller?->hasVerifiedBadge(),
            ];
        }

        return [
            'name' => (string) getInHouseShopConfig(key: 'name'),
            'slug' => getInHouseShopConfig(key: 'slug'),
            'is_verified' => isInHouseShopVerified(),
        ];
    }

    /** @return array<string, mixed> */
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
}
