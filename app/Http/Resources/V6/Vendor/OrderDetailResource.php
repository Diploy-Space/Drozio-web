<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    private const CONTROLLER_ATTRIBUTES = [
        'delivery_partner_available',
        'courier_shipment',
        'modified_variation',
        'current_stock',
        'current_price',
        'edit_order_payment_histories',
    ];

    public function toArray(Request $request): array
    {
        $controllerAttributes = array_intersect_key($this->resource->getAttributes(), array_flip(self::CONTROLLER_ATTRIBUTES));

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
            'variation' => array_key_exists('modified_variation', $controllerAttributes) ? $controllerAttributes['modified_variation'] : $this->variation,
            'discount_type' => $this->discount_type,
            'is_stock_decreased' => $this->is_stock_decreased,
            'refund_request' => $this->refund_request,
            'refund_started_at' => $this->refund_started_at,
            'order' => $this->whenLoaded('order', fn () => $this->order === null ? null : new OrderResource($this->order)),
            'product_all_status' => $this->whenLoaded('productAllStatus', fn () => $this->productAllStatus === null ? null : new ProductListResource($this->productAllStatus)),
            'verification_images' => $this->whenLoaded('verificationImages', fn (): array => $this->verificationImages
                ->map(static fn ($image): array => [
                    'id' => $image->id,
                    'order_id' => $image->order_id,
                    'image' => $image->image,
                    'image_full_url' => $image->image_full_url,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ])->all()),
            'latest_edit_history' => $this->whenLoaded('latestEditHistory', fn () => $this->latestEditHistory === null ? null : $this->editHistoryData($this->latestEditHistory)),
            'order_edit_history' => $this->whenLoaded('orderEditHistory', fn (): array => $this->orderEditHistory
                ->map(fn ($history): array => $this->editHistoryData($history))->all()),
            ...$controllerAttributes,
        ];
    }

    private function editHistoryData(object $history): array
    {
        return [
            'id' => $history->id,
            'u_id' => $history->u_id,
            'order_id' => $history->order_id,
            'edit_by' => $history->edit_by,
            'edited_user_id' => $history->edited_user_id,
            'edited_user_name' => $history->edited_user_name,
            'order_amount' => $history->order_amount,
            'order_due_amount' => $history->order_due_amount,
            'order_due_payment_status' => $history->order_due_payment_status,
            'order_due_payment_info' => $history->order_due_payment_info,
            'order_due_payment_method' => $history->order_due_payment_method,
            'order_due_transaction_ref' => $history->order_due_transaction_ref,
            'order_due_payment_note' => $history->order_due_payment_note,
            'order_return_amount' => $history->order_return_amount,
            'order_return_payment_status' => $history->order_return_payment_status,
            'order_return_payment_method' => $history->order_return_payment_method,
            'order_return_payment_info' => $history->order_return_payment_info,
            'order_return_transaction_ref' => $history->order_return_transaction_ref,
            'order_return_payment_note' => $history->order_return_payment_note,
            'created_at' => $history->created_at,
            'updated_at' => $history->updated_at,
        ];
    }
}
