<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Review\ListRequest;
use App\Http\Requests\Customer\Review\ShopListRequest;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use App\Services\New\ShopService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;

class ReviewListController extends Controller
{
    private const PRODUCT_PAGE_SIZE = 2;

    private const SHOP_PAGE_SIZE = 4;

    private const STATUS_ACTIVE = 1;

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ProductRetailService $productService,
        private readonly ShopService $shopService,
    ) {}

    public function product(ListRequest $request): JsonResponse
    {
        $reviews = $this->reviewService->getInfiniteScrollList(
            dataLimit: self::PRODUCT_PAGE_SIZE,
            page: $request->pageNumber(),
            filters: ['product_id' => $request['product_id']],
        );

        return $this->getReviewListResponse($reviews);
    }

    public function shop(ShopListRequest $request): JsonResponse
    {
        $reviews = $this->reviewService->getInfiniteScrollList(
            dataLimit: self::SHOP_PAGE_SIZE,
            page: $request->pageNumber(),
            filters: [
                'status' => self::STATUS_ACTIVE,
                'product_id' => $this->getShopProductIds((int) $request['shop_id']),
            ],
        );

        return $this->getReviewListResponse($reviews);
    }

    /**
     * @return array<int, int>
     */
    private function getShopProductIds(int $shopId): array
    {
        if ($shopId === ShopService::IN_HOUSE_ID) {
            return $this->productService->getActiveIds(filters: ['added_by' => ProductRetailService::ADDED_BY_IN_HOUSE]);
        }

        return $this->productService->getActiveIds(filters: [
            'added_by' => ProductRetailService::ADDED_BY_SELLER,
            'seller_id' => $this->shopService->find(id: $shopId)?->seller_id,
        ]);
    }

    private function getReviewListResponse(Paginator $reviews): JsonResponse
    {
        $loadedCount = $reviews->count();

        return response()->json([
            'productReview' => view(themeViewName(key: 'product_reviews_partials'), ['productReviews' => $reviews])->render(),
            'not_empty' => $loadedCount,
            'checkReviews' => $loadedCount,
        ]);
    }
}
