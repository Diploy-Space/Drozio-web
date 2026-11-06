<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    private const AGGREGATE_ATTRIBUTES = ['order_details_count', 'order_details_sum_qty'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_module' => $this->business_module,
            'customer_id' => $this->customer_id,
            'is_guest' => $this->is_guest,
            'customer_type' => $this->customer_type,
            'payment_status' => $this->payment_status,
            'order_status' => $this->order_status,
            'payment_method' => $this->payment_method,
            'transaction_ref' => $this->transaction_ref,
            'payment_by' => $this->payment_by,
            'payment_note' => $this->payment_note,
            'order_amount' => $this->order_amount,
            'init_order_amount' => $this->init_order_amount,
            'edit_due_amount' => $this->edit_due_amount,
            'edit_return_amount' => $this->edit_return_amount,
            'total_tax_amount' => $this->total_tax_amount,
            'tax_type' => $this->tax_type,
            'tax_model' => $this->tax_model,
            'paid_amount' => $this->paid_amount,
            'bring_change_amount' => $this->bring_change_amount,
            'bring_change_amount_currency' => $this->bring_change_amount_currency,
            'is_pause' => $this->is_pause,
            'cause' => $this->cause,
            'shipping_address' => $this->getAttribute('shipping_address'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'coupon_code' => $this->coupon_code,
            'coupon_discount_bearer' => $this->coupon_discount_bearer,
            'shipping_responsibility' => $this->shipping_responsibility,
            'shipping_method_id' => $this->shipping_method_id,
            'shipping_cost' => $this->shipping_cost,
            'is_shipping_free' => $this->is_shipping_free,
            'order_group_id' => $this->order_group_id,
            'verification_code' => $this->verification_code,
            'verification_status' => $this->verification_status,
            'seller_id' => $this->seller_id,
            'seller_is' => $this->seller_is,
            'shipping_address_data' => $this->shipping_address_data,
            'delivery_man_id' => $this->delivery_man_id,
            'deliveryman_assigned_at' => $this->deliveryman_assigned_at,
            'deliveryman_charge' => $this->deliveryman_charge,
            'expected_delivery_date' => $this->expected_delivery_date,
            'order_note' => $this->order_note,
            'billing_address' => $this->getAttribute('billing_address'),
            'billing_address_data' => $this->billing_address_data,
            'order_type' => $this->order_type,
            'extra_discount' => $this->extra_discount,
            'extra_discount_type' => $this->extra_discount_type,
            'refer_and_earn_discount' => $this->refer_and_earn_discount,
            'free_delivery_bearer' => $this->free_delivery_bearer,
            'checked' => $this->checked,
            'shipping_type' => $this->shipping_type,
            'delivery_type' => $this->delivery_type,
            'delivery_service_name' => $this->delivery_service_name,
            'third_party_delivery_tracking_id' => $this->third_party_delivery_tracking_id,
            'edited_status' => $this->edited_status,
            ...$this->aggregateAttributes(),
            'details' => OrderDetailResource::collection($this->whenLoaded('details')),
            'delivery_man' => $this->whenLoaded('deliveryMan', fn (): ?array => $this->deliveryManData()),
            'delivery_man_review' => $this->whenLoaded('deliveryManReview', fn (): ?array => $this->reviewData()),
            'seller' => $this->whenLoaded('seller', fn (): ?array => $this->sellerData()),
            'shipping' => $this->whenLoaded('shipping', fn (): ?array => $this->shipping === null ? null : [
                'id' => $this->shipping->id,
                'creator_id' => $this->shipping->creator_id,
                'creator_type' => $this->shipping->creator_type,
                'title' => $this->shipping->title,
                'cost' => $this->shipping->cost,
                'duration' => $this->shipping->duration,
                'status' => $this->shipping->status,
                'created_at' => $this->shipping->created_at,
                'updated_at' => $this->shipping->updated_at,
            ]),
            'offline_payments' => $this->whenLoaded('offlinePayments', fn (): ?array => $this->offlinePayments === null ? null : [
                'id' => $this->offlinePayments->id,
                'order_id' => $this->offlinePayments->order_id,
                'payment_info' => $this->offlinePayments->payment_info,
                'created_at' => $this->offlinePayments->created_at,
                'updated_at' => $this->offlinePayments->updated_at,
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
            'latest_edit_history' => $this->whenLoaded('latestEditHistory', fn (): ?array => $this->latestEditHistory === null ? null : $this->editHistoryData($this->latestEditHistory)),
            'order_edit_history' => $this->whenLoaded('orderEditHistory', fn (): array => $this->orderEditHistory
                ->map(fn ($history): array => $this->editHistoryData($history))->all()),
            'order_status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('orderStatusHistory')),
        ];
    }

    private function aggregateAttributes(): array
    {
        return array_intersect_key($this->resource->getAttributes(), array_flip(self::AGGREGATE_ATTRIBUTES));
    }

    private function deliveryManData(): ?array
    {
        $deliveryMan = $this->deliveryMan;

        if ($deliveryMan === null) {
            return null;
        }

        return [
            'id' => $deliveryMan->id,
            'seller_id' => $deliveryMan->seller_id,
            'f_name' => $deliveryMan->f_name,
            'l_name' => $deliveryMan->l_name,
            'address' => $deliveryMan->address,
            'country_code' => $deliveryMan->country_code,
            'phone' => $deliveryMan->phone,
            'full_phone' => $deliveryMan->full_phone,
            'email' => $deliveryMan->email,
            'image' => $deliveryMan->image,
            'image_full_url' => $deliveryMan->image_full_url,
            'is_active' => $deliveryMan->is_active,
            'is_online' => $deliveryMan->is_online,
            'app_language' => $deliveryMan->app_language,
            'created_at' => $deliveryMan->created_at,
            'updated_at' => $deliveryMan->updated_at,
            ...array_intersect_key($deliveryMan->getAttributes(), array_flip(['review_count'])),
            ...($deliveryMan->relationLoaded('rating') ? ['rating' => $deliveryMan->rating] : []),
        ];
    }

    private function reviewData(): ?array
    {
        $review = $this->deliveryManReview;

        if ($review === null) {
            return null;
        }

        return [
            'id' => $review->id,
            'product_id' => $review->product_id,
            'customer_id' => $review->customer_id,
            'delivery_man_id' => $review->delivery_man_id,
            'order_id' => $review->order_id,
            'comment' => $review->comment,
            'attachment' => $review->attachment,
            'attachment_full_url' => $review->attachment_full_url,
            'rating' => $review->rating,
            'status' => $review->status,
            'is_saved' => $review->is_saved,
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
    }

    private function sellerData(): ?array
    {
        $seller = $this->seller;

        if ($seller === null) {
            return null;
        }

        return [
            'id' => $seller->id,
            'f_name' => $seller->f_name,
            'l_name' => $seller->l_name,
            'phone' => $seller->phone,
            'image' => $seller->image,
            'image_full_url' => $seller->image_full_url,
            'email' => $seller->email,
            'status' => $seller->status,
            'created_at' => $seller->created_at,
            'updated_at' => $seller->updated_at,
            ...($seller->relationLoaded('shop') ? ['shop' => $seller->shop === null ? null : [
                'id' => $seller->shop->id,
                'seller_id' => $seller->shop->seller_id,
                'author_type' => $seller->shop->author_type,
                'name' => $seller->shop->name,
                'slug' => $seller->shop->slug,
                'address' => $seller->shop->address,
                'contact' => $seller->shop->contact,
                'image' => $seller->shop->image,
                'image_full_url' => $seller->shop->image_full_url,
                'banner_full_url' => $seller->shop->banner_full_url,
                'bottom_banner_full_url' => $seller->shop->bottom_banner_full_url,
                'offer_banner_full_url' => $seller->shop->offer_banner_full_url,
                'vacation_start_date' => $seller->shop->vacation_start_date,
                'vacation_end_date' => $seller->shop->vacation_end_date,
                'vacation_note' => $seller->shop->vacation_note,
                'vacation_status' => $seller->shop->vacation_status,
                'temporary_close' => $seller->shop->temporary_close,
                'created_at' => $seller->shop->created_at,
                'updated_at' => $seller->shop->updated_at,
            ]] : []),
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
