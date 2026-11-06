<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product_full_info' => $this->whenLoaded(
                'productFullInfo',
                fn () => $this->productFullInfo === null ? null : new ProductResource($this->productFullInfo),
            ),
        ];
    }
}
