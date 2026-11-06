<?php

namespace Modules\AI\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\AI\app\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $registerHostRoutes = function (): void {
            $this->mapApiV1Routes();
            $this->mapApiV3Routes();
            $this->mapApiV6Routes();

            $this->mapWebRoutes();
        };

        $hostDomain = config('app.host_domain');

        $hostDomain
            ? Route::domain($hostDomain)->group($registerHostRoutes)
            : $registerHostRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/web.php'));

        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/admin.php'));

        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/vendor.php'));
    }

    protected function mapApiV1Routes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/rest_api/v1/api.php'));
    }

    protected function mapApiV3Routes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/rest_api/v3/api.php'));
    }

    protected function mapApiV6Routes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('AI', '/routes/rest_api/v6/api.php'));
    }
}
