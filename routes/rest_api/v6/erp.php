<?php

use App\Http\Controllers\RestAPI\v6\Erp\PingController;
use App\Http\Controllers\RestAPI\v6\Erp\RefundController;
use App\Http\Controllers\RestAPI\v6\Erp\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware('erp_token')->prefix('erp')->group(function () {
    Route::get('ping', [PingController::class, 'ping']);

    Route::controller(VendorController::class)->group(function () {
        Route::get('vendors', 'getList');
        Route::get('vendors/{id}', 'find')->whereNumber('id');
    });

    Route::controller(RefundController::class)->group(function () {
        Route::get('refunds', 'getList');
        Route::get('refunds/count', 'count');
        Route::get('refunds/{id}', 'find')->whereNumber('id');
    });
});
