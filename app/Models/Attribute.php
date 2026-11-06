<?php

namespace App\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;

/**
 * @property int $id
 * @property string $name
 * @property string $business_module
 */
class Attribute extends Model
{
    protected $fillable = [
        'name',
        'business_module',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'business_module' => 'string',
    ];

    // Kept out of every JSON payload: api/v1/attributes is frozen and predates this column.
    protected $hidden = [
        'business_module',
    ];

    public function scopeBusinessModule($query, string $module = BusinessModule::RETAIL)
    {
        return $query->where('business_module', $module);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function getDefaultNameAttribute(): ?string
    {
        if (! $this->relationLoaded('translations')) {
            return $this->name;
        }

        return $this->translations->firstWhere('locale', getDefaultLanguage())?->value ?? $this->name;
    }

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                if (strpos(url()->current(), '/api')) {
                    return $query->where('locale', App::getLocale());
                } else {
                    return $query->where('locale', getDefaultLanguage());
                }
            }]);
        });
    }
}
