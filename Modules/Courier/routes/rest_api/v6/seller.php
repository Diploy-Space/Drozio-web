<?php

use Illuminate\Support\Facades\Route;
use Modules\Courier\app\Http\Controllers\Admin\CourierLocationController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierConfigController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierDispatchController;
use Modules\Courier\app\Http\Controllers\RestAPI\v6\Seller\CourierProviderController;
use Modules\Courier\app\Http\Middleware\ForceJsonResponse;

Route::middleware([ForceJsonResponse::class, 'api_lang', 'v6_seller_auth'])
    ->prefix('v6/seller/courier')
    ->group(function () {
        Route::middleware('vendor_delivery_partner_setup')->group(function () {
            Route::get('config', [CourierConfigController::class, 'index']);
            Route::put('config', [CourierConfigController::class, 'update']);

            Route::get('providers', [CourierProviderController::class, 'index']);

            Route::get('locations/stores', [CourierLocationController::class, 'stores']);
            Route::get('locations/cities', [CourierLocationController::class, 'cities']);
            Route::get('locations/zones/{cityId}', [CourierLocationController::class, 'zones']);
            Route::get('locations/areas/{zoneId}', [CourierLocationController::class, 'areas']);

            Route::post('estimate', [CourierDispatchController::class, 'estimate']);
            Route::post('dispatch', [CourierDispatchController::class, 'store']);
            Route::post('revise', [CourierDispatchController::class, 'revise']);
        });

        Route::get('track/{consignmentId}', [CourierDispatchController::class, 'track']);
    });
