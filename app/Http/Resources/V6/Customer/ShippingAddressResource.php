<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_person_name' => $this->contact_person_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_type' => $this->address_type,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_billing' => (bool) $this->is_billing,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
