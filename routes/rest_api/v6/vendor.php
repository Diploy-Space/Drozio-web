<?php

use App\Http\Controllers\RestAPI\v6\Vendor\Auth\AuthenticationController;
use App\Http\Controllers\RestAPI\v6\Vendor\Auth\FirebaseAuthController;
use App\Http\Controllers\RestAPI\v6\Vendor\Auth\ForgotPasswordController;
use App\Http\Controllers\RestAPI\v6\Vendor\CatalogueController;
use App\Http\Controllers\RestAPI\v6\Vendor\ChatController;
use App\Http\Controllers\RestAPI\v6\Vendor\ClearanceSaleController;
use App\Http\Controllers\RestAPI\v6\Vendor\CouponController;
use App\Http\Controllers\RestAPI\v6\Vendor\DashboardController;
use App\Http\Controllers\RestAPI\v6\Vendor\DeliveryManController;
use App\Http\Controllers\RestAPI\v6\Vendor\DeliveryManWithdrawController;
use App\Http\Controllers\RestAPI\v6\Vendor\EmergencyContactController;
use App\Http\Controllers\RestAPI\v6\Vendor\NotificationController;
use App\Http\Controllers\RestAPI\v6\Vendor\OrderController;
use App\Http\Controllers\RestAPI\v6\Vendor\OrderEditController;
use App\Http\Controllers\RestAPI\v6\Vendor\OrderFulfilmentController;
use App\Http\Controllers\RestAPI\v6\Vendor\PaymentMethodController;
use App\Http\Controllers\RestAPI\v6\Vendor\PosController;
use App\Http\Controllers\RestAPI\v6\Vendor\ProductController;
use App\Http\Controllers\RestAPI\v6\Vendor\ProductInsightController;
use App\Http\Controllers\RestAPI\v6\Vendor\ProductMediaController;
use App\Http\Controllers\RestAPI\v6\Vendor\ProductStockController;
use App\Http\Controllers\RestAPI\v6\Vendor\ProfileController;
use App\Http\Controllers\RestAPI\v6\Vendor\RefundController;
use App\Http\Controllers\RestAPI\v6\Vendor\RestockRequestController;
use App\Http\Controllers\RestAPI\v6\Vendor\ReviewController;
use App\Http\Controllers\RestAPI\v6\Vendor\SalesReportController;
use App\Http\Controllers\RestAPI\v6\Vendor\ShippingController;
use App\Http\Controllers\RestAPI\v6\Vendor\ShopController;
use App\Http\Controllers\RestAPI\v6\Vendor\TransactionController;
use App\Http\Controllers\RestAPI\v6\Vendor\WithdrawController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

Route::prefix('seller/auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_LOGIN);

    Route::post('register', [AuthenticationController::class, 'register'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);

    Route::post('availability/phone', [AuthenticationController::class, 'checkExisting'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);

    Route::post('logout', [AuthenticationController::class, 'logout'])
        ->middleware('v6_seller_auth');

    Route::post('firebase/session', [FirebaseAuthController::class, 'storeSession'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);

    Route::post('firebase/verify', [FirebaseAuthController::class, 'verifyOtp'])
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);

    Route::controller(ForgotPasswordController::class)->prefix('password')->group(function () {
        Route::post('forgot', 'forgotPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_SEND);
        Route::post('verify-otp', 'verifyOtp')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_OTP_VERIFY);
        Route::put('reset', 'resetPassword')
            ->middleware('throttle:'.RouteServiceProvider::LIMITER_AUTH_WRITE);
    });
});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {

    Route::controller(PosController::class)->prefix('pos')->group(function () {
        Route::get('customers', 'getCustomers');
        Route::post('customers', 'storeCustomer');
    });

    Route::controller(ChatController::class)->prefix('chats')->group(function () {
        Route::get('/', 'getThreads');
        Route::get('{type}/{id}/messages', 'getMessages');
        Route::post('{type}/{id}/messages', 'sendMessage');
        Route::put('{type}/{id}/seen', 'markSeen');
    });

    Route::controller(CouponController::class)->prefix('coupons')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::get('customers', 'getCustomers');
        Route::post('check', 'check');
        Route::put('{id}', 'update');
        Route::put('{id}/status', 'updateStatus');
        Route::delete('{id}', 'delete');
    });

    Route::controller(DeliveryManWithdrawController::class)->prefix('delivery-men/withdraws')->group(function () {
        Route::get('/', 'getList');
        Route::get('{id}', 'getDetails');
        Route::put('{id}/status', 'updateStatus');
    });

    Route::controller(DeliveryManController::class)->prefix('delivery-men')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::get('options', 'getOptions');
        Route::get('top', 'getTop');
        Route::get('order-status-history/{orderId}', 'getOrderStatusHistory');
        Route::get('{id}', 'getDetails');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'delete');
        Route::put('{id}/status', 'updateStatus');
        Route::get('{id}/earnings', 'getEarnings');
        Route::get('{id}/reviews', 'getReviews');
        Route::get('{id}/cash-collections', 'getCashCollections');
        Route::post('{id}/cash-collections', 'collectCash');
    });

    Route::controller(EmergencyContactController::class)->prefix('emergency-contacts')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::put('{id}/status', 'updateStatus');
        Route::delete('{id}', 'delete');
    });

    Route::controller(WithdrawController::class)->prefix('withdraws')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::get('methods', 'getMethods');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'delete');
    });

    Route::controller(PaymentMethodController::class)->prefix('payment-methods')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::put('{id}/status', 'updateStatus');
        Route::put('{id}/default', 'updateDefault');
        Route::delete('{id}', 'delete');
    });

    Route::controller(ShippingController::class)->prefix('shipping')->group(function () {
        Route::get('type', 'getType');
        Route::put('type', 'updateType');
        Route::get('category-costs', 'getCategoryCosts');
        Route::put('category-costs', 'updateCategoryCosts');
        Route::get('methods', 'getMethods');
        Route::post('methods', 'storeMethod');
        Route::get('methods/{id}', 'getMethod');
        Route::put('methods/{id}', 'updateMethod');
        Route::put('methods/{id}/status', 'updateMethodStatus');
        Route::delete('methods/{id}', 'deleteMethod');
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'getProfile');
        Route::put('/', 'updateProfile');
    });

    Route::controller(ShopController::class)->prefix('shop')->group(function () {
        Route::get('/', 'getShop');
        Route::put('/', 'updateShop');
        Route::get('verification-criteria', 'getVerificationCriteria');
        Route::put('temporary-close', 'updateTemporaryClose');
        Route::put('vacation', 'updateVacation');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::put('language', 'updateLanguage');
        Route::put('fcm-token', 'updateFcmToken');
        Route::delete('account', 'deleteAccount');
    });

    Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
        Route::get('/', 'getList');
        Route::put('{id}/seen', 'markSeen');
    });

    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::get('/', 'getList');
        Route::post('/', 'store');
        Route::post('variations', 'getVariationCombinations');
        Route::put('{id}', 'update');
        Route::delete('{id}/images', 'deleteImage');
    });

    Route::controller(ClearanceSaleController::class)->prefix('clearance-sale')->group(function () {
        Route::get('/', 'getSetup');
        Route::put('/', 'updateSetup');
        Route::post('seo', 'updateSeo');
        Route::put('status', 'updateSetupStatus');
        Route::get('products', 'getProducts');
        Route::get('products/available', 'getAvailableProducts');
        Route::post('products', 'addProducts');
        Route::put('products/discount', 'updateProductDiscount');
        Route::put('products/status', 'updateProductStatus');
        Route::delete('products', 'deleteAllProducts');
        Route::delete('products/{productId}', 'deleteProduct');
    });

});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('retail/earning-statistics', 'getEarningStatistics');
            Route::get('order-statistics', 'getOrderStatistics');
            Route::get('monthly-earnings', 'getMonthlyEarnings');
            Route::get('monthly-commissions', 'getMonthlyCommissions');
        });
        Route::put('setup-guide', 'updateSetupGuide');
    });
});

Route::middleware('v6_seller_auth')->prefix('seller/retail')->group(function () {
    Route::get('sales-report', [SalesReportController::class, 'getReport']);
    Route::get('transactions', [TransactionController::class, 'getList']);
});

Route::middleware('v6_seller_auth')->prefix('seller')->controller(ReviewController::class)->group(function () {
    Route::get('reviews', 'getList');
    Route::post('reviews/{id}/reply', 'reply')->whereNumber('id');
    Route::put('reviews/{id}/status', 'updateStatus')->whereNumber('id');
    Route::get('products/{id}/reviews', 'getProductReviews')->whereNumber('id');
});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {
    Route::controller(OrderController::class)->prefix('orders')->group(function () {
        Route::get('/', 'getList');
        Route::get('{id}', 'find')->whereNumber('id');
        Route::get('{id}/invoice', 'getInvoice');
        Route::put('{id}/delivery-man', 'assignDeliveryMan')->whereNumber('id');
        Route::put('{id}/delivery-schedule', 'updateDeliverySchedule')->whereNumber('id');
        Route::put('{id}/payment-status', 'updatePaymentStatus')->whereNumber('id');
    });

    Route::get('delivery-men/{id}/orders', [DeliveryManController::class, 'getOrders'])->whereNumber('id');
});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {
    Route::controller(OrderFulfilmentController::class)->prefix('orders')->group(function () {
        Route::put('{id}/status', 'updateStatus')->whereNumber('id');
        Route::post('{id}/digital-files', 'uploadDigitalFile')->whereNumber('id');
        Route::put('{id}/third-party-delivery', 'assignThirdPartyDelivery')->whereNumber('id');
        Route::put('{id}/address', 'updateAddress')->whereNumber('id');
        Route::put('{id}/items', 'updateItems')->whereNumber('id');
    });
});

Route::middleware('v6_seller_auth')->prefix('seller')->controller(OrderEditController::class)->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('{id}/edit/validate', 'validateEdit')->whereNumber('id');
        Route::put('{id}/edit', 'submitEdit')->whereNumber('id');
        Route::put('{id}/edit/cash-on-delivery', 'switchDueToCashOnDelivery')->whereNumber('id');
        Route::get('{id}/edit/products', 'getEditableProducts')->whereNumber('id');
    });

    Route::get('products/all', 'getAllProducts');
});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {
    Route::controller(RefundController::class)->prefix('refunds')->group(function () {
        Route::get('/', 'getList');
        Route::get('{id}', 'find')->whereNumber('id');
        Route::get('{id}/order-item', 'getOrderItem')->whereNumber('id');
        Route::put('{id}/status', 'updateStatus')->whereNumber('id');
    });
});

Route::middleware('v6_seller_auth')->prefix('seller/pos')->controller(PosController::class)->group(function () {
    Route::get('categories', 'getCategories');
    Route::get('products/barcode', 'findProductByBarcode');
    Route::get('products', 'getProducts');
    Route::post('tax-amount', 'calculateTax');
    Route::post('orders', 'placeOrder');
    Route::get('orders/{id}/invoice', 'getInvoice')->whereNumber('id');
});

Route::middleware('v6_seller_auth')->prefix('seller/products')->controller(ProductController::class)->group(function () {
    Route::get('{id}', 'getDetails')->whereNumber('id');
    Route::get('{id}/edit', 'getEditData')->whereNumber('id');
    Route::get('{id}/images', 'getImages')->whereNumber('id');
    Route::put('{id}/status', 'updateStatus')->whereNumber('id');
    Route::delete('{id}', 'delete')->whereNumber('id');
});

Route::middleware('v6_seller_auth')->prefix('seller/products')->controller(ProductStockController::class)->group(function () {
    Route::get('stock-out', 'getStockOutList');
    Route::get('stock-limit-status', 'getStockLimitStatus');
    Route::put('{id}/quantity', 'updateQuantity')->whereNumber('id');
    Route::get('{id}/barcode', 'generateBarcode')->whereNumber('id');
});

Route::middleware('v6_seller_auth')->prefix('seller')->controller(ProductMediaController::class)->group(function () {
    Route::post('products/images', 'uploadImages');
    Route::post('products/digital-files', 'uploadDigitalFile');
    Route::delete('products/{id}/digital-file', 'deleteDigitalFile')->whereNumber('id');
    Route::delete('products/{id}/preview-file', 'deletePreviewFile')->whereNumber('id');
    Route::get('digital-authors', 'getAuthors');
    Route::get('digital-publishing-houses', 'getPublishingHouses');
});

Route::middleware('v6_seller_auth')->prefix('seller')->group(function () {
    Route::controller(ProductInsightController::class)->prefix('products')->group(function () {
        Route::get('top-selling', 'getTopSelling');
        Route::get('most-popular', 'getMostPopular');
    });

    Route::controller(CatalogueController::class)->group(function () {
        Route::get('categories', 'getCategories');
        Route::get('brands', 'getBrands');
    });
});

Route::middleware('v6_seller_auth')->prefix('seller/restock-requests')->controller(RestockRequestController::class)->group(function () {
    Route::get('/', 'getList');
    Route::get('brands', 'getBrands');
    Route::put('{id}/stock', 'updateStock')->whereNumber('id');
    Route::delete('{id}', 'delete')->whereNumber('id');
});
