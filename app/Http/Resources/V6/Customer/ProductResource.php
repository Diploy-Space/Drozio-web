<?php

namespace App\Http\Resources\V6\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,
            'thumbnail_full_url' => $this->thumbnail_full_url,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'current_stock' => $this->current_stock,
            'minimum_order_qty' => $this->minimum_order_qty,
            'product_type' => $this->product_type,
            'added_by' => $this->added_by,
            'rating' => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'order_count' => (int) $this->order_count,
            'shop' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller?->shop?->id,
                'name' => $this->seller?->shop?->name,
            ]),
            'deal' => $this->whenLoaded('flashDealProducts', fn () => [
                'discount' => $this->flashDealProducts->first()?->discount,
                'discount_type' => $this->flashDealProducts->first()?->discount_type,
            ]),
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
            'category_ids' => $this->whenHas('category_ids', fn () => json_decode((string) $this->getRawOriginal('category_ids'), true) ?? []),
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'sub_sub_category_id' => $this->sub_sub_category_id,
            'brand_id' => $this->brand_id,
            'unit' => $this->unit,
            'code' => $this->code,
            'images' => $this->whenHas('images', fn () => json_decode((string) $this->getRawOriginal('images'), true) ?? []),
            'images_full_url' => $this->whenHas('images', fn () => $this->images_full_url),
            'color_images_full_url' => $this->color_images_full_url,
            'colors' => $this->whenHas('colors', fn () => json_decode((string) $this->getRawOriginal('colors'), true) ?? []),
            'attributes' => $this->whenHas('attributes', fn () => array_map('intval', json_decode((string) $this->getRawOriginal('attributes'), true) ?? [])),
            'choice_options' => $this->whenHas('choice_options', fn () => json_decode((string) $this->getRawOriginal('choice_options'), true) ?? []),
            'variation' => $this->formattedVariation(),
            'tax' => $this->tax,
            'tax_type' => $this->tax_type,
            'tax_model' => $this->tax_model,
            'shipping_cost' => $this->shipping_cost,
            'multiply_qty' => $this->multiply_qty,
            'video_provider' => $this->video_provider,
            'video_url' => $this->video_url,
            'status' => $this->status,
            'featured_status' => $this->featured_status,
            'request_status' => $this->request_status,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_image_full_url' => $this->whenHas('meta_image', fn () => $this->meta_image_full_url),
            'digital_product_type' => $this->retailDetail?->digital_product_type,
            'digital_product_file_types' => $this->retailDetail?->digital_product_file_types,
            'digital_product_extensions' => $this->retailDetail?->digital_product_extensions,
            'preview_file_full_url' => $this->preview_file_full_url,
            'business_module' => $this->whenHas('business_module'),
            'details' => $this->whenHas('details', fn () => $this->details),
            'color_image' => json_decode((string) $this->retailDetail?->color_image, true) ?? [],
            'colors_formatted' => $this->whenHas('colors_formatted'),
            'digital_product_authors_names' => $this->whenLoaded('digitalProductAuthors', fn () => $this->digitalProductAuthors
                ->map(fn ($row) => $row->relationLoaded('author') ? $row->author?->name : null)
                ->filter()
                ->values()),
            'digital_product_publishing_house_names' => $this->whenLoaded('digitalProductPublishingHouse', fn () => $this->digitalProductPublishingHouse
                ->map(fn ($row) => $row->relationLoaded('publishingHouse') ? $row->publishingHouse?->name : null)
                ->filter()
                ->values()),
            'digital_product_authors' => $this->whenLoaded('digitalProductAuthors', fn () => $this->digitalProductAuthors->map(fn ($row) => [
                'id' => $row->id,
                'author_id' => $row->author_id,
                'product_id' => $row->product_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'author' => $row->relationLoaded('author') && $row->author !== null ? [
                    'id' => $row->author->id,
                    'name' => $row->author->name,
                    'created_at' => $row->author->created_at,
                    'updated_at' => $row->author->updated_at,
                ] : null,
            ])->values()),
            'digital_product_publishing_house' => $this->whenLoaded('digitalProductPublishingHouse', fn () => $this->digitalProductPublishingHouse->map(fn ($row) => [
                'id' => $row->id,
                'publishing_house_id' => $row->publishing_house_id,
                'product_id' => $row->product_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'publishing_house' => $row->relationLoaded('publishingHouse') && $row->publishingHouse !== null ? [
                    'id' => $row->publishingHouse->id,
                    'name' => $row->publishingHouse->name,
                    'created_at' => $row->publishingHouse->created_at,
                    'updated_at' => $row->publishingHouse->updated_at,
                ] : null,
            ])->values()),
            'featured' => $this->retailDetail?->featured,
            'is_shop_temporary_close' => $this->is_shop_temporary_close,
            'reviews_count' => $this->whenHas('reviews_count', fn ($count) => (int) $count),
            'reviews_avg_rating' => $this->whenHas('reviews_avg_rating'),
            'order_details_count' => $this->whenHas('order_details_count', fn ($count) => (int) $count),
            'order_delivered_count' => $this->whenHas('order_delivered_count', fn ($count) => (int) $count),
            'order_details_sum_qty' => $this->whenHas('order_details_sum_qty'),
            'tags_sum_visit_count' => $this->whenHas('tags_sum_visit_count'),
            'wish_list_count' => $this->whenHas('wish_list_count', fn ($count) => (int) $count),
            'is_in_compare_list' => $this->whenHas('is_in_compare_list', fn ($flag) => (int) $flag),
            'flash_deal_status' => $this->whenHas('flash_deal_status', fn ($status) => (int) $status),
            'flash_deal_end_date' => $this->whenHas('flash_deal_end_date'),
            'flash_deal_end_at' => $this->whenHas('flash_deal_end_at'),
            'rating_details' => $this->whenLoaded('rating', fn () => $this->rating->map(fn ($rating) => [
                'average' => $rating->average,
                'product_id' => $rating->product_id,
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'tag' => $tag->tag,
            ])->values()),
            'seller' => $this->whenLoaded('seller', fn () => $this->seller === null ? null : [
                'id' => $this->seller->id,
                'f_name' => $this->seller->f_name,
                'l_name' => $this->seller->l_name,
                'image_full_url' => $this->seller->image_full_url,
                'shop' => $this->seller->shop === null ? null : [
                    'id' => $this->seller->shop->id,
                    'name' => $this->seller->shop->name,
                    'slug' => $this->seller->shop->slug,
                    'image_full_url' => $this->seller->shop->image_full_url,
                    'temporary_close' => $this->seller->shop->temporary_close,
                    'vacation_status' => $this->seller->shop->vacation_status,
                    'vacation_start_date' => $this->seller->shop->vacation_start_date,
                    'vacation_end_date' => $this->seller->shop->vacation_end_date,
                ],
            ]),
            'clearance_sale' => $this->whenLoaded('clearanceSale', fn () => $this->clearanceSale === null ? null : [
                'id' => $this->clearanceSale->id,
                'product_id' => $this->clearanceSale->product_id,
                'setup_id' => $this->clearanceSale->setup_id,
                'discount_type' => $this->clearanceSale->discount_type,
                'discount_amount' => $this->clearanceSale->discount_amount,
                'is_active' => $this->clearanceSale->is_active,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formattedVariation(): array
    {
        $variations = json_decode((string) $this->retailDetail?->variation, true);

        return array_map(static fn (array $variation): array => [
            'type' => $variation['type'] ?? null,
            'price' => (float) ($variation['price'] ?? 0),
            'sku' => $variation['sku'] ?? null,
            'qty' => (int) ($variation['qty'] ?? 0),
        ], is_array($variations) ? $variations : []);
    }
}
