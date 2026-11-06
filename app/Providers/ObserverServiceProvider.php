<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DealOfTheDay;
use App\Models\DeliveryCountryCode;
use App\Models\DeliveryZipCode;
use App\Models\FeatureDeal;
use App\Models\FlashDeal;
use App\Models\FlashDealProduct;
use App\Models\LoginSetup;
use App\Models\OrderDetail;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\ShippingType;
use App\Models\Translation;
use App\Observers\Cache\CatalogCacheObserver;
use App\Observers\Cache\ContentCacheObserver;
use App\Observers\Cache\LocaleCacheObserver;
use App\Observers\Cache\SettingsCacheObserver;
use App\Observers\Cache\ShippingCacheObserver;
use App\Observers\Erp\RefundWebhookObserver;
use App\Observers\Erp\VendorWebhookObserver;
use App\Observers\OrderDetailAggregateObserver;
use App\Observers\ReviewAggregateObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Binds one cache-invalidation observer per AppCache group to the models that
 * back that group. On saved/deleted the observer clears ONLY its own group, so
 * an admin edit propagates immediately to both web themes and all three APIs
 * (they read the same cache) while leaving unrelated groups warm.
 *
 * These sit alongside each model's existing cacheRemoveByType() boot hooks
 * (which clear the legacy cache keys); the two are complementary and both fire.
 */
class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Model => group observer. Every model backing a cached read is listed so
     * the invalidation path is explicit and auditable.
     */
    private const OBSERVERS = [
        // settings group
        BusinessSetting::class => SettingsCacheObserver::class,
        Setting::class => SettingsCacheObserver::class,
        LoginSetup::class => SettingsCacheObserver::class,

        // catalog group
        Category::class => CatalogCacheObserver::class,
        Brand::class => CatalogCacheObserver::class,

        // content group
        Banner::class => ContentCacheObserver::class,
        FlashDeal::class => ContentCacheObserver::class,
        FlashDealProduct::class => ContentCacheObserver::class,
        DealOfTheDay::class => ContentCacheObserver::class,
        FeatureDeal::class => ContentCacheObserver::class,

        // shipping group
        ShippingMethod::class => ShippingCacheObserver::class,
        ShippingType::class => ShippingCacheObserver::class,
        DeliveryCountryCode::class => ShippingCacheObserver::class,
        DeliveryZipCode::class => ShippingCacheObserver::class,

        // locale group
        Currency::class => LocaleCacheObserver::class,
        Translation::class => LocaleCacheObserver::class,

        // denormalized product-aggregate sync (rating_avg/rating_count/order_count)
        Review::class => ReviewAggregateObserver::class,
        OrderDetail::class => OrderDetailAggregateObserver::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::OBSERVERS as $model => $observer) {
            $model::observe($observer);
        }
        Seller::observe(VendorWebhookObserver::class);
        RefundRequest::observe(RefundWebhookObserver::class);
    }
}
