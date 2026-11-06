<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\CartService;
use App\Services\New\CouponService;
use App\Services\New\OrderService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartCouponController extends Controller
{
    private const COUPON_SESSION_KEYS = [
        'coupon_code',
        'coupon_type',
        'coupon_bearer',
        'coupon_discount',
        'coupon_seller_id',
    ];

    public function __construct(
        private readonly CouponService $couponService,
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
    ) {}

    public function apply(Request $request): JsonResponse|RedirectResponse
    {
        $this->forgetCouponSession();

        $result = $this->calculateCartCouponDiscount(
            request: $request,
            customerId: auth('customer')->user()?->id,
            couponCode: is_scalar($request['code']) ? (string) $request['code'] : null,
        );

        if ($result['reason'] === null) {
            session()->put('coupon_code', $result['coupon']->code);
            session()->put('coupon_type', $result['coupon']->coupon_type);
            session()->put('coupon_discount', $result['discount']);
            session()->put('coupon_bearer', $result['coupon']->coupon_bearer);
            session()->put('coupon_seller_id', $result['coupon']->seller_id);

            $message = translate('Coupon applied successfully').'!';

            if ($request->ajax()) {
                return response()->json(['status' => 1, 'messages' => $message]);
            }

            ToastMagic::success($message);

            return back();
        }

        $message = $this->getRejectionMessage(reason: $result['reason']);

        if ($request->ajax()) {
            return response()->json(['status' => 0, 'messages' => $message]);
        }

        ToastMagic::error($message);

        return back();
    }

    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $this->forgetCouponSession();

        if ($request->ajax()) {
            return response()->json(['messages' => translate('Coupon removed')]);
        }

        ToastMagic::success(translate('Coupon removed'));

        return back();
    }

    private function forgetCouponSession(): void
    {
        foreach (self::COUPON_SESSION_KEYS as $sessionKey) {
            session()->forget($sessionKey);
        }
    }

    private function calculateCartCouponDiscount(Request $request, ?int $customerId, ?string $couponCode): array
    {
        $customerCouponOrderGroupCount = $customerId === null ? 0 : $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]);
        $customerOrderCount = $customerId === null ? 0 : $this->orderService->count(filters: ['customer_id' => $customerId]);

        $cartIndependentResult = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: [],
        );

        if ($cartIndependentResult['reason'] !== CouponService::REASON_CART_EMPTY) {
            return $cartIndependentResult;
        }

        $this->cartService->refreshPrices(customerId: $customerId, guestId: session('guest_id') ?? $request['guest_id'] ?? 0);

        $cartItems = $this->cartService->getCartItemList(
            customerId: $customerId,
            guestId: session('guest_id'),
            type: CartService::TYPE_CHECKED,
        );

        $isFreeDeliveryCoupon = $cartIndependentResult['coupon']->coupon_type === CouponService::TYPE_FREE_DELIVERY;

        return $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: $cartItems,
            groupShippingCosts: $isFreeDeliveryCoupon && $cartItems->isNotEmpty()
                ? $this->cartService->getGroupShippingCosts(
                    customerId: $customerId,
                    requestGuestId: $request['guest_id'],
                    cartGroupIds: $cartItems->pluck('cart_group_id')->unique()->map(fn ($cartGroupId) => (string) $cartGroupId)->values()->all(),
                )
                : [],
        );
    }

    private function getRejectionMessage(string $reason): string
    {
        return match ($reason) {
            CouponService::REASON_CUSTOMER_REQUIRED => translate('Coupon not applicable'),
            CouponService::REASON_USAGE_LIMIT_REACHED, CouponService::REASON_NOT_FIRST_ORDER => translate('Sorry this coupon is not valid for this user').'!',
            CouponService::REASON_CART_EMPTY => translate('Please add item to cart'),
            CouponService::REASON_CUSTOMER_NOT_ELIGIBLE, CouponService::REASON_NO_DISCOUNT => translate('Coupon not valid'),
            CouponService::REASON_VENDOR_NOT_APPLICABLE => translate('Coupon not applicable to selected vendors'),
            CouponService::REASON_MINIMUM_PURCHASE_NOT_REACHED => translate('Minimum purchase amount not reached for this coupon.'),
            CouponService::REASON_SHIPPING_COST_NOT_APPLICABLE => translate('Not applicable for current shipping cost'),
            default => translate('Invalid coupon'),
        };
    }
}
