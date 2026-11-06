<?php

namespace Modules\TaxModule\app\Http\Resources\V6;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'tax_rate' => (float) $this->tax_rate,
        ];
    }
}
