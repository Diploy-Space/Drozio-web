<?php

namespace App\Observers\Cache;

use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates ONLY the "catalog" cache group (categories tree, brands) on any
 * write to Category or Brand.
 */
class CatalogCacheObserver
{
    public function saved(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CATALOG);
    }

    public function deleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CATALOG);
    }

    public function restored(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CATALOG);
    }

    public function forceDeleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CATALOG);
    }
}
