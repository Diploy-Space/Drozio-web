<?php

namespace App\Services\New;

use App\Models\ReferralCustomer;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReferralCustomerService extends BaseService
{
    private const DEFAULT_DISCOUNT_TYPE = 'percentage';

    private const DISCOUNT_TYPE_FLAT = 'flat';

    private const DEFAULT_VALIDITY_TYPE = 'day';

    private const VALIDITY_TYPE_WEEK = 'week';

    private const VALIDITY_TYPE_MONTH = 'month';

    private const ORDER_STATUS_DELIVERED = 'delivered';

    private const PAYMENT_STATUS_PAID = 'paid';

    private const DELIVERED_NOTIFIED = 1;

    private const REFERRAL_EARNING_STATUS = 'ref_earning_status';

    private const REFERRAL_EARNING_EXCHANGE_RATE = 'ref_earning_exchange_rate';

    public function __construct(ReferralCustomer $referralCustomer)
    {
        parent::__construct($referralCustomer);
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'user_id' => $data['user_id'],
            'refer_by' => $data['refer_by'],
            'ref_by_earning_amount' => $data['ref_by_earning_amount'] ?? 0,
            'customer_discount_amount' => $data['discount_amount'] ?? 0,
            'customer_discount_amount_type' => $data['discount_type'] ?? self::DEFAULT_DISCOUNT_TYPE,
            'customer_discount_validity' => $data['validity'] ?? 1,
            'customer_discount_validity_type' => $data['validity_type'] ?? self::DEFAULT_VALIDITY_TYPE,
            'registered_notify' => 0,
            'ordered_notify' => 0,
            'delivered_notify' => 0,
            'is_used_by_refer' => 0,
            'is_checked' => 0,
        ]);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateWhere(array $params, array $data): int
    {
        return $this->query()->where($params)->update($data);
    }

    public function findRedeemableForCustomer(?int $customerId, int $completedOrderCount): ?Model
    {
        if ($customerId === null) {
            return null;
        }

        $referralCustomer = $this->query()->where('user_id', $customerId)->where('is_used', 0)->first();

        return $referralCustomer && $completedOrderCount <= 0 ? $referralCustomer : null;
    }

    public function calculateReferralDiscount(?Model $referralCustomer, float|int $totalAmount, float|int $groupAmount): float|int
    {
        if (! $referralCustomer) {
            return 0;
        }

        $discountAmount = $referralCustomer->customer_discount_amount;
        $validity = $referralCustomer->customer_discount_validity;
        $validityType = $referralCustomer->customer_discount_validity_type;

        $expirationDate = Carbon::parse($referralCustomer->created_at);
        if ($validityType == self::DEFAULT_VALIDITY_TYPE) {
            $expirationDate->addDays($validity);
        } elseif ($validityType == self::VALIDITY_TYPE_WEEK) {
            $expirationDate->addWeeks($validity);
        } elseif ($validityType == self::VALIDITY_TYPE_MONTH) {
            $expirationDate->addMonths($validity);
        } else {
            return 0;
        }

        if (Carbon::now()->greaterThan($expirationDate)) {
            return 0;
        }

        if ($referralCustomer->customer_discount_amount_type === self::DEFAULT_DISCOUNT_TYPE) {
            return ($groupAmount * $discountAmount) / 100;
        }

        return ($discountAmount * $groupAmount) / $totalAmount;
    }

    public function calculateCartReferralDiscount(int $customerId, float|int $cartAmount, mixed $couponDiscount): mixed
    {
        $couponDiscount = is_null($couponDiscount) ? 0 : (float) $couponDiscount;

        if ($cartAmount > $couponDiscount && $couponDiscount != 0) {
            $cartAmount = $cartAmount - $couponDiscount;
        }

        $referralCustomer = $this->query()->where('user_id', $customerId)->where('is_used', 0)->first();

        if ($referralCustomer === null || ! $this->isDiscountWindowOpen(referralCustomer: $referralCustomer)) {
            return 0;
        }

        if ($referralCustomer->customer_discount_amount_type == self::DISCOUNT_TYPE_FLAT) {
            return $referralCustomer->customer_discount_amount;
        }

        return ($cartAmount * $referralCustomer->customer_discount_amount) / 100;
    }

    private function isDiscountWindowOpen(Model $referralCustomer): bool
    {
        $validity = $referralCustomer->customer_discount_validity;
        $validityType = $referralCustomer->customer_discount_validity_type;
        $expirationDate = Carbon::parse($referralCustomer->created_at);

        if ($validityType == self::DEFAULT_VALIDITY_TYPE) {
            $expirationDate->addDays($validity);
        } elseif ($validityType == self::VALIDITY_TYPE_WEEK) {
            $expirationDate->addWeeks($validity);
        } elseif ($validityType == self::VALIDITY_TYPE_MONTH) {
            $expirationDate->addMonths($validity);
        } else {
            return false;
        }

        return ! Carbon::now()->greaterThan($expirationDate);
    }

    public function shouldNotifyReferrerOfOrder(?Model $referralCustomer): bool
    {
        return $referralCustomer && $referralCustomer->is_used != 1 && $referralCustomer->ordered_notify != 1;
    }

    public function isOrderEligibleForReferrerBonus(object|array $order, mixed $referralEarningStatus): bool
    {
        return ! $order['is_guest'] && $referralEarningStatus == 1 && $order['order_status'] == self::ORDER_STATUS_DELIVERED;
    }

    public function isReferrerBonusDue(object|array $customer, ?Model $referrer, int $customerCompletedOrderCount): bool
    {
        return $customerCompletedOrderCount == 1 && isset($customer->referred_by) && isset($referrer);
    }

    public function getReferrerIdForFirstDeliveredOrder(Model $order, int $customerDeliveredPaidOrderCount): int|string|null
    {
        $referrerId = $order->customer?->referred_by;

        if ($order['is_guest']
            || getWebConfig(name: self::REFERRAL_EARNING_STATUS) != 1
            || $order['order_status'] != self::ORDER_STATUS_DELIVERED
            || $order['payment_status'] != self::PAYMENT_STATUS_PAID
            || empty($referrerId)
            || $customerDeliveredPaidOrderCount != 1) {
            return null;
        }

        return $referrerId;
    }

    public function getReferrerEarningAmount(): float
    {
        return (float) usdToDefaultCurrency(amount: (float) (getWebConfig(name: self::REFERRAL_EARNING_EXCHANGE_RATE) ?? 0));
    }

    /** @return array{notifies_promoter: bool, allows_referrer_bonus: bool} */
    public function recordReferredCustomerDelivery(int|string $customerId): array
    {
        if ($this->markReferredCustomerDeliveryNotified(customerId: $customerId)) {
            return ['notifies_promoter' => true, 'allows_referrer_bonus' => true];
        }

        return [
            'notifies_promoter' => false,
            'allows_referrer_bonus' => ! $this->query()->where('user_id', $customerId)->exists(),
        ];
    }

    public function markReferredCustomerDeliveryNotified(int|string $customerId): bool
    {
        return $this->query()
            ->where('user_id', $customerId)
            ->where('delivered_notify', '!=', self::DELIVERED_NOTIFIED)
            ->update(['delivered_notify' => self::DELIVERED_NOTIFIED]) > 0;
    }
}
