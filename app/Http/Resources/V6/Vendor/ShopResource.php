<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'seller_id' => (int) $this->seller_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'contact' => $this->contact,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'banner' => $this->banner,
            'banner_full_url' => $this->banner_full_url,
            'bottom_banner' => $this->bottom_banner,
            'bottom_banner_full_url' => $this->bottom_banner_full_url,
            'offer_banner' => $this->offer_banner,
            'offer_banner_full_url' => $this->offer_banner_full_url,
            'tax_identification_number' => $this->tax_identification_number,
            'tin_expire_date' => $this->tin_expire_date,
            'tin_certificate_full_url' => $this->tin_certificate_full_url,
            'temporary_close' => (int) $this->temporary_close,
            'wholesale_temporary_close' => (int) $this->wholesale_temporary_close,
            'auction_temporary_close' => (int) $this->auction_temporary_close,
            'vacation_status' => (int) $this->vacation_status,
            'vacation_duration_type' => $this->vacation_duration_type,
            'vacation_start_date' => $this->vacation_start_date,
            'vacation_end_date' => $this->vacation_end_date,
            'vacation_note' => $this->vacation_note,
            'vacation_modules' => $this->vacation_modules,
            'is_verified' => (bool) ($this->seller?->is_verified ?? false),
            'verified_at' => $this->seller?->verified_at,
            'created_at' => $this->created_at,
        ];
    }
}
