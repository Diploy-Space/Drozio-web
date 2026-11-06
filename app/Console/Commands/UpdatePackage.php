<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UpdatePackage extends Command
{
    protected $signature = 'prepare:updatable';

    protected $description = 'Create an updatable package.';

    public function handle(): int
    {
        if (array_key_exists('debugbar:clear', Artisan::all())) {
            Artisan::call('debugbar:clear');
        }

        $folder = base_path('resources/themes');
        $directories = glob($folder . '/*', GLOB_ONLYDIR);
        foreach ($directories as $directory) {
            $array = explode('/', $directory);
            if (File::isDirectory($directory) && !in_array(end($array), ["default", "theme_aster"])) {
                File::deleteDirectory($directory);
            }
        }

        $addOnFolder = base_path('Modules');
        $addOnDirectories = glob($addOnFolder . '/*', GLOB_ONLYDIR);
        foreach ($addOnDirectories as $directory) {
            $array = explode('/', $directory);
            $directoryName = end($array);
            if (File::isDirectory($directory) && $directoryName == 'Gateways') {
                File::deleteDirectory($directory);
            }
        }

        $this->removeStorefrontBuild();

        $routes = base_path('app/Providers/RouteServiceProvider.php');
        $new_routes = base_path('installation/activate_update_routes.txt');
        copy($new_routes, $routes);
        return 0;
    }

    private function removeStorefrontBuild(): void
    {
        if (is_link(base_path('build'))) {
            unlink(base_path('build'));
        }

        File::deleteDirectory(base_path('build'));
        File::deleteDirectory(public_path('build'));
    }
}
