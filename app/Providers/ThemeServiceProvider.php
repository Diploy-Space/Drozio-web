<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (! App::runningInConsole()) {
            $theme = config('app.theme');
            $path = base_path('resources/themes/'.$theme);
            if (! defined('VIEW_FILE_NAMES')) {
                define('VIEW_FILE_NAMES', include ($path.'/file_names.php'));
            }
            view()->addLocation($path);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
