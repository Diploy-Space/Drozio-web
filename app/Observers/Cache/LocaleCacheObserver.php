<?php

namespace App\Observers\Cache;

use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates ONLY the "locale" cache group (currencies, translations) on any
 * write to Currency or Translation.
 *
 * Cached locale values are language-scoped in their keys; clearing the whole
 * group on a write drops every language variant at once.
 */
class LocaleCacheObserver
{
    public function saved(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_LOCALE);
    }

    public function deleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_LOCALE);
    }

    public function restored(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_LOCALE);
    }

    public function forceDeleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_LOCALE);
    }
}
