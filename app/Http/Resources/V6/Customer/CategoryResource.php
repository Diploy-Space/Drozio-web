<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'icon_full_url' => $this->icon_full_url,
            'product_count' => $this->product_count ?? $this->sub_category_product_count ?? 0,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'position' => $this->position,
            'home_status' => $this->home_status,
            'priority' => $this->priority,
            'sub_category_product_count' => $this->whenHas('sub_category_product_count'),
            'childes' => self::collection($this->whenLoaded('childes')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
