<?php

use Illuminate\Support\Facades\Route;
use Modules\AI\app\Http\Controllers\Admin\Blog\BlogController;
use Modules\AI\app\Http\Controllers\Admin\ProductController;
use Modules\AI\app\Http\Controllers\Admin\SettingController;
use Modules\AI\app\Http\Controllers\Admin\WholesaleProductController;
use Modules\AI\app\Http\Controllers\API\V3\AuctionProductController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin', 'actch:admin_panel']], function () {
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

    Route::group(['prefix' => 'settings/content/blog', 'as' => 'settings.content.blog.'], function () {
        Route::get('title-auto-fill', [BlogController::class, 'titleAutoFill'])->name('title-auto-fill');
        Route::get('description-auto-fill', [BlogController::class, 'descriptionAutoFill'])->name('description-auto-fill');
        Route::post('seo-section-auto-fill', [BlogController::class, 'seoSectionAutoFill'])->name('seo-section-auto-fill');
        Route::post('generate-title-suggestions', [BlogController::class, 'generateBlogTitleSuggestion'])->name('generate-title-suggestions');
        Route::post('analyze-image-auto-fill', [BlogController::class, 'generateBlogTitleFromImages'])->name('analyze-image-auto-fill');
    });

    Route::group(['prefix' => 'settings/third-party', 'as' => 'settings.third-party.'], function () {
        Route::group(['prefix' => 'ai-setting', 'as' => 'ai-setting.'], function () {
            Route::controller(SettingController::class)->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('store', 'store')->name('store');

                Route::get('vendors-usage-limits', 'getVendorUsagesLimitView')->name('vendors-usage-limits');
                Route::post('vendors-usage-limits/update', 'updateVendorUsagesLimit')->name('vendors-usage-limits-update');
                Route::get('customers-usage-limits', 'getCustomerUsagesLimitView')->name('customers-usage-limits');
                Route::post('customers-usage-limits/update', 'updateCustomerUsagesLimit')->name('customers-usage-limits-update');
            });
        });
    });
});
