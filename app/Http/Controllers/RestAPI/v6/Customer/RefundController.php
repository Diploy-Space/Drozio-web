<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Events\RefundEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Refund\DetailsRequest;
use App\Http\Requests\Customer\Refund\EligibilityRequest;
use App\Http\Requests\Customer\Refund\StoreRequest;
use App\Http\Resources\V6\Customer\RefundRequestResource;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\RefundRequestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RefundController extends BaseApiController
{
    private const REFUND_EVENT_STATUS = 'refund_request';

    private const DELIVERY_STATUS_DELIVERED = 'delivered';

    private const ORDER_DETAIL_REFUND_REQUESTED = 1;

    private const ORDER_DETAIL_RELATIONS = ['order'];

    public function __construct(
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly RefundRequestService $refundRequestService,
    ) {}

    #[ApiDoc(
        summary: 'Whether an order item can be refunded, with its refundable amounts',
        tag: 'Refund',
        status: [401, 403, 404],
    )]
    public function getEligibility(EligibilityRequest $request, int|string $orderDetailId): JsonResponse
    {
        $customer = $request->user('api');
        $orderDetail = $this->findCustomerOrderDetail(orderDetailId: $orderDetailId, customerId: $customer->id);

        if ($orderDetail === null) {
            return $this->responseFormatter(config: config('response.refund_order_item_not_found'));
        }

        $requiredLoyaltyPoints = $this->orderDetailsRewardsService->getLoyaltyPointForOrderDetail(orderDetailId: $orderDetail->id);

        if (($customer->loyalty_point ?? 0) < $requiredLoyaltyPoints) {
            return $this->responseFormatter(config: config('response.refund_insufficient_loyalty_point'));
        }

        if ($orderDetail->delivery_status !== self::DELIVERY_STATUS_DELIVERED) {
            return $this->responseFormatter(config: config('response.refund_order_not_delivered'));
        }

        $refundDetails = $this->orderDetailService->getRefundDetails(orderDetail: $orderDetail, order: $orderDetail->order);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => [
                    'already_requested' => $orderDetail->refund_request != 0,
                    'expired' => $this->orderDetailService->isRefundWindowExpired(
                        orderDetail: $orderDetail,
                        refundDayLimit: getWebConfig(name: 'refund_day_limit'),
                    ),
                    'refund' => $this->getRefundSummary(orderDetail: $orderDetail, refundDetails: $refundDetails) + [
                        'referral_discount' => $refundDetails['referral_discount'],
                    ],
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Request a refund for one of the customer\'s order items',
        tag: 'Refund',
        status: [401, 403, 404, 409, 422],
    )]
    public function create(StoreRequest $request): JsonResponse
    {
        $customer = $request->user('api');
        $orderDetail = $this->findCustomerOrderDetail(orderDetailId: $request['order_details_id'], customerId: $customer->id);

        if ($orderDetail === null) {
            return $this->responseFormatter(config: config('response.refund_order_item_not_found'));
        }

        $loyaltyReward = $this->orderDetailsRewardsService->findBy(params: [
            'order_details_id' => $orderDetail->id,
            'reward_type' => OrderDetailsRewardsService::REWARD_TYPE_LOYALTY_POINT,
        ]);

        if ($loyaltyReward && $customer->loyalty_point < $loyaltyReward['reward_amount']) {
            return $this->responseFormatter(config: config('response.refund_insufficient_loyalty_point'));
        }

        if ($orderDetail->refund_request != 0) {
            return $this->responseFormatter(config: config('response.refund_already_requested'));
        }

        $storedImages = $this->refundRequestService->uploadImages(images: $request->file('images') ?? []);

        [$refundRequest, $requestedOrderDetail] = DB::transaction(function () use ($request, $customer, $orderDetail, $storedImages): array {
            $refundRequest = $this->refundRequestService->create(data: [
                'order_details_id' => $orderDetail->id,
                'customer_id' => $customer->id,
                'status' => RefundRequestService::STATUS_PENDING,
                'amount' => $this->orderDetailService->getRefundDetails(orderDetail: $orderDetail, order: $orderDetail->order)['total_refundable_amount'],
                'product_id' => $orderDetail->product_id,
                'order_id' => $orderDetail->order_id,
                'refund_reason' => $request['refund_reason'],
                'images' => $storedImages,
            ]);

            return [
                $refundRequest,
                $this->orderDetailService->update(id: $orderDetail->id, data: ['refund_request' => self::ORDER_DETAIL_REFUND_REQUESTED]),
            ];
        });

        event(new RefundEvent(
            status: self::REFUND_EVENT_STATUS,
            order: $orderDetail->order,
            refund: $refundRequest,
            orderDetails: $requestedOrderDetail,
        ));

        return $this->responseFormatter(
            config: config('response.refund_requested'),
            content: ['data' => new RefundRequestResource($refundRequest)],
        );
    }

    #[ApiDoc(
        summary: 'Refund details of one of the customer\'s order items',
        tag: 'Refund',
        status: [401, 404],
    )]
    public function find(DetailsRequest $request, int|string $orderDetailId): JsonResponse
    {
        $customerId = $request->user('api')->id;
        $orderDetail = $this->findCustomerOrderDetail(orderDetailId: $orderDetailId, customerId: $customerId);

        if ($orderDetail === null) {
            return $this->responseFormatter(config: config('response.refund_order_item_not_found'));
        }

        $order = $orderDetail->order;
        $refundDetails = $this->orderDetailService->getRefundDetails(orderDetail: $orderDetail, order: $order);
        $refundRequests = $this->refundRequestService->getListByOrderDetail(
            orderDetailId: $orderDetail->id,
            customerId: $customerId,
            relations: ['refundStatus'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => $this->getRefundSummary(orderDetail: $orderDetail, refundDetails: $refundDetails) + [
                    'refund_request' => RefundRequestResource::collection($refundRequests),
                    'order_place_date' => $order->created_at,
                    'referral_discount' => $order->refer_and_earn_discount ?? 0,
                ],
            ],
        );
    }

    private function findCustomerOrderDetail(int|string $orderDetailId, int|string $customerId): ?Model
    {
        return $this->orderDetailService->findForCustomerOwner(
            id: $orderDetailId,
            customerId: $customerId,
            isGuest: false,
            relations: self::ORDER_DETAIL_RELATIONS,
        );
    }

    private function getRefundSummary(Model $orderDetail, array $refundDetails): array
    {
        return [
            'product_price' => $refundDetails['product_price'],
            'quntity' => $orderDetail->qty,
            'product_total_discount' => $refundDetails['product_discount'],
            'product_total_tax' => $refundDetails['tax'],
            'subtotal' => $refundDetails['sub_total'],
            'coupon_discount' => $refundDetails['coupon_discount'],
            'refund_amount' => $refundDetails['total_refundable_amount'],
        ];
    }
}
