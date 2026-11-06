<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_details_id' => (int) $this->order_details_id,
            'customer_id' => (int) $this->customer_id,
            'status' => $this->status,
            'approved_count' => (int) $this->approved_count,
            'denied_count' => (int) $this->denied_count,
            'amount' => (float) $this->amount,
            'product_id' => (int) $this->product_id,
            'order_id' => (int) $this->order_id,
            'refund_reason' => $this->refund_reason,
            'images' => $this->images,
            'images_full_url' => $this->images_full_url,
            'approved_note' => $this->approved_note,
            'rejected_note' => $this->rejected_note,
            'payment_info' => $this->payment_info,
            'change_by' => $this->change_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'refund_status' => $this->whenLoaded('refundStatus', fn (): array => $this->refundStatus->map(static fn ($status): array => [
                'id' => $status->id,
                'refund_request_id' => (int) $status->refund_request_id,
                'change_by' => $status->change_by,
                'change_by_id' => $status->change_by_id,
                'status' => $status->status,
                'message' => $status->message,
                'created_at' => $status->created_at,
                'updated_at' => $status->updated_at,
            ])->all()),
        ];
    }
}
