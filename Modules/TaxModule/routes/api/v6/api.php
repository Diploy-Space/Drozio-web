<?php

use Illuminate\Support\Facades\Route;
use Modules\TaxModule\app\Http\Controllers\Api\V6\TaxController;

Route::group(['prefix' => 'v6/seller', 'middleware' => ['v6_seller_auth', 'api_lang']], function () {
    Route::get('vat-tax/setup', [TaxController::class, 'getSetup']);
    Route::get('vat-tax/list', [TaxController::class, 'getList']);
    Route::get('vat-tax/retail-report', [TaxController::class, 'getReport']);
});
