<?php

namespace Modules\Courier\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\Courier\app\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebhookRoutes();

        $registerHostRoutes = function (): void {
            $this->mapSellerApiRoutes();
            $this->mapAdminRoutes();
            $this->mapVendorRoutes();
        };

        $hostDomain = config('app.host_domain');

        $hostDomain
            ? Route::domain($hostDomain)->group($registerHostRoutes)
            : $registerHostRoutes();
    }

    protected function mapSellerApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/seller_api.php'));

        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/rest_api/v6/seller.php'));

        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/rest_api/v6/wholesale_seller.php'));
    }

    protected function mapWebhookRoutes(): void
    {
        Route::middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/webhook.php'));
    }

    protected function mapAdminRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/admin.php'));
    }

    protected function mapVendorRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Courier', '/routes/vendor.php'));
    }
}
