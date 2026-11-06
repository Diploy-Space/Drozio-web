<?php

namespace App\Http\Resources\V6\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => (bool) $this->status,
            'is_default' => (bool) $this->default_status,
            'banner_full_url' => $this->banner_full_url,
        ];
    }
}
