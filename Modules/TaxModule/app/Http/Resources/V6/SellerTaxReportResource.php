<?php

namespace Modules\TaxModule\app\Http\Resources\V6;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerTaxReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => (int) $this->order_id,
            'order_amount' => (float) $this->order_amount,
            'tax_amount' => (float) $this->tax,
            'tax_groups' => $this->tax_groups,
        ];
    }
}
