<?php

namespace App\Http\Resources\V6\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'method_name' => $this->method_name,
            'method_fields' => $this->method_fields,
            'is_default' => (int) $this->is_default,
        ];
    }
}
