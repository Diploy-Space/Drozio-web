<?php

namespace App\Models;

use App\Enums\BusinessModule;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    use StorageTrait;

    protected $fillable = [
        'sent_by',
        'sent_to',
        'business_module',
        'title',
        'description',
        'notification_count',
        'image',
        'status',
    ];

    protected $casts = [
        'sent_by' => 'string',
        'sent_to' => 'string',
        'business_module' => 'string',
        'title' => 'string',
        'description' => 'string',
        'notification_count' => 'integer',
        'image' => 'string',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_full_url'];

    protected $with = ['storage'];

    public function scopeActive($query): mixed
    {
        return $query->where('status', 1);
    }

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL): mixed
    {
        return $query->where('business_module', $module);
    }

    public function notificationSeenBy(): HasOne
    {
        return $this->hasOne(NotificationSeen::class, 'notification_id');
    }

    public function getImageFullUrlAttribute(): ?array
    {
        $value = $this->image;
        if (count($this->storage) > 0) {
            $storage = $this->storage->where('key', 'image')->first();
        }

        return $this->storageLink('notification', $value, $storage['value'] ?? 'public');
    }

    protected static function boot(): void
    {
        parent::boot();
        static::saved(function ($model) {
            if ($model->isDirty('image')) {
                $storage = config('filesystems.disks.default') ?? 'public';
                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'image',
                ], [
                    'value' => $storage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
