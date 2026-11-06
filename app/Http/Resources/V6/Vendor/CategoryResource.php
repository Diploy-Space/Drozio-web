<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'icon_storage_type' => $this->icon_storage_type,
            'parent_id' => $this->parent_id,
            'position' => $this->position,
            'business_module' => $this->business_module,
            'home_status' => $this->home_status,
            'auction_home_status' => $this->auction_home_status,
            'priority' => $this->priority,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sub_category_product_count' => $this->whenHas('sub_category_product_count', fn ($count) => (int) $count),
            'sub_sub_category_product_count' => $this->whenHas('sub_sub_category_product_count', fn ($count) => (int) $count),
            'icon_full_url' => $this->icon_full_url,
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->map(fn ($translation) => [
                'translationable_type' => $translation->translationable_type,
                'translationable_id' => $translation->translationable_id,
                'locale' => $translation->locale,
                'key' => $translation->key,
                'value' => $translation->value,
            ])->values()),
            'childes' => $this->whenLoaded('childes', fn () => self::collection($this->childes)->resolve()),
        ];
    }
}
