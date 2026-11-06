<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isApplicableListRow = $this->isApplicableListRow();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'coupon_type' => $this->coupon_type,
            'coupon_bearer' => $this->coupon_bearer,
            'seller_id' => $this->seller_id,
            'seller' => $this->whenLoaded('seller', fn () => $this->sellerPayload(withApplicableListFields: $isApplicableListRow)),
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'min_purchase' => $this->min_purchase,
            'max_discount' => $this->max_discount,
            'start_date' => $this->start_date,
            'expire_date' => $this->expire_date,
            'limit' => $this->limit,
            'used_count' => $this->order_count ?? 0,
            $this->mergeWhen($isApplicableListRow, fn () => [
                'added_by' => $this->added_by,
                'customer_id' => $this->customer_id,
                'status' => $this->status,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'order_count' => $this->order_count ?? 0,
                'plain_expire_date' => $this->plain_expire_date,
            ]),
        ];
    }

    private function isApplicableListRow(): bool
    {
        return array_key_exists('plain_expire_date', $this->resource->getAttributes());
    }

    private function sellerPayload(bool $withApplicableListFields): ?array
    {
        $seller = $this->seller;

        if ($seller === null) {
            return null;
        }

        $shopLoaded = $seller->relationLoaded('shop');

        if (! $withApplicableListFields) {
            return [
                'id' => $seller->id,
                'name' => trim($seller->f_name.' '.$seller->l_name),
                'shop' => $this->when($shopLoaded, fn () => [
                    'id' => $seller->shop?->id,
                    'name' => $seller->shop?->name,
                ]),
            ];
        }

        return [
            'id' => $seller->id,
            'name' => trim($seller->f_name.' '.$seller->l_name),
            'f_name' => $seller->f_name,
            'l_name' => $seller->l_name,
            'image' => $seller->image,
            'image_full_url' => $seller->image_full_url,
            'status' => $seller->status,
            'shop' => $shopLoaded && $seller->shop !== null ? [
                'id' => $seller->shop->id,
                'seller_id' => $seller->shop->seller_id,
                'author_type' => $seller->shop->author_type,
                'name' => $seller->shop->name,
                'slug' => $seller->shop->slug,
                'address' => $seller->shop->address,
                'contact' => $seller->shop->contact,
                'image' => $seller->shop->image,
                'image_full_url' => $seller->shop->image_full_url,
                'banner' => $seller->shop->banner,
                'banner_full_url' => $seller->shop->banner_full_url,
                'bottom_banner' => $seller->shop->bottom_banner,
                'bottom_banner_full_url' => $seller->shop->bottom_banner_full_url,
                'offer_banner' => $seller->shop->offer_banner,
                'offer_banner_full_url' => $seller->shop->offer_banner_full_url,
                'vacation_status' => $seller->shop->vacation_status,
                'temporary_close' => $seller->shop->temporary_close,
            ] : null,
        ];
    }
}
