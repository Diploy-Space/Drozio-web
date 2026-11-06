<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'banner_type' => $this->banner_type,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'button_text' => $this->button_text,
            'background_color' => $this->background_color,
            'photo' => $this->photo,
            'photo_full_url' => $this->photo_full_url,
            'url' => $this->url,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'resource' => $this->whenLoaded('linkedResource', fn () => [
                'id' => $this->linkedResource->id,
                'name' => $this->linkedResource->name,
                'slug' => $this->linkedResource->slug,
            ]),
        ];
    }
}
