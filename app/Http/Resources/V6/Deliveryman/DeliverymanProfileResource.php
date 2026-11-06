<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverymanProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'email' => $this->email,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'full_phone' => $this->full_phone,
            'address' => $this->address,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'identity_number' => $this->identity_number,
            'identity_type' => $this->identity_type,
            'identity_images_full_url' => $this->identity_images_full_url,
            'bank_name' => $this->bank_name,
            'branch' => $this->branch,
            'account_no' => $this->account_no,
            'holder_name' => $this->holder_name,
            'is_active' => (bool) $this->is_active,
            'is_online' => (bool) $this->is_online,
            'app_language' => $this->app_language,
            'created_at' => $this->created_at,
        ];
    }
}
