<?php

namespace App\Listeners;

use App\Events\CheckoutCartStateRequestedEvent;
use App\Services\New\CartService;
use App\Services\New\CouponService;
use App\Services\New\OrderService;
use App\Services\New\ReferralCustomerService;

class CheckoutCartStateListener
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly OrderService $orderService,
        private readonly ReferralCustomerService $referralCustomerService,
    ) {}

    public function handle(CheckoutCartStateRequestedEvent $event): void
    {
        $customerId = $event->customer?->id;
        $checkoutSession = $event->checkoutSession;

        $cartItems = $this->cartService->getRefreshedCartItemList(
            customerId: $customerId,
            sessionGuestId: $checkoutSession['guest_id'],
            requestGuestId: $event->requestGuestId,
            type: CartService::TYPE_CHECKED,
        );
        $cartItemsByGroup = $cartItems->groupBy('cart_group_id');

        $groupShippingCosts = $this->cartService->getGroupShippingCosts(
            customerId: $customerId,
            requestGuestId: $event->requestGuestId,
            cartGroupIds: $cartItemsByGroup->keys()->map(fn ($cartGroupId) => (string) $cartGroupId)->all(),
        );
        $couponOrderGroupCount = $customerId === null ? 0 : $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $event->couponCode]);

        $cartCoupon = $this->couponService->calculateCartCouponDiscount(
            couponCode: $event->couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $couponOrderGroupCount,
            customerOrderCount: $customerId === null ? 0 : $this->orderService->count(filters: ['customer_id' => $customerId]),
            cartItems: $cartItems,
            groupShippingCosts: $groupShippingCosts,
        );

        $couponSession = $cartCoupon['reason'] === null
            ? [
                'coupon_code' => $cartCoupon['coupon']->code,
                'coupon_type' => $cartCoupon['coupon']->coupon_type,
                'coupon_discount' => $cartCoupon['discount'],
                'coupon_bearer' => $cartCoupon['coupon']->coupon_bearer,
                'coupon_seller_id' => $cartCoupon['coupon']->seller_id,
            ]
            : null;
        $checkoutSession = [...$checkoutSession, ...($couponSession ?? [])];

        $groupCouponDiscounts = [];
        foreach ($cartItemsByGroup as $cartGroupId => $groupCartItems) {
            $groupCouponDiscounts[$cartGroupId] = $this->couponService->calculateGroupCouponDiscount(
                couponCode: $event->couponCode,
                customerId: $customerId,
                customerCouponOrderGroupCount: $couponOrderGroupCount,
                applicableAmount: $cartCoupon['total_cart_amount'],
                discountAmount: $cartCoupon['discount'],
                groupCartItems: $groupCartItems,
                groupShippingCost: (float) ($groupShippingCosts[$cartGroupId] ?? 0),
            );
        }

        $referralCustomer = $this->referralCustomerService->findRedeemableForCustomer(
            customerId: $customerId,
            completedOrderCount: $customerId === null ? 0 : $this->orderService->count(filters: [
                'customer_id' => $customerId,
                'order_status' => OrderService::STATUS_DELIVERED,
                'payment_status' => OrderService::PAYMENT_STATUS_PAID,
                'order_type' => OrderService::ORDER_TYPE_DEFAULT,
            ]),
        );
        $referralDiscountBases = $this->cartService->getReferralDiscountBases(
            cartItems: $cartItems,
            totalCouponDiscount: $cartCoupon['discount'],
            groupCouponDiscounts: $groupCouponDiscounts,
        );

        $groupReferralDiscounts = [];
        foreach ($referralDiscountBases['group_amounts'] as $cartGroupId => $groupAmount) {
            $groupReferralDiscounts[$cartGroupId] = $this->referralCustomerService->calculateReferralDiscount(
                referralCustomer: $referralCustomer,
                totalAmount: $referralDiscountBases['total_amount'],
                groupAmount: $groupAmount,
            );
        }

        $event->cartState = [
            'vendor_carts' => $this->cartService->getOrderGenerateCartList(
                customerId: $customerId,
                sessionGuestId: $checkoutSession['guest_id'],
                requestGuestId: $event->requestGuestId,
                groupCouponDiscounts: $groupCouponDiscounts,
                groupReferralDiscounts: $groupReferralDiscounts,
                shippingAddressId: $this->resolveOrderAddressId(addressId: $event->shippingAddressId, sessionAddressId: $checkoutSession['address_id']),
                billingAddressId: $this->resolveOrderAddressId(addressId: $event->billingAddressId, sessionAddressId: $checkoutSession['billing_address_id']),
            ),
            'coupon_session' => $couponSession,
        ];
    }

    private function resolveOrderAddressId(mixed $addressId, mixed $sessionAddressId): mixed
    {
        if (! is_null($addressId) && is_null($sessionAddressId)) {
            return $addressId;
        }

        return $sessionAddressId ? $sessionAddressId : 0;
    }
}
