<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'status' => (int) $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
