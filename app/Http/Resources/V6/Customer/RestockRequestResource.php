<?php

namespace App\Http\Resources\V6\Customer;

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
            'restock_product_customers_count' => $this->whenCounted('restockProductCustomers'),
            'fcm_topic' => getRestockProductFCMTopic(restockRequest: $this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => $this->whenLoaded('product', fn (): ?ProductResource => $this->product === null ? null : ProductResource::make($this->product)),
        ];
    }
}
