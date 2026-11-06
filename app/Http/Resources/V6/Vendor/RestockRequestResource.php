<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestockRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => (int) $this->product_id,
            'variant' => $this->variant,
            'variant_keys' => $this->variant_keys ?? [],
            'restock_product_customers_count' => $this->whenCounted('restockProductCustomers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => $this->whenLoaded('product', fn (): ?ProductListResource => $this->product === null ? null : ProductListResource::make($this->product)),
        ];
    }
}
