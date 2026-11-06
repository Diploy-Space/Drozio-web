<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'withdraw_method_id' => $this->withdraw_method_id === null ? null : (int) $this->withdraw_method_id,
            'method_name' => $this->method_name,
            'method_info' => $this->method_info,
            'is_active' => (int) $this->is_active,
            'is_default' => (int) $this->is_default,
            'withdraw_method' => $this->whenLoaded('withdraw_method', fn (): ?array => $this->withdraw_method === null ? null : [
                'id' => (int) $this->withdraw_method->id,
                'method_name' => $this->withdraw_method->method_name,
                'method_fields' => $this->withdraw_method->method_fields,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
