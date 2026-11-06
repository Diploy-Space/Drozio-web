<?php

namespace App\Observers\Cache;

use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates ONLY the "content" cache group (banners, flash deals, featured /
 * home sections) on any write to Banner, FlashDeal, FlashDealProduct,
 * DealOfTheDay or FeatureDeal.
 */
class ContentCacheObserver
{
    public function saved(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CONTENT);
    }

    public function deleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CONTENT);
    }

    public function restored(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CONTENT);
    }

    public function forceDeleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_CONTENT);
    }
}
