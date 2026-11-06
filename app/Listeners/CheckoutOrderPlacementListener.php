<?php

namespace App\Listeners;

use App\Events\CheckoutCartStateRequestedEvent;
use App\Events\CheckoutOrdersPlacingEvent;
use App\Services\New\AdminService;
use App\Services\New\BusinessSettingService;
use App\Services\New\CartService;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\OrderDetailsRewardsService;
use App\Services\New\OrderPlacementService;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;
use App\Services\New\ShippingAddressService;
use App\Services\New\VendorService;
use App\Services\New\WalletTransactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutOrderPlacementListener
{
    private const REFERRAL_EARNING_REFERENCE = 'earned_by_referral';

    private const AMOUNT_TOLERANCE = 0.01;

    private const REASON_ALREADY_PLACED = 'already_placed';

    private const REASON_CART_CHANGED = 'cart_changed';

    private const REASON_AMOUNT_MISMATCH = 'amount_mismatch';

    public function __construct(
        private readonly OrderPlacementService $orderPlacementService,
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly ShippingAddressService $shippingAddressService,
        private readonly VendorService $vendorService,
        private readonly AdminService $adminService,
        private readonly CustomerService $customerService,
        private readonly ReferralCustomerService $referralCustomerService,
        private readonly BusinessSettingService $businessSettingService,
        private readonly WalletTransactionService $walletTransactionService,
    ) {}

    public function handle(CheckoutOrdersPlacingEvent $event): void
    {
        $event->placement = $this->withPlacementLock(
            ownerId: $event->orderCustomer['id'],
            customerId: $event->customer?->id,
            requestGuestId: $event->requestGuestId,
            sessionGuestId: $event->checkoutSession['guest_id'],
            placement: function () use ($event): array {
                if ($this->orderPlacementService->isTransactionPlaced(transactionRef: (string) ($event->orderData['transaction_ref'] ?? ''))) {
                    $this->reportUnplacedPayment(event: $event, reason: self::REASON_ALREADY_PLACED);

                    return $this->emptyPlacement();
                }

                return $this->placeResolvedOrders(event: $event);
            },
        );
    }

    /** @return array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null} */
    private function placeResolvedOrders(CheckoutOrdersPlacingEvent $event): array
    {
        $customer = $event->customer;
        $requestGuestId = $event->requestGuestId;
        $orderData = $event->orderData;
        $checkoutSession = $event->checkoutSession;

        $cartState = $this->getCartState(
            customer: $customer,
            requestGuestId: $requestGuestId,
            checkoutSession: $checkoutSession,
            couponCode: $orderData['coupon_code'] ?? ($checkoutSession['coupon_code'] ?? ''),
            shippingAddressId: $orderData['address_id'] ?? $checkoutSession['address_id'],
            billingAddressId: $orderData['billing_address_id'] ?? $checkoutSession['billing_address_id'],
        );

        $couponSession = $cartState['coupon_session'];
        $checkoutSession = [...$checkoutSession, ...($couponSession ?? [])];
        $vendorWiseCartList = $this->withAdminCommissions(
            vendorWiseCartList: $cartState['vendor_carts'],
            couponCode: $checkoutSession['coupon_code'],
        );

        $quoteReason = $this->getQuoteMismatchReason(event: $event, vendorWiseCartList: $vendorWiseCartList);

        if ($quoteReason !== null) {
            $this->reportUnplacedPayment(event: $event, reason: $quoteReason);

            return $this->emptyPlacement();
        }

        $placement = DB::transaction(fn (): array => $this->persistPlacedOrders(
            vendorWiseCartList: $vendorWiseCartList,
            customer: $customer,
            orderCustomer: $event->orderCustomer,
            orderData: $orderData + ['order_note' => $checkoutSession['order_note']],
            checkoutSession: $checkoutSession,
            walletDebitAmount: $event->walletDebitAmount,
        ));

        return [
            'order_ids' => $placement['order_ids'],
            'forget_session_keys' => $placement['forget_session_keys'],
            'coupon_session' => $couponSession,
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $vendorWiseCartList
     * @param  array{id: int|string|null, is_guest: int}  $orderCustomer
     * @param  array<string, mixed>  $orderData
     * @param  array<string, mixed>  $checkoutSession
     * @return array{order_ids: array<int, int>, order_group_id: string, forget_session_keys: array<int, string>}
     */
    private function persistPlacedOrders(
        array $vendorWiseCartList,
        ?Model $customer,
        array $orderCustomer,
        array $orderData,
        array $checkoutSession,
        ?float $walletDebitAmount,
    ): array {
        $firstVendorCart = $vendorWiseCartList[array_key_first($vendorWiseCartList)] ?? null;

        $placement = $this->orderPlacementService->placeOrders(
            vendorWiseCartList: $vendorWiseCartList,
            orderCustomer: $orderCustomer,
            customer: $customer,
            orderData: $orderData,
            loyaltyPointSettings: $this->businessSettingService->getValuesByTypes(types: OrderDetailsRewardsService::LOYALTY_POINT_SETTING_KEYS),
            coupon: $this->findCheckoutCoupon(couponCode: $checkoutSession['coupon_code']),
            shippingAddress: $firstVendorCart === null ? null : $this->shippingAddressService->find(id: $firstVendorCart['shipping_address_id']),
            billingAddress: $firstVendorCart === null ? null : $this->shippingAddressService->find(id: $firstVendorCart['billing_address_id']),
            inHouseAdmin: $this->adminService->find(id: CartService::IN_HOUSE_CART_SELLER_ID),
        );

        if ($customer !== null) {
            $this->markReferralUsed(customer: $customer, orderIds: $placement['order_ids']);
        }

        if ($walletDebitAmount !== null) {
            $this->recordCustomerWalletTransaction(
                customerId: $customer->id,
                amount: $walletDebitAmount,
                transactionType: WalletTransactionService::TRANSACTION_TYPE_ORDER_PLACE,
                reference: OrderService::WALLET_ORDER_PAYMENT_REFERENCE,
            );
        }

        if ($orderData['payment_status'] == OrderService::PAYMENT_STATUS_PAID) {
            foreach ($placement['order_ids'] as $orderId) {
                $this->creditReferrerBonusForFirstOrder(orderId: $orderId);
            }
        }

        return $placement;
    }

    /**
     * @param  array<string, mixed>  $checkoutSession
     * @return array{vendor_carts: array<int, array<string, mixed>>, coupon_session: array<string, mixed>|null}
     */
    private function getCartState(?Model $customer, int|string|null $requestGuestId, array $checkoutSession, ?string $couponCode, mixed $shippingAddressId, mixed $billingAddressId): array
    {
        $state = new CheckoutCartStateRequestedEvent(
            customer: $customer,
            requestGuestId: $requestGuestId,
            checkoutSession: $checkoutSession,
            couponCode: $couponCode,
            shippingAddressId: $shippingAddressId,
            billingAddressId: $billingAddressId,
        );

        event($state);

        return $state->cartState;
    }

    /**
     * @param  array<int, array<string, mixed>>  $vendorWiseCartList
     * @return array<int, array<string, mixed>>
     */
    private function withAdminCommissions(array $vendorWiseCartList, ?string $couponCode): array
    {
        $coupon = $this->findCheckoutCoupon(couponCode: $couponCode);

        foreach ($vendorWiseCartList as $index => $vendorCart) {
            $vendorWiseCartList[$index]['admin_commission'] = $this->orderPlacementService->calculateAdminCommission(
                groupCartItems: $vendorCart['cart_list'],
                coupon: $coupon,
                couponDiscount: $vendorCart['coupon_discount'],
                seller: $vendorCart['seller_is'] == OrderService::SELLER_IS_SELLER ? $this->vendorService->find(id: $vendorCart['seller_id']) : null,
            );
        }

        return $vendorWiseCartList;
    }

    private function findCheckoutCoupon(mixed $couponCode): ?Model
    {
        return blank($couponCode)
            ? null
            : $this->couponService->findBy(params: ['code' => $couponCode, 'status' => CouponService::STATUS_ACTIVE]);
    }

    /** @param  array<int, int>  $orderIds */
    private function markReferralUsed(Model $customer, array $orderIds): void
    {
        if ($orderIds !== []) {
            $referralCustomer = $this->referralCustomerService->findBy(params: ['user_id' => $customer['id']]);

            if ($this->referralCustomerService->shouldNotifyReferrerOfOrder(referralCustomer: $referralCustomer)) {
                $order = $this->orderPlacementService->find(id: $orderIds[0]);
                $notification = $this->orderPlacementService->getReferredCustomerOrderedNotification(order: $order);

                DB::afterCommit(fn () => $this->orderPlacementService->dispatchPlacedOrderEvents(notifications: [$notification], mails: []));
            }
        }

        $this->referralCustomerService->updateWhere(params: ['user_id' => $customer['id']], data: ['is_used' => 1]);
    }

    private function creditReferrerBonusForFirstOrder(int $orderId): void
    {
        $order = $this->orderPlacementService->find(id: $orderId, relations: ['customer', 'seller.shop', 'deliveryMan']);

        if (! $this->referralCustomerService->isOrderEligibleForReferrerBonus(order: $order, referralEarningStatus: getWebConfig(name: BusinessSettingService::REFERRAL_EARNING_STATUS) ?? 0)) {
            return;
        }

        $referredDelivery = $this->referralCustomerService->recordReferredCustomerDelivery(customerId: $order['customer_id']);

        if ($referredDelivery['notifies_promoter']) {
            $this->orderPlacementService->dispatchStatusNotification(
                key: OrderService::NOTIFICATION_REFERRED_CUSTOMER_ORDER_DELIVERED,
                type: OrderService::NOTIFICATION_TYPE_PROMOTER,
                order: $order,
            );
        }

        if (! $referredDelivery['allows_referrer_bonus']) {
            return;
        }

        $customer = $this->customerService->find(id: $order['customer_id']);
        $referrer = $this->customerService->find(id: $customer['referred_by']);
        $completedOrderCount = $this->orderPlacementService->count(filters: [
            'customer_id' => $order['customer_id'],
            'order_status' => OrderService::STATUS_DELIVERED,
            'payment_status' => OrderService::PAYMENT_STATUS_PAID,
        ]);

        if (! $this->referralCustomerService->isReferrerBonusDue(customer: $customer, referrer: $referrer, customerCompletedOrderCount: $completedOrderCount)) {
            return;
        }

        if ($this->businessSettingService->getValueByType(type: BusinessSettingService::WALLET_STATUS) != 1) {
            return;
        }

        $this->recordCustomerWalletTransaction(
            customerId: $referrer['id'],
            amount: usdToDefaultCurrency(amount: getWebConfig(name: BusinessSettingService::REFERRAL_EARNING_EXCHANGE_RATE) ?? 0),
            transactionType: WalletTransactionService::TRANSACTION_TYPE_ADD_FUND_BY_ADMIN,
            reference: self::REFERRAL_EARNING_REFERENCE,
        );
    }

    private function recordCustomerWalletTransaction(int $customerId, float $amount, string $transactionType, string $reference): void
    {
        $customer = $this->customerService->findForWalletUpdate(id: $customerId);

        if ($customer === null) {
            return;
        }

        $walletTransaction = $this->walletTransactionService->createWalletEntry(
            customerId: $customer->id,
            currentBalance: (float) $customer->wallet_balance,
            amount: $amount,
            transactionType: $transactionType,
            reference: $reference,
        );

        if ($walletTransaction !== null) {
            $this->customerService->updateWalletBalance(
                id: $customer->id,
                walletBalance: $walletTransaction->balance + $walletTransaction->admin_bonus,
            );
        }
    }

    /**
     * @param  callable(): array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null}  $placement
     * @return array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null}
     */
    private function withPlacementLock(int|string|null $ownerId, ?int $customerId, int|string|null $requestGuestId, int|string|null $sessionGuestId, callable $placement): array
    {
        $cartGroupIds = $this->cartService->getCartGroupIds(
            customerId: $customerId,
            guestId: $customerId === null ? ($sessionGuestId ?? $requestGuestId ?? 0) : 0,
            type: CartService::TYPE_CHECKED,
        );

        return Cache::lock(
            name: $this->orderPlacementService->getPlacementLockKey(ownerId: $ownerId ?? 0, cartGroupIds: $cartGroupIds),
            seconds: OrderPlacementService::LOCK_SECONDS,
        )->block(OrderPlacementService::LOCK_SECONDS, $placement);
    }

    /** @param  array<int, array<string, mixed>>  $vendorWiseCartList */
    private function getQuoteMismatchReason(CheckoutOrdersPlacingEvent $event, array $vendorWiseCartList): ?string
    {
        if ($event->quotedCartGroupIds !== null && ! $this->isQuotedCartIntact(quotedCartGroupIds: $event->quotedCartGroupIds, vendorWiseCartList: $vendorWiseCartList)) {
            return self::REASON_CART_CHANGED;
        }

        if ($event->quotedAmount !== null && abs($event->quotedAmount - $this->cartService->getPayableAmount(vendorWiseCartList: $vendorWiseCartList)) > self::AMOUNT_TOLERANCE) {
            return self::REASON_AMOUNT_MISMATCH;
        }

        return null;
    }

    /**
     * @param  array<int, int|string>  $quotedCartGroupIds
     * @param  array<int, array<string, mixed>>  $vendorWiseCartList
     */
    private function isQuotedCartIntact(array $quotedCartGroupIds, array $vendorWiseCartList): bool
    {
        $quotedCartGroupIds = array_unique(array_map('strval', $quotedCartGroupIds));
        $placedCartGroupIds = array_map(static fn (array $vendorCart): string => (string) $vendorCart['cart_group_id'], $vendorWiseCartList);

        return count($placedCartGroupIds) === count($quotedCartGroupIds)
            && array_diff($quotedCartGroupIds, $placedCartGroupIds) === [];
    }

    private function reportUnplacedPayment(CheckoutOrdersPlacingEvent $event, string $reason): void
    {
        $transactionRef = (string) ($event->orderData['transaction_ref'] ?? '');

        if ($transactionRef === '') {
            return;
        }

        Log::critical('A retail digital payment was received but no order was placed', [
            'reason' => $reason,
            'transaction_ref' => $transactionRef,
            'payment_method' => $event->orderData['payment_method'] ?? null,
            'customer_id' => $event->orderCustomer['id'] ?? null,
            'quoted_amount' => $event->quotedAmount,
        ]);
    }

    /** @return array{order_ids: array<int, int>, forget_session_keys: array<int, string>, coupon_session: array<string, mixed>|null} */
    private function emptyPlacement(): array
    {
        return ['order_ids' => [], 'forget_session_keys' => [], 'coupon_session' => null];
    }
}
