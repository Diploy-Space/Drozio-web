<?php

use App\Http\Controllers\RestAPI\v6\Shared\ConfigController;
use App\Http\Controllers\RestAPI\v6\Shared\MapController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

Route::controller(ConfigController::class)->group(function () {
    Route::get('config', 'configuration');
    Route::get('business-pages', 'businessPages');
    Route::get('faq', 'faq');
});

Route::controller(MapController::class)
    ->prefix('maps')
    ->middleware('throttle:'.RouteServiceProvider::LIMITER_MAP_API)
    ->group(function () {
        Route::get('geocode', 'geocode');
        Route::get('places', 'searchPlaces');
        Route::get('places/{placeid}', 'findPlace');
        Route::get('distance', 'getDistance');
    });
