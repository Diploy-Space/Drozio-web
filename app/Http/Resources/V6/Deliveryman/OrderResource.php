<?php

namespace App\Http\Resources\V6\Deliveryman;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
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
            'shipping_address' => $this->resource->relationLoaded('shippingAddress')
                ? $this->addressData(address: $this->shippingAddress)
                : $this->getAttribute('shipping_address'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'discount_amount' => $this->discount_amount,
            'discount_type' => $this->discount_type,
            'coupon_code' => $this->coupon_code,
            'shipping_responsibility' => $this->shipping_responsibility,
            'shipping_method_id' => $this->shipping_method_id,
            'shipping_cost' => $this->shipping_cost,
            'is_shipping_free' => $this->is_shipping_free,
            'order_group_id' => $this->order_group_id,
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
            'shipping_type' => $this->shipping_type,
            'delivery_type' => $this->delivery_type,
            'delivery_service_name' => $this->delivery_service_name,
            'third_party_delivery_tracking_id' => $this->third_party_delivery_tracking_id,
            'edited_status' => $this->edited_status,
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'f_name' => $this->customer->f_name,
                'l_name' => $this->customer->l_name,
                'phone' => $this->customer->phone,
                'image' => $this->customer->image,
                'image_full_url' => $this->customer->image_full_url,
            ]),
            'seller' => $this->whenLoaded('seller', fn (): ?array => $this->seller === null ? null : [
                'id' => $this->seller->id,
                'f_name' => $this->seller->f_name,
                'l_name' => $this->seller->l_name,
                'phone' => $this->seller->phone,
                'image' => $this->seller->image,
                'image_full_url' => $this->seller->image_full_url,
                ...($this->seller->relationLoaded('shop') ? ['shop' => $this->seller->shop === null ? null : [
                    'id' => $this->seller->shop->id,
                    'seller_id' => $this->seller->shop->seller_id,
                    'name' => $this->seller->shop->name,
                    'slug' => $this->seller->shop->slug,
                    'address' => $this->seller->shop->address,
                    'contact' => $this->seller->shop->contact,
                    'image' => $this->seller->shop->image,
                    'image_full_url' => $this->seller->shop->image_full_url,
                ]] : []),
            ]),
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

    private function addressData(?object $address): ?array
    {
        return $address === null ? null : [
            'id' => $address->id,
            'customer_id' => $address->customer_id,
            'is_guest' => $address->is_guest,
            'contact_person_name' => $address->contact_person_name,
            'email' => $address->email,
            'address_type' => $address->address_type,
            'address' => $address->address,
            'city' => $address->city,
            'zip' => $address->zip,
            'phone' => $address->phone,
            'state' => $address->state,
            'country' => $address->country,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_billing' => $address->is_billing,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];
    }
}
