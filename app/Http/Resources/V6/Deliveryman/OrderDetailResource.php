<?php

namespace App\Http\Resources\V6\Deliveryman;

use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
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
            'product_details' => $this->productDetailsData(),
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
            'variation' => is_array($this->variation) ? $this->variation : json_decode((string) $this->variation, true),
            'discount_type' => $this->discount_type,
            'is_stock_decreased' => $this->is_stock_decreased,
            'refund_request' => $this->refund_request,
            'refund_started_at' => $this->refund_started_at,
            'is_pause' => $this->resource->relationLoaded('order') ? $this->order?->is_pause : null,
            'order' => $this->whenLoaded('order', fn (): ?OrderResource => $this->order === null ? null : new OrderResource($this->order)),
            'latest_edit_history' => $this->whenLoaded('latestEditHistory', fn (): ?array => $this->latestEditHistory === null
                ? null
                : $this->editHistoryData(history: $this->latestEditHistory)),
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
            'order_due_payment_method' => $history->order_due_payment_method,
            'order_due_transaction_ref' => $history->order_due_transaction_ref,
            'order_due_payment_note' => $history->order_due_payment_note,
            'order_return_amount' => $history->order_return_amount,
            'order_return_payment_status' => $history->order_return_payment_status,
            'order_return_payment_method' => $history->order_return_payment_method,
            'order_return_transaction_ref' => $history->order_return_transaction_ref,
            'order_return_payment_note' => $history->order_return_payment_note,
            'created_at' => $history->created_at,
            'updated_at' => $history->updated_at,
        ];
    }

    private function productDetailsData(): mixed
    {
        $productDetails = is_array($this->product_details)
            ? $this->product_details
            : json_decode((string) $this->product_details, true);

        $product = is_array($productDetails) ? $productDetails : [];
        $product['thumbnail_full_url'] = $this->resource->relationLoaded('productAllStatus')
            ? $this->productAllStatus?->thumbnail_full_url
            : null;

        return Helpers::product_data_formatting_for_json_data($product);
    }
}
