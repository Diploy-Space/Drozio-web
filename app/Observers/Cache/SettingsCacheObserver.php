<?php

namespace App\Observers\Cache;

use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates ONLY the "settings" cache group when any settings-backing model
 * (BusinessSetting, Setting, LoginSetup) is written. One observer per group so a
 * write to one domain never over-invalidates another.
 */
class SettingsCacheObserver
{
    public function saved(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SETTINGS);
    }

    public function deleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SETTINGS);
    }

    public function restored(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SETTINGS);
    }

    public function forceDeleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SETTINGS);
    }
}
