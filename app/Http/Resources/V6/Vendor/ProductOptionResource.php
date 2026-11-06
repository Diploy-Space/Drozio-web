<?php

namespace App\Http\Resources\V6\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail_full_url' => $this->thumbnail_full_url,
            'unit_price' => $this->unit_price,
            'current_stock' => $this->current_stock,
            'product_type' => $this->product_type,
            'variations' => json_decode((string) $this->variation, true) ?: [],
            'code' => $this->code,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'tax' => $this->tax,
            'tax_type' => $this->tax_type,
            'tax_model' => $this->tax_model,
            'minimum_order_qty' => $this->minimum_order_qty,
            'digital_variation' => $this->whenLoaded('digitalVariation', fn () => $this->digitalVariation->map(fn ($variation) => [
                'id' => $variation->id,
                'variant_key' => $variation->variant_key,
                'sku' => $variation->sku,
                'price' => $variation->price,
            ])->values()),
            'clearance_sale' => $this->whenLoaded('clearanceSale'),
        ];
    }
}
