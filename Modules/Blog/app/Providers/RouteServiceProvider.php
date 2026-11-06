<?php

namespace Modules\Blog\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\Blog\app\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $registerHostRoutes = function (): void {
            Route::middleware('web')
                ->namespace($this->moduleNamespace)
                ->group(module_path('Blog', '/routes/web.php'));

            Route::middleware('web')
                ->namespace($this->moduleNamespace)
                ->group(module_path('Blog', '/routes/admin/routes.php'));
        };

        $hostDomain = config('app.host_domain');

        $hostDomain
            ? Route::domain($hostDomain)->group($registerHostRoutes)
            : $registerHostRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Blog', '/routes/api.php'));
    }
}
