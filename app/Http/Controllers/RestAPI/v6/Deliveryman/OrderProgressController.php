<?php

namespace App\Http\Controllers\RestAPI\v6\Deliveryman;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Deliveryman\Order\ExpectedDeliveryUpdateRequest;
use App\Http\Requests\Deliveryman\Order\PauseUpdateRequest;
use App\Http\Requests\Deliveryman\Order\PaymentStatusUpdateRequest;
use App\Http\Requests\Deliveryman\Order\StatusUpdateRequest;
use App\Services\New\CustomerService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderEditHistoryService;
use App\Services\New\OrderService;
use App\Services\New\OrderSettlementService;
use App\Services\New\OrderStatusHistoryService;
use App\Services\New\OrderTransactionService;
use App\Services\New\ProductRetailService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\WalletTransactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class OrderProgressController extends BaseApiController
{
    private const STATUS_CHANGE_RELATIONS = ['customer', 'deliveryMan', 'latestEditHistory'];

    private const LOCK_RELATIONS = ['latestEditHistory'];

    private const WALLET_RECEIVED_BY_DELIVERY_MAN = 'delivery man';

    private const LOYALTY_POINT_STATUS = 'loyalty_point_status';

    private const LOYALTY_POINT_FOR_EACH_ORDER = 'loyalty_point_for_each_order';

    private const EXPECTED_DELIVERY_DATE_FIELD = 'expected_delivery_date';

    private const OUTCOME_UPDATED = 'updated';

    private const OUTCOME_NOT_FOUND = 'not_found';

    private const OUTCOME_ALREADY_DELIVERED = 'already_delivered';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderEditHistoryService $orderEditHistoryService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly OrderSettlementService $orderSettlementService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly ProductRetailService $productRetailService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly CustomerService $customerService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    #[ApiDoc(
        summary: 'Change the status of an assigned order',
        description: 'status is one of delivered, canceled, returned or out_for_delivery. Delivering settles any edit due amount, credits the delivery man wallet, the seller wallet, loyalty points and referral earnings. cause is stored on the order only when canceling.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $deliverymanId = $this->deliverymanId($request);
        $status = $request['status'];
        $cause = $request['cause'];

        $order = $this->orderService->findForDeliveryman(id: (int) $id, deliverymanId: $deliverymanId, relations: self::STATUS_CHANGE_RELATIONS);

        if ($order === null) {
            return $this->orderNotFound();
        }

        if ($this->orderService->isAlreadyDelivered(order: $order)) {
            return $this->orderAlreadyDelivered();
        }

        $notifications = [];

        $outcome = DB::transaction(function () use ($order, $deliverymanId, $status, $cause, &$notifications): string {
            $lockedOrder = $this->orderService->lockForDeliveryman(id: $order->id, deliverymanId: $deliverymanId, relations: self::LOCK_RELATIONS);

            if ($lockedOrder === null) {
                return self::OUTCOME_NOT_FOUND;
            }

            $isDelivered = $status === OrderService::STATUS_DELIVERED;

            if ($this->orderService->isAlreadyDelivered(order: $lockedOrder) || ($isDelivered && $this->orderTransactionService->isSettled(orderId: $lockedOrder->id))) {
                return self::OUTCOME_ALREADY_DELIVERED;
            }

            $this->orderService->updateWhere(
                params: ['id' => $lockedOrder->id, 'delivery_man_id' => $deliverymanId],
                data: ['order_status' => $status, 'cause' => $status === OrderService::STATUS_CANCELED ? $cause : null],
            );

            $statusOrder = $isDelivered
                ? $this->settleDelivery(lockedOrder: $lockedOrder, deliverymanId: $deliverymanId) ?? $order
                : $order;

            $notifications = $this->statusNotifications(status: $status, order: $statusOrder);

            $this->updateStockForOrderStatus(orderId: $statusOrder->id, orderStatus: $status);

            if ($isDelivered && $statusOrder['seller_id'] != null) {
                $this->orderSettlementService->manageWalletOnOrderStatusChange(order: $statusOrder, receivedBy: self::WALLET_RECEIVED_BY_DELIVERY_MAN);
                $this->orderDetailService->updateWhere(
                    params: ['order_id' => $statusOrder->id],
                    data: ['delivery_status' => OrderDetailService::DELIVERY_STATUS_DELIVERED],
                );
            }

            if ($isDelivered) {
                $this->orderDetailService->startRefundWindowForOrder(orderId: $statusOrder->id);
                $this->creditLoyaltyReward(order: $statusOrder);
                $notifications = [...$notifications, ...$this->creditReferrerOnceForDelivery(order: $statusOrder)];
            }

            $this->orderStatusHistoryService->create(data: [
                'order_id' => $statusOrder->id,
                'user_id' => $deliverymanId,
                'user_type' => OrderStatusHistoryService::USER_TYPE_DELIVERY_MAN,
                'status' => $status,
                'cause' => $cause,
            ]);

            return self::OUTCOME_UPDATED;
        });

        if ($outcome === self::OUTCOME_NOT_FOUND) {
            return $this->orderNotFound();
        }

        if ($outcome === self::OUTCOME_ALREADY_DELIVERED) {
            return $this->orderAlreadyDelivered();
        }

        foreach ($notifications as $notification) {
            $this->orderService->dispatchStatusNotification(key: $notification['key'], type: $notification['type'], order: $notification['order']);
        }

        return $this->responseFormatter(config: config('response.order_status_updated'));
    }

    #[ApiDoc(
        summary: 'Reschedule an assigned order',
        description: 'Writes the expected delivery date and its history row, stores cause on the order and notifies the customer of the new date.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function updateExpectedDelivery(ExpectedDeliveryUpdateRequest $request, string $id): JsonResponse
    {
        $deliverymanId = $this->deliverymanId($request);
        $order = $this->orderService->findForDeliveryman(id: (int) $id, deliverymanId: $deliverymanId, relations: ['deliveryMan']);

        if ($order === null) {
            return $this->orderNotFound();
        }

        if ($this->orderService->isAlreadyDelivered(order: $order)) {
            return $this->orderAlreadyDelivered();
        }

        $isRescheduled = DB::transaction(function () use ($order, $deliverymanId, $request): bool {
            $isDateUpdated = $this->orderSettlementService->updateAmountDate(
                request: new Fluent([
                    'order_id' => $order->id,
                    'field_name' => self::EXPECTED_DELIVERY_DATE_FIELD,
                    'field_val' => $request['expected_delivery_date'],
                    'cause' => $request['cause'],
                ]),
                userId: $deliverymanId,
                userType: OrderStatusHistoryService::USER_TYPE_DELIVERY_MAN,
            );

            if (! $isDateUpdated) {
                return false;
            }

            $this->orderService->updateWhere(
                params: ['id' => $order->id, 'delivery_man_id' => $deliverymanId],
                data: ['cause' => $request['cause']],
            );

            return true;
        });

        if (! $isRescheduled) {
            return $this->responseFormatter(config: config('response.order_delivery_schedule_update_failed'));
        }

        $this->orderService->dispatchStatusNotification(
            key: OrderService::NOTIFICATION_ORDER_RESCHEDULED,
            type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
            order: $this->orderService->findForDeliveryman(id: $order->id, deliverymanId: $deliverymanId, relations: ['deliveryMan']) ?? $order,
        );

        return $this->responseFormatter(config: config('response.order_expected_delivery_updated'));
    }

    #[ApiDoc(
        summary: 'Pause or resume an assigned order',
        description: 'is_pause is 1 to pause and 0 to resume; cause is stored on the order either way.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function updatePause(PauseUpdateRequest $request, string $id): JsonResponse
    {
        $deliverymanId = $this->deliverymanId($request);
        $order = $this->orderService->findForDeliveryman(id: (int) $id, deliverymanId: $deliverymanId);

        if ($order === null) {
            return $this->orderNotFound();
        }

        if ($this->orderService->isAlreadyDelivered(order: $order)) {
            return $this->orderAlreadyDelivered();
        }

        $this->orderService->updateWhere(
            params: ['id' => $order->id, 'delivery_man_id' => $deliverymanId],
            data: ['is_pause' => (int) $request['is_pause'], 'cause' => $request['cause']],
        );

        return $this->responseFormatter(config: config('response.order_pause_status_updated'));
    }

    #[ApiDoc(
        summary: 'Mark an assigned order as paid',
        description: 'payment_status must be paid. Any edit due amount is added to the order amount and the latest edit is marked paid by the delivery man.',
        tag: 'Delivery-man Orders',
        status: [401, 403, 404, 422],
        guard: 'deliveryman',
    )]
    public function updatePaymentStatus(PaymentStatusUpdateRequest $request, string $id): JsonResponse
    {
        $deliverymanId = $this->deliverymanId($request);

        $isFound = DB::transaction(function () use ($id, $deliverymanId): bool {
            $order = $this->orderService->lockForDeliveryman(id: (int) $id, deliverymanId: $deliverymanId, relations: self::LOCK_RELATIONS);

            if ($order === null) {
                return false;
            }

            $this->orderService->settleEditDueAmount(order: $order);

            if ($order->latestEditHistory) {
                $this->orderEditHistoryService->markDuePaidByDeliveryman(
                    id: $order->latestEditHistory->id,
                    paymentMethod: $order->latestEditHistory->order_due_payment_method,
                );
            }

            return true;
        });

        if (! $isFound) {
            return $this->orderNotFound();
        }

        return $this->responseFormatter(config: config('response.order_payment_status_updated'));
    }

    private function settleDelivery(Model $lockedOrder, int $deliverymanId): ?Model
    {
        $this->orderService->settleEditDueAmount(order: $lockedOrder);

        if ($lockedOrder->latestEditHistory) {
            $this->orderEditHistoryService->markDuePaidOnDelivery(id: $lockedOrder->latestEditHistory->id);
        }

        $deliveredOrder = $this->orderService->findForDeliveryman(id: $lockedOrder->id, deliverymanId: $deliverymanId, relations: self::STATUS_CHANGE_RELATIONS);

        if ($deliveredOrder !== null) {
            $this->deliverymanWalletService->creditDeliveryEarning(
                deliverymanId: $deliverymanId,
                deliverymanCharge: (float) ($deliveredOrder['deliveryman_charge'] ?? 0),
                cashInHand: (float) $this->orderService->getDeliverymanCashCollection(order: $deliveredOrder),
            );
        }

        return $deliveredOrder;
    }

    /** @return array<int, array{key: string, type: string, order: Model}> */
    private function statusNotifications(string $status, Model $order): array
    {
        $type = match ($status) {
            OrderService::STATUS_OUT_FOR_DELIVERY, OrderService::STATUS_DELIVERED => OrderService::NOTIFICATION_TYPE_CUSTOMER,
            OrderService::STATUS_CANCELED => OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
            default => null,
        };

        return $type === null ? [] : [['key' => $status, 'type' => $type, 'order' => $order]];
    }

    private function updateStockForOrderStatus(int $orderId, string $orderStatus): void
    {
        foreach ($this->orderDetailService->getList(dataLimit: 'all', filters: ['order_id' => $orderId]) as $orderDetail) {
            $stockChange = $this->productRetailService->updateStockForOrderDetail(orderDetail: $orderDetail, status: $orderStatus);

            if ($stockChange !== null) {
                $this->orderDetailService->update(id: $orderDetail->id, data: $stockChange);
            }
        }
    }

    private function creditLoyaltyReward(Model $order): void
    {
        $loyaltyPointStatus = getWebConfig(name: self::LOYALTY_POINT_STATUS);
        $loyaltyPointForEachOrder = getWebConfig(name: self::LOYALTY_POINT_FOR_EACH_ORDER) ?? $loyaltyPointStatus;
        if ($loyaltyPointStatus != 1 || $loyaltyPointForEachOrder != 1 || $order['is_guest']) {
            return;
        }

        $loyaltyPoint = $this->orderDetailsRewardsService->getUndeliveredLoyaltyPoint(orderId: $order->id);

        if ($loyaltyPoint <= 0) {
            return;
        }

        $this->recordLoyaltyPointEntry(
            customerId: $order['customer_id'],
            amount: (float) $loyaltyPoint,
            transactionType: LoyaltyPointTransactionService::TRANSACTION_TYPE_ORDER_PLACE,
            reference: (string) $order->id,
        );
        $this->orderDetailsRewardsService->markLoyaltyPointDelivered(orderId: $order->id);
    }

    /** @return array<int, array{key: string, type: string, order: Model}> */
    private function creditReferrerOnceForDelivery(Model $order): array
    {
        $referredDelivery = $order['is_guest']
            ? ['notifies_promoter' => false, 'allows_referrer_bonus' => true]
            : $this->referralCustomerService->recordReferredCustomerDelivery(customerId: $order['customer_id']);

        if ($referredDelivery['allows_referrer_bonus']) {
            $this->creditReferrerForFirstDeliveredOrder(order: $order);
        }

        return $referredDelivery['notifies_promoter']
            ? [['key' => OrderService::NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED, 'type' => OrderService::NOTIFICATION_TYPE_PROMOTER, 'order' => $order]]
            : [];
    }

    private function creditReferrerForFirstDeliveredOrder(Model $order): void
    {
        $referrerId = $this->referralCustomerService->getReferrerIdForFirstDeliveredOrder(
            order: $order,
            customerDeliveredPaidOrderCount: $this->orderService->count(filters: [
                'customer_id' => $order['customer_id'],
                'order_status' => OrderService::STATUS_DELIVERED,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
            ]),
        );

        if ($referrerId !== null) {
            $this->creditCustomerWallet(
                customerId: $referrerId,
                amount: $this->referralCustomerService->getReferrerEarningAmount(),
                transactionType: WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
                reference: WalletTransactionService::REFERENCE_TYPES['earned_by_referral'],
            );
        }
    }

    private function creditCustomerWallet(int|string $customerId, float $amount, string $transactionType, string $reference, ?string $paymentMethod = null): bool
    {
        $customer = getWebConfig(name: 'wallet_status') == 1 ? $this->customerService->find(id: $customerId) : null;

        if ($customer === null) {
            return false;
        }

        $walletTransaction = $this->walletTransactionService->createWalletEntry(
            customerId: $customer->id,
            currentBalance: (float) $customer->wallet_balance,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
            paymentMethod: $paymentMethod,
        );

        return $walletTransaction !== null && $this->customerService->updateWalletBalance(
            id: $customer->id,
            walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
        );
    }

    private function recordLoyaltyPointEntry(int|string $customerId, float $amount, string $transactionType, string $reference): void
    {
        $customer = $this->customerService->find(id: $customerId);

        if ($customer === null) {
            return;
        }

        $pointTransaction = $this->loyaltyPointTransactionService->createPointEntry(
            customerId: $customer->id,
            currentPoint: (float) $customer->loyalty_point,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
        );

        if ($pointTransaction !== null) {
            $this->customerService->updateWhere(params: ['id' => $customer->id], data: ['loyalty_point' => $pointTransaction->balance]);
        }
    }

    private function deliverymanId(Request $request): int
    {
        return (int) $request->attributes->get('delivery_man')->id;
    }

    private function orderNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }

    private function orderAlreadyDelivered(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_status_already_delivered'));
    }
}
