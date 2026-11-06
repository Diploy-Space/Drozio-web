<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getRawOriginal('name'),
            'slug' => $this->slug,
            'code' => $this->code,
            'product_type' => $this->product_type,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'sub_sub_category_id' => $this->sub_sub_category_id,
            'brand_id' => $this->brand_id,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'discount_type' => $this->discount_type,
            'minimum_order_qty' => (int) $this->minimum_order_qty,
            'current_stock' => (int) $this->current_stock,
            'shipping_cost' => (float) $this->shipping_cost,
            'status' => (int) $this->status,
            'request_status' => (int) $this->request_status,
            'details' => $this->getRawOriginal('details'),
            'colors' => json_decode($this->getRawOriginal('colors'), true) ?: [],
            'choice_options' => json_decode($this->choice_options, true) ?: [],
            'variation' => json_decode($this->retailDetail?->variation ?: '[]', true) ?: [],
            'thumbnail' => $this->thumbnail_full_url,
            'images' => $this->images_full_url,
            'color_images' => $this->color_images_full_url,
            'added_by' => $this->added_by,
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
            'category_ids' => json_decode((string) $this->getRawOriginal('category_ids'), true) ?? [],
            'attributes' => array_map('intval', json_decode((string) $this->getRawOriginal('attributes'), true) ?? []),
            'tax' => $this->tax,
            'tax_type' => $this->tax_type,
            'tax_model' => $this->tax_model,
            'multiply_qty' => (int) $this->multiply_qty,
            'temp_shipping_cost' => (float) $this->temp_shipping_cost,
            'is_shipping_cost_updated' => (int) $this->is_shipping_cost_updated,
            'denied_note' => $this->denied_note,
            'video_provider' => $this->video_provider,
            'video_url' => $this->video_url,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_image_full_url' => $this->meta_image_full_url,
            'image_names' => json_decode((string) $this->getRawOriginal('images'), true) ?? [],
            'color_image_names' => json_decode((string) $this->retailDetail?->color_image, true) ?? [],
            'thumbnail_name' => $this->thumbnail,
            'digital_product_type' => $this->retailDetail?->digital_product_type,
            'digital_file_ready' => $this->retailDetail?->digital_file_ready,
            'digital_file_ready_full_url' => $this->digital_file_ready_full_url,
            'digital_product_file_types' => $this->retailDetail?->digital_product_file_types,
            'digital_product_extensions' => $this->retailDetail?->digital_product_extensions,
            'preview_file' => $this->retailDetail?->preview_file,
            'preview_file_full_url' => $this->preview_file_full_url,
            'purchase_price' => $this->retailDetail?->purchase_price,
            'featured' => $this->retailDetail?->featured,
            'reviews_count' => $this->whenHas('reviews_count', fn ($count) => (int) $count),
            'digital_product_authors_names' => $this->whenHas('digital_product_authors_names'),
            'digital_product_publishing_house_names' => $this->whenHas('digital_product_publishing_house_names'),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->map(fn ($translation) => [
                'locale' => $translation->locale,
                'key' => $translation->key,
                'value' => $translation->value,
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => ['id' => $tag->id, 'tag' => $tag->tag])->values()),
            'seo_info' => $this->whenLoaded('seoInfo'),
            'digital_variation' => $this->whenLoaded('digitalVariation', fn () => $this->digitalVariation->map(fn ($variation) => [
                'id' => $variation->id,
                'product_id' => $variation->product_id,
                'variant_key' => $variation->variant_key,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'file' => $variation->file,
            ])->values()),
            'digital_product_authors' => $this->whenLoaded('digitalProductAuthors', fn () => $this->digitalProductAuthors->map(fn ($author) => [
                'author_id' => $author->author_id,
                'name' => $author->relationLoaded('author') ? $author->author?->name : null,
            ])->values()),
            'digital_product_publishing_house' => $this->whenLoaded('digitalProductPublishingHouse', fn () => $this->digitalProductPublishingHouse->map(fn ($house) => [
                'publishing_house_id' => $house->publishing_house_id,
                'name' => $house->relationLoaded('publishingHouse') ? $house->publishingHouse?->name : null,
            ])->values()),
            'clearance_sale' => $this->whenLoaded('clearanceSale'),
            'tax_vats' => $this->whenLoaded('taxVats', fn () => $this->taxVats->map(fn ($taxVat) => [
                'id' => $taxVat->id,
                'tax_id' => $taxVat->tax_id,
                'tax' => $taxVat->relationLoaded('tax') ? $taxVat->tax : null,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
