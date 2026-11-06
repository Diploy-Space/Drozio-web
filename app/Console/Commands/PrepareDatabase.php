<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PrepareDatabase extends Command
{
    protected $signature = 'prepare:database';

    protected $description = 'Command description';

    public function handle(): int
    {
        if (array_key_exists('debugbar:clear', Artisan::all())) {
            Artisan::call('debugbar:clear');
        }
        $routes = base_path('app/Providers/RouteServiceProvider.php');
        $new_routes = base_path('installation/activate_update_routes.txt');
        copy($new_routes, $routes);
        return 0;
    }
}
