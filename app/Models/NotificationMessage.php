<?php

namespace App\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NotificationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_module',
        'user_type',
        'key',
        'message',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'business_module' => 'string',
        'user_type' => 'string',
        'key' => 'string',
        'message' => 'string',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL): mixed
    {
        return $query->where('business_module', $module);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }
}
