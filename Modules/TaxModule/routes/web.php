<?php

use Illuminate\Support\Facades\Route;
use Modules\TaxModule\app\Http\Controllers\SystemTaxVatSetupController;
use Modules\TaxModule\app\Http\Controllers\TaxVatController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'settings/vat-tax', 'as' => 'settings.vat-tax.', 'middleware' => ['module:settings.business']], function () {
        Route::controller(TaxVatController::class)->group(function () {
            Route::get('retail', 'retail')->name('tax-list-retail');
            Route::get('wholesale', 'wholesale')->name('tax-list-wholesale');
            Route::get('auction', 'auction')->name('tax-list-auction');
            Route::post('add-vat-tax-data', 'store')->name('store');
            Route::any('update-vat-tax-data', 'update')->name('update');
            Route::post('update-vat-tax-status', 'updateStatus')->name('status');
            Route::get('export-vat-tax', 'export')->name('export');
        });

        Route::controller(SystemTaxVatSetupController::class)->group(function () {
            Route::get('system-vat-tax/retail', 'retail')->name('system-vat-tax-retail');
            Route::get('system-vat-tax/wholesale', 'wholesale')->name('system-vat-tax-wholesale');
            Route::get('system-vat-tax/auction', 'auction')->name('system-vat-tax-auction');
            Route::post('system-vat-tax', 'systemTaxVatStore')->name('system-tax-vat-store');
            Route::post('system-vat-tax-vendor-status', 'vendorStatus')->name('system-tax-vat-vendor-status');
        });
    });
});
