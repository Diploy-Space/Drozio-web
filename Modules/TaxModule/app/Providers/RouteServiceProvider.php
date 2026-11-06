<?php

namespace Modules\TaxModule\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\TaxModule\app\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $registerHostRoutes = function (): void {
            $this->mapApiRoutes();

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
            ->group(module_path('TaxModule', '/routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        foreach (['/routes/api/v1/api.php', '/routes/api/v3/api.php', '/routes/api/v6/api.php'] as $routeFile) {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->moduleNamespace)
                ->group(module_path('TaxModule', $routeFile));
        }
    }
}
