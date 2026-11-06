<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'image' => $this->image,
            'image_full_url' => $this->image_full_url,
            'status' => $this->status,
            'app_language' => $this->app_language,
            'bank_name' => $this->bank_name,
            'branch' => $this->branch,
            'account_no' => $this->account_no,
            'holder_name' => $this->holder_name,
            'pos_status' => (int) $this->pos_status,
            'gst' => $this->gst,
            'sales_commission_percentage' => $this->sales_commission_percentage,
            'minimum_order_amount' => (float) $this->minimum_order_amount,
            'free_delivery_status' => (int) $this->free_delivery_status,
            'free_delivery_over_amount' => (float) $this->free_delivery_over_amount,
            'wholesale_free_delivery_over_amount' => (float) $this->wholesale_free_delivery_over_amount,
            'wholesale_free_delivery_status' => (int) $this->wholesale_free_delivery_status,
            'wholesale_minimum_order_amount' => (float) $this->wholesale_minimum_order_amount,
            'is_verified' => (bool) ($this->is_verified ?? false),
            'stock_limit' => $this->stock_limit,
            'created_at' => $this->created_at,
            'wallet' => $this->whenLoaded('wallet', fn (): array => [
                'total_earning' => (float) $this->wallet->total_earning,
                'withdrawn' => (float) $this->wallet->withdrawn,
                'commission_given' => (float) $this->wallet->commission_given,
                'pending_withdraw' => (float) $this->wallet->pending_withdraw,
                'delivery_charge_earned' => (float) $this->wallet->delivery_charge_earned,
                'collected_cash' => (float) $this->wallet->collected_cash,
                'total_tax_collected' => (float) $this->wallet->total_tax_collected,
            ]),
        ];
    }
}
