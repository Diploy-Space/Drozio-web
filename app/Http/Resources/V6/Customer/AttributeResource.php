<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localizedName(),
        ];
    }

    private function localizedName(): ?string
    {
        if (! $this->relationLoaded('translations')) {
            return $this->name;
        }

        return $this->translations->firstWhere('key', 'name')?->value ?? $this->name;
    }
}
