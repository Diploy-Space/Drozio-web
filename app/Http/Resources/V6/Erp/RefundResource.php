<?php

namespace App\Http\Resources\V6\Erp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $store = $order?->seller?->shop;
        $customer = $order?->customer;
        $firstImage = $this->images_full_url[0] ?? null;

        return [
            'id' => (string) $this->id,
            'order_id' => $this->order_id,
            'user_id' => $this->customer_id,
            'refund_amount' => $this->amount,
            'refund_status' => $this->status,
            'refund_method' => $this->payment_info,
            'customer_reason' => $this->refund_reason,
            'customer_note' => null,
            'admin_note' => $this->approved_note ?? $this->rejected_note,
            'image_full_url' => is_array($firstImage) ? ($firstImage['path'] ?? null) : $firstImage,
            'order' => $order === null ? null : [
                'order_amount' => $order->order_amount,
                'order_status' => $order->order_status,
                'store' => [
                    'name' => $store->name ?? null,
                ],
                'customer' => [
                    'id' => $customer->id ?? null,
                    'email' => $customer->email ?? null,
                    'f_name' => $customer->f_name ?? null,
                    'l_name' => $customer->l_name ?? null,
                    'phone' => $customer->phone ?? null,
                ],
            ],
        ];
    }
}
