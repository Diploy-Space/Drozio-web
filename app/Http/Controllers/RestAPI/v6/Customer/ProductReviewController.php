<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Review\AddRequest;
use App\Http\Requests\Customer\Review\DeleteImageRequest;
use App\Http\Requests\Customer\Review\ListRequest;
use App\Http\Resources\V6\Customer\ReviewResource;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductReviewController extends BaseApiController
{
    private const LIST_RELATIONS = ['customer', 'product', 'reply'];

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ProductRetailService $productRetailService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'Active reviews of a product, with its average rating and rating breakdown',
        tag: 'Review',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ],
        status: [404, 422],
        auth: false,
    )]
    public function getList(ListRequest $request, string $slug): JsonResponse
    {
        $product = $this->productRetailService->findBy(filters: ['slug' => $slug]);

        if ($product === null) {
            return $this->responseFormatter(config: config('response.product_not_found'));
        }

        $args = [
            'limit' => $request->query('limit'),
            'offset' => $request->query('offset'),
        ];

        $reviews = $this->reviewService->getList(
            dataLimit: (int) ($args['limit'] ?? self::LIST_DEFAULT_LIMIT),
            page: (int) ($args['offset'] ?? self::LIST_DEFAULT_OFFSET),
            relations: self::LIST_RELATIONS,
            filters: ['product_id' => $product->id],
            sortBy: ReviewService::SORT_RECENTLY_UPDATED,
        );

        $averageRating = $this->reviewService->getAverageRating(productId: $product->id);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => ReviewResource::collection($reviews),
                'average_rating' => $averageRating === null ? 0 : number_format($averageRating, 2, '.', ' '),
                'group-wise-rating' => $this->reviewService
                    ->getRatingDistribution(filters: ['product_id' => $product->id])
                    ->map(static fn ($row): array => ['rating' => (int) $row->rating, 'total' => (int) $row->total])
                    ->values(),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($reviews),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Overall rating of a product',
        tag: 'Review',
        auth: false,
    )]
    public function getRating(int|string $productId): JsonResponse
    {
        $product = $this->productRetailService->find(id: $productId);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $product === null ? 0.0 : $this->reviewService->getOverallRatingForProduct(productId: $product->id),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Submit a review for a product of one of the customer\'s orders',
        tag: 'Review',
        status: [401, 404, 422],
    )]
    public function create(AddRequest $request): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;

        if (! $this->isCustomerOrder(orderId: $request['order_id'], customerId: $customerId)) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $review = $this->reviewService->submitProductReview(
            customerId: $customerId,
            productId: $request['product_id'],
            orderId: $request['order_id'],
            comment: $request['comment'],
            rating: $request['rating'],
            images: $this->uploadedImages(request: $request),
        );

        return $this->responseFormatter(
            config: config('response.product_review_submitted'),
            content: ['data' => new ReviewResource($review)],
        );
    }

    #[ApiDoc(
        summary: 'Update one of the customer\'s product reviews',
        description: 'Send fileUpload to append images, as a multipart POST request (PHP does not parse a multipart PUT body); omit it to keep the stored images untouched.',
        tag: 'Review',
        status: [401, 404, 422],
    )]
    public function update(AddRequest $request, int|string $id): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;

        if (! $this->isCustomerOrder(orderId: $request['order_id'], customerId: $customerId)) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $review = $this->reviewService->updateCustomerProductReview(
            customerId: $customerId,
            id: $id,
            orderId: $request['order_id'],
            comment: $request['comment'],
            rating: $request['rating'],
            images: $request->has('fileUpload') ? $this->uploadedImages(request: $request) : null,
        );

        if ($review === null) {
            return $this->responseFormatter(config: config('response.review_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.product_review_updated'),
            content: ['data' => new ReviewResource($review)],
        );
    }

    #[ApiDoc(
        summary: 'The customer\'s review of a product within one order',
        tag: 'Review',
        status: [401, 404],
    )]
    public function findByOrder(Request $request, int|string $orderId, int|string $productId): JsonResponse
    {
        $review = $this->reviewService->findCustomerProductReview(
            customerId: (int) $request->user('api')->id,
            productId: $productId,
            orderId: $orderId,
        );

        if ($review === null) {
            return $this->responseFormatter(config: config('response.review_not_found'));
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new ReviewResource($review)],
        );
    }

    #[ApiDoc(
        summary: 'Remove one image from the customer\'s product review',
        tag: 'Review',
        query: ['name' => 'required|string'],
        status: [401, 404, 422],
    )]
    public function deleteImage(DeleteImageRequest $request, int|string $id): JsonResponse
    {
        $review = $this->reviewService->findBy(params: ['id' => $id, 'customer_id' => $request->user('api')->id]);

        if ($review === null) {
            return $this->responseFormatter(config: config('response.review_not_found'));
        }

        $this->reviewService->removeAttachment(reviewId: $review->id, fileName: $request['name']);

        return $this->responseFormatter(config: config('response.product_review_image_removed'));
    }

    private function isCustomerOrder(int|string $orderId, int $customerId): bool
    {
        return $this->orderService->exists(params: [
            'id' => $orderId,
            'customer_id' => $customerId,
            'is_guest' => 0,
        ]);
    }

    /** @return array<int, UploadedFile> */
    private function uploadedImages(Request $request): array
    {
        return array_values(array_filter(
            Arr::wrap($request->file('fileUpload')),
            static fn ($image): bool => $image instanceof UploadedFile,
        ));
    }
}
