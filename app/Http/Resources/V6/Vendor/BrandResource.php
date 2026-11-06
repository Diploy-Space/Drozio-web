<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'image_storage_type' => $this->image_storage_type,
            'image_alt_text' => $this->image_alt_text,
            'status' => (int) $this->status,
            'business_module' => $this->business_module,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'image_full_url' => $this->image_full_url,
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->map(fn ($translation) => [
                'translationable_type' => $translation->translationable_type,
                'translationable_id' => $translation->translationable_id,
                'locale' => $translation->locale,
                'key' => $translation->key,
                'value' => $translation->value,
            ])->values()),
        ];
    }
}
