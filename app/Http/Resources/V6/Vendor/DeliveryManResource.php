<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryManResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'email' => $this->email,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'address' => $this->address,
            'identity_number' => $this->identity_number,
            'identity_type' => $this->identity_type,
            'image_full_url' => $this->image_full_url,
            'identity_image_full_url' => $this->identity_images_full_url,
            'is_active' => (int) $this->is_active,
            'is_online' => (int) $this->is_online,
            'app_language' => $this->app_language,
            'bank_name' => $this->bank_name,
            'branch' => $this->branch,
            'account_no' => $this->account_no,
            'holder_name' => $this->holder_name,
            'orders_count' => (int) ($this->orders_count ?? $this->delivered_orders_count ?? 0),
            'average_rating' => round((float) ($this->rating?->first()?->average ?? 0), 2),
            'wallet' => $this->whenLoaded('wallet', fn (): array => [
                'current_balance' => (float) $this->wallet->current_balance,
                'cash_in_hand' => (float) $this->wallet->cash_in_hand,
                'pending_withdraw' => (float) $this->wallet->pending_withdraw,
                'total_withdraw' => (float) $this->wallet->total_withdraw,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
