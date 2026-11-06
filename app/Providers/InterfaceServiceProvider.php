<?php

namespace App\Providers;

use App\Contracts\ControllerInterface;
use App\Http\Controllers\BaseController;
use Illuminate\Support\ServiceProvider;

class InterfaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ControllerInterface::class, BaseController::class);
    }
}
