<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'details' => $this->details,
            'colors_formatted' => $this->whenHas('colors_formatted'),
            'average_review' => $this->whenHas('average_review'),
            'digital_product_authors_names' => $this->whenHas('digital_product_authors_names'),
            'digital_product_publishing_house_names' => $this->whenHas('digital_product_publishing_house_names'),
            'restock_requested_list' => $this->whenHas('restock_requested_list'),
            'is_restock_requested' => $this->whenHas('is_restock_requested', fn ($flag) => (int) $flag),
            'digital_variation' => $this->whenLoaded('digitalVariation', fn () => $this->digitalVariation->map(fn ($variation) => [
                'id' => $variation->id,
                'product_id' => $variation->product_id,
                'variant_key' => $variation->variant_key,
                'sku' => $variation->sku,
                'price' => $variation->price,
            ])->values()),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'product_id' => $review->product_id,
                'customer_id' => $review->customer_id,
                'order_id' => $review->order_id,
                'comment' => $review->comment,
                'rating' => $review->rating,
                'status' => $review->status,
                'attachment_full_url' => $review->attachment_full_url,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
                'customer' => $review->relationLoaded('customer') && $review->customer !== null ? [
                    'id' => $review->customer->id,
                    'f_name' => $review->customer->f_name,
                    'l_name' => $review->customer->l_name,
                    'image_full_url' => $review->customer->image_full_url,
                ] : null,
            ])->values()),
        ];
    }
}
