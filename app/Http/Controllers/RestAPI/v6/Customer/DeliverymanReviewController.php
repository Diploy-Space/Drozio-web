<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\DeliveryMan\ReviewGetRequest;
use App\Http\Requests\Customer\DeliveryMan\ReviewUpdateRequest;
use App\Http\Requests\Customer\Review\DeliverymanAddRequest;
use App\Http\Resources\V6\Customer\ReviewResource;
use App\Services\New\GuestUserService;
use App\Services\New\OrderService;
use App\Services\New\ReviewService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class DeliverymanReviewController extends BaseApiController
{
    use CustomerOwnerTrait;

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly OrderService $orderService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'The delivery-man review of a paid order',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Review',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 404],
        auth: false,
        guard: 'api',
    )]
    public function find(ReviewGetRequest $request, int|string $orderId): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findPaidOrder(orderId: $orderId, ownerFilters: $this->customerOwnerFilters($owner));

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        $review = $this->reviewService->findBy(params: [
            'delivery_man_id' => $order->delivery_man_id,
            'customer_id' => $owner['id'],
            'order_id' => $orderId,
        ]);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => $review === null ? null : new ReviewResource($review)],
        );
    }

    #[ApiDoc(
        summary: 'Review the delivery man of one of the customer\'s paid orders',
        tag: 'Review',
        status: [401, 403, 404, 422],
    )]
    public function create(DeliverymanAddRequest $request, int|string $orderId): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;

        $order = $this->findPaidOrder(orderId: $orderId, ownerFilters: ['customer_id' => $customerId, 'is_guest' => 0]);

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        if ($order->delivery_man_id === null) {
            return $this->responseFormatter(config: config('response.deliveryman_review_unavailable'));
        }

        $this->reviewService->upsertDeliverymanReview(
            customerId: $customerId,
            deliveryManId: (int) $order->delivery_man_id,
            orderId: $order->id,
            comment: $request['comment'],
            rating: (int) $request['rating'],
        );

        return $this->responseFormatter(config: config('response.deliveryman_review_submitted'));
    }

    #[ApiDoc(
        summary: 'Add or update the delivery-man review of a paid order',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Review',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function update(ReviewUpdateRequest $request, int|string $orderId): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->findPaidOrder(orderId: $orderId, ownerFilters: $this->customerOwnerFilters($owner));

        if ($order === null) {
            return $this->responseFormatter(config: config('response.order_not_found'));
        }

        if ($order->delivery_man_id === null) {
            return $this->responseFormatter(config: config('response.deliveryman_review_unavailable'));
        }

        $existingReview = $this->reviewService->findBy(params: [
            'delivery_man_id' => $order->delivery_man_id,
            'customer_id' => $owner['id'],
            'order_id' => $orderId,
        ]);

        $this->reviewService->upsertDeliverymanReview(
            customerId: (int) $owner['id'],
            deliveryManId: (int) $order->delivery_man_id,
            orderId: $orderId,
            comment: $request['comment'],
            rating: (int) $request['rating'],
        );

        return $this->responseFormatter(
            config: config($existingReview === null ? 'response.deliveryman_review_added' : 'response.deliveryman_review_updated'),
        );
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function findPaidOrder(int|string $orderId, array $ownerFilters): ?Model
    {
        return $this->orderService->findBy(params: [
            'id' => $orderId,
            ...$ownerFilters,
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ]);
    }
}
