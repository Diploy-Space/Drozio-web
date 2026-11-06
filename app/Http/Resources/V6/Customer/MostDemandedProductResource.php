<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MostDemandedProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'banner' => $this->banner ?? '',
            'product_id' => $this->product?->id ?? 0,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'banner_full_url' => $this->banner_full_url,
            'slug' => $this->product?->slug ?? '',
            'review_count' => $this->review_count ?? 0,
            'order_count' => $this->order_count ?? 0,
            'delivery_count' => $this->delivery_count ?? 0,
            'wishlist_count' => $this->wishlist_count ?? 0,
        ];
    }
}
