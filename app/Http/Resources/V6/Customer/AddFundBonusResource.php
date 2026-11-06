<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddFundBonusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'bonus_type' => $this->bonus_type,
            'bonus_amount' => (float) $this->bonus_amount,
            'min_add_money_amount' => (float) $this->min_add_money_amount,
            'max_bonus_amount' => (float) $this->max_bonus_amount,
            'description' => $this->description,
            'start_date_time' => $this->start_date_time,
            'end_date_time' => $this->end_date_time,
        ];
    }
}
