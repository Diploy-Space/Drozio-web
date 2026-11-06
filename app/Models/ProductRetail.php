<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRetail extends Model
{
    protected $table = 'products_retail';

    protected $fillable = [
        'product_id',
        'digital_product_type',
        'digital_file_ready',
        'digital_file_ready_storage_type',
        'digital_product_file_types',
        'digital_product_extensions',
        'preview_file',
        'preview_file_storage_type',
        'featured',
        'flash_deal',
        'color_image',
        'variation',
        'purchase_price',
    ];

    /**
     * Mirrors the casts these columns carried on Product before the 16.6 split,
     * so a value serialises identically to the frozen v1/v2/v3 API responses.
     */
    protected $casts = [
        'product_id' => 'integer',
        'digital_product_type' => 'string',
        'digital_file_ready' => 'string',
        'digital_file_ready_storage_type' => 'string',
        'digital_product_file_types' => 'array',
        'digital_product_extensions' => 'array',
        'preview_file' => 'string',
        'featured' => 'integer',
        'flash_deal' => 'integer',
        'purchase_price' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
