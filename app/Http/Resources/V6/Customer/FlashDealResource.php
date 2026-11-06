<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashDealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'banner' => $this->banner,
            'banner_full_url' => $this->banner_full_url,
            'background_color' => $this->background_color,
            'text_color' => $this->text_color,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'products_count' => $this->products_count ?? 0,
        ];
    }
}
