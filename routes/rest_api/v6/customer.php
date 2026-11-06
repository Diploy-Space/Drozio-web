<?php

use App\Http\Controllers\RestAPI\v6\Customer\AccountController;
use App\Http\Controllers\RestAPI\v6\Customer\AddressController;
use App\Http\Controllers\RestAPI\v6\Customer\AttributeController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\AuthenticationController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\FirebaseAuthController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\ForgotPasswordController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\IdentityVerificationController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\OtpVerificationController;
use App\Http\Controllers\RestAPI\v6\Customer\Auth\SocialAuthController;
use App\Http\Controllers\RestAPI\v6\Customer\BannerController;
use App\Http\Controllers\RestAPI\v6\Customer\BrandController;
use App\Http\Controllers\RestAPI\v6\Customer\CartController;
use App\Http\Controllers\RestAPI\v6\Customer\CategoryController;
use App\Http\Controllers\RestAPI\v6\Customer\ChatController;
use App\Http\Controllers\RestAPI\v6\Customer\ClearanceSaleController;
use App\Http\Controllers\RestAPI\v6\Customer\CompareController;
use App\Http\Controllers\RestAPI\v6\Customer\ContactController;
use App\Http\Controllers\RestAPI\v6\Customer\CouponController;
use App\Http\Controllers\RestAPI\v6\Customer\DealController;
use App\Http\Controllers\RestAPI\v6\Customer\DeliverymanReviewController;
use App\Http\Controllers\RestAPI\v6\Customer\DeliveryRestrictionController;
use App\Http\Controllers\RestAPI\v6\Customer\DigitalCatalogueController;
use App\Http\Controllers\RestAPI\v6\Customer\DigitalProductDownloadController;
use App\Http\Controllers\RestAPI\v6\Customer\GuestController;
use App\Http\Controllers\RestAPI\v6\Customer\LoyaltyController;
use App\Http\Controllers\RestAPI\v6\Customer\NotificationController;
use App\Http\Controllers\RestAPI\v6\Customer\OrderActionController;
use App\Http\Controllers\RestAPI\v6\Customer\OrderController;
use App\Http\Controllers\RestAPI\v6\Customer\OrderPaymentController;
use App\Http\Controllers\RestAPI\v6\Customer\OrderPlacementController;
use App\Http\Controllers\RestAPI\v6\Customer\OrderTrackingController;
use App\Http\Controllers\RestAPI\v6\Customer\ProductController;
use App\Http\Controllers\RestAPI\v6\Customer\ProductRecommendationController;
use App\Http\Controllers\RestAPI\v6\Customer\ProductReviewController;
use App\Http\Controllers\RestAPI\v6\Customer\ProductSearchController;
use App\Http\Controllers\RestAPI\v6\Customer\ProductSectionController;
use App\Http\Controllers\RestAPI\v6\Customer\RefundController;
use App\Http\Controllers\RestAPI\v6\Customer\RestockRequestController;
use App\Http\Controllers\RestAPI\v6\Customer\ShippingMethodController;
use App\Http\Controllers\RestAPI\v6\Customer\ShopController;
use App\Http\Controllers\RestAPI\v6\Customer\ShopProductController;
use App\Http\Controllers\RestAPI\v6\Customer\SupportTicketController;
use App\Http\Controllers\RestAPI\v6\Customer\WalletController;
use App\Http\Controllers\RestAPI\v6\Customer\WishlistController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

Route::controller(CategoryController::class)->prefix('categories')->group(function () {
    Route::get('/', 'getList');
    Route::get('sub-categories/{categoryId}', 'getSubCategories');
    Route::get('sub-sub-categories/{subCategoryId}', 'getSubSubCategories');
});

Route::get('brands', [BrandController::class, 'getList']);

Route::get('attributes', [AttributeController::class, 'getList']);

Route::get('banners', [BannerController::class, 'getList']);

Route::controller(ShopController::class)->group(function () {
    Route::get('shops', 'getList');
    Route::get('shops/{slug}', 'find');
});

Route::controller(CouponController::class)->group(function () {
    Route::get('coupons', 'getList');
    Route::get('shops/{slug}/coupons', 'getShopCoupons');
});

Route::controller(DealController::class)->group(function () {
    Route::get('flash-deals', 'getFlashDeals');
    Route::get('flash-deals/{dealId}/products', 'getFlashDealProducts');
    Route::get('featured-deals', 'getFeaturedDeals');
    Route::get('featured-deals/{dealId}/products', 'getFeaturedDealProducts');
    Route::get('deal-of-the-day', 'getDealOfTheDay');
});

Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'getList');
    Route::put('{id}/seen', 'markSeen')->middleware('auth:api');
});

Route::controller(ClearanceSaleController::class)->prefix('clearance-sale')->group(function () {
    Route::get('/', 'getSetup');
    Route::get('products', 'getProducts');
});

Route::prefix('auth')->group(function () {
    Route::controller(AuthenticationController::class)->group(function () {
        Route::post('register', 'register')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
        Route::post('register/otp', 'registerWithOtp')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
        Route::post('login', 'login')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_LOGIN);
        Route::post('logout', 'logout')->middleware('auth:api');
        Route::post('availability/email', 'checkEmail')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
        Route::post('availability/phone', 'checkPhone')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
    });

    Route::controller(OtpVerificationController::class)->prefix('otp')->group(function () {
        Route::post('send', 'sendOtp')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);
        Route::post('verify', 'verifyOtp')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);
    });

    Route::post('register/social', [SocialAuthController::class, 'registerWithSocialMedia'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);

    Route::controller(SocialAuthController::class)->prefix('social')->group(function () {
        Route::post('login', 'socialLogin')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_LOGIN);
        Route::post('phone', 'updatePhone')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
        Route::post('existing-account', 'existingAccount')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_LOGIN);
    });

    Route::post('identity/verify', [IdentityVerificationController::class, 'verifyIdentity'])
        ->middleware(['auth:api', 'throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY]);

    Route::post('firebase/session', [FirebaseAuthController::class, 'storeSession'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);

    Route::post('firebase/verify', [FirebaseAuthController::class, 'verifyOtp'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);

    Route::controller(ForgotPasswordController::class)->prefix('password')->group(function () {
        Route::post('forgot', 'forgotPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);
        Route::post('verify-otp', 'verifyToken')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);
        Route::put('reset', 'resetPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
    });
});

Route::post('contact-us', [ContactController::class, 'store']);
Route::get('get-guest-id', [GuestController::class, 'create']);

Route::prefix('customer')->group(function () {
    Route::put('device-token', [AccountController::class, 'updateDeviceToken']);

    Route::controller(DeliveryRestrictionController::class)->prefix('delivery-restrictions')->group(function () {
        Route::get('countries', 'getCountries');
        Route::get('zip-codes', 'getZipCodes');
    });

    Route::controller(AddressController::class)->prefix('addresses')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'create');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'delete');
    });
});

Route::prefix('customer')->middleware('auth:api')->group(function () {
    Route::get('profile', [AccountController::class, 'getProfile']);
    Route::put('profile', [AccountController::class, 'updateProfile']);
    Route::put('language', [AccountController::class, 'updateLanguage']);
    Route::delete('account', [AccountController::class, 'deleteAccount']);

    Route::controller(WalletController::class)->prefix('wallet')->group(function () {
        Route::get('transactions', 'getTransactions');
        Route::get('bonuses', 'getBonuses');
        Route::post('funds', 'addFund');
    });

    Route::controller(LoyaltyController::class)->prefix('loyalty')->group(function () {
        Route::get('transactions', 'getTransactions');
        Route::post('conversions', 'convert');
    });

    Route::controller(SupportTicketController::class)->prefix('support-tickets')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'create');
        Route::get('{id}/conversations', 'getConversations');
        Route::post('{id}/conversations', 'reply');
        Route::put('{id}/close', 'close');
    });

    Route::controller(ChatController::class)->prefix('chats')->group(function () {
        Route::get('/', 'getThreads');

        Route::get('{type}/{id}/messages', 'getMessages');
        Route::post('{type}/{id}/messages', 'sendMessage');
        Route::put('{type}/{id}/seen', 'markSeen');
    });
});

Route::controller(CartController::class)->prefix('customer/cart')->group(function () {
    Route::get('/', 'getList');
    Route::delete('/', 'clear');
    Route::post('items', 'addItem');
    Route::put('items/{id}', 'updateItem')->whereNumber('id');
    Route::delete('items/{id}', 'deleteItem')->whereNumber('id');
    Route::put('selection', 'updateSelection');
    Route::post('merge-guest', 'mergeGuestCart')->middleware('auth:api');
    Route::get('referral-discount', 'getReferralDiscount')->middleware('auth:api');
});

Route::controller(RestockRequestController::class)->prefix('customer/restock-requests')->group(function () {
    Route::get('/', 'getList')->middleware('auth:api');
    Route::post('/', 'create');
    Route::delete('/', 'delete')->middleware('auth:api');
});

Route::controller(ShippingMethodController::class)->prefix('customer')->group(function () {
    Route::get('shipping-methods', 'getSellerMethods');
    Route::get('shipping-methods/{id}', 'find')->whereNumber('id');
    Route::put('cart/shipping-methods', 'choose');
    Route::get('cart/shipping-methods', 'getChosen');
    Route::get('cart/shipping-type', 'getShippingType');
});

Route::controller(CouponController::class)->prefix('customer/coupons')->middleware('auth:api')->group(function () {
    Route::get('applicable', 'getApplicable');
    Route::get('discount', 'getDiscount');
});

Route::controller(OrderPlacementController::class)->prefix('customer')->group(function () {
    Route::post('orders/cash-on-delivery', 'placeByCashOnDelivery');
    Route::post('orders/offline-payment', 'placeByOfflinePayment');
    Route::post('orders/wallet', 'placeByWallet')->middleware('auth:api');
    Route::get('offline-payment-methods', 'getOfflinePaymentMethods');
});

Route::controller(OrderController::class)->prefix('customer/orders')->group(function () {
    Route::get('/', 'getList')->middleware('auth:api');
    Route::get('{id}', 'find')->whereNumber('id');
    Route::get('{id}/items', 'getItems')->whereNumber('id');
    Route::get('{id}/invoice', 'getInvoice')->whereNumber('id');
});

Route::controller(OrderActionController::class)->prefix('customer/orders')->group(function () {
    Route::put('{id}/cancel', 'cancel')->whereNumber('id');
    Route::post('{id}/reorder', 'reorder')->whereNumber('id')->middleware('auth:api');
});

Route::controller(OrderTrackingController::class)->prefix('orders')->group(function () {
    Route::post('track', 'track');
    Route::get('{id}/tracking', 'getTracking')->whereNumber('id');
    Route::get('{id}/status-history', 'getStatusHistory')->whereNumber('id');
});

Route::controller(RefundController::class)->prefix('customer/refunds')->middleware('auth:api')->group(function () {
    Route::get('eligibility/{orderDetailId}', 'getEligibility')->whereNumber('orderDetailId');
    Route::post('/', 'create');
    Route::get('{orderDetailId}', 'find')->whereNumber('orderDetailId');
});

Route::controller(DigitalProductDownloadController::class)->prefix('customer/order-items')->group(function () {
    Route::get('{orderDetailId}/download', 'download')->whereNumber('orderDetailId');
    Route::post('{orderDetailId}/download/otp', 'sendOtp')->whereNumber('orderDetailId');
    Route::post('{orderDetailId}/download/verify', 'verifyOtp')->whereNumber('orderDetailId');
});

Route::controller(OrderPaymentController::class)->prefix('customer')->group(function () {
    Route::post('orders/{id}/due-payments/wallet', 'payDueByWallet')->whereNumber('id');
    Route::post('orders/{id}/due-payments/cash-on-delivery', 'payDueByCashOnDelivery')->whereNumber('id');
    Route::post('orders/{id}/due-payments/offline-payment', 'payDueByOfflinePayment')->whereNumber('id');
    Route::post('orders/{id}/due-payments/digital-payment', 'payDueByDigitalPayment')->whereNumber('id');
    Route::post('payments/digital', 'createDigitalPayment');
});

Route::controller(WishlistController::class)->prefix('customer/wishlist')->middleware('auth:api')->group(function () {
    Route::get('/', 'getList');
    Route::post('/', 'create');
    Route::delete('{productId}', 'delete')->whereNumber('productId');
});

Route::controller(CompareController::class)->prefix('customer/compare')->middleware('auth:api')->group(function () {
    Route::get('/', 'getList');
    Route::post('/', 'create');
    Route::put('{id}', 'replace')->whereNumber('id');
    Route::delete('/', 'clear');
});

Route::controller(ProductReviewController::class)->group(function () {
    Route::get('products/{slug}/reviews', 'getList');
    Route::get('products/{productId}/rating', 'getRating')->whereNumber('productId');

    Route::middleware('auth:api')->group(function () {
        Route::post('customer/reviews', 'create');
        Route::match(['put', 'post'], 'customer/reviews/{id}', 'update')->whereNumber('id');
        Route::delete('customer/reviews/{id}/images', 'deleteImage')->whereNumber('id');
        Route::get('customer/orders/{orderId}/products/{productId}/review', 'findByOrder')->whereNumber(['orderId', 'productId']);
    });
});

Route::controller(DeliverymanReviewController::class)->prefix('customer/orders')->group(function () {
    Route::get('{orderId}/deliveryman-review', 'find')->whereNumber('orderId');
    Route::post('{orderId}/deliveryman-review', 'create')->whereNumber('orderId')->middleware('auth:api');
    Route::put('{orderId}/deliveryman-review', 'update')->whereNumber('orderId');
});

Route::prefix('products')->controller(ProductSectionController::class)->group(function () {
    Route::get('latest', 'getLatest');
    Route::get('new-arrival', 'getNewArrival');
    Route::get('featured', 'getFeatured');
    Route::get('top-rated', 'getTopRated');
    Route::get('best-selling', 'getBestSelling');
});

Route::controller(ProductRecommendationController::class)->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('discounted', 'getDiscounted');
        Route::get('most-searched', 'getMostSearched');
        Route::get('most-demanded', 'getMostDemanded');
        Route::get('just-for-you', 'getJustForYou');
    });

    Route::get('customer/products/shop-again', 'getShopAgain')->middleware('auth:api');
});

Route::prefix('products')->controller(ProductSearchController::class)->group(function () {
    Route::get('search', 'search');
    Route::post('filter', 'filter');
    Route::get('suggestions', 'getSuggestions');
});

Route::prefix('categories')->controller(CategoryController::class)->group(function () {
    Route::get('home', 'getHomeCategories');
    Route::get('find-what-you-need', 'getFindWhatYouNeed');
    Route::get('{categoryId}/products', 'getProducts')->whereNumber('categoryId');
});

Route::get('brands/{brandId}/products', [BrandController::class, 'getProducts'])->whereNumber('brandId');

Route::controller(DigitalCatalogueController::class)->group(function () {
    Route::get('digital-authors', 'getAuthors');
    Route::get('digital-publishing-houses', 'getPublishingHouses');
});

Route::prefix('shops/{slug}/products')->controller(ShopProductController::class)->group(function () {
    Route::get('/', 'getList');
    Route::get('best-selling', 'getBestSelling');
    Route::get('featured', 'getFeatured');
    Route::get('recommended', 'getRecommended');
});

Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::get('shipping-methods', 'getShippingMethods');
    Route::get('{slug}/share-link', 'getShareLink');
    Route::get('{slug}/related', 'getRelated');
    Route::get('{slug}/counts', 'getCounts');
    Route::get('{slug}', 'find');
});
