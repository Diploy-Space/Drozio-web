<?php

namespace App\Http\Controllers\RestAPI\v6\Customer;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Customer\Coupon\ApplyRequest;
use App\Http\Resources\V6\Customer\CouponResource;
use App\Services\New\CartService;
use App\Services\New\CouponService;
use App\Services\New\OrderService;
use App\Services\New\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CouponController extends BaseApiController
{
    private const DISCOUNT_REASON_RESPONSE_KEYS = [
        CouponService::REASON_COUPON_NOT_FOUND => 'coupon_code_invalid',
        CouponService::REASON_USAGE_LIMIT_REACHED => 'coupon_usage_limit_reached',
        CouponService::REASON_NOT_FIRST_ORDER => 'coupon_not_first_order',
        CouponService::REASON_CART_EMPTY => 'coupon_cart_empty',
        CouponService::REASON_CUSTOMER_NOT_ELIGIBLE => 'coupon_customer_not_eligible',
        CouponService::REASON_VENDOR_NOT_APPLICABLE => 'coupon_vendor_not_applicable',
        CouponService::REASON_MINIMUM_PURCHASE_NOT_REACHED => 'coupon_minimum_purchase_not_reached',
        CouponService::REASON_SHIPPING_COST_NOT_APPLICABLE => 'coupon_shipping_cost_not_applicable',
        CouponService::REASON_NO_DISCOUNT => 'coupon_no_discount',
    ];

    public function __construct(
        private readonly CouponService $couponService,
        private readonly ShopService $shopService,
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'Coupon list',
        tag: 'Coupon',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string', 'sort_by' => 'nullable|string'],
        auth: false,
        guard: 'api',
    )]
    public function getList(Request $request): JsonResponse
    {
        $args = [
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $customerId = auth('api')->id();

        $coupons = $this->couponService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['seller.shop'],
            withCount: $this->redemptionCount($customerId),
            filters: $this->couponFilters($customerId),
            sortBy: $args['sort_by'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CouponResource::collection($coupons),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($coupons),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Coupons of one shop',
        tag: 'Coupon',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string', 'sort_by' => 'nullable|string'],
        status: [404],
        auth: false,
        guard: 'api',
    )]
    public function getShopCoupons(Request $request, string $slug): JsonResponse
    {
        $shop = $this->shopService->findBy(params: ['slug' => $slug]);

        if ($shop === null) {
            return $this->responseFormatter(config: config('response.shop_not_found'));
        }

        $args = [
            'search' => $request->query('search'),
            'sort_by' => $request->query('sort_by'),
        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);
        $customerId = auth('api')->id();

        $coupons = $this->couponService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            relations: ['seller.shop'],
            withCount: $this->redemptionCount($customerId),
            filters: $this->couponFilters($customerId) + [
                'seller_id' => $shop->author_type === ShopService::AUTHOR_TYPE_ADMIN
                    ? null
                    : [CouponService::VENDOR_ALL, $shop->seller_id],
            ],
            sortBy: $args['sort_by'],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: [
                'data' => CouponResource::collection($coupons),
                'params' => $this->paramsFormatter($args),
                'pagination' => $this->paginateFormatter($coupons),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Coupons the authenticated customer can use on the selected cart items',
        tag: 'Coupon',
        status: [200, 401],
    )]
    public function getApplicable(Request $request): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;

        $this->cartService->refreshPrices(customerId: $customerId, guestId: 0);
        $cartItems = $this->cartService->getCartItemList(customerId: $customerId, type: CartService::TYPE_CHECKED);

        $coupons = $this->couponService->getApplicableCartCoupons(
            customerId: $customerId,
            cartItems: $cartItems,
            customerOrderCount: $this->orderService->count(filters: ['customer_id' => $customerId]),
            customerCouponOrderGroupCounts: $this->orderService->getCouponOrderGroupCounts(customerId: $customerId),
            groupShippingCosts: $cartItems->isEmpty() ? [] : $this->cartService->getGroupShippingCosts(
                customerId: $customerId,
                requestGuestId: null,
                cartGroupIds: $cartItems->pluck('cart_group_id')->unique()->map(static fn (mixed $id): string => (string) $id)->values()->all(),
            ),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => CouponResource::collection($coupons)],
        );
    }

    #[ApiDoc(
        summary: 'Discount a coupon code gives the selected cart items of the authenticated customer',
        tag: 'Coupon',
        query: ['code' => 'required|string'],
        status: [200, 401, 404, 422],
    )]
    public function getDiscount(ApplyRequest $request): JsonResponse
    {
        $customerId = (int) $request->user('api')->id;
        $couponCode = $request['code'];
        $customerCouponOrderGroupCount = $this->orderService->countOrderGroups(filters: ['customer_id' => $customerId, 'coupon_code' => $couponCode]);
        $customerOrderCount = $this->orderService->count(filters: ['customer_id' => $customerId]);

        $result = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: [],
        );

        if ($result['reason'] === CouponService::REASON_CART_EMPTY) {
            $result = $this->calculateSelectedCartDiscount(
                customerId: $customerId,
                couponCode: $couponCode,
                customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
                customerOrderCount: $customerOrderCount,
                isFreeDeliveryCoupon: $result['coupon']->coupon_type === CouponService::TYPE_FREE_DELIVERY,
            );
        }

        if ($result['reason'] !== null) {
            return $this->responseFormatter(
                config: config('response.'.(self::DISCOUNT_REASON_RESPONSE_KEYS[$result['reason']] ?? 'coupon_code_invalid')),
            );
        }

        return $this->responseFormatter(
            config: config('response.coupon_discount_calculated'),
            content: [
                'data' => [
                    'coupon_discount' => $result['discount'],
                    'coupon_type' => $result['coupon']->coupon_type,
                ],
            ],
        );
    }

    private function calculateSelectedCartDiscount(
        int $customerId,
        string $couponCode,
        int $customerCouponOrderGroupCount,
        int $customerOrderCount,
        bool $isFreeDeliveryCoupon,
    ): array {
        $this->cartService->refreshPrices(customerId: $customerId, guestId: 0);

        $cartItems = $this->cartService->getCartItemList(customerId: $customerId, type: CartService::TYPE_CHECKED);

        $result = $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: $cartItems,
        );

        if (! $isFreeDeliveryCoupon || $result['reason'] !== CouponService::REASON_SHIPPING_COST_NOT_APPLICABLE) {
            return $result;
        }

        return $this->couponService->calculateCartCouponDiscount(
            couponCode: $couponCode,
            customerId: $customerId,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
            cartItems: $cartItems,
            groupShippingCosts: $this->cartService->getGroupShippingCosts(
                customerId: $customerId,
                requestGuestId: null,
                cartGroupIds: $this->couponService->getApplicableCartGroupIds(coupon: $result['coupon'], cartItems: $cartItems),
            ),
        );
    }

    private function couponFilters(?int $customerId): array
    {
        return [
            'status' => 1,
            CouponService::FILTER_RUNNING => true,
            'customer_id' => $customerId === null
                ? CouponService::CUSTOMER_ALL
                : [$customerId, CouponService::CUSTOMER_ALL],
            CouponService::FILTER_FIRST_ORDER_EXCLUDED => $customerId !== null
                && $this->orderService->count(filters: ['customer_id' => $customerId]) > 0,
        ];
    }

    private function redemptionCount(?int $customerId): array
    {
        return $customerId === null ? [] : ['order' => fn ($query) => $query->where('customer_id', $customerId)];
    }
}
