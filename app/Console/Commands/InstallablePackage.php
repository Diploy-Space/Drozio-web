<?php

namespace App\Console\Commands;

use App\Utils\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Madnest\Madzipper\Facades\Madzipper;

class InstallablePackage extends Command
{
    protected $signature = 'prepare:installable';

    protected $description = 'Create an installable package.';

    private const SEPARATELY_SHIPPED_MODULES = ['ApiPlayground', 'Auction', 'Gateways', 'Builder', 'Wholesale'];

    public function handle(): void
    {
        if (is_link(base_path('build'))) {
            unlink(base_path('build'));
        }

        foreach (['.claude', '.idea', 'build', 'public/build', 'docs', 'database/seeders/development'] as $directory) {
            File::deleteDirectory(base_path($directory));
        }

        File::delete(base_path('database/seeders/DevelopmentSeeder.php'));

        if (array_key_exists('debugbar:clear', Artisan::all())) {
            Artisan::call('debugbar:clear');
        }
        Artisan::call('optimize:clear');

        Helpers::remove_dir('storage/app/public');
        Madzipper::make('installation/backup/public.zip')->extractTo('storage/app');

        $folder = base_path('resources/themes');
        $directories = glob($folder.'/*', GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $array = explode('/', $directory);
            if (File::isDirectory($directory) && ! in_array(end($array), ['default', 'theme_aster'])) {
                File::deleteDirectory($directory);
            }
        }

        foreach (self::SEPARATELY_SHIPPED_MODULES as $moduleName) {
            File::deleteDirectory(base_path('Modules/'.$moduleName));
            File::deleteDirectory(public_path('Modules/'.$moduleName));
        }

        $dot_env = base_path('.env');
        $new_env = base_path('.env.example');
        copy($new_env, $dot_env);

        $routes = base_path('app/Providers/RouteServiceProvider.php');
        $new_routes = base_path('installation/activate_install_routes.txt');
        copy($new_routes, $routes);
    }
}
