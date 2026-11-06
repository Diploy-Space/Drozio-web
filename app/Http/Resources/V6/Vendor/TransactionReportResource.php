<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => (int) $this->resource['order_id'],
            'shop_name' => $this->resource['shop_name'],
            'is_guest' => (bool) $this->resource['is_guest'],
            'customer_id' => $this->resource['customer_id'],
            'customer_name' => $this->resource['customer_name'],
            'total_product_amount' => $this->resource['total_product_amount'],
            'product_discount' => $this->resource['product_discount'],
            'coupon_discount' => $this->resource['coupon_discount'],
            'referral_discount' => $this->resource['referral_discount'],
            'discounted_amount' => $this->resource['discounted_amount'],
            'tax' => $this->resource['tax'],
            'shipping_charge' => $this->resource['shipping_charge'],
            'order_amount' => $this->resource['order_amount'],
            'edit_due_amount' => $this->resource['edit_due_amount'],
            'edit_return_amount' => $this->resource['edit_return_amount'],
            'delivered_by' => $this->resource['delivered_by'],
            'deliveryman_incentive' => $this->resource['deliveryman_incentive'],
            'admin_discount' => $this->resource['admin_discount'],
            'vendor_discount' => $this->resource['vendor_discount'],
            'admin_commission' => $this->resource['admin_commission'],
            'vendor_net_income' => $this->resource['vendor_net_income'],
            'payment_method' => $this->resource['payment_method'],
            'payment_status' => $this->resource['payment_status'],
            'shipping_responsibility' => $this->resource['shipping_responsibility'],
        ];
    }
}
