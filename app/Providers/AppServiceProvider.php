<?php

namespace App\Providers;

use App\Services\BusinessModule\BusinessModuleManager;
use App\Services\Courier\HostCourierOwnerResolver;
use App\Services\Courier\HostCourierShipmentAccessResolver;
use App\Services\ProductRecommendationService;
use App\Traits\AddonHelper;
use App\Traits\CacheManagerTrait;
use App\Traits\FileManagerTrait;
use App\Traits\ThemeHelper;
use App\Traits\UpdateClass;
use App\Utils\Helpers;
use App\View\Composers\Admin\BusinessModuleCountOrderComposer;
use App\View\Composers\Admin\HeaderComposer as AdminHeaderComposer;
use App\View\Composers\Admin\PeopleSidebarComposer;
use App\View\Composers\Admin\RetailSidebarComposer;
use App\View\Composers\Vendor\AdvancedSearchComposer as VendorAdvancedSearchComposer;
use App\View\Composers\Vendor\HeaderComposer as VendorHeaderComposer;
use App\View\Composers\Vendor\RetailSidebarComposer as VendorRetailSidebarComposer;
use App\View\Composers\Web\CartComposer;
use App\View\Composers\Web\FooterComposer as WebFooterComposer;
use App\View\Composers\Web\HeaderComposer as WebHeaderComposer;
use App\View\Composers\Web\ProfileSidebarComposer;
use App\View\Composers\Web\WishlistPanelComposer;
use App\View\Composers\Web\WishlistShopComposer;
use App\View\Composers\WebConfigComposer;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Facades\Excel;
use Madnest\Madzipper\Madzipper;
use Modules\Courier\app\Contracts\CourierOwnerResolver;
use Modules\Courier\app\Contracts\CourierShipmentAccessResolver;

ini_set('memory_limit', -1);
ini_set('upload_max_filesize', '180M');
ini_set('post_max_size', '200M');

class AppServiceProvider extends ServiceProvider
{
    use AddonHelper;
    use CacheManagerTrait;
    use FileManagerTrait;
    use ThemeHelper;
    use UpdateClass;

    public function register(): void
    {
        require_once app_path('Utils/retail-checkout-hooks.php');

        $loader = AliasLoader::getInstance();
        $loader->alias('Helper', Helpers::class);
        $loader->alias('Madzipper', Madzipper::class);
        $loader->alias('Excel', Excel::class);

        $this->app->singleton(ProductRecommendationService::class);

        $this->app->singleton(WebConfigComposer::class);
        $this->app->singleton(WebHeaderComposer::class);
        $this->app->singleton(WebFooterComposer::class);
        $this->app->singleton(CartComposer::class);
        $this->app->singleton(WishlistPanelComposer::class);
        $this->app->singleton(ProfileSidebarComposer::class);
        $this->app->singleton(RetailSidebarComposer::class);
        $this->app->singleton(PeopleSidebarComposer::class);
        $this->app->singleton(AdminHeaderComposer::class);
        $this->app->singleton(VendorRetailSidebarComposer::class);
        $this->app->singleton(VendorHeaderComposer::class);

        $this->app->singleton(BusinessModuleManager::class);

        if (addon_published_status('Courier') && interface_exists(CourierOwnerResolver::class)) {
            $this->app->bind(
                CourierOwnerResolver::class,
                HostCourierOwnerResolver::class,
            );
        }

        if (addon_published_status('Courier') && interface_exists(CourierShipmentAccessResolver::class)) {
            $this->app->bind(
                CourierShipmentAccessResolver::class,
                HostCourierShipmentAccessResolver::class,
            );
        }
    }

    public function boot(): void
    {
        $this->registerCommandSchedules();
        $this->registerWebHeaderComposer();
        $this->registerWebFooterComposer();
        $this->registerCartDetailsComposer();
        $this->registerWishlistPanelComposer();
        $this->registerWishlistShopComposer();
        $this->registerProfileSidebarComposer();
        $this->registerVendorAdvancedSearchComposer();

        Model::automaticallyEagerLoadRelationships();

        if (addon_published_status('Builder')) {
            Vite::createAssetPathsUsing(function ($path, $secure = null) {
                if (DOMAIN_POINTED_DIRECTORY !== 'public') {
                    $path = 'public/'.ltrim($path, '/');
                }

                return asset($path, $secure);
            });
        }

        if (! in_array(request()->ip(), ['127.0.0.1', '::1']) && config('app.force_https')) {
            \URL::forceScheme('https');
        }

        $this->shareDeliveryPartnerServiceStatusWithCourierConfigScreens();
        if (! App::runningInConsole()) {
            Paginator::useBootstrap();

            Config::set('addon_admin_routes', $this->getAddonAdminRoutes());
            Config::set('get_payment_publish_status', $this->getPaymentPublishStatus());
            Config::set('get_theme_routes', $this->getThemeRoutesArray());

            try {
                $hasBusinessSettings = Cache::remember('_schema_has_business_settings', 21600, fn () => Schema::hasTable('business_settings'));
                $hasPublishingHouses = Cache::remember('_schema_has_publishing_houses', 21600, fn () => Schema::hasTable('publishing_houses'));
                $hasAuthors = Cache::remember('_schema_has_authors', 21600, fn () => Schema::hasTable('authors'));

                if ($hasBusinessSettings) {
                    $this->setStorageConnectionEnvironment();
                    $this->cacheInHouseShopInTemporaryStatus();

                    $web = $this->cacheBusinessSettingsTable();

                    $multiCurrency = getWebConfig(name: 'currency_model') === 'multi_currency';
                    Config::set('builder.capabilities.currency', [
                        'mode' => $multiCurrency ? 'multi' : 'single',
                        'switcher' => $multiCurrency,
                    ]);

                    Config::set('builder.capabilities.location.map', ! empty(getWebConfig(name: 'map_api_key')));

                    $language = getWebConfig(name: 'language') ?? [];
                    if (! App::runningInConsole() && (Request::is('admin') || Request::is('admin/*'))) {
                        $this->adminSidebarLayoutCount();
                    }
                    if (! App::runningInConsole() && (Request::is('vendor') || Request::is('vendor/*'))) {
                        $this->vendorSidebarLayoutCount();
                    }
                    Helpers::currency_load();
                    View::share('language', $language);
                    View::composer('*', WebConfigComposer::class);
                    View::composer('admin-views.vendor.partials._module-count-list', BusinessModuleCountOrderComposer::class);
                    Schema::defaultStringLength(191);
                }
            } catch (Exception $exception) {

            }

            try {
                if (! in_array(request()->ip(), ['127.0.0.1', '::1'])) {
                    $this->autoClearDebugBarLogs();
                }
            } catch (Exception $exception) {
            }
        }

        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }

    protected function registerVendorAdvancedSearchComposer(): void
    {
        View::composer(
            ['layouts.vendor.partials.v2._header', 'layouts.vendor.partials.v2._body', 'layouts.vendor.partials._script-partials'],
            VendorAdvancedSearchComposer::class,
        );
    }

    protected function registerWebHeaderComposer(): void
    {
        View::composer(
            ['layouts.front-end.partials._header', 'theme-views.layouts.partials._header'],
            WebHeaderComposer::class,
        );
    }

    protected function registerWebFooterComposer(): void
    {
        View::composer(
            ['layouts.front-end.partials._footer', 'theme-views.layouts.partials._footer'],
            WebFooterComposer::class,
        );
    }

    protected function registerCartDetailsComposer(): void
    {
        View::composer(['web-views.cart._cart-details', 'theme-views.cart.cart-details'], CartComposer::class);
    }

    protected function registerWishlistPanelComposer(): void
    {
        View::composer(
            [
                'layouts.front-end.partials._header-profile',
                'layouts.front-end.partials._wishlist',
                'layouts.front-end.partials._module-wishlist',
                'theme-views.layouts.partials._wishlist',
                'theme-views.layouts.partials._app-bar',
                'theme-views.layouts.partials._app-bar-common',
                'wholesale::themes.default.layouts.partials._wishlist',
                'wholesale::themes.theme_aster.layouts.partials._wishlist',
                'wholesale::themes.theme_aster.layouts.partials._app-bar',
            ],
            WishlistPanelComposer::class,
        );
    }

    protected function registerWishlistShopComposer(): void
    {
        View::composer('theme-views.partials._wish-list-data', WishlistShopComposer::class);
    }

    protected function registerProfileSidebarComposer(): void
    {
        View::composer(
            [
                'web-views.partials._profile-aside',
                'theme-views.partials._profile-aside',
                'layouts.front-end.partials._header-profile',
                'layouts.front-end.partials._header',
            ],
            ProfileSidebarComposer::class,
        );
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('cache:warm-home --fresh')
                ->everyTwoHours()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command('storage:index')
                ->daily()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command('queue:work --queue=high,default,low --stop-when-empty --tries=3 --max-time=55')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command('products:reconcile-aggregates')
                ->dailyAt('03:15')
                ->withoutOverlapping()
                ->runInBackground();

            $schedule->command('dashboard:warm-report-cache')
                ->dailyAt('03:30')
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    protected function schemaHasTable(string $table): bool
    {
        $cacheKey = '_schema_has_'.$table;

        if (Cache::get($cacheKey) === true) {
            return true;
        }

        if (Schema::hasTable($table)) {
            Cache::put($cacheKey, true, 300);

            return true;
        }

        return false;
    }

    protected function shareDeliveryPartnerServiceStatusWithCourierConfigScreens(): void
    {
        View::composer(['courier::admin.config.index', 'courier::vendor.config.index'], function ($view) {
            $view->with('deliveryPartnerServiceEnabled', deliveryPartnerServiceAvailable(businessModule: deliveryPartnerRequestBusinessModule()));
        });
    }

    protected function adminSidebarLayoutCount(): void
    {
        View::composer('layouts.admin.partials.v2.sidebar.areas._retail', RetailSidebarComposer::class);
        View::composer('layouts.admin.partials.v2.sidebar.areas._people', PeopleSidebarComposer::class);
        View::composer('layouts.admin.partials.v2._header', AdminHeaderComposer::class);
    }

    protected function vendorSidebarLayoutCount(): void
    {
        View::composer('layouts.vendor.partials.v2.sidebar.areas._retail', VendorRetailSidebarComposer::class);
        View::composer('layouts.vendor.partials.v2._header', VendorHeaderComposer::class);
    }

    protected function autoClearDebugBarLogs(): void
    {
        $key = 'debugbar:last_clear';
        $minutes = 720;
        $lastClear = Cache::get($key);

        if (! $lastClear || now()->diffInMinutes($lastClear) >= $minutes) {
            $debugBarPath = storage_path('debugbar');
            if (File::exists($debugBarPath)) {
                foreach (File::files($debugBarPath) as $file) {
                    File::delete($file);
                }
            }

            $logFile = storage_path('logs/laravel.log');
            if (File::exists($logFile)) {
                file_put_contents($logFile, '');
            }

            Cache::put($key, now(), $minutes);
        }
    }
}
