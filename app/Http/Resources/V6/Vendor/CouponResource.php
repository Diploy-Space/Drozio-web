<?php

namespace App\Http\Resources\V6\Vendor;

use App\Services\New\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'coupon_type' => $this->coupon_type,
            'customer_id' => (int) $this->customer_id,
            'limit' => $this->limit === null ? null : (int) $this->limit,
            'discount_type' => $this->discount_type,
            'discount' => (float) $this->discount,
            'max_discount' => (float) $this->max_discount,
            'min_purchase' => (float) $this->min_purchase,
            'start_date' => $this->start_date,
            'expire_date' => $this->expire_date,
            'status' => (int) $this->status,
            'is_editable' => $this->added_by === CouponService::ADDED_BY_SELLER
                && (int) $this->seller_id !== CouponService::VENDOR_ALL,
            'order_count' => (int) ($this->order_count ?? 0),
            'created_at' => $this->created_at,
        ];
    }
}
