<?php

Route::group(['prefix' => 'v6', 'middleware' => ['api_lang']], function () {

    require __DIR__.'/shared.php';
    require __DIR__.'/customer.php';
    require __DIR__.'/vendor.php';
    require __DIR__.'/deliveryman.php';
    require __DIR__.'/erp.php';
    require __DIR__.'/drojio_vendor_verification.php';

});

