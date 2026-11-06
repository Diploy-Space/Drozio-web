<?php

namespace App\Services\New;

use App\Enums\BusinessModule;
use App\Models\Coupon;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class CouponService extends BaseService
{
    public const STATUS_ACTIVE = 1;

    public const TYPE_DISCOUNT_ON_PURCHASE = 'discount_on_purchase';

    public const TYPE_FREE_DELIVERY = 'free_delivery';

    public const TYPE_FIRST_ORDER = 'first_order';

    public const BEARER_INHOUSE = 'inhouse';

    public const BEARER_SELLER = 'seller';

    public const DISCOUNT_TYPE_AMOUNT = 'amount';

    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';

    public const ADDED_BY_ADMIN = 'admin';

    public const ADDED_BY_SELLER = 'seller';

    public const CUSTOMER_ALL = 0;

    public const VENDOR_ALL = 0;

    public const VENDOR_INHOUSE = 'inhouse';

    public const FILTER_RUNNING = 'running';

    public const FILTER_USAGE_BELOW = 'usage_below';

    public const FILTER_FIRST_ORDER_EXCLUDED = 'first_order_excluded';

    public const FILTER_VISIBLE_TO_VENDOR = 'visible_to_vendor';

    public const SORT_OLDEST = 'oldest';

    public const SORT_EXPIRING_SOON = 'expiring-soon';

    public const SORT_RANDOM = 'random';

    public const SELLER_IS_ADMIN = 'admin';

    public const SELLER_IS_SELLER = 'seller';

    public const REASON_CUSTOMER_REQUIRED = 'customer_required';

    public const REASON_COUPON_NOT_FOUND = 'coupon_not_found';

    public const REASON_USAGE_LIMIT_REACHED = 'usage_limit_reached';

    public const MIN_USAGE_LIMIT = 1;

    public const MAX_USAGE_LIMIT = 2147483647;

    public const REASON_NOT_FIRST_ORDER = 'not_first_order';

    public const REASON_CART_EMPTY = 'cart_empty';

    public const REASON_CUSTOMER_NOT_ELIGIBLE = 'customer_not_eligible';

    public const REASON_VENDOR_NOT_APPLICABLE = 'vendor_not_applicable';

    public const REASON_MINIMUM_PURCHASE_NOT_REACHED = 'minimum_purchase_not_reached';

    public const REASON_SHIPPING_COST_NOT_APPLICABLE = 'shipping_cost_not_applicable';

    public const REASON_NO_DISCOUNT = 'no_discount';

    public function __construct(Coupon $coupon)
    {
        parent::__construct($coupon);
    }

    protected function businessModule(): string
    {
        return BusinessModule::RETAIL;
    }

    protected function query(): Builder
    {
        return parent::query()->businessModule($this->businessModule());
    }

    /** @return array<string, string> */
    public function getCouponTypes(string $addedBy = self::ADDED_BY_ADMIN): array
    {
        $types = [
            self::TYPE_DISCOUNT_ON_PURCHASE => translate('Discount on Purchase'),
            self::TYPE_FREE_DELIVERY => translate('Free Delivery'),
            self::TYPE_FIRST_ORDER => translate('First Order'),
        ];

        if ($addedBy === self::ADDED_BY_SELLER) {
            unset($types[self::TYPE_FIRST_ORDER]);
        }

        return $types;
    }

    /** @return array<string, string> */
    public function getCouponBearers(): array
    {
        return [
            self::BEARER_SELLER => translate('Vendor'),
            self::BEARER_INHOUSE => translate('Admin'),
        ];
    }

    /** @return array<string, string> */
    public function getDiscountTypes(): array
    {
        return [
            self::DISCOUNT_TYPE_AMOUNT => translate('Amount'),
            self::DISCOUNT_TYPE_PERCENTAGE => translate('Percentage'),
        ];
    }

    /** @return array<string, array{targeting: bool, discount: bool}> */
    public function getFieldVisibility(): array
    {
        return [
            self::TYPE_DISCOUNT_ON_PURCHASE => ['targeting' => true, 'discount' => true],
            self::TYPE_FREE_DELIVERY => ['targeting' => true, 'discount' => false],
            self::TYPE_FIRST_ORDER => ['targeting' => false, 'discount' => true],
        ];
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(function (Builder $inner) use ($searchValue): void {
                $inner->where('title', 'like', "%{$searchValue}%")
                    ->orWhere('code', 'like', "%{$searchValue}%")
                    ->orWhere('discount_type', 'like', "%{$searchValue}%");
            }));

        match ($sortBy) {
            self::SORT_OLDEST => $query->orderBy('id'),
            self::SORT_EXPIRING_SOON => $query->orderBy('expire_date'),
            self::SORT_RANDOM => $query->inRandomOrder(),
            default => $query->orderByDesc('id'),
        };

        $filters = $this->applyKeyedFilters($query, $filters);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = [], array $filters = []): ?Model
    {
        $query = $this->query()->with($relations);

        $filters = $this->applyKeyedFilters($query, $filters);

        $this->applyFilters($query, $filters);

        return $query->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $params = $this->applyKeyedFilters($query, $params);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    /** @return array<string, mixed> */
    private function applyKeyedFilters(Builder $query, array $filters): array
    {
        if (! empty($filters[self::FILTER_RUNNING])) {
            $query->whereDate('start_date', '<=', now())->whereDate('expire_date', '>=', now());
        }

        if (isset($filters[self::FILTER_USAGE_BELOW])) {
            $query->where('limit', '>', $filters[self::FILTER_USAGE_BELOW]);
        }

        if (! empty($filters[self::FILTER_FIRST_ORDER_EXCLUDED])) {
            $query->where('coupon_type', '!=', self::TYPE_FIRST_ORDER);
        }

        if (isset($filters[self::FILTER_VISIBLE_TO_VENDOR])) {
            $this->scopeVisibleToVendor(query: $query, vendorId: (int) $filters[self::FILTER_VISIBLE_TO_VENDOR]);
        }

        unset(
            $filters[self::FILTER_RUNNING],
            $filters[self::FILTER_USAGE_BELOW],
            $filters[self::FILTER_FIRST_ORDER_EXCLUDED],
            $filters[self::FILTER_VISIBLE_TO_VENDOR],
        );

        return $filters;
    }

    private function scopeVisibleToVendor(Builder $query, int $vendorId): void
    {
        $query->where(fn (Builder $visible) => $visible
            ->where(fn (Builder $own) => $own
                ->where('added_by', self::ADDED_BY_SELLER)
                ->where('seller_id', $vendorId))
            ->orWhere(fn (Builder $adminAdded) => $adminAdded
                ->where('added_by', self::ADDED_BY_ADMIN)
                ->whereIn('seller_id', [self::VENDOR_ALL, $vendorId])));
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $record = $this->findBy(params: $params, relations: []);

        return $record === null ? null : $this->update(id: $record->id, data: $data);
    }

    public function create(array $data, ?int $sellerId = null, string $addedBy = self::ADDED_BY_ADMIN): ?Model
    {
        return $this->query()->create(
            $this->normalizeByCouponType(data: $data, sellerId: $sellerId) + [
                'business_module' => $this->businessModule(),
                'added_by' => $addedBy,
                'status' => 1,
            ]
        );
    }

    public function update(mixed $id, array $data, ?int $sellerId = null): ?Model
    {
        $coupon = $this->query()->find($id);

        if ($coupon === null) {
            return null;
        }

        $coupon->update($this->normalizeByCouponType(data: $data, sellerId: $sellerId));

        return $coupon->refresh();
    }

    public function updateStatus(mixed $id, bool $status): bool
    {
        $coupon = $this->query()->find($id);

        if ($coupon === null) {
            return false;
        }

        return (bool) $coupon->update(['status' => $status]);
    }

    public function delete(mixed $id): bool
    {
        $coupon = $this->query()->find($id);

        if ($coupon === null) {
            return false;
        }

        return (bool) $coupon->delete();
    }

    public function deleteBy(array $params): int
    {
        $query = $this->query();

        $this->applyFilters($query, $params);

        return $query->delete();
    }

    public function exists(array $params): bool
    {
        return $this->query()->where($params)->exists();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * @param  array<int, string>  $vendors
     * @return array<int|string, string>
     */
    public function getVendorOptions(array $vendors = [], ?string $bearer = null): array
    {
        $options = [self::VENDOR_ALL => translate('All Vendor')];

        if ($bearer === self::BEARER_INHOUSE) {
            $options[self::VENDOR_INHOUSE] = getInHouseShopConfig(key: 'name');
        }

        return $options + $vendors;
    }

    public function isCustomerEligible(Model $coupon, int $customerId): bool
    {
        return (int) $coupon->customer_id === self::CUSTOMER_ALL || (int) $coupon->customer_id === $customerId;
    }

    public function hasReachedLimit(Model $coupon, int $timesUsed): bool
    {
        return $coupon->limit !== null && $timesUsed >= (int) $coupon->limit;
    }

    public function calculateDiscount(Model $coupon, float $orderAmount): float
    {
        if ($coupon->discount_type !== self::DISCOUNT_TYPE_PERCENTAGE) {
            return (float) $coupon->discount;
        }

        $discount = $orderAmount * (float) $coupon->discount / 100;
        $maxDiscount = (float) $coupon->max_discount;

        return $maxDiscount > 0 ? min($discount, $maxDiscount) : $discount;
    }

    /**
     * @param  iterable<int, mixed>  $cartItems
     * @param  array<string, float>  $groupShippingCosts
     * @return array{reason: ?string, coupon: ?Model, discount: float, total_cart_amount: float}
     */
    public function calculateCartCouponDiscount(
        ?string $couponCode,
        ?int $customerId,
        int $customerCouponOrderGroupCount,
        int $customerOrderCount,
        iterable $cartItems,
        array $groupShippingCosts = [],
    ): array {
        if ($customerId === null) {
            return $this->cartCouponRejection(reason: self::REASON_CUSTOMER_REQUIRED);
        }

        $coupon = $this->findRunningByCode(couponCode: $couponCode);

        if ($coupon === null) {
            return $this->cartCouponRejection(reason: self::REASON_COUPON_NOT_FOUND);
        }

        $usageRejectionReason = $this->getUsageRejectionReason(
            coupon: $coupon,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCount,
            customerOrderCount: $customerOrderCount,
        );

        if ($usageRejectionReason !== null) {
            return $this->cartCouponRejection(reason: $usageRejectionReason, coupon: $coupon);
        }

        return $this->calculateCartItemsCouponDiscount(
            coupon: $coupon,
            customerId: $customerId,
            cartItems: collect($cartItems),
            groupShippingCosts: $groupShippingCosts,
        );
    }

    /**
     * @param  iterable<int, mixed>  $groupCartItems
     * @return array{discount: float|int, coupon_bearer: string, coupon_code: string|int, coupon_type: ?string}
     */
    public function calculateGroupCouponDiscount(
        ?string $couponCode,
        ?int $customerId,
        int $customerCouponOrderGroupCount,
        float $applicableAmount,
        float $discountAmount,
        iterable $groupCartItems,
        float $groupShippingCost = 0,
    ): array {
        if ($applicableAmount <= 0 || $discountAmount <= 0 || $customerId === null) {
            return $this->emptyGroupCouponDiscount();
        }

        $coupon = $this->findRunningByCode(couponCode: $couponCode);

        if ($coupon === null) {
            return $this->emptyGroupCouponDiscount();
        }

        $isFirstOrderCoupon = $coupon->coupon_type === self::TYPE_FIRST_ORDER;

        if (! $isFirstOrderCoupon && ! $this->isBelowOrderGroupLimit(coupon: $coupon, orderGroupCount: $customerCouponOrderGroupCount)) {
            return $this->emptyGroupCouponDiscount();
        }

        $groupCartItems = collect($groupCartItems);
        $discount = 0;

        if ($isFirstOrderCoupon || $coupon->coupon_type === self::TYPE_DISCOUNT_ON_PURCHASE) {
            $applicableItems = $isFirstOrderCoupon
                ? $groupCartItems
                : $groupCartItems->filter(fn (mixed $cartItem): bool => $this->isApplicableToCartItem(coupon: $coupon, cartItem: $cartItem));

            $discount = ($this->sumProductAmount(cartItems: $applicableItems) * $discountAmount) / $applicableAmount;
        } elseif ($coupon->coupon_type === self::TYPE_FREE_DELIVERY) {
            $firstCartItem = $groupCartItems->first();

            if ($firstCartItem && $this->isApplicableToCartItem(coupon: $coupon, cartItem: $firstCartItem)) {
                $discount += $groupShippingCost;
            }
        }

        return [
            'discount' => $discount,
            'coupon_bearer' => $coupon->coupon_bearer,
            'coupon_code' => $coupon->code,
            'coupon_type' => $coupon->coupon_type,
        ];
    }

    /**
     * @param  iterable<int, mixed>  $cartItems
     * @param  array<string, int>  $customerCouponOrderGroupCounts
     * @param  array<string, float>  $groupShippingCosts
     */
    public function getApplicableCartCoupons(
        int $customerId,
        iterable $cartItems,
        int $customerOrderCount,
        array $customerCouponOrderGroupCounts,
        array $groupShippingCosts = [],
    ): SupportCollection {
        $cartItems = collect($cartItems);

        if ($cartItems->isEmpty()) {
            return collect();
        }

        $runningCoupons = $this->query()
            ->active()
            ->with('seller.shop')
            ->select('coupons.*', DB::raw('DATE(expire_date) as plain_expire_date'))
            ->withCount(['order' => fn (Builder $order) => $order->where('customer_id', $customerId)])
            ->whereIn('customer_id', [$customerId, (string) self::CUSTOMER_ALL])
            ->whereDate('start_date', '<=', now())
            ->whereDate('expire_date', '>=', now())
            ->get();

        return $runningCoupons->filter(fn (Model $coupon): bool => $this->getUsageRejectionReason(
            coupon: $coupon,
            customerCouponOrderGroupCount: $customerCouponOrderGroupCounts[mb_strtolower((string) $coupon->code)] ?? 0,
            customerOrderCount: $customerOrderCount,
        ) === null && $this->calculateCartItemsCouponDiscount(
            coupon: $coupon,
            customerId: $customerId,
            cartItems: $cartItems,
            groupShippingCosts: $groupShippingCosts,
        )['reason'] === null)->values();
    }

    public function findApplicableForEditedOrder(object|array $order): ?Model
    {
        $today = now()->toDateString();

        return $this->query()
            ->active()
            ->with(['seller'])
            ->whereIn('customer_id', [$order['customer_id'], self::CUSTOMER_ALL])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('expire_date', '>=', $today)
            ->when($order['seller_is'] == self::SELLER_IS_ADMIN, fn (Builder $builder) => $builder->whereNull('seller_id'))
            ->when($order['seller_is'] == self::SELLER_IS_SELLER, fn (Builder $builder) => $builder->where('seller_id', $order['seller_id']))
            ->where('code', $order['coupon_code'])
            ->first();
    }

    /**
     * @param  iterable<int, mixed>  $cartItems
     * @return array<int, string>
     */
    public function getApplicableCartGroupIds(Model $coupon, iterable $cartItems): array
    {
        $groupIds = [];

        foreach (collect($cartItems)->groupBy('cart_group_id') as $groupId => $groupItems) {
            $firstCartItem = $groupItems->first();

            if ($firstCartItem && $this->isApplicableToCartItem(coupon: $coupon, cartItem: $firstCartItem)) {
                $groupIds[] = (string) $groupId;
            }
        }

        return $groupIds;
    }

    private function getUsageRejectionReason(Model $coupon, int $customerCouponOrderGroupCount, int $customerOrderCount): ?string
    {
        $isFirstOrderCoupon = $coupon->coupon_type === self::TYPE_FIRST_ORDER;

        if (! $isFirstOrderCoupon && ! $this->isBelowOrderGroupLimit(coupon: $coupon, orderGroupCount: $customerCouponOrderGroupCount)) {
            return self::REASON_USAGE_LIMIT_REACHED;
        }

        if ($isFirstOrderCoupon && $customerOrderCount > 0) {
            return self::REASON_NOT_FIRST_ORDER;
        }

        return null;
    }

    /**
     * @param  array<string, float>  $groupShippingCosts
     * @return array{reason: ?string, coupon: ?Model, discount: float, total_cart_amount: float}
     */
    private function calculateCartItemsCouponDiscount(Model $coupon, int $customerId, SupportCollection $cartItems, array $groupShippingCosts): array
    {
        $isFirstOrderCoupon = $coupon->coupon_type === self::TYPE_FIRST_ORDER;

        if ($cartItems->isEmpty()) {
            return $this->cartCouponRejection(reason: self::REASON_CART_EMPTY, coupon: $coupon);
        }

        if (! $this->isCustomerEligible(coupon: $coupon, customerId: $customerId)) {
            return $this->cartCouponRejection(reason: self::REASON_CUSTOMER_NOT_ELIGIBLE, coupon: $coupon);
        }

        $applicableItems = $isFirstOrderCoupon
            ? $cartItems
            : $cartItems->filter(fn (mixed $cartItem): bool => $this->isApplicableToCartItem(coupon: $coupon, cartItem: $cartItem));

        if (! $isFirstOrderCoupon && $applicableItems->isEmpty()) {
            return $this->cartCouponRejection(reason: self::REASON_VENDOR_NOT_APPLICABLE, coupon: $coupon);
        }

        $productTotalAmount = $this->sumProductAmount(cartItems: $applicableItems);

        if ($coupon->coupon_type === self::TYPE_FREE_DELIVERY) {
            if (! $this->isMinimumPurchaseReached(coupon: $coupon, productTotalAmount: $productTotalAmount)) {
                return $this->cartCouponRejection(reason: self::REASON_MINIMUM_PURCHASE_NOT_REACHED, coupon: $coupon);
            }

            $discount = $this->sumApplicableShippingCost(coupon: $coupon, cartItems: $cartItems, groupShippingCosts: $groupShippingCosts);

            if ($discount <= 0) {
                return $this->cartCouponRejection(reason: self::REASON_SHIPPING_COST_NOT_APPLICABLE, coupon: $coupon);
            }

            return $this->cartCouponResult(coupon: $coupon, discount: $discount, totalCartAmount: $productTotalAmount);
        }

        if ($isFirstOrderCoupon || $coupon->coupon_type === self::TYPE_DISCOUNT_ON_PURCHASE) {
            if (! $this->isPurchaseAmountSufficient(coupon: $coupon, productTotalAmount: $productTotalAmount)) {
                return $this->cartCouponRejection(reason: self::REASON_MINIMUM_PURCHASE_NOT_REACHED, coupon: $coupon);
            }

            return $this->cartCouponResult(
                coupon: $coupon,
                discount: $this->calculateCappedDiscount(coupon: $coupon, productTotalAmount: $productTotalAmount),
                totalCartAmount: $productTotalAmount,
            );
        }

        return $this->cartCouponRejection(reason: self::REASON_NO_DISCOUNT, coupon: $coupon);
    }

    private function findRunningByCode(?string $couponCode): ?Model
    {
        return $this->findBy(params: [
            'code' => $couponCode,
            'status' => 1,
            self::FILTER_RUNNING => true,
        ]);
    }

    private function isBelowOrderGroupLimit(Model $coupon, int $orderGroupCount): bool
    {
        return (int) $coupon->limit > $orderGroupCount;
    }

    private function isApplicableToCartItem(Model $coupon, mixed $cartItem): bool
    {
        $sellerIs = $cartItem['seller_is'];

        return $coupon->seller_id === self::VENDOR_ALL
            || ($coupon->seller_id === null && $sellerIs === self::SELLER_IS_ADMIN)
            || ((int) $coupon->seller_id === (int) $cartItem['seller_id'] && $sellerIs === self::SELLER_IS_SELLER);
    }

    private function sumProductAmount(iterable $cartItems): float
    {
        $total = 0;

        foreach ($cartItems as $cartItem) {
            $total += ($cartItem['price'] - $cartItem['discount']) * $cartItem['quantity'];
        }

        return (float) $total;
    }

    /** @param  array<string, float>  $groupShippingCosts */
    private function sumApplicableShippingCost(Model $coupon, iterable $cartItems, array $groupShippingCosts): float
    {
        $discount = 0;

        foreach (collect($cartItems)->groupBy('cart_group_id') as $groupId => $groupItems) {
            $firstCartItem = $groupItems->first();

            if ($firstCartItem && $this->isApplicableToCartItem(coupon: $coupon, cartItem: $firstCartItem)) {
                $discount += $groupShippingCosts[$groupId] ?? 0;
            }
        }

        return (float) $discount;
    }

    private function isPurchaseAmountSufficient(Model $coupon, float $productTotalAmount): bool
    {
        return $this->isMinimumPurchaseReached(coupon: $coupon, productTotalAmount: $productTotalAmount)
            && (float) $coupon->discount <= $productTotalAmount;
    }

    private function isMinimumPurchaseReached(Model $coupon, float $productTotalAmount): bool
    {
        return $productTotalAmount > 0 && (float) $coupon->min_purchase <= $productTotalAmount;
    }

    private function calculateCappedDiscount(Model $coupon, float $productTotalAmount): float
    {
        if ($coupon->discount_type !== self::DISCOUNT_TYPE_PERCENTAGE) {
            return (float) $coupon->discount;
        }

        $discount = ($productTotalAmount * (float) $coupon->discount) / 100;

        return $discount > (float) $coupon->max_discount ? (float) $coupon->max_discount : $discount;
    }

    /** @return array{reason: ?string, coupon: ?Model, discount: float, total_cart_amount: float} */
    private function cartCouponResult(Model $coupon, float $discount, float $totalCartAmount): array
    {
        if ($discount <= 0) {
            return $this->cartCouponRejection(reason: self::REASON_NO_DISCOUNT, coupon: $coupon);
        }

        return [
            'reason' => null,
            'coupon' => $coupon,
            'discount' => $discount,
            'total_cart_amount' => $totalCartAmount,
        ];
    }

    /** @return array{reason: ?string, coupon: ?Model, discount: float, total_cart_amount: float} */
    private function cartCouponRejection(string $reason, ?Model $coupon = null): array
    {
        return [
            'reason' => $reason,
            'coupon' => $coupon,
            'discount' => 0.0,
            'total_cart_amount' => 0.0,
        ];
    }

    /** @return array{discount: int, coupon_bearer: string, coupon_code: int, coupon_type: null} */
    private function emptyGroupCouponDiscount(): array
    {
        return [
            'discount' => 0,
            'coupon_bearer' => self::BEARER_INHOUSE,
            'coupon_code' => 0,
            'coupon_type' => null,
        ];
    }

    public function isCodeAvailable(string $code, mixed $ignoreId = null): bool
    {
        return ! $this->model->newQuery()
            ->where('code', $code)
            ->when($ignoreId, fn (Builder $builder) => $builder->whereKeyNot($ignoreId))
            ->exists();
    }

    private function normalizeByCouponType(array $data, ?int $sellerId = null): array
    {
        $couponType = $data['coupon_type'];
        $groups = $this->getFieldVisibility()[$couponType];

        return [
            'coupon_type' => $couponType,
            'title' => $data['title'],
            'code' => $data['code'],
            'start_date' => $data['start_date'],
            'expire_date' => $data['expire_date'],
            'min_purchase' => currencyConverter(amount: $data['min_purchase']),
        ]
            + $this->targetingAttributes($groups['targeting'], $data, $sellerId)
            + $this->discountAttributes($groups['discount'], $data);
    }

    private function targetingAttributes(bool $isTargeted, array $data, ?int $sellerId): array
    {
        if (! $isTargeted) {
            return [
                'coupon_bearer' => self::BEARER_INHOUSE,
                'seller_id' => null,
                'customer_id' => self::CUSTOMER_ALL,
                'limit' => 0,
            ];
        }

        if ($sellerId !== null) {
            return [
                'coupon_bearer' => self::BEARER_SELLER,
                'seller_id' => $sellerId,
                'customer_id' => $data['customer_id'],
                'limit' => $data['limit'],
            ];
        }

        return [
            'coupon_bearer' => $data['coupon_bearer'],
            'seller_id' => $data['seller_id'] === self::VENDOR_INHOUSE ? null : $data['seller_id'],
            'customer_id' => $data['customer_id'],
            'limit' => $data['limit'],
        ];
    }

    private function discountAttributes(bool $hasDiscount, array $data): array
    {
        if (! $hasDiscount) {
            return [
                'discount_type' => self::DISCOUNT_TYPE_PERCENTAGE,
                'discount' => 0,
                'max_discount' => 0,
            ];
        }

        $discountType = $data['discount_type'];
        $isPercentage = $discountType === self::DISCOUNT_TYPE_PERCENTAGE;

        return [
            'discount_type' => $discountType,
            'discount' => $isPercentage
                ? $data['discount']
                : currencyConverter(amount: $data['discount']),
            'max_discount' => currencyConverter(amount: $isPercentage
                ? $data['max_discount']
                : (($data['max_discount'] ?? null) ?: $data['discount'])),
        ];
    }
}
