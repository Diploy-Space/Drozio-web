<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'image_full_url' => $this->image_full_url,
            'wallet_balance' => (float) $this->wallet_balance,
            'country' => $this->country,
            'city' => $this->city,
            'zip' => $this->zip,
            'street_address' => $this->street_address,
        ];
    }
}
