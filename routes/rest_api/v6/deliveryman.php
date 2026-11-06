<?php

use App\Http\Controllers\RestAPI\v6\Deliveryman\Auth\AuthenticationController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\Auth\ForgotPasswordController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\ChatController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\DeliveryVerificationController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\EmergencyContactController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\LocationController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\NotificationController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\OrderController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\OrderProgressController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\ProfileController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\ReviewController;
use App\Http\Controllers\RestAPI\v6\Deliveryman\WalletController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

Route::prefix('delivery-man/auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_LOGIN);

    Route::post('logout', [AuthenticationController::class, 'logout'])
        ->middleware('v6_delivery_man_auth');

    Route::controller(ForgotPasswordController::class)->prefix('password')->group(function () {
        Route::post('forgot', 'forgotPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);
        Route::post('verify-otp', 'verifyOtp')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);
        Route::put('reset', 'resetPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
    });
});

Route::middleware('v6_delivery_man_auth')->prefix('delivery-man')->group(function () {
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'getProfile');
        Route::put('profile', 'updateProfile');
        Route::put('bank-info', 'updateBankInfo');
        Route::put('online-status', 'updateOnlineStatus');
        Route::put('language', 'updateLanguage');
        Route::put('device-token', 'updateDeviceToken');
    });

    Route::get('notifications', [NotificationController::class, 'getList']);
    Route::post('locations', [LocationController::class, 'record']);
    Route::get('emergency-contacts', [EmergencyContactController::class, 'getList']);

    Route::controller(ReviewController::class)->prefix('reviews')->group(function () {
        Route::get('/', 'getList');
        Route::put('{id}/saved', 'updateSavedStatus');
    });

    Route::controller(ChatController::class)->prefix('chats')->group(function () {
        Route::get('/', 'getThreads');

        Route::get('{type}/{id}/messages', 'getMessages');
        Route::post('{type}/{id}/messages', 'sendMessage');
        Route::put('{type}/{id}/seen', 'markSeen');
    });

    Route::controller(WalletController::class)->group(function () {
        Route::get('wallet', 'getWallet');
        Route::get('withdraws', 'getWithdraws');
        Route::post('withdraws', 'createWithdraw');
        Route::get('earnings', 'getEarnings');
        Route::get('cash-collections', 'getCashCollections');
    });
});

Route::middleware('v6_delivery_man_auth')->prefix('delivery-man/orders')->group(function () {
    Route::controller(OrderController::class)->group(function () {
        Route::get('current', 'getCurrent');
        Route::get('search', 'search');
        Route::get('/', 'getList');
        Route::get('{id}', 'find')->whereNumber('id');
        Route::get('{id}/items', 'getItems')->whereNumber('id');
        Route::get('{id}/delivery-history', 'getDeliveryHistory')->whereNumber('id');
        Route::get('{id}/last-location', 'getLastLocation')->whereNumber('id');
    });

    Route::controller(OrderProgressController::class)->group(function () {
        Route::put('{id}/status', 'updateStatus')->whereNumber('id');
        Route::put('{id}/expected-delivery', 'updateExpectedDelivery')->whereNumber('id');
        Route::put('{id}/pause', 'updatePause')->whereNumber('id');
        Route::put('{id}/payment-status', 'updatePaymentStatus')->whereNumber('id');
    });

    Route::controller(DeliveryVerificationController::class)->group(function () {
        Route::post('{id}/verification-code', 'sendVerificationCode')->whereNumber('id');
        Route::post('{id}/verification-code/verify', 'verifyCode')
            ->whereNumber('id')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);
        Route::post('{id}/delivery-proofs', 'storeDeliveryProofs')->whereNumber('id');
    });
});
