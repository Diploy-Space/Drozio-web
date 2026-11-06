<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_id' => $this->customer_id,
            'delivery_man_id' => $this->delivery_man_id,
            'order_id' => $this->order_id,
            'comment' => $this->comment,
            'attachment' => $this->attachment,
            'attachment_full_url' => $this->attachment_full_url,
            'rating' => (int) $this->rating,
            'status' => (int) $this->status,
            'is_saved' => (bool) $this->is_saved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'customer' => $this->whenLoaded('customer', fn (): ?array => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'f_name' => $this->customer->f_name,
                'l_name' => $this->customer->l_name,
                'image_full_url' => $this->customer->image_full_url,
            ]),
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product === null ? null : [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'thumbnail_full_url' => $this->product->thumbnail_full_url,
            ]),
            'reply' => $this->whenLoaded('reply', fn (): ?array => $this->reply === null ? null : [
                'id' => $this->reply->id,
                'review_id' => $this->reply->review_id,
                'added_by' => $this->reply->added_by,
                'added_by_id' => $this->reply->added_by_id,
                'reply_text' => $this->reply->reply_text,
                'created_at' => $this->reply->created_at,
                'updated_at' => $this->reply->updated_at,
            ]),
        ];
    }
}
