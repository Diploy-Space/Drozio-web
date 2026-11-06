<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

class SessionFlush extends Command
{
    protected $signature = 'session:flush';

    protected $description = 'Command description';

    public function handle(): void
    {
        Session::getHandler()->gc(0);
        Artisan::call('cache:clear');
        if (array_key_exists('debugbar:clear', Artisan::all())) {
            Artisan::call('debugbar:clear');
        }
    }
}
