<?php

namespace App\Services\New;

use App\Models\OrderDetailsRewards;
use App\Services\BaseService;
use App\Utils\Convert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderDetailsRewardsService extends BaseService
{
    public const REWARD_TYPE_LOYALTY_POINT = 'loyalty_point';

    public const REWARD_TYPE_COUPON = 'coupon';

    /** @var array<int, string> */
    public const LOYALTY_POINT_SETTING_KEYS = [
        'loyalty_point_status',
        'loyalty_point_exchange_rate',
        'loyalty_point_item_purchase_point',
        'loyalty_point_minimum_point',
        'loyalty_point_each_order_status',
        'loyalty_point_for_each_order',
    ];

    private const COUPON_STATUS_ACTIVE = 1;

    public function __construct(OrderDetailsRewards $orderDetailsRewards)
    {
        parent::__construct($orderDetailsRewards);
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
        $query = $this->query()->with($relations)->withCount($withCount);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->query()->find($id);

        if ($record === null) {
            return null;
        }

        $record->update($data);

        return $record->refresh();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function getUndeliveredLoyaltyPoint(int|string $orderId): int
    {
        return (int) $this->query()
            ->where(['order_id' => $orderId, 'reward_type' => self::REWARD_TYPE_LOYALTY_POINT, 'reward_delivered' => 0])
            ->sum('reward_amount');
    }

    public function markLoyaltyPointDelivered(int|string $orderId): int
    {
        return $this->updateWhere(
            params: ['order_id' => $orderId, 'reward_type' => self::REWARD_TYPE_LOYALTY_POINT, 'reward_delivered' => 0],
            data: ['reward_delivered' => 1],
        );
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

    public function deleteExceptOrderDetails(int|string $orderId, array $keepIds): int
    {
        return $this->query()->where('order_id', $orderId)->whereNotIn('order_details_id', $keepIds)->delete();
    }

    public function getLoyaltyPointForOrderDetail(int|string $orderDetailId): int
    {
        if (getWebConfig(name: 'loyalty_point_status') != 1) {
            return 0;
        }

        $reward = $this->findBy(params: ['order_details_id' => $orderDetailId, 'reward_type' => self::REWARD_TYPE_LOYALTY_POINT]);

        return (int) ($reward['reward_amount'] ?? 0);
    }

    public function createForOrderDetail(
        int $orderId,
        int $orderDetailsId,
        float|int $price,
        float|int $totalPrice,
        mixed $couponCode,
        mixed $couponDiscount,
        ?Model $coupon,
        array $loyaltyPointSettings,
    ): void {
        if (! empty($couponCode) && $coupon !== null) {
            $couponReward = clone $coupon;
            if ($couponDiscount > 0) {
                $couponReward['individual_coupon_discount'] = ((float) $couponDiscount * (float) $price) / $totalPrice;
            }

            if ($couponReward['status'] == self::COUPON_STATUS_ACTIVE) {
                $this->saveReward(
                    orderId: $orderId,
                    orderDetailsId: $orderDetailsId,
                    rewardType: self::REWARD_TYPE_COUPON,
                    rewardAmount: $couponReward['individual_coupon_discount'] ?? 0,
                    rewardDetails: $couponReward,
                    isRewardDelivered: 1,
                );
            }
        }

        $isLoyaltyPointEarnedPerOrder = (int) ($loyaltyPointSettings['loyalty_point_status'] ?? 0)
            && (int) ($loyaltyPointSettings['loyalty_point_for_each_order'] ?? 0);

        if (! $isLoyaltyPointEarnedPerOrder
            || ! isset($loyaltyPointSettings['loyalty_point_item_purchase_point'])
            || ! ($loyaltyPointSettings['loyalty_point_item_purchase_point'] > 0)) {
            return;
        }

        $couponDiscountShare = (! empty($couponCode) && $couponDiscount > 0 && $totalPrice > 0)
            ? ((float) $couponDiscount * (float) $price) / $totalPrice
            : 0;
        $loyaltyPointBase = Convert::default((float) $price - $couponDiscountShare);
        $loyaltyPointSettings['individual_loyalty_point'] = floor($loyaltyPointBase * $loyaltyPointSettings['loyalty_point_item_purchase_point'] / 100);

        $this->saveReward(
            orderId: $orderId,
            orderDetailsId: $orderDetailsId,
            rewardType: self::REWARD_TYPE_LOYALTY_POINT,
            rewardAmount: $loyaltyPointSettings['individual_loyalty_point'] ?? 0,
            rewardDetails: $loyaltyPointSettings,
            isRewardDelivered: 0,
        );
    }

    private function saveReward(int $orderId, int $orderDetailsId, string $rewardType, mixed $rewardAmount, mixed $rewardDetails, int $isRewardDelivered): void
    {
        $this->query()->updateOrCreate([
            'order_id' => $orderId,
            'order_details_id' => $orderDetailsId,
            'reward_type' => $rewardType,
        ], [
            'order_id' => $orderId,
            'order_details_id' => $orderDetailsId,
            'reward_type' => $rewardType,
            'reward_amount' => $rewardAmount,
            'reward_details' => $rewardDetails,
            'reward_delivered' => $isRewardDelivered,
        ]);
    }
}
