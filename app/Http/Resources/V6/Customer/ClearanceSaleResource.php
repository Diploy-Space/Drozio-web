<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClearanceSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'setup_by' => $this->setup_by,
            'shop_id' => $this->shop_id,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'offer_active_time' => $this->offer_active_time,
            'offer_active_range_start' => $this->offer_active_range_start,
            'offer_active_range_end' => $this->offer_active_range_end,
            'duration_start_date' => $this->duration_start_date,
            'duration_end_date' => $this->duration_end_date,
        ];
    }
}
