<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestockRequestBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'image_alt_text' => $this->image_alt_text,
            'status' => (int) $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'brand_all_products_count' => $this->whenCounted('brandAllProducts'),
            'brand_product_order_details_count' => $this->whenCounted('brandProductOrderDetails'),
            'image_full_url' => $this->image_full_url,
            'product_count' => (int) ($this->product_count ?? 0),
        ];
    }
}
