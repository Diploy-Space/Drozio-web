<?php

namespace App\Http\Resources\V6\Customer;

use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'contact' => $this->contact,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'banner' => $this->banner,
            'banner_full_url' => $this->banner_full_url,
            'temporary_close' => (bool) $this->temporary_close_now,
            'wholesale_temporary_close' => (bool) $this->wholesale_temporary_close,
            'auction_temporary_close' => (bool) $this->auction_temporary_close,
            'vacation_status' => (bool) $this->vacation_status,
            'is_vacation_mode_now' => (bool) $this->is_vacation_mode_now,
            'vacation_duration_type' => $this->vacation_duration_type,
            'vacation_start_date' => $this->vacation_start_date,
            'vacation_end_date' => $this->vacation_end_date,
            'vacation_modules' => $this->vacation_modules,
            'created_at' => $this->created_at,
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'f_name' => $this->seller->f_name,
                'l_name' => $this->seller->l_name,
                'phone' => $this->seller->phone,
                'image' => $this->seller->image,
                'image_full_url' => $this->seller->image_full_url,
            ]),
            'is_verified' => $this->author_type === UserType::ADMIN ? isInHouseShopVerified() : $this->whenLoaded('seller', fn () => (bool) $this->seller?->is_verified, false),
            'verified_at' => $this->whenLoaded('seller', fn () => $this->seller->verified_at, null),
            'avg_rating' => (float) $this->avg_rating,
            'rating_percentage' => (int) $this->rating_percentage,
            'total_review' => (int) $this->total_review,
            'total_order' => (int) $this->orders_count,
            'total_product' => (int) $this->total_product,
            'minimum_order_amount' => (float) $this->minimum_order_amount,
        ];
    }
}
