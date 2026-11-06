<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\API\V1\ShoppingAssistantController;
use Modules\AI\app\Http\Controllers\API\V3\AuctionProductController;

Route::group(['prefix' => 'v1', 'middleware' => ['api_lang']], function () {

    Route::group(['prefix' => 'ai/assistant', 'as' => 'v1/ai/assistant.', 'middleware' => ['apiGuestCheck', 'throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_REQUEST]], function () {
        Route::post('upload-image', [ShoppingAssistantController::class, 'uploadImage'])
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_IMAGE_UPLOAD);
        Route::post('sessions', [ShoppingAssistantController::class, 'startSession'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE);
        Route::get('sessions', [ShoppingAssistantController::class, 'listSessions']);
        Route::get('sessions/{id}', [ShoppingAssistantController::class, 'getSession']);
        Route::delete('sessions/{id}', [ShoppingAssistantController::class, 'deleteSession'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE);
        Route::post('sessions/{id}/message', [ShoppingAssistantController::class, 'sendMessage'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_MESSAGE);
    });

    Route::group(['prefix' => 'customer', 'as' => 'v1/customer.', 'middleware' => ['auth:api']], function () {
        Route::group(['prefix' => 'auction/product', 'as' => 'auction.product.'], function () {
            Route::post('title-auto-fill', [AuctionProductController::class, 'titleAutoFill'])->name('title-auto-fill');
            Route::post('description-auto-fill', [AuctionProductController::class, 'descriptionAutoFill'])->name('description-auto-fill');
            Route::post('general-setup-auto-fill', [AuctionProductController::class, 'generalSetupAutoFill'])->name('general-setup-auto-fill');
            Route::post('shipping-policy-auto-fill', [AuctionProductController::class, 'shippingPolicyAutoFill'])->name('shipping-policy-auto-fill');
            Route::post('auction-info-auto-fill', [AuctionProductController::class, 'auctionInfoAutoFill'])->name('auction-info-auto-fill');
            Route::post('seo-section-auto-fill', [AuctionProductController::class, 'seoSectionAutoFill'])->name('seo-section-auto-fill');
            Route::post('analyze-image-auto-fill', [AuctionProductController::class, 'generateTitleFromImages'])->name('analyze-image-auto-fill');
            Route::post('generate-title-suggestions', [AuctionProductController::class, 'generateProductTitleSuggestion'])->name('generate-title-suggestions');
            Route::post('setup-auto-fill', [AuctionProductController::class, 'setupAutoFill'])->name('setup-auto-fill');
            Route::get('generate-limit-check', [AuctionProductController::class, 'generateLimitCheck'])->name('generate-limit-check');
        });
    });

});
