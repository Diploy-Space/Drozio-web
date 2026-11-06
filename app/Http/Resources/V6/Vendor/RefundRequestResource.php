<?php

namespace App\Http\Resources\V6\Vendor;

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
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'f_name' => $this->customer->f_name,
                'l_name' => $this->customer->l_name,
                'phone' => $this->customer->phone,
                'email' => $this->customer->email,
                'image_full_url' => $this->customer->image_full_url,
            ]),
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product === null ? null : [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'product_type' => $this->product->product_type,
                'unit_price' => (float) $this->product->unit_price,
                'discount' => (float) $this->product->discount,
                'discount_type' => $this->product->discount_type,
                'thumbnail_full_url' => $this->product->thumbnail_full_url,
            ]),
            'order_details' => $this->whenLoaded('orderDetails', fn (): ?array => $this->orderDetails === null ? null : [
                'id' => $this->orderDetails->id,
                'order_id' => $this->orderDetails->order_id,
                'product_id' => $this->orderDetails->product_id,
                'seller_id' => $this->orderDetails->seller_id,
                'qty' => (int) $this->orderDetails->qty,
                'price' => (float) $this->orderDetails->price,
                'tax' => (float) $this->orderDetails->tax,
                'discount' => (float) $this->orderDetails->discount,
                'tax_model' => $this->orderDetails->tax_model,
                'delivery_status' => $this->orderDetails->delivery_status,
                'payment_status' => $this->orderDetails->payment_status,
                'variant' => $this->orderDetails->variant,
                'variation' => $this->orderDetails->variation,
                'refund_request' => (int) $this->orderDetails->refund_request,
                'created_at' => $this->orderDetails->created_at,
                'updated_at' => $this->orderDetails->updated_at,
            ]),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order === null ? null : [
                'id' => $this->order->id,
                'payment_method' => $this->order->payment_method,
                'payment_status' => $this->order->payment_status,
                'order_type' => $this->order->order_type,
            ]),
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
