<?php

use App\Http\Controllers\RestAPI\v6\Vendor\DrojioVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('v6_seller_auth')->prefix('drojio/vendor/verification')->group(function () {
    Route::get('profile', [DrojioVerificationController::class, 'getProfile']);
    Route::put('profile', [DrojioVerificationController::class, 'updateProfile']);
    Route::post('documents', [DrojioVerificationController::class, 'uploadDocument']);
    Route::get('status', [DrojioVerificationController::class, 'getStatus']);
    Route::post('resubmit', [DrojioVerificationController::class, 'resubmit']);
    Route::get('requirements', [DrojioVerificationController::class, 'getRequirements']);
});
