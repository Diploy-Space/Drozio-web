<?php

namespace App\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $product_id
 * @property int|null $user_id
 * @property string $business_module
 */
class ProductRecentView extends Model
{
    use HasFactory;

    protected $table = 'product_recent_views';

    protected $fillable = [
        'product_id',
        'user_id',
        'business_module',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'business_module' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
