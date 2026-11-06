<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\RestAPI\v6\Shared\AuctionProductController;
use Modules\AI\app\Http\Controllers\RestAPI\v6\Vendor\RetailProductController;
use Modules\AI\app\Http\Controllers\RestAPI\v6\Vendor\WholesaleProductController;

Route::middleware('v6_seller_auth')->group(function () {

    Route::controller(RetailProductController::class)->prefix('seller/product')->as('v6/seller.product.')->group(function () {
        Route::post('title-auto-fill', 'titleAutoFill')->name('title-auto-fill');
        Route::post('description-auto-fill', 'descriptionAutoFill')->name('description-auto-fill');
        Route::post('general-setup-auto-fill', 'generalSetupAutoFill')->name('general-setup-auto-fill');
        Route::post('price-others-auto-fill', 'pricingAndOthersAutoFill')->name('price-others-auto-fill');
        Route::post('variation-setup-auto-fill', 'variationSetupAutoFill')->name('variation-setup-auto-fill');
        Route::post('seo-section-auto-fill', 'seoSectionAutoFill')->name('seo-section-auto-fill');
        Route::post('analyze-image-auto-fill', 'generateTitleFromImages')->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', 'generateProductTitleSuggestion')->name('generate-title-suggestions');
        Route::get('generate-limit-check', 'generateLimitCheck')->name('generate-limit-check');
    });

    Route::controller(WholesaleProductController::class)->prefix('wholesale/seller/product')->as('v6/wholesale/seller.product.')->group(function () {
        Route::post('title-auto-fill', 'titleAutoFill')->name('title-auto-fill');
        Route::post('description-auto-fill', 'descriptionAutoFill')->name('description-auto-fill');
        Route::post('general-setup-auto-fill', 'generalSetupAutoFill')->name('general-setup-auto-fill');
        Route::post('variation-setup-auto-fill', 'variationSetupAutoFill')->name('variation-setup-auto-fill');
        Route::post('pricing-setup-auto-fill', 'pricingSetupAutoFill')->name('pricing-setup-auto-fill');
        Route::post('faq-auto-fill', 'faqAutoFill')->name('faq-auto-fill');
        Route::post('seo-section-auto-fill', 'seoSectionAutoFill')->name('seo-section-auto-fill');
        Route::post('analyze-image-auto-fill', 'generateTitleFromImages')->name('analyze-image-auto-fill');
        Route::post('generate-title-suggestions', 'generateProductTitleSuggestion')->name('generate-title-suggestions');
        Route::get('generate-limit-check', 'generateLimitCheck')->name('generate-limit-check');
    });

    Route::controller(AuctionProductController::class)->prefix('auction/seller/product')->as('v6/auction/seller.product.')->group(function () {
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
