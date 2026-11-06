<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProductResource extends JsonResource
{
    private const PRODUCT_ATTRIBUTES = [
        'id', 'business_module', 'added_by', 'user_id', 'shop_id', 'name', 'slug', 'product_type', 'category_ids',
        'category_id', 'sub_category_id', 'sub_sub_category_id', 'brand_id', 'unit', 'images', 'thumbnail',
        'thumbnail_storage_type', 'video_provider', 'video_url', 'video_file', 'video_file_storage_type', 'colors',
        'attributes', 'choice_options', 'unit_price', 'tax', 'tax_type', 'tax_model', 'discount', 'discount_type',
        'current_stock', 'minimum_order_qty', 'details', 'created_at', 'updated_at', 'status', 'featured_status',
        'meta_title', 'meta_description', 'meta_image', 'request_status', 'denied_note', 'shipping_cost', 'multiply_qty',
        'temp_shipping_cost', 'is_shipping_cost_updated', 'code', 'digital_product_type', 'digital_file_ready',
        'digital_file_ready_storage_type', 'digital_product_file_types', 'digital_product_extensions', 'preview_file',
        'preview_file_storage_type', 'featured', 'flash_deal', 'color_image', 'variation', 'purchase_price',
        'reviews_count', 'colors_formatted', 'is_shop_temporary_close', 'thumbnail_full_url', 'preview_file_full_url',
        'color_images_full_url', 'meta_image_full_url', 'images_full_url', 'digital_file_ready_full_url',
    ];

    private const SELLER_ATTRIBUTES = ['id', 'f_name', 'l_name', 'phone', 'image', 'email', 'status', 'pos_status', 'image_full_url'];

    private const SHOP_ATTRIBUTES = [
        'id', 'seller_id', 'author_type', 'name', 'slug', 'address', 'contact', 'image', 'vacation_start_date',
        'vacation_end_date', 'vacation_duration_type', 'vacation_note', 'vacation_status', 'temporary_close',
        'image_full_url', 'banner_full_url',
    ];

    public function toArray(Request $request): array
    {
        $attributes = $this->resource->attributesToArray();

        return [
            ...array_intersect_key($attributes, array_flip(self::PRODUCT_ATTRIBUTES)),
            'category_ids' => $this->decodeJson($attributes['category_ids'] ?? null),
            'colors' => $this->decodeJson($attributes['colors'] ?? null),
            'attributes' => array_map('intval', (array) ($this->decodeJson($attributes['attributes'] ?? null) ?? [])),
            'choice_options' => $this->decodeJson($attributes['choice_options'] ?? null),
            'variation' => $this->formatVariations($attributes['variation'] ?? null),
            'digital_variation' => $this->whenLoaded('digitalVariation', fn () => $this->digitalVariation->map(fn ($variation) => [
                'id' => $variation->id,
                'product_id' => $variation->product_id,
                'variant_key' => $variation->variant_key,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'file' => $variation->file,
                'created_at' => $variation->created_at,
                'updated_at' => $variation->updated_at,
            ])->values()),
            'clearance_sale' => $this->whenLoaded('clearanceSale'),
            'seller' => $this->whenLoaded('seller', fn (): ?array => $this->seller === null ? null : [
                ...array_intersect_key($this->seller->attributesToArray(), array_flip(self::SELLER_ATTRIBUTES)),
                ...($this->seller->relationLoaded('shop') ? ['shop' => $this->seller->shop === null
                    ? null
                    : array_intersect_key($this->seller->shop->attributesToArray(), array_flip(self::SHOP_ATTRIBUTES)),
                ] : []),
            ]),
        ];
    }

    private function decodeJson(mixed $value): mixed
    {
        return is_string($value) ? json_decode($value) : $value;
    }

    private function formatVariations(mixed $variation): array
    {
        $variations = is_array($variation) ? $variation : json_decode((string) $variation, true);

        return array_map(static fn (array $item): array => [
            'type' => $item['type'],
            'price' => (float) $item['price'],
            'sku' => $item['sku'],
            'qty' => (int) $item['qty'],
        ], $variations ?? []);
    }
}
