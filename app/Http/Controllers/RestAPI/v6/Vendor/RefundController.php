<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Events\RefundEvent;
use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Order\RefundStatusUpdateRequest;
use App\Http\Resources\V6\Vendor\DeliveryManResource;
use App\Http\Resources\V6\Vendor\RefundRequestResource;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderService;
use App\Services\New\RefundRequestService;
use App\Services\New\RefundStatusService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RefundController extends BaseApiController
{
    private const REFUND_RELATIONS = ['customer', 'product', 'orderDetails', 'order:id,payment_method,payment_status,order_type'];

    private const LOYALTY_POINT_ENABLED = 1;

    public function __construct(
        private readonly RefundRequestService $refundRequestService,
        private readonly RefundStatusService $refundStatusService,
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly CustomerService $customerService,
        private readonly DeliveryManService $deliveryManService,
    ) {}

    #[ApiDoc(
        summary: 'Refund requests on the authenticated seller orders',
        description: 'Newest first. date_type is this_year, this_month, this_week, today or custom_date; custom_date without start_date and end_date covers the last seven days.',
        tag: 'Refunds (Seller)',
        query: [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'date_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ],
        status: [401],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $dataLimit = max(1, (int) ($request['limit'] ?? DEFAULT_DATA_LIMIT));
        $page = max(1, (int) ($request['offset'] ?? 1));

        $refunds = $this->refundRequestService->getListForSeller(
            sellerId: $this->sellerId($request),
            searchValue: $request['search'],
            dateType: $request['date_type'],
            startDate: $request['start_date'],
            endDate: $request['end_date'],
            relations: self::REFUND_RELATIONS,
            status: filled($request['status']) ? (string) $request['status'] : null,
            dataLimit: $dataLimit,
            page: $page,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => RefundRequestResource::collection($refunds->getCollection()),
                'params' => $this->paramsFormatter($request->only(['search', 'status', 'date_type', 'start_date', 'end_date'])),
                'pagination' => $this->paginateFormatter($refunds),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Refund amount breakdown of one seller refund request',
        description: 'Amounts are for the refunded order item; refund_request lists every refund request raised on that item with its status history.',
        tag: 'Refunds (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function find(Request $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $refund = $this->findSellerRefund(id: $id, sellerId: $sellerId);
        $orderDetail = $refund === null ? null : $this->orderDetailService->findForSeller(id: $refund->order_details_id, sellerId: $sellerId);
        $order = $orderDetail === null ? null : $this->orderService->findForSeller(id: $orderDetail->order_id, sellerId: $sellerId);

        if ($order === null) {
            return $this->refundNotFound();
        }

        $refundDetails = $this->orderDetailService->getRefundDetails(orderDetail: $orderDetail, order: $order);
        $orderDetailRefunds = $this->refundRequestService->getListByOrderDetail(
            orderDetailId: $orderDetail->id,
            sellerId: $sellerId,
            relations: ['refundStatus'],
        );
        $deliveryMan = $order->delivery_man_id === null ? null : $this->deliveryManService->find(id: $order->delivery_man_id);

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => [
                    'product_price' => $orderDetail->price,
                    'quntity' => $orderDetail->qty,
                    'product_total_discount' => $orderDetail->discount,
                    'product_total_tax' => $orderDetail->tax,
                    'subtotal' => $refundDetails['sub_total'],
                    'coupon_discount' => $refundDetails['coupon_discount'],
                    'refund_amount' => $refundDetails['total_refundable_amount'],
                    'refund_request' => RefundRequestResource::collection($orderDetailRefunds),
                    'referral_discount' => $refundDetails['referral_discount'],
                    'deliveryman_details' => $deliveryMan === null ? null : new DeliveryManResource($deliveryMan),
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'One seller refund request with its customer, product, order item and order payment method',
        tag: 'Refunds (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function getOrderItem(Request $request, string $id): JsonResponse
    {
        $refund = $this->findSellerRefund(id: $id, sellerId: $this->sellerId($request), relations: self::REFUND_RELATIONS);

        if ($refund === null) {
            return $this->refundNotFound();
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => new RefundRequestResource($refund)],
        );
    }

    #[ApiDoc(
        summary: 'Approve or reject a seller refund request',
        description: 'refund_status is approved or rejected; a note is required to reject. A refund the admin already changed, or one already refunded, cannot be changed. With loyalty points on, approving needs the customer to hold the points the order item earned.',
        tag: 'Refunds (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(RefundStatusUpdateRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $refund = $this->findSellerRefund(id: $id, sellerId: $sellerId);

        if ($refund === null) {
            return $this->refundNotFound();
        }

        $status = (string) $request['refund_status'];
        $note = $request['note'];

        if (getWebConfig(name: 'loyalty_point_status') == self::LOYALTY_POINT_ENABLED) {
            $customer = $this->customerService->find(id: $refund->customer_id);

            if ($customer === null) {
                return $this->responseFormatter(config: config('response.seller_refund_status_customer_deleted'));
            }

            $loyaltyPoint = $this->orderDetailsRewardsService->getLoyaltyPointForOrderDetail(orderDetailId: $refund->order_details_id);

            if ($customer->loyalty_point < $loyaltyPoint && $status == RefundRequestService::STATUS_APPROVED) {
                return $this->responseFormatter(config: config('response.seller_refund_status_loyalty_point_insufficient'));
            }
        }

        $blocker = $this->refundRequestService->getSellerStatusChangeBlocker(refund: $refund);

        if ($blocker !== null) {
            return $blocker === RefundRequestService::REASON_CHANGED_BY_ADMIN
                ? $this->responseFormatter(config: config('response.seller_refund_status_changed_by_admin'), content: ['status' => $refund->status])
                : $this->responseFormatter(config: config('response.seller_refund_status_already_refunded'));
        }

        [$updatedRefund, $orderDetail] = DB::transaction(function () use ($refund, $sellerId, $status, $note): array {
            $orderDetail = $this->orderDetailService->update(
                id: $refund->order_details_id,
                data: ['refund_request' => $this->refundRequestService->getOrderDetailRefundFlag(status: $status)],
            );

            $updatedRefund = $this->refundRequestService->updateStatusBySeller(id: $refund->id, status: $status, note: $note);

            $this->refundStatusService->createBySeller(refundRequestId: $refund->id, sellerId: $sellerId, status: $status, note: $note);

            return [$updatedRefund, $orderDetail];
        });

        $order = $this->orderService->findForSeller(id: $refund->order_id, sellerId: $sellerId);

        if ($order !== null) {
            event(new RefundEvent(status: $status, order: $order, refund: $updatedRefund, orderDetails: $orderDetail));
        }

        return $this->responseFormatter(config: config('response.seller_refund_status_updated'));
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }

    private function findSellerRefund(string $id, int $sellerId, array $relations = []): ?Model
    {
        return $this->refundRequestService->findBy(
            params: [
                'id' => (int) $id,
                RefundRequestService::FILTER_ORDER_SELLER_IS => RefundRequestService::SELLER_IS_SELLER,
                RefundRequestService::FILTER_ORDER_SELLER_ID => $sellerId,
            ],
            relations: $relations,
        );
    }

    private function refundNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.seller_refund_not_found'));
    }
}
