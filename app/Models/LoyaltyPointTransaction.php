<?php

namespace App\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_module',
        'transaction_id',
        'credit',
        'debit',
        'balance',
        'reference',
        'transaction_type',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'credit' => 'float',
        'debit' => 'float',
        'balance' => 'float',
        'reference' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
