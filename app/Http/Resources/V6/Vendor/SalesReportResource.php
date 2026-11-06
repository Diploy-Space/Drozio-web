<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReportResource extends JsonResource
{
    private const EXTRA_DISCOUNT_FREE_SHIPPING = 'free_shipping_over_order_amount';

    public function toArray(Request $request): array
    {
        $freeShippingDiscount = $this->extra_discount_type === self::EXTRA_DISCOUNT_FREE_SHIPPING ? (float) $this->extra_discount : 0.0;

        return [
            'order_id' => (int) $this->id,
            'order_amount' => (float) $this->order_amount,
            'product_discount' => (float) ($this->details_sum_discount ?? 0),
            'coupon_discount' => (float) ($this->discount_amount ?? 0),
            'referral_discount' => (float) ($this->refer_and_earn_discount ?? 0),
            'shipping_charge' => (float) $this->shipping_cost - $freeShippingDiscount,
            'tax' => (float) ($this->total_tax_amount ?? $this->details_sum_tax ?? 0),
            'admin_commission' => (float) ($this->admin_commission ?? 0),
            'deliveryman_incentive' => (float) ($this->deliveryman_charge ?? 0),
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at,
        ];
    }
}
