<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Coupon\AddRequest;
use App\Http\Requests\Vendor\Coupon\CheckRequest;
use App\Http\Requests\Vendor\Coupon\StatusUpdateRequest;
use App\Http\Requests\Vendor\Coupon\UpdateRequest;
use App\Http\Resources\V6\Vendor\CouponCustomerResource;
use App\Http\Resources\V6\Vendor\CouponResource;
use App\Services\New\CouponService;
use App\Services\New\CustomerService;
use App\Services\New\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class CouponController extends BaseApiController
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CustomerService $customerService,
        private readonly OrderService $orderService,
    ) {}

    #[ApiDoc(
        summary: 'Coupons available to the vendor',
        description: 'The vendor own coupons plus the admin coupons that apply to this vendor or to every vendor. is_editable marks which rows the vendor may change.',
        tag: 'Coupon (Vendor)',
        query: ['limit' => 'nullable|string', 'offset' => 'nullable|integer', 'search' => 'nullable|string'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getList(Request $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $args = [

            'search' => $request->query('search'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $coupons = $this->couponService->getList(
            searchValue: $args['search'],
            dataLimit: $limit,
            page: $offset,
            withCount: ['order'],
            filters: [CouponService::FILTER_VISIBLE_TO_VENDOR => $seller->id],
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
        summary: 'Create a coupon',
        description: 'The coupon is stored against the signed-in vendor, who bears its cost. The code must be unused and the discount cannot exceed the minimum purchase.',
        tag: 'Coupon (Vendor)',
        status: [201, 401, 403, 422],
        guard: 'seller',
    )]
    public function store(AddRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        $coupon = $this->couponService->create(
            data: $request->validated(),
            sellerId: (int) $seller->id,
            addedBy: CouponService::ADDED_BY_SELLER,
        );

        return $this->responseFormatter(
            config: config('response.coupon_added_successfully'),
            content: ['data' => new CouponResource($coupon)],
        );
    }

    #[ApiDoc(
        summary: 'Update one of the vendor own coupons',
        description: 'Only a coupon the vendor created can be changed; an admin coupon the vendor merely bears is read-only.',
        tag: 'Coupon (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        if ($this->ownCoupon(id: (int) $id, sellerId: (int) $seller->id) === null) {
            return $this->couponNotFound();
        }

        $coupon = $this->couponService->update(
            id: (int) $id,
            data: $request->validated(),
            sellerId: (int) $seller->id,
        );

        return $this->responseFormatter(
            config: config('response.coupon_updated_successfully'),
            content: ['data' => new CouponResource($coupon)],
        );
    }

    #[ApiDoc(
        summary: 'Turn one of the vendor own coupons on or off',
        tag: 'Coupon (Vendor)',
        status: [401, 403, 404, 422],
        guard: 'seller',
    )]
    public function updateStatus(StatusUpdateRequest $request, string $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        if ($this->ownCoupon(id: (int) $id, sellerId: (int) $seller->id) === null) {
            return $this->couponNotFound();
        }

        $this->couponService->updateStatus(id: (int) $id, status: (bool) $request['status']);

        return $this->responseFormatter(config: config('response.coupon_status_updated_successfully'));
    }

    #[ApiDoc(
        summary: 'Delete one of the vendor own coupons',
        tag: 'Coupon (Vendor)',
        status: [401, 403, 404],
        guard: 'seller',
    )]
    public function delete(Request $request, string $id): JsonResponse
    {
        $seller = $request->attributes->get('seller');

        if ($this->ownCoupon(id: (int) $id, sellerId: (int) $seller->id) === null) {
            return $this->couponNotFound();
        }

        $this->couponService->delete(id: (int) $id);

        return $this->responseFormatter(config: config('response.coupon_deleted_successfully'));
    }

    #[ApiDoc(
        summary: 'Check what a coupon code is worth on an order',
        description: 'Answers the discount for a running coupon the vendor bears. An unusable code answers 422 with the reason in error_code.',
        tag: 'Coupon (Vendor)',
        status: [401, 403, 422],
        guard: 'seller',
    )]
    public function check(CheckRequest $request): JsonResponse
    {
        $seller = $request->attributes->get('seller');
        $customerId = (int) ($request['user_id'] ?? CouponService::CUSTOMER_ALL);
        $orderAmount = (float) $request['order_amount'];

        $coupon = $this->couponService->findBy(params: [
            'code' => $request['code'],
            'coupon_bearer' => CouponService::BEARER_SELLER,
            'status' => 1,
            'seller_id' => [(int) $seller->id, CouponService::VENDOR_ALL],
            CouponService::FILTER_RUNNING => true,
        ]);

        if ($coupon === null || $coupon->coupon_type !== CouponService::TYPE_DISCOUNT_ON_PURCHASE) {
            return $this->couponInvalid('coupon_unusable');
        }

        if (! $this->couponService->isCustomerEligible(coupon: $coupon, customerId: $customerId)) {
            return $this->couponInvalid('coupon_customer_mismatch');
        }

        if ($customerId !== CouponService::CUSTOMER_ALL && $this->couponService->hasReachedLimit(
            coupon: $coupon,
            timesUsed: $this->timesUsed(code: $coupon->code, customerId: $customerId),
        )) {
            return $this->couponInvalid('coupon_limit_reached');
        }

        if ($orderAmount < (float) $coupon->min_purchase) {
            return $this->couponInvalid('coupon_min_purchase_not_met');
        }

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['coupon_discount_amount' => $this->couponService->calculateDiscount(coupon: $coupon, orderAmount: $orderAmount),
                'data' => new CouponResource($coupon),
            ],
        );
    }

    #[ApiDoc(
        summary: 'Customers a coupon can be targeted at',
        description: 'Names only, so a coupon form never exposes customer contact details. The first row is the all-customers option, whose id is 0.',
        tag: 'Coupon (Vendor)',
        query: ['name' => 'nullable|string', 'limit' => 'nullable|string', 'offset' => 'nullable|integer'],
        status: [401, 403],
        guard: 'seller',
    )]
    public function getCustomers(Request $request): JsonResponse
    {
        $args = [

            'search' => $request->query('search'),

            'name' => $request->query('name'),

        ];
        $limit = $request->query('limit', DEFAULT_DATA_LIMIT);
        $offset = (int) $request->query('offset', 1);

        $customers = $this->customerService->getList(
            searchValue: $args['name'] ?? $args['search'],
            dataLimit: $limit,
            page: $offset,
            filters: [CustomerService::FILTER_EXCLUDE_WALKING_CUSTOMER => true],
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['total_size' => $customers->total(),
                'limit' => $limit,
                'offset' => $offset,
                'data' => array_merge(
                    [['id' => CouponService::CUSTOMER_ALL, 'f_name' => translate('All'), 'l_name' => translate('Customer')]],
                    CouponCustomerResource::collection($customers)->resolve(),
                ),
            ],
        );
    }

    private function ownCoupon(int $id, int $sellerId): mixed
    {
        return $this->couponService->find(id: $id, filters: [
            'added_by' => CouponService::ADDED_BY_SELLER,
            'coupon_bearer' => CouponService::BEARER_SELLER,
            'seller_id' => $sellerId,
        ]);
    }

    private function timesUsed(string $code, int $customerId): int
    {
        return $this->orderService->count(filters: [
            'customer_id' => $customerId,
            'customer_type' => 'customer',
            'coupon_code' => $code,
        ]);
    }

    private function couponNotFound(): JsonResponse
    {
        return $this->responseFormatter(config: config('response.coupon_not_found'));
    }

    private function couponInvalid(string $key): JsonResponse
    {
        return $this->responseFormatter(config: config('response.'.$key));
    }
}
