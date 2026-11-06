<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;

class ProductEditResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'business_module' => $this->business_module,
            'featured_status' => $this->featured_status,
            'flash_deal' => $this->retailDetail?->flash_deal,
            'meta_image' => $this->meta_image,
            'thumbnail_storage_type' => $this->thumbnail_storage_type,
            'video_file' => $this->video_file,
            'video_file_storage_type' => $this->video_file_storage_type,
            'digital_file_ready_storage_type' => $this->retailDetail?->digital_file_ready_storage_type,
            'preview_file_storage_type' => $this->retailDetail?->preview_file_storage_type,
            'color_image' => $this->retailDetail?->color_image ?? '',
            'is_shop_temporary_close' => $this->is_shop_temporary_close,
            'thumbnail_full_url' => $this->thumbnail_full_url,
            'images_full_url' => $this->images_full_url,
            'color_images_full_url' => $this->color_images_full_url,
            'colors_formatted' => $this->whenHas('colors_formatted'),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'product_id' => $review->product_id,
                'customer_id' => $review->customer_id,
                'delivery_man_id' => $review->delivery_man_id,
                'order_id' => $review->order_id,
                'comment' => $review->comment,
                'attachment_full_url' => $review->attachment_full_url,
                'rating' => $review->rating,
                'status' => $review->status,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ])->values()),
        ];
    }
}
