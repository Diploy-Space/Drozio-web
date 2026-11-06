<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClearanceSaleProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'setup_id' => $this->setup_id,
            'is_active' => (bool) $this->is_active,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'product' => $this->whenLoaded('product', fn () => new ProductOptionResource($this->product)),
        ];
    }
}
