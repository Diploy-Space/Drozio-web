<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_amount' => (float) $this->order_amount,
            'deliveryman_charge' => (float) ($this->deliveryman_charge ?? 0),
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'shop' => $this->whenLoaded('seller', fn (): ?array => $this->seller?->shop === null ? null : [
                'id' => $this->seller->shop->id,
                'name' => $this->seller->shop->name,
            ]),
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => trim(($this->customer->f_name ?? '').' '.($this->customer->l_name ?? '')),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
