<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopTinDocument extends Model
{
    use StorageTrait;

    protected $fillable = [
        'shop_id',
        'business_module',
        'tax_identification_number',
        'tin_expire_date',
        'tin_certificate',
        'tin_certificate_storage_type',
    ];

    protected $appends = ['tin_certificate_full_url'];

    protected function casts(): array
    {
        return [
            'shop_id' => 'integer',
            'tin_expire_date' => 'date',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeBusinessModule(Builder $query, string $businessModule): Builder
    {
        return $query->where('business_module', $businessModule);
    }

    public function getTinCertificateFullUrlAttribute(): string|null|array
    {
        return $this->storageLink('shop/documents', $this->tin_certificate, $this->tin_certificate_storage_type ?? 'public');
    }
}
