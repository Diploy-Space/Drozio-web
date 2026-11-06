<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Review\ReplyRequest;
use App\Http\Requests\Vendor\Review\StatusUpdateRequest;
use App\Http\Resources\V6\Vendor\ReviewResource;
use App\Services\New\CustomerService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewReplyService;
use App\Services\New\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ReviewController extends BaseApiController
{
    private const LIST_RELATIONS = ['product', 'customer', 'reply'];

    private const UNSELECTED_PRODUCT_ID = '0';

    private const UNMATCHED_PRODUCT_IDS = [0];

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ReviewReplyService $reviewReplyService,
        private readonly ProductRetailService $productRetailService,
        private readonly CustomerService $customerService,
    ) {}

    #[ApiDoc(
        summary: 'Reviews on the authenticated seller products',
        description: 'Newest first, active and inactive alike. from and to filter by review date only when both are sent. search keeps reviews on products whose name contains every word, or by customers whose first or last name contains any word.',
        tag: 'Reviews (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'product_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'status' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ],
        status: [401],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = $request->only(['limit', 'offset', 'search', 'product_id', 'customer_id', 'status', 'from', 'to']);

        $reviews = $this->reviewService->getList(
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            relations: self::LIST_RELATIONS,
            filters: $this->listFilters(sellerId: $this->sellerId($request), args: $args),
            sortBy: ReviewService::SORT_LATEST,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ReviewResource::collection($reviews),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($reviews),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Reply to a review on a seller product, or edit the seller existing reply',
        tag: 'Reviews (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function reply(ReplyRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $review = $this->reviewService->findForSeller(sellerId: $sellerId, id: (int) $id);

        if ($review === null) {
            return $this->responseFormatter(config: config('response.seller_review_not_found'));
        }

        $this->reviewReplyService->updateBy(
            params: [
                'review_id' => $review->id,
                'added_by' => ReviewReplyService::ADDED_BY_SELLER,
                'added_by_id' => $sellerId,
            ],
            data: ['reply_text' => $request['reply_text']],
        );

        return $this->responseFormatter(config: config('response.seller_review_replied'));
    }

    #[ApiDoc(
        summary: 'Show or hide a review on a seller product',
        description: 'status 1 shows the review to customers, 0 hides it.',
        tag: 'Reviews (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $isUpdated = $this->reviewService->updateStatusForSeller(
            sellerId: $this->sellerId($request),
            id: (int) $id,
            status: (int) $request['status'],
        );

        return $this->responseFormatter(
            config: config($isUpdated ? 'response.seller_review_status_updated' : 'response.seller_review_not_found'),
        );
    }

    #[ApiDoc(
        summary: 'Reviews of one seller product, with its average rating and rating breakdown',
        description: 'Most recently updated first, active and inactive alike.',
        tag: 'Reviews (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [401, 404],
        guard: 'seller',
    )]
    public function getProductReviews(Request $request, string $id): JsonResponse
    {
        $product = $this->productRetailService->findSellerProductBy(sellerId: $this->sellerId($request), filters: ['id' => (int) $id]);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.seller_review_product_not_found'));
        }

        $args = $request->only(['limit', 'offset']);

        $reviews = $this->reviewService->getList(
            dataLimit: max(1, (int) ($args['limit'] ?? DEFAULT_DATA_LIMIT)),
            page: max(1, (int) ($args['offset'] ?? 1)),
            relations: self::LIST_RELATIONS,
            filters: ['product_id' => $product->id, ReviewService::FILTER_INCLUDE_INACTIVE => true],
            sortBy: ReviewService::SORT_RECENTLY_UPDATED,
        );

        $averageRating = $this->reviewService->getAverageRating(productId: $product->id, includeInactive: true);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ReviewResource::collection($reviews),
                'average_rating' => $averageRating === null ? 0 : number_format($averageRating, 2, '.', ' '),
                'group-wise-rating' => $this->reviewService
                    ->getRatingDistribution(filters: ['product_id' => $product->id, ReviewService::FILTER_INCLUDE_INACTIVE => true])
                    ->map(static fn ($row): array => ['rating' => (int) $row->rating, 'total' => (int) $row->total])
                    ->values(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($reviews),
            ],
        );
    }

    private function listFilters(int $sellerId, array $args): array
    {
        $filters = [
            ReviewService::FILTER_PRODUCT_SELLER_ID => $sellerId,
            ReviewService::FILTER_INCLUDE_INACTIVE => true,
        ];

        if (array_key_exists('search', $args)) {
            $searchValue = (string) $args['search'];

            $filters[ReviewService::FILTER_MATCHING_PRODUCT_IDS] = $this->productRetailService->getSellerProductIdsMatchingAllWords(
                sellerId: $sellerId,
                searchValue: $searchValue,
            ) ?: self::UNMATCHED_PRODUCT_IDS;
            $filters[ReviewService::FILTER_MATCHING_CUSTOMER_IDS] = $this->customerService->getIdsMatchingAnyNameWord(searchValue: $searchValue);
        }

        if (filled($args['product_id'] ?? null) && (string) $args['product_id'] !== self::UNSELECTED_PRODUCT_ID) {
            $filters['product_id'] = $args['product_id'];
        }

        if (filled($args['customer_id'] ?? null)) {
            $filters['customer_id'] = $args['customer_id'];
        }

        if (filled($args['status'] ?? null)) {
            $filters['status'] = $args['status'];
        }

        if (! empty($args['from']) && ! empty($args['to'])) {
            $filters[ReviewService::FILTER_DATE_FROM] = $args['from'];
            $filters[ReviewService::FILTER_DATE_TO] = $args['to'];
        }

        return $filters;
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }
}
