<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $fillable = [
        'product_id',
        'variant',
        'sku',
        'price',
        'qty',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant' => 'string',
        'sku' => 'string',
        'price' => 'float',
        'qty' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
