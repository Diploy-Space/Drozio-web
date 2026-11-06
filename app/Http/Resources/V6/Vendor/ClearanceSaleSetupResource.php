<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClearanceSaleSetupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'is_active' => (bool) $this->is_active,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'offer_active_time' => $this->offer_active_time,
            'offer_active_range_start' => $this->offer_active_range_start,
            'offer_active_range_end' => $this->offer_active_range_end,
            'duration_start_date' => $this->duration_start_date,
            'duration_end_date' => $this->duration_end_date,
            'show_in_shop' => (bool) $this->show_in_shop,
            'show_in_homepage_once' => (bool) $this->show_in_homepage_once,
            'seo' => $this->whenLoaded('seo', fn () => [
                'meta_title' => $this->seo?->title,
                'meta_description' => $this->seo?->description,
                'meta_image' => $this->seo?->image,
                'meta_image_full_url' => $this->seo?->image_full_url,
            ]),
        ];
    }
}
