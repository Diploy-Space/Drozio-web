<?php

Route::group(['prefix' => 'v6', 'middleware' => ['api_lang']], function () {

    require __DIR__.'/customer.php';
    require __DIR__.'/vendor.php';

});
