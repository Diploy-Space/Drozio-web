<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    protected $fillable = [
        'creator_id',
        'creator_type',
        'title',
        'cost',
        'duration',
        'status',
        'context',
    ];

    protected $casts = [
        'status' => 'boolean',
        'cost' => 'float',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'creator_id');
    }
}
