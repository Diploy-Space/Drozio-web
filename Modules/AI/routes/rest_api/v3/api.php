<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\API\V3\AuctionProductController;
use Modules\AI\app\Http\Controllers\API\V3\ProductController;

Route::group(['prefix' => 'v3/seller', 'as' => 'v3/seller.', 'middleware' => ['api_lang']], function () {
    Route::group(['middleware' => ['seller_api_auth']], function () {
        Route::group(['prefix' => 'product', 'as' => 'product.'], function () {
            Route::post('title-auto-fill', [ProductController::class, 'titleAutoFill'])->name('title-auto-fill');
            Route::post('description-auto-fill', [ProductController::class, 'descriptionAutoFill'])->name('description-auto-fill');
            Route::post('general-setup-auto-fill', [ProductController::class, 'generalSetupAutoFill'])->name('general-setup-auto-fill');
            Route::post('price-others-auto-fill', [ProductController::class, 'pricingAndOthersAutoFill'])->name('price-others-auto-fill');
            Route::post('seo-section-auto-fill', [ProductController::class, 'productSeoSectionAutoFill'])->name('seo-section-auto-fill');
            Route::post('variation-setup-auto-fill', [ProductController::class, 'productVariationSetupAutoFill'])->name('variation-setup-auto-fill');
            Route::post('analyze-image-auto-fill', [ProductController::class, 'generateTitleFromImages'])->name('analyze-image-auto-fill');
            Route::post('generate-title-suggestions', [ProductController::class, 'generateProductTitleSuggestion'])->name('generate-title-suggestions');
            Route::get('generate-limit-check', [ProductController::class, 'generateLimitCheck']);
        });

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
