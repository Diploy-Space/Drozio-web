<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\API\V3\AuctionProductController;
use Modules\AI\app\Http\Controllers\ShoppingAssistantController;

Route::group([
    'prefix' => 'ai/assistant',
    'as' => 'ai.assistant.',
    'middleware' => ['guestCheck', 'throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_REQUEST],
], function () {
    Route::post('upload-image', [ShoppingAssistantController::class, 'uploadImage'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_IMAGE_UPLOAD)->name('upload-image');
    Route::post('sessions', [ShoppingAssistantController::class, 'startSession'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE)->name('sessions.start');
    Route::get('sessions', [ShoppingAssistantController::class, 'listSessions'])->name('sessions.list');
    Route::get('sessions/{id}', [ShoppingAssistantController::class, 'getSession'])->name('sessions.show');
    Route::delete('sessions/{id}', [ShoppingAssistantController::class, 'deleteSession'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_SESSION_WRITE)->name('sessions.delete');
    Route::post('sessions/{id}/message', [ShoppingAssistantController::class, 'sendMessage'])->middleware('throttle:'.RouteServiceProvider::LIMITER_ASSISTANT_MESSAGE)->name('sessions.message');
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['customer']], function () {
    Route::group(['prefix' => 'auction/product', 'as' => 'auction.product.'], function () {
        Route::get('title-auto-fill', [AuctionProductController::class, 'titleAutoFill'])->name('title-auto-fill');
        Route::get('description-auto-fill', [AuctionProductController::class, 'descriptionAutoFill'])->name('description-auto-fill');
        Route::get('general-setup-auto-fill', [AuctionProductController::class, 'generalSetupAutoFill'])->name('general-setup-auto-fill');
        Route::get('shipping-policy-auto-fill', [AuctionProductController::class, 'shippingPolicyAutoFill'])->name('shipping-policy-auto-fill');
        Route::get('auction-info-auto-fill', [AuctionProductController::class, 'auctionInfoAutoFill'])->name('auction-info-auto-fill');
        Route::get('seo-section-auto-fill', [AuctionProductController::class, 'seoSectionAutoFill'])->name('seo-section-auto-fill');
        Route::post('analyze-image-auto-fill', [AuctionProductController::class, 'generateTitleFromImages'])->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', [AuctionProductController::class, 'generateProductTitleSuggestion'])->name('generate-title-suggestions');
    });
});
