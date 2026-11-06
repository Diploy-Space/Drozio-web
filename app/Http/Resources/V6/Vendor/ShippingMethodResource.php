<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'duration' => $this->duration,
            'cost' => (float) $this->cost,
            'status' => (int) $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
