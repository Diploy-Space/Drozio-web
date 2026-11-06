<?php

namespace App\Observers\Cache;

use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates ONLY the "shipping" cache group (shipping methods, in-house
 * shipping type, delivery country/zip zones) on any write to ShippingMethod,
 * ShippingType, DeliveryCountryCode or DeliveryZipCode.
 *
 * Shipping methods are per-seller (creator_id/creator_type). The group is
 * cleared wholesale so a vendor's edit can never leave another vendor's cached
 * methods stale — no cross-vendor bleed.
 */
class ShippingCacheObserver
{
    public function saved(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SHIPPING);
    }

    public function deleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SHIPPING);
    }

    public function restored(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SHIPPING);
    }

    public function forceDeleted(Model $model): void
    {
        AppCache::forgetGroup(AppCache::GROUP_SHIPPING);
    }
}
