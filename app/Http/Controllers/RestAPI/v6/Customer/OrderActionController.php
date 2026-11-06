<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Order\AgainRequest;
use App\Http\Requests\Customer\Order\CancelRequest;
use App\Services\New\CartService;
use App\Services\New\ColorService;
use App\Services\New\GuestUserService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderService;
use App\Services\New\ProductRetailService;
use App\Traits\CustomerOwnerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OrderActionController extends BaseApiController
{
    use CustomerOwnerTrait;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly ProductRetailService $productRetailService,
        private readonly CartService $cartService,
        private readonly ColorService $colorService,
        private readonly GuestUserService $guestUserService,
    ) {}

    #[ApiDoc(
        summary: 'Cancel a pending cash on delivery order and restore its stock',
        description: 'Send a bearer token, or a guest_id for an unauthenticated shopper.',
        tag: 'Order',
        query: ['guest_id' => 'nullable|integer'],
        status: [401, 403, 404, 422],
        auth: false,
        guard: 'api',
    )]
    public function cancel(CancelRequest $request, int|string $id): JsonResponse
    {
        $owner = $this->resolveCustomerOwner($request);

        if ($owner === null) {
            return $this->customerOwnerNotResolved();
        }

        $order = $this->orderService->findForCustomerOwner(
            id: $id,
            customerId: $owner['id'],
            isGuest: $owner['is_guest'],
        );

        if ($order === null) {
            return $this->orderNotFound();
        }

        if (! $this->orderService->isCancellableByCustomer(order: $order)) {
            return $this->responseFormatter(config: config('response.order_status_not_changeable'));
        }

        DB::transaction(function () use ($order): void {
            $this->restoreStockForCanceledOrder(order: $order);
            $this->orderService->updateWhere(
                params: ['id' => $order->id],
                data: ['order_status' => OrderService::STATUS_CANCELED],
            );
        });

        return $this->responseFormatter(config: config('response.order_canceled'));
    }

    #[ApiDoc(
        summary: 'Put the items of a past order back into the cart',
        tag: 'Order',
        status: [401, 403, 404],
    )]
    public function reorder(AgainRequest $request, int|string $id): JsonResponse
    {
        $customerId = $request->user('api')->id;

        $order = $this->orderService->findForCustomerOwner(
            id: $id,
            customerId: $customerId,
            isGuest: false,
        );

        if ($order === null) {
            return $this->orderNotFound();
        }

        $orderDetails = $this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $order->id]);

        $result = $this->cartService->addOrderItemsAgain(
            orderDetails: $orderDetails,
            products: $this->productRetailService->getActiveCartProducts(ids: $orderDetails->pluck('product_id')->all()),
            colorCodesByName: $this->colorService->getCodeMapByName(),
            customerId: $customerId,
        );

        return $this->responseFormatter(config: config($this->getReorderResponseKey(result: $result)));
    }

    protected function guestUserService(): GuestUserService
    {
        return $this->guestUserService;
    }

    private function restoreStockForCanceledOrder(Model $order): void
    {
        $lines = $this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $order->id]);

        foreach ($lines as $line) {
            $change = $this->productRetailService->updateStockForOrderDetail(
                orderDetail: $line,
                status: OrderService::STATUS_CANCELED,
            );

            if ($change !== null) {
                $this->orderDetailService->update(id: $line->id, data: $change);
            }
        }
    }

    private function getReorderResponseKey(array $result): string
    {
        if ($result['order_product_count'] == $result['add_to_cart_count']) {
            return $result['updated_cart_count'] > 0 ? 'response.order_reorder_updated' : 'response.order_reorder_added';
        }

        if ($result['add_to_cart_count'] > 0) {
            return $result['failedAddToCartCount'] == 0
                ? 'response.order_reorder_added_with_unavailable'
                : 'response.order_reorder_partially_added';
        }

        return 'response.order_reorder_unavailable';
    }

    private function orderNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }
}
