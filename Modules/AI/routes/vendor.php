<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\Vendor\AuctionProductController;
use Modules\AI\app\Http\Controllers\Vendor\ProductController;
use Modules\AI\app\Http\Controllers\Vendor\WholesaleProductController;

Route::group(['prefix' => 'vendor', 'as' => 'vendor.', 'middleware' => ['seller']], function () {
    Route::group(['prefix' => 'retail/product', 'as' => 'retail.product.'], function () {
        Route::get('title-auto-fill', [ProductController::class, 'titleAutoFill'])->name('title-auto-fill');
        Route::get('description-auto-fill', [ProductController::class, 'descriptionAutoFill'])->name('description-auto-fill');
        Route::get('general-setup-auto-fill', [ProductController::class, 'generalSetupAutoFill'])->name('general-setup-auto-fill');
        Route::get('price-others-auto-fill', [ProductController::class, 'pricingAndOthersAutoFill'])->name('price-others-auto-fill');
        Route::get('seo-section-auto-fill', [ProductController::class, 'productSeoSectionAutoFill'])->name('seo-section-auto-fill');
        Route::get('variation-setup-auto-fill', [ProductController::class, 'productVariationSetupAutoFill'])->name('variation-setup-auto-fill');
        Route::post('analyze-image-auto-fill', [ProductController::class, 'generateTitleFromImages'])->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', [ProductController::class, 'generateProductTitleSuggestion'])->name('generate-title-suggestions');
    });

    Route::group(['prefix' => 'wholesale/product', 'as' => 'wholesale.product.'], function () {
        Route::get('title-auto-fill', [WholesaleProductController::class, 'titleAutoFill'])->name('title-auto-fill');
        Route::get('description-auto-fill', [WholesaleProductController::class, 'descriptionAutoFill'])->name('description-auto-fill');
        Route::get('general-setup-auto-fill', [WholesaleProductController::class, 'generalSetupAutoFill'])->name('general-setup-auto-fill');
        Route::get('variation-setup-auto-fill', [WholesaleProductController::class, 'variationSetupAutoFill'])->name('variation-setup-auto-fill');
        Route::get('pricing-setup-auto-fill', [WholesaleProductController::class, 'pricingSetupAutoFill'])->name('pricing-setup-auto-fill');
        Route::get('faq-auto-fill', [WholesaleProductController::class, 'faqAutoFill'])->name('faq-auto-fill');
        Route::get('seo-section-auto-fill', [WholesaleProductController::class, 'seoSectionAutoFill'])->name('seo-section-auto-fill');
        Route::post('analyze-image-auto-fill', [WholesaleProductController::class, 'generateTitleFromImages'])->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', [WholesaleProductController::class, 'generateProductTitleSuggestion'])->name('generate-title-suggestions');
    });

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
