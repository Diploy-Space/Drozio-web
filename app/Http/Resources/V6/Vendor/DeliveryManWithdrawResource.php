<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryManWithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'amount' => (float) $this->amount,
            'approved' => (int) $this->approved,
            'transaction_note' => $this->transaction_note,
            'delivery_man_id' => $this->delivery_man_id === null ? null : (int) $this->delivery_man_id,
            'delivery_man' => $this->whenLoaded('deliveryMan', fn (): ?array => $this->deliveryMan === null ? null : [
                'id' => (int) $this->deliveryMan->id,
                'f_name' => $this->deliveryMan->f_name,
                'l_name' => $this->deliveryMan->l_name,
                'email' => $this->deliveryMan->email,
                'phone' => $this->deliveryMan->phone,
                'bank_name' => $this->deliveryMan->bank_name,
                'branch' => $this->deliveryMan->branch,
                'account_no' => $this->deliveryMan->account_no,
                'holder_name' => $this->deliveryMan->holder_name,
                'image_full_url' => $this->deliveryMan->image_full_url,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
