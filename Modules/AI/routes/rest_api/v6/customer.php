<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\RestAPI\v6\Customer\ShoppingAssistantController;
use Modules\AI\app\Http\Controllers\RestAPI\v6\Shared\AuctionProductController;

Route::middleware(['auth:api', 'auction_customer_api_access:strict'])->prefix('auction/product')->as('v6/auction.product.')->group(function () {
    Route::controller(AuctionProductController::class)->group(function () {
        Route::post('title-auto-fill', 'titleAutoFill')->name('title-auto-fill');
        Route::post('description-auto-fill', 'descriptionAutoFill')->name('description-auto-fill');
        Route::post('general-setup-auto-fill', 'generalSetupAutoFill')->name('general-setup-auto-fill');
        Route::post('shipping-policy-auto-fill', 'shippingPolicyAutoFill')->name('shipping-policy-auto-fill');
        Route::post('auction-info-auto-fill', 'auctionInfoAutoFill')->name('auction-info-auto-fill');
        Route::post('seo-section-auto-fill', 'seoSectionAutoFill')->name('seo-section-auto-fill');
        Route::post('analyze-image-auto-fill', 'generateTitleFromImages')->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', 'generateProductTitleSuggestion')->name('generate-title-suggestions');
        Route::post('setup-auto-fill', 'setupAutoFill')->name('setup-auto-fill');
        Route::get('generate-limit-check', 'generateLimitCheck')->name('generate-limit-check');
    });
});

Route::controller(ShoppingAssistantController::class)
    ->prefix('ai/assistant')
    ->as('v6/ai.assistant.')
    ->middleware(['apiGuestCheck', 'throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_REQUEST])
    ->group(function () {
        Route::post('upload-image', 'uploadImage')->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_IMAGE_UPLOAD)->name('upload-image');
        Route::post('sessions', 'startSession')->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE)->name('sessions.start');
        Route::get('sessions', 'listSessions')->name('sessions.list');
        Route::get('sessions/{id}', 'getSession')->whereNumber('id')->name('sessions.show');
        Route::delete('sessions/{id}', 'deleteSession')->whereNumber('id')->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE)->name('sessions.delete');
        Route::post('sessions/{id}/message', 'sendMessage')->whereNumber('id')->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_MESSAGE)->name('sessions.message');
    });
