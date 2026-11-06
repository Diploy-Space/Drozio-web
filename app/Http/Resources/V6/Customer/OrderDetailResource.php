<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    private const CONTROLLER_ATTRIBUTES = ['courier_shipment', 'courier_tracking_url', 'reviewData'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'seller_id' => $this->seller_id,
            'campaign_id' => $this->campaign_id,
            'digital_file_after_sell' => $this->digital_file_after_sell,
            'digital_file_after_sell_full_url' => $this->digital_file_after_sell_full_url,
            'product_details' => $this->product_details,
            'qty' => $this->qty,
            'price' => $this->price,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'tax_model' => $this->tax_model,
            'delivery_status' => $this->delivery_status,
            'payment_status' => $this->payment_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shipping_method_id' => $this->shipping_method_id,
            'variant' => $this->variant,
            'variation' => $this->variation,
            'discount_type' => $this->discount_type,
            'is_stock_decreased' => $this->is_stock_decreased,
            'refund_request' => $this->refund_request,
            'refund_started_at' => $this->refund_started_at,
            'product' => $this->whenLoaded('product', fn () => $this->product === null ? null : new ProductResource($this->product)),
            'product_all_status' => $this->whenLoaded('productAllStatus', fn () => $this->productAllStatus === null ? null : new ProductResource($this->productAllStatus)),
            'order' => $this->whenLoaded('order', fn () => $this->order === null ? null : new OrderResource($this->order)),
            'seller' => $this->whenLoaded('seller', fn () => $this->seller === null ? null : [
                'id' => $this->seller->id,
                'f_name' => $this->seller->f_name,
                'l_name' => $this->seller->l_name,
                'image_full_url' => $this->seller->image_full_url,
                ...($this->seller->relationLoaded('shop') ? ['shop' => $this->seller->shop === null ? null : [
                    'id' => $this->seller->shop->id,
                    'seller_id' => $this->seller->shop->seller_id,
                    'name' => $this->seller->shop->name,
                    'slug' => $this->seller->shop->slug,
                    'contact' => $this->seller->shop->contact,
                    'image_full_url' => $this->seller->shop->image_full_url,
                    'temporary_close' => $this->seller->shop->temporary_close,
                    'vacation_status' => $this->seller->shop->vacation_status,
                ]] : []),
            ]),
            'verification_images' => $this->whenLoaded('verificationImages', fn (): array => $this->verificationImages
                ->map(static fn ($image): array => [
                    'id' => $image->id,
                    'order_id' => $image->order_id,
                    'image' => $image->image,
                    'image_full_url' => $image->image_full_url,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ])->all()),
            'latest_edit_history' => $this->whenLoaded('latestEditHistory', fn () => $this->latestEditHistory === null ? null : [
                'id' => $this->latestEditHistory->id,
                'order_id' => $this->latestEditHistory->order_id,
                'edit_by' => $this->latestEditHistory->edit_by,
                'order_amount' => $this->latestEditHistory->order_amount,
                'order_due_amount' => $this->latestEditHistory->order_due_amount,
                'order_due_payment_status' => $this->latestEditHistory->order_due_payment_status,
                'order_due_payment_method' => $this->latestEditHistory->order_due_payment_method,
                'order_return_amount' => $this->latestEditHistory->order_return_amount,
                'order_return_payment_status' => $this->latestEditHistory->order_return_payment_status,
                'created_at' => $this->latestEditHistory->created_at,
                'updated_at' => $this->latestEditHistory->updated_at,
            ]),
            ...array_intersect_key($this->resource->getAttributes(), array_flip(self::CONTROLLER_ATTRIBUTES)),
        ];
    }
}
