<?php

namespace App\Http\Controllers\Web\v6;

use App\Events\RefundEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\RefundAddRequest;
use App\Services\New\CustomerService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Services\New\RefundRequestService;
use App\Services\New\ReviewService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    private const REFUND_REQUEST_VIEW = 'web-views.users-profile.refund-request';

    private const REFUND_EVENT_STATUS = 'refund_request';

    private const ORDER_DETAIL_REFUND_REQUESTED = 1;

    private const REGISTERED_CUSTOMER_ORDER = '0';

    public function __construct(
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderService $orderService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly CustomerService $customerService,
        private readonly RefundRequestService $refundRequestService,
        private readonly ProductRetailService $productRetailService,
        private readonly ReviewService $reviewService,
    ) {}

    public function create(int|string $id): View|RedirectResponse
    {
        $orderLine = $this->findCustomerOrderLine(orderDetailId: $id);

        if ($orderLine === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        if ($this->hasInsufficientLoyaltyPoints(requiredPoints: $this->orderDetailsRewardsService->getLoyaltyPointForOrderDetail(orderDetailId: $id))) {
            ToastMagic::warning(translate('You do not have sufficient loyalty points to refund this order').'!!');

            return back();
        }

        return view(self::REFUND_REQUEST_VIEW, [
            'order_details' => $orderLine['orderDetail'],
        ]);
    }

    public function details(Request $request, int|string $id): View|JsonResponse|RedirectResponse
    {
        $orderLine = $this->findCustomerOrderLine(orderDetailId: $id);
        $product = $orderLine === null ? null : $this->productRetailService->find(id: $orderLine['orderDetail']->product_id);

        if ($product === null) {
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => translate('Not found')]);
            }

            ToastMagic::error(translate('Not found'));

            return redirect()->back();
        }

        $viewData = [
            'order_details' => $orderLine['orderDetail'],
            'refund' => $this->refundRequestService->findBy(
                params: ['customer_id' => auth('customer')->id(), 'order_details_id' => $orderLine['orderDetail']->id],
                relations: ['product', 'order'],
            ),
            'product' => $product,
            'order' => $orderLine['order'],
        ];

        if ($request->ajax()) {
            return response()->json([
                'status' => 1,
                'view' => view(themeViewName(key: 'refund_details'), $viewData)->render(),
            ]);
        }

        return view(themeViewName(key: 'refund_details'), $viewData);
    }

    public function store(RefundAddRequest $request): RedirectResponse
    {
        $images = $request->file('images') ?? [];
        $disallowedExtension = $this->reviewService->hasDisallowedExtension(files: $images);

        if ($disallowedExtension !== null) {
            ToastMagic::error(translate('Invalid file format given').$disallowedExtension);

            return redirect()->back();
        }

        $orderLine = $this->findCustomerOrderLine(orderDetailId: $request['order_details_id']);

        if ($orderLine === null) {
            ToastMagic::error(translate('Not found'));

            return back();
        }

        $orderDetail = $orderLine['orderDetail'];
        $order = $orderLine['order'];

        $loyaltyReward = $this->orderDetailsRewardsService->findBy(params: [
            'order_details_id' => $orderDetail->id,
            'reward_type' => OrderDetailsRewardsService::REWARD_TYPE_LOYALTY_POINT,
        ]);

        if ($loyaltyReward && $this->hasInsufficientLoyaltyPoints(requiredPoints: $loyaltyReward['reward_amount'])) {
            ToastMagic::warning(translate('You do not have sufficient loyalty points to refund this order').'!!');

            return back();
        }

        $storedImages = $this->refundRequestService->uploadImages(images: $images);

        [$refundRequest, $requestedOrderDetail] = DB::transaction(function () use ($request, $orderDetail, $order, $storedImages): array {
            $refundRequest = $this->refundRequestService->create(data: [
                'order_details_id' => $orderDetail->id,
                'customer_id' => auth('customer')->id(),
                'status' => RefundRequestService::STATUS_PENDING,
                'amount' => $this->orderDetailService->getRefundDetails(orderDetail: $orderDetail, order: $order)['total_refundable_amount'],
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

        event(new RefundEvent(status: self::REFUND_EVENT_STATUS, order: $order, refund: $refundRequest, orderDetails: $requestedOrderDetail));

        ToastMagic::success(translate('Refund requested successfully'));

        return redirect()->route('account-order-details', ['id' => $orderDetail->order_id]);
    }

    /** @return array{orderDetail: Model, order: Model}|null */
    private function findCustomerOrderLine(mixed $orderDetailId): ?array
    {
        if (! auth('customer')->check()) {
            return null;
        }

        $orderDetail = $this->orderDetailService->find(id: $orderDetailId);

        if ($orderDetail === null) {
            return null;
        }

        $order = $this->orderService->findBy(params: [
            'id' => $orderDetail->order_id,
            'customer_id' => auth('customer')->id(),
            'is_guest' => self::REGISTERED_CUSTOMER_ORDER,
        ]);

        return $order === null ? null : ['orderDetail' => $orderDetail, 'order' => $order];
    }

    private function hasInsufficientLoyaltyPoints(int|float|string $requiredPoints): bool
    {
        return $this->customerService->find(id: auth('customer')->id())['loyalty_point'] < $requiredPoints;
    }
}
