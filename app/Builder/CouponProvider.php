<?php

namespace App\Builder;

use App\Enums\BusinessModule;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Builder\app\Contracts\CouponProvider as CouponProviderContract;
use Modules\Builder\app\ValueObjects\StorefrontScope;

class CouponProvider implements CouponProviderContract
{
    public function forCustomer(?int $customerId, ?int $storeId): array
    {
        if (! $storeId) {
            return [];
        }

        if ($customerId === null && ! $this->guestCouponAllowed()) {
            return [];
        }

        $sellerId = Shop::query()->where('id', $storeId)->value('seller_id');
        if ($sellerId === null) {
            return [];
        }

        $today = Carbon::today()->toDateString();

        return Coupon::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('expire_date', '>=', $today)
            ->where(fn (Builder $q) => $this->applySellerScope($q, (int) $sellerId))
            ->where(fn (Builder $q) => $this->applyCustomerScope($q, $customerId))
            ->get()
            ->filter(fn (Coupon $coupon) => $this->isUsableByCustomer($coupon, $customerId))
            ->map(fn (Coupon $coupon) => $this->toDto($coupon))
            ->values()
            ->all();
    }

    public function validate(string $code, ?int $customerId, ?StorefrontScope $scope, float $cartTotal): array
    {
        if ($customerId === null && ! $this->guestCouponAllowed()) {
            return ['ok' => false, 'error' => translate('Please login to apply a coupon') ?: 'Please log in to apply a coupon.'];
        }

        $code = trim($code);
        $today = date('Y-m-d');

        $coupon = Coupon::query()
            ->businessModule(BusinessModule::RETAIL)
            ->where('code', $code)
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('expire_date', '>=', $today)
            ->first();

        if (! $coupon) {
            return ['ok' => false, 'error' => translate('Invalid coupon') ?: 'Coupon code not found.'];
        }

        if ((int) $coupon->customer_id !== 0 && (int) $coupon->customer_id !== (int) $customerId) {
            return ['ok' => false, 'error' => translate('Coupon not valid') ?: 'This coupon is not available for your account.'];
        }

        if ($coupon->coupon_type === 'first_order') {
            if ($customerId && Order::query()->businessModule(BusinessModule::RETAIL)->where('customer_id', $customerId)->count() > 0) {
                return ['ok' => false, 'error' => translate('Sorry this coupon is not valid for this user') ?: 'This coupon is only valid on your first order.'];
            }
        } elseif ($customerId) {
            $used = Order::query()->businessModule(BusinessModule::RETAIL)->where(['customer_id' => $customerId, 'coupon_code' => $coupon->code])
                ->groupBy('order_group_id')
                ->get()
                ->count();
            if ((int) $coupon->limit <= $used) {
                return ['ok' => false, 'error' => translate('Sorry this coupon is not valid for this user') ?: 'Coupon usage limit exceeded.'];
            }
        }

        $sellerId = $scope?->tenantId;
        if (! $this->couponMatchesSeller($coupon, $sellerId)) {
            return ['ok' => false, 'error' => translate('Coupon not applicable to selected vendors') ?: 'This coupon is not valid at this store.'];
        }

        if ((float) $coupon->min_purchase > 0 && $cartTotal < (float) $coupon->min_purchase) {
            return ['ok' => false, 'error' => translate('Minimum purchase amount is').' '.number_format((float) $coupon->min_purchase, 2)];
        }

        $isFreeDelivery = $coupon->coupon_type === 'free_delivery';
        $discount = $isFreeDelivery ? 0.0 : $this->discountAmount($coupon, $cartTotal);

        return [
            'ok' => true,
            'code' => (string) $coupon->code,
            'title' => (string) $coupon->title,
            'discount' => $discount,
            'freeDelivery' => $isFreeDelivery,
            'couponType' => (string) $coupon->coupon_type,
        ];
    }

    private function isUsableByCustomer(Coupon $coupon, ?int $customerId): bool
    {
        if ($customerId === null) {
            return true;
        }

        if ($coupon->coupon_type === 'first_order') {
            return Order::query()->businessModule(BusinessModule::RETAIL)->where('customer_id', $customerId)->count() === 0;
        }

        $used = Order::query()->businessModule(BusinessModule::RETAIL)->where(['customer_id' => $customerId, 'coupon_code' => $coupon->code])
            ->groupBy('order_group_id')
            ->get()
            ->count();

        return $used < (int) $coupon->limit;
    }

    private function applySellerScope(Builder $q, int $sellerId): void
    {
        $q->where('added_by', 'seller')
            ->where('seller_id', $sellerId);
    }

    private function applyCustomerScope(Builder $q, ?int $customerId): void
    {
        $q->where('customer_id', 0);
        if ($customerId !== null) {
            $q->orWhere('customer_id', $customerId);
        }
    }

    private function guestCouponAllowed(): bool
    {
        return (bool) config('builder.capabilities.checkout.guestCoupon', true);
    }

    private function couponMatchesSeller(Coupon $coupon, ?int $sellerId): bool
    {
        if ($sellerId === null) {
            return false;
        }

        return $coupon->added_by === 'seller' && (int) $coupon->seller_id === (int) $sellerId;
    }

    private function discountAmount(Coupon $coupon, float $cartTotal): float
    {
        $discount = (float) $coupon->discount;

        if (\str_starts_with((string) $coupon->discount_type, 'percent')) {
            $amount = ($cartTotal * $discount) / 100;
            $maxDiscount = (float) $coupon->max_discount;
            if ($maxDiscount > 0 && $amount > $maxDiscount) {
                $amount = $maxDiscount;
            }

            return $amount;
        }

        return min($discount, $cartTotal);
    }

    private function toDto(Coupon $coupon): array
    {
        $discountType = $coupon->discount_type;
        $isPercent = \str_starts_with((string) $discountType, 'percent');

        $discount = $isPercent ? (float) $coupon->discount : (float) webCurrencyConverterOnlyDigit((float) $coupon->discount);
        $minPurchase = (float) webCurrencyConverterOnlyDigit((float) $coupon->min_purchase);
        $maxDiscount = (float) webCurrencyConverterOnlyDigit((float) $coupon->max_discount);
        $symbol = \App\Utils\currency_symbol() ?: '$';

        $benefit = match (true) {
            $coupon->coupon_type === 'free_delivery' => $this->typeLabel('free_delivery'),
            $isPercent => $this->trimNumber($discount).'% '.(translate('Off') ?: 'Off'),
            default => $symbol.$this->trimNumber($discount).' '.(translate('Off') ?: 'Off'),
        };

        $note = null;
        if ($minPurchase > 0) {
            $note = (translate('Min purchase') ?: 'Min purchase').' '.$symbol.$this->trimNumber($minPurchase);
        } elseif ($isPercent && $maxDiscount > 0) {
            $note = (translate('Max discount') ?: 'Max discount').' '.$symbol.$this->trimNumber($maxDiscount);
        }

        return [
            'id' => (int) $coupon->id,
            'code' => (string) $coupon->code,
            'title' => (string) $coupon->title,
            'coupon_type' => $coupon->coupon_type,
            'type_label' => $this->typeLabel($coupon->coupon_type),
            'discount' => $discount,
            'discount_type' => $discountType,
            'min_purchase' => $minPurchase,
            'max_discount' => $maxDiscount,
            'valid_from' => $this->formatDate($coupon->start_date),
            'valid_to' => $this->formatDate($coupon->expire_date),
            'store_name' => null,
            'benefit' => $benefit,
            'note' => $note,
        ];
    }

    private function typeLabel(?string $type): string
    {
        return match ($type) {
            'first_order' => translate('First Order') ?: 'First Order',
            'free_delivery' => translate('Free Delivery') ?: 'Free Delivery',
            'discount_on_purchase' => translate('Discount') ?: 'Discount',
            default => translate('Special Offer') ?: 'Special Offer',
        };
    }

    private function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
