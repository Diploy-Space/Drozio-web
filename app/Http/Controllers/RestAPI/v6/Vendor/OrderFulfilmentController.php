<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Shared\Order\AddressUpdateRequest;
use App\Http\Requests\Shared\Order\DigitalFileUploadAfterSellRequest;
use App\Http\Requests\Shared\Order\StatusUpdateRequest;
use App\Http\Requests\Vendor\Order\DetailsUpdateRequest;
use App\Http\Requests\Vendor\Order\ThirdPartyDeliveryRequest;
use App\Services\New\CustomerService;
use App\Services\New\DeliveryManService;
use App\Services\New\DeliveryManTransactionService;
use App\Services\New\DeliverymanWalletService;
use App\Services\New\LoyaltyPointTransactionService;
use App\Services\New\OrderDetailService;
use App\Services\New\OrderDetailsRewardsService;
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

class OrderFulfilmentController extends BaseApiController
{
    private const STATUS_CHANGE_RELATIONS = ['customer', 'seller.shop', 'deliveryMan', 'latestEditHistory'];

    private const STATUS_WRITE_RELATIONS = ['customer', 'seller.shop', 'deliveryMan'];

    private const SELLER_USER_TYPE = 'seller';

    private const DELIVERY_TYPE_THIRD_PARTY = 'third_party_delivery';

    private const EXPECTED_DELIVERY_DATE_FIELD = 'expected_delivery_date';

    private const DELIVERY_MAN_TRANSACTION_TYPE_CHARGE = 'deliveryman_charge';

    private const LOYALTY_TRANSACTION_TYPE_ORDER_PLACE = 'order_place';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderDetailService $orderDetailService,
        private readonly OrderStatusHistoryService $orderStatusHistoryService,
        private readonly OrderSettlementService $orderSettlementService,
        private readonly OrderTransactionService $orderTransactionService,
        private readonly ProductRetailService $productRetailService,
        private readonly DeliverymanWalletService $deliverymanWalletService,
        private readonly DeliveryManTransactionService $deliveryManTransactionService,
        private readonly DeliveryManService $deliveryManService,
        private readonly LoyaltyPointTransactionService $loyaltyPointTransactionService,
        private readonly OrderDetailsRewardsService $orderDetailsRewardsService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly CustomerService $customerService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    #[ApiDoc(
        summary: 'Change the status of a seller order',
        description: 'Delivering settles the seller wallet, the delivery man wallet, loyalty points and the referral bonus. Returning, failing or canceling restores stock.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $orderStatus = $request['order_status'];
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId, relations: self::STATUS_CHANGE_RELATIONS);

        if ($order === null) {
            return $this->orderNotFound();
        }

        if ($this->orderService->isCustomerAccountDeleted(order: $order)) {
            return $this->responseFormatter(config: config('response.order_status_customer_deleted'));
        }

        $settlementBlocker = $this->orderService->getSettlementBlocker(order: $order, orderStatus: $orderStatus);

        if ($settlementBlocker !== null) {
            return $this->settlementBlocked(reason: $settlementBlocker);
        }

        $isDelivered = $orderStatus === OrderService::STATUS_DELIVERED;

        if ($this->orderService->isAlreadyDelivered(order: $order) || ($isDelivered && $this->orderTransactionService->isSettled(orderId: $order->id))) {
            return $this->responseFormatter(config: config('response.order_status_already_delivered'));
        }

        $isWalletEnabled = getWebConfig(name: 'wallet_status') == 1;
        $isLoyaltyPointEnabled = getWebConfig(name: 'loyalty_point_status') == 1;

        DB::transaction(function () use ($order, $orderStatus, $sellerId, $isWalletEnabled, $isLoyaltyPointEnabled, $isDelivered): void {
            $this->dispatchStatusChangeNotifications(order: $order, orderStatus: $orderStatus);

            if ($isDelivered) {
                $this->orderService->updateWhere(params: ['id' => $order->id], data: ['is_pause' => 0]);
                $this->orderDetailService->markDeliveredForOrder(orderId: $order->id);
                $this->orderDetailService->startRefundWindowForOrder(orderId: $order->id);
            }

            $this->updateStockForOrderStatus(orderId: $order->id, orderStatus: $orderStatus);

            if ($isDelivered && $order['seller_id'] != null) {
                $this->orderSettlementService->manageWalletOnOrderStatusChange(order: $order, receivedBy: self::SELLER_USER_TYPE);
            }

            $updatedOrder = $this->orderService->update(
                id: $order->id,
                data: ['order_status' => $orderStatus] + ($isDelivered ? ['payment_status' => OrderService::PAYMENT_STATUS_PAID] : []),
            );

            if ($isDelivered && $order['delivery_man_id']) {
                $this->creditDeliveryManEarning(order: $order, sellerId: $sellerId, overwritesWallet: false);
            }

            if ($isDelivered) {
                $this->creditLoyaltyPoints(order: $order, isWalletEnabled: $isWalletEnabled, isLoyaltyPointEnabled: $isLoyaltyPointEnabled);
                $this->creditReferrerOnceForDelivery(order: $order, deliveredOrder: $updatedOrder);
            }

            $this->orderStatusHistoryService->create(data: [
                'order_id' => $order->id,
                'user_id' => $sellerId,
                'user_type' => self::SELLER_USER_TYPE,
                'status' => $orderStatus,
            ]);
        });

        return $this->responseFormatter(config: config('response.order_status_updated'));
    }

    #[ApiDoc(
        summary: 'Upload the file of a digital item sold as ready after sale',
        description: 'order_detail_id names the item of this order the file belongs to.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function uploadDigitalFile(DigitalFileUploadAfterSellRequest $request, string $id): JsonResponse
    {
        $orderDetail = $this->orderDetailService->findForSeller(id: (int) $request['order_detail_id'], sellerId: $this->sellerId($request));

        if ($orderDetail === null || (int) $orderDetail->order_id !== (int) $id) {
            return $this->responseFormatter(config: config('response.order_item_not_found'));
        }

        if (! $this->orderDetailService->acceptsDigitalFileAfterSell(orderDetail: $orderDetail)) {
            return $this->responseFormatter(config: config('response.order_digital_file_not_applicable'));
        }

        $isUploaded = $this->orderDetailService->updateDigitalFileAfterSell(
            orderDetail: $orderDetail,
            file: $request->file('digital_file_after_sell'),
        );

        return $this->responseFormatter(config: config($isUploaded ? 'response.order_digital_file_uploaded' : 'response.order_digital_file_upload_failed'));
    }

    #[ApiDoc(
        summary: 'Manual third-party delivery assignment, no longer available',
        description: 'Always refused for an existing order: send the order to a delivery partner instead.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function assignThirdPartyDelivery(ThirdPartyDeliveryRequest $request, string $id): JsonResponse
    {
        if ($this->orderService->findForSeller(id: (int) $id, sellerId: $this->sellerId($request)) === null) {
            return $this->orderNotFound();
        }

        return $this->responseFormatter(config: config('response.order_third_party_delivery_unavailable'));
    }

    #[ApiDoc(
        summary: 'Update the shipping or billing address of a seller order',
        description: 'Every address field is written; an omitted field is stored as null.',
        tag: 'Orders (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function updateAddress(AddressUpdateRequest $request, string $id): JsonResponse
    {
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $this->sellerId($request));

        if ($order === null) {
            return $this->orderNotFound();
        }

        $this->orderService->updateAddressData(
            order: $order,
            addressType: $request['address_type'],
            address: $request->getAddressData(),
        );

        return $this->responseFormatter(config: config('response.order_address_updated'));
    }

    #[ApiDoc(
        summary: 'Update the delivery man, charge, expected date, status and payment status of a seller order',
        description: 'Each field is applied only when sent and different from the stored value.',
        tag: 'Orders (Seller)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateItems(DetailsUpdateRequest $request, string $id): JsonResponse
    {
        $sellerId = $this->sellerId($request);
        $orderStatus = $request['order_status'];
        $order = $this->orderService->findForSeller(id: (int) $id, sellerId: $sellerId, relations: [...self::STATUS_CHANGE_RELATIONS, 'details']);

        if ($order === null) {
            return $this->orderNotFound();
        }

        if ($order['payment_status'] == OrderService::PAYMENT_STATUS_PAID && $request['payment_status'] != OrderService::PAYMENT_STATUS_PAID) {
            return $this->responseFormatter(config: config('response.order_items_payment_status_cannot_revert'));
        }

        $settlementBlocker = $this->orderService->getSettlementBlocker(order: $order, orderStatus: $orderStatus);

        if ($settlementBlocker !== null) {
            return $this->settlementBlocked(reason: $settlementBlocker);
        }

        if ($orderStatus === OrderService::STATUS_DELIVERED && $this->orderService->hasPendingDigitalFileUpload(order: $order)) {
            return $this->responseFormatter(config: config('response.order_status_digital_file_pending'));
        }

        if ($request['delivery_type'] === self::DELIVERY_TYPE_THIRD_PARTY) {
            if (deliveryPartnerShipment($order->id) === null) {
                return $this->responseFormatter(config: config('response.order_third_party_delivery_unavailable'));
            }
        } elseif (! empty($request['delivery_man_id']) && $order['delivery_man_id'] != $request['delivery_man_id']) {
            if (! $this->deliveryManService->exists(params: ['id' => (int) $request['delivery_man_id'], 'seller_id' => $sellerId])) {
                return $this->responseFormatter(config: config('response.delivery_man_not_found'));
            }

            $this->orderService->updateWhere(params: ['id' => $order->id], data: [
                'delivery_man_id' => (int) $request['delivery_man_id'],
                'delivery_type' => OrderService::DELIVERY_TYPE_SELF,
                'delivery_service_name' => null,
                'third_party_delivery_tracking_id' => null,
            ]);
            $this->orderService->dispatchStatusNotification(
                key: OrderService::NOTIFICATION_NEW_ORDER_ASSIGNED,
                type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
                order: $order,
            );
        }

        if (! is_null($request['deliveryman_charge']) && $order['deliveryman_charge'] != $request['deliveryman_charge']) {
            $this->orderService->updateWhere(params: ['id' => $order->id], data: ['deliveryman_charge' => $request['deliveryman_charge']]);
        }

        $this->updateExpectedDeliveryDate(order: $order, sellerId: $sellerId, expectedDeliveryDate: $request['expected_delivery_date']);

        if (! empty($orderStatus)) {
            $refusal = $this->changeStatusFromItemsUpdate(orderId: $order->id, sellerId: $sellerId, orderStatus: $orderStatus);

            if ($refusal !== null) {
                return $refusal;
            }
        }

        $order = $this->orderService->findForSeller(id: $order->id, sellerId: $sellerId, relations: self::STATUS_WRITE_RELATIONS);

        if ($order['payment_status'] != OrderService::PAYMENT_STATUS_PAID && $request['payment_status'] == OrderService::PAYMENT_STATUS_PAID) {
            if ($this->orderService->isCustomerAccountDeleted(order: $order)) {
                return $this->responseFormatter(config: config('response.order_status_customer_deleted'));
            }

            $this->orderService->updateWhere(params: ['id' => $order->id], data: ['payment_status' => $request['payment_status']]);
        }

        return $this->responseFormatter(config: config('response.order_items_updated'));
    }

    private function changeStatusFromItemsUpdate(int $orderId, int $sellerId, string $orderStatus): ?JsonResponse
    {
        $order = $this->orderService->findForSeller(id: $orderId, sellerId: $sellerId, relations: self::STATUS_WRITE_RELATIONS);

        if ($this->orderService->isCustomerAccountDeleted(order: $order)) {
            return $this->responseFormatter(config: config('response.order_status_customer_deleted'));
        }

        $isWalletEnabled = getWebConfig(name: 'wallet_status') == 1;
        $isLoyaltyPointEnabled = getWebConfig(name: 'loyalty_point_status') == 1;

        $isDelivered = $orderStatus === OrderService::STATUS_DELIVERED;

        if ($this->orderService->isAlreadyDelivered(order: $order, orderStatus: $orderStatus, allowsReversal: true)
            || ($isDelivered && $this->orderTransactionService->isSettled(orderId: $order->id))) {
            return $this->responseFormatter(config: config('response.order_status_already_delivered'));
        }

        DB::transaction(function () use ($order, $orderStatus, $sellerId, $isWalletEnabled, $isLoyaltyPointEnabled, $isDelivered): void {
            $this->dispatchStatusChangeNotifications(order: $order, orderStatus: $orderStatus);

            $this->orderService->updateWhere(params: ['id' => $order->id], data: ['order_status' => $orderStatus]);

            if ($isDelivered) {
                $this->orderService->updateWhere(params: ['id' => $order->id], data: [
                    'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                    'is_pause' => 0,
                ]);
                $this->orderDetailService->markDeliveredForOrder(orderId: $order->id);
                $this->orderDetailService->startRefundWindowForOrder(orderId: $order->id);
            }

            $this->updateStockForOrderStatus(orderId: $order->id, orderStatus: $orderStatus);

            if ($isDelivered && $order['seller_id'] != null) {
                $this->orderSettlementService->manageWalletOnOrderStatusChange(order: $order, receivedBy: self::SELLER_USER_TYPE);
            }

            if ($isDelivered && $order['delivery_man_id']) {
                $this->creditDeliveryManEarning(order: $order, sellerId: $sellerId, overwritesWallet: true);
            }

            if ($isDelivered) {
                $this->creditLoyaltyPoints(order: $order, isWalletEnabled: $isWalletEnabled, isLoyaltyPointEnabled: $isLoyaltyPointEnabled);
                $this->creditReferrerOnceForDelivery(order: $order, deliveredOrder: $this->orderService->find(id: $order->id, relations: ['customer']));
            }

            $this->orderStatusHistoryService->create(data: [
                'order_id' => $order->id,
                'user_id' => $sellerId,
                'user_type' => self::SELLER_USER_TYPE,
                'status' => $orderStatus,
            ]);
        });

        return null;
    }

    private function updateExpectedDeliveryDate(Model $order, int $sellerId, ?string $expectedDeliveryDate): void
    {
        $orderInfo = $this->orderService->findForSeller(id: $order->id, sellerId: $sellerId, relations: ['deliveryMan']);

        if (! $this->orderService->hasExpectedDeliveryDateChanged(order: $orderInfo, expectedDeliveryDate: $expectedDeliveryDate)) {
            return;
        }

        $this->orderSettlementService->updateAmountDate(
            request: new Fluent([
                'order_id' => $order->id,
                'field_name' => self::EXPECTED_DELIVERY_DATE_FIELD,
                'field_val' => $expectedDeliveryDate,
            ]),
            userId: $sellerId,
            userType: self::SELLER_USER_TYPE,
        );

        $this->orderService->dispatchStatusNotification(
            key: OrderService::NOTIFICATION_EXPECTED_DELIVERY_DATE,
            type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
            order: $order,
        );
    }

    private function dispatchStatusChangeNotifications(Model $order, string $orderStatus): void
    {
        $this->orderService->dispatchStatusNotification(key: $orderStatus, type: OrderService::NOTIFICATION_TYPE_CUSTOMER, order: $order);

        if ($orderStatus === OrderService::STATUS_CANCELED) {
            $this->orderService->dispatchStatusNotification(
                key: OrderService::STATUS_CANCELED,
                type: OrderService::NOTIFICATION_TYPE_DELIVERY_MAN,
                order: $order,
            );
        }
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

    private function creditDeliveryManEarning(Model $order, int $sellerId, bool $overwritesWallet): void
    {
        $earning = $this->orderService->getDeliveryManEarning(order: $order);
        $wallet = $this->deliverymanWalletService->findBy(params: ['delivery_man_id' => $order['delivery_man_id']]);

        if ($wallet === null) {
            $this->deliverymanWalletService->create(data: [
                'delivery_man_id' => $order['delivery_man_id'],
                'current_balance' => $earning['deliveryman_charge'],
                'cash_in_hand' => $earning['cash_in_hand'],
                'pending_withdraw' => 0,
                'total_withdraw' => 0,
            ]);
        } elseif ($overwritesWallet) {
            $this->deliverymanWalletService->updateWhere(
                params: ['delivery_man_id' => $order['delivery_man_id']],
                data: ['current_balance' => $earning['deliveryman_charge'], 'cash_in_hand' => $earning['cash_in_hand']],
            );
        } else {
            $this->deliverymanWalletService->incrementBalances(
                deliveryManId: (int) $order['delivery_man_id'],
                currentBalance: (float) $earning['deliveryman_charge'],
                cashInHand: (float) $earning['cash_in_hand'],
            );
        }

        if ($order['deliveryman_charge']) {
            $this->deliveryManTransactionService->create(data: [
                'delivery_man_id' => $order['delivery_man_id'],
                'user_id' => $sellerId,
                'user_type' => self::SELLER_USER_TYPE,
                'credit' => $earning['deliveryman_charge'],
                'transaction_type' => self::DELIVERY_MAN_TRANSACTION_TYPE_CHARGE,
            ]);
        }
    }

    private function creditLoyaltyPoints(Model $order, bool $isWalletEnabled, bool $isLoyaltyPointEnabled): void
    {
        if ($order['is_guest'] || ! $isWalletEnabled || ! $isLoyaltyPointEnabled) {
            return;
        }

        $loyaltyPoint = $this->orderDetailsRewardsService->getUndeliveredLoyaltyPoint(orderId: $order->id);

        if ($loyaltyPoint <= 0) {
            return;
        }

        $this->recordLoyaltyPointEntry(
            customerId: $order['customer_id'],
            amount: (float) $loyaltyPoint,
            transactionType: self::LOYALTY_TRANSACTION_TYPE_ORDER_PLACE,
            reference: (string) $order->id,
        );
        $this->orderDetailsRewardsService->markLoyaltyPointDelivered(orderId: $order->id);
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

    private function creditReferrerOnceForDelivery(Model $order, Model $deliveredOrder): void
    {
        $referredDelivery = $order['is_guest']
            ? ['notifies_promoter' => false, 'allows_referrer_bonus' => true]
            : $this->referralCustomerService->recordReferredCustomerDelivery(customerId: $order['customer_id']);

        if ($referredDelivery['allows_referrer_bonus']) {
            $this->creditReferrerForFirstDeliveredOrder(order: $deliveredOrder);
        }

        if (! $referredDelivery['notifies_promoter']) {
            return;
        }

        $this->orderService->dispatchStatusNotification(
            key: OrderService::NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED,
            type: OrderService::NOTIFICATION_TYPE_PROMOTER,
            order: $order,
        );
    }

    private function settlementBlocked(string $reason): JsonResponse
    {
        return $this->responseFormatter(config: config(match ($reason) {
            OrderService::REASON_OFFLINE_PAYMENT_UNCONFIRMED => 'response.order_status_offline_payment_unconfirmed',
            OrderService::REASON_EDIT_DUE_PAYMENT_UNCONFIRMED => 'response.order_status_edit_due_payment_unconfirmed',
            OrderService::REASON_EDIT_RETURN_PAYMENT_UNCONFIRMED => 'response.order_status_edit_return_payment_unconfirmed',
            default => 'response.order_status_edit_due_unpaid_before_delivery',
        }));
    }

    private function sellerId(Request $request): int
    {
        return (int) $request->attributes->get('seller')->id;
    }

    private function orderNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.order_not_found'));
    }
}
