<?php

use App\Http\Controllers\Web\v6\AddressController;
use App\Http\Controllers\Web\v6\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\v6\Auth\LoginController;
use App\Http\Controllers\Web\v6\Auth\LoginVerificationController;
use App\Http\Controllers\Web\v6\Auth\PasswordResetController;
use App\Http\Controllers\Web\v6\Auth\RegisterController;
use App\Http\Controllers\Web\v6\Auth\RegisterOtpController;
use App\Http\Controllers\Web\v6\Auth\SocialAuthController;
use App\Http\Controllers\Web\v6\BrandController;
use App\Http\Controllers\Web\v6\CartController;
use App\Http\Controllers\Web\v6\CartCouponController;
use App\Http\Controllers\Web\v6\CartItemController;
use App\Http\Controllers\Web\v6\CartPageController;
use App\Http\Controllers\Web\v6\CartReorderController;
use App\Http\Controllers\Web\v6\CategoryController;
use App\Http\Controllers\Web\v6\ChattingController;
use App\Http\Controllers\Web\v6\CheckoutController;
use App\Http\Controllers\Web\v6\CheckoutSelectionController;
use App\Http\Controllers\Web\v6\ContactController;
use App\Http\Controllers\Web\v6\CurrencyController;
use App\Http\Controllers\Web\v6\CustomerCouponController;
use App\Http\Controllers\Web\v6\CustomerOrderActionController;
use App\Http\Controllers\Web\v6\CustomerOrderController;
use App\Http\Controllers\Web\v6\DigitalProductDownloadController;
use App\Http\Controllers\Web\v6\HomeController;
use App\Http\Controllers\Web\v6\LocaleController;
use App\Http\Controllers\Web\v6\LoyaltyController;
use App\Http\Controllers\Web\v6\MaintenanceModeController;
use App\Http\Controllers\Web\v6\NewsletterController;
use App\Http\Controllers\Web\v6\OrderConfirmationController;
use App\Http\Controllers\Web\v6\OrderPlacementController;
use App\Http\Controllers\Web\v6\PageController;
use App\Http\Controllers\Web\v6\PaymentRequestController;
use App\Http\Controllers\Web\v6\PaymentResultController;
use App\Http\Controllers\Web\v6\ProductCompareController;
use App\Http\Controllers\Web\v6\ProductDetailsController;
use App\Http\Controllers\Web\v6\ProductListController;
use App\Http\Controllers\Web\v6\ProductQuickViewController;
use App\Http\Controllers\Web\v6\ProductSearchController;
use App\Http\Controllers\Web\v6\ProductViewStyleController;
use App\Http\Controllers\Web\v6\ProfileController;
use App\Http\Controllers\Web\v6\ReferEarnController;
use App\Http\Controllers\Web\v6\RefundController;
use App\Http\Controllers\Web\v6\RestockRequestController;
use App\Http\Controllers\Web\v6\ReviewController;
use App\Http\Controllers\Web\v6\ReviewListController;
use App\Http\Controllers\Web\v6\Shop\FollowerController;
use App\Http\Controllers\Web\v6\ShopController;
use App\Http\Controllers\Web\v6\SupportTicketController;
use App\Http\Controllers\Web\v6\TrackOrderController;
use App\Http\Controllers\Web\v6\VendorController;
use App\Http\Controllers\Web\v6\WalletController;
use App\Http\Controllers\Web\v6\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('maintenance-mode', [MaintenanceModeController::class, 'index'])->name('maintenance-mode');

Route::permanentRedirect('helpTopic', '/help-topics');
Route::permanentRedirect('product-compare/index', '/product-compare');

Route::middleware(['maintenance_mode', 'guestCheck'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

    Route::get('quick-view', [ProductQuickViewController::class, 'show'])->name('quick-view');

    Route::get('search/products', [ProductSearchController::class, 'index'])->name('product-search');

    Route::post('product-view-style', [ProductViewStyleController::class, 'update'])->name('product-view-style.update');

    Route::post('currency', [CurrencyController::class, 'update'])->name('currency.update');

    Route::prefix('product-compare')->as('product-compare.')->controller(ProductCompareController::class)->group(function () {
        Route::get('/', 'index')->name('index')->middleware('customer');
        Route::post('/', 'add')->name('store');
        Route::get('search', 'search')->name('search');
        Route::delete('/', 'deleteAll')->name('destroy-all')->middleware('customer');
        Route::delete('{id}', 'delete')->name('destroy')->middleware('customer');
    });

    Route::get('customer/wishlist', [WishlistController::class, 'index'])->name('wishlists.index')->middleware('customer');

    Route::prefix('wishlists')->as('wishlists.')->controller(WishlistController::class)->group(function () {
        Route::get('header', 'getHeaderWishlist')->name('header');
        Route::post('/', 'toggle')->name('store');
        Route::delete('/', 'deleteAll')->name('destroy-all')->middleware('customer');
        Route::delete('{productId}', 'delete')->name('destroy')->middleware('customer');
    });

    Route::middleware('customer')->prefix('review')->as('review.')->controller(ReviewController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::post('deliveryman', 'storeDeliverymanReview')->name('deliveryman.store');
        Route::delete('image', 'deleteImage')->name('image.destroy');
    });

    Route::prefix('contact')->as('contact.')->controller(ContactController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('captcha/{token}', 'captcha')->name('captcha');
    });

    Route::controller(PageController::class)->group(function () {
        Route::get('business-page/{slug}', 'show')->name('business-page.view');
        Route::get('contacts', 'showContactPage')->name('contacts');
        Route::get('help-topics', 'getHelpTopics')->name('help-topics');
    });

    Route::controller(CategoryController::class)->group(function () {
        Route::get('categories', 'index')->name('categories');
        Route::get('categories/{id}/children', 'getChildren')->name('categories.children');
    });

    Route::get('brands', [BrandController::class, 'index'])->name('brands');

    Route::get('vendors', [VendorController::class, 'index'])->name('vendors');

    Route::controller(ShopController::class)->group(function () {
        Route::get('vendor-shop/{slug}', 'show')->name('vendor-shop');
        Route::post('vendor-shop/vacation-status', 'vacationCheck')->name('vendor-shop.vacation-status');
    });

    Route::post('vendor-shop/follow', [FollowerController::class, 'toggle'])->name('vendor-shop.follow');

    Route::get('product/{slug}', [ProductDetailsController::class, 'show'])->name('product');

    Route::controller(ProductListController::class)->group(function () {
        Route::get('products', 'index')->name('products');
        Route::get('brand/{slug}', 'brandProducts')->name('brand-products');
        Route::get('category/{slug}', 'categoryProducts')->name('category-products');
        Route::get('featured-products', 'featuredProducts')->name('featured-products');
        Route::get('featured-deal-products', 'featuredDealProducts')->name('featured-deal-products');
        Route::get('latest-products', 'latestProducts')->name('latest-products');
        Route::get('best-selling-products', 'bestSellingProducts')->name('best-selling-products');
        Route::get('top-rated-products', 'topRatedProducts')->name('top-rated-products');
        Route::get('most-favorite-products', 'mostFavoriteProducts')->name('most-favorite-products');
        Route::get('discounted-products', 'discountedProducts')->name('discounted-products');
        Route::get('clearance-sale-products', 'clearanceSaleProducts')->name('clearance-sale-products');
        Route::get('flash-deals/{id}', 'flashDealProducts')->name('flash-deals');
        Route::post('flash-deals/{id}', 'filterFlashDealProducts')->name('flash-deals.products');
        Route::get('top-rated', 'topRatedProducts')->name('top-rated');
        Route::get('best-sell', 'bestSellingProducts')->name('best-sell');
        Route::get('new-product', 'latestProducts')->name('new-product');
        Route::post('products/filter', 'filterProducts')->name('products.filter');
    });

    Route::prefix('review')->as('review.')->controller(ReviewListController::class)->group(function () {
        Route::post('product-list', 'product')->name('product-list');
        Route::post('shop-list', 'shop')->name('shop-list');
    });
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(OrderPlacementController::class)->group(function () {
    Route::post('checkout-complete', 'placeCashOnDeliveryOrder')->name('checkout-complete');
    Route::post('offline-payment-checkout-complete', 'placeOfflinePaymentOrder')->name('offline-payment-checkout-complete');
    Route::get('pay-offline-method-list', 'getOfflinePaymentMethodForm')->name('pay-offline-method-list')->middleware('guestCheck');
    Route::post('checkout-complete-wallet', 'placeWalletOrder')->name('checkout-complete-wallet');
});

Route::prefix('cart')->as('cart.')->controller(CartController::class)->group(function () {
    Route::post('variant-price', 'getVariantPrice')->name('variant-price');
    Route::post('add', 'add')->name('add');
    Route::post('remove', 'remove')->name('remove');
    Route::post('update-quantity', 'updateQuantity')->name('update-quantity');
    Route::post('update-quantity-guest', 'updateGuestQuantity')->name('update-quantity.guest');
    Route::post('nav-cart-items', 'getNavCart')->name('nav-cart');
    Route::post('floating-nav-cart-items', 'getFloatingNavCart')->name('floating-nav-cart-items');
});

Route::prefix('cart')->as('cart.')->controller(CartItemController::class)->group(function () {
    Route::post('update-variation', 'updateVariation')->name('update-variation');
    Route::get('remove-all', 'removeAll')->name('remove-all');
    Route::post('select-cart-items', 'updateCheckedItems')->name('select-cart-items');
});

Route::prefix('cart')->as('cart.')->controller(CartReorderController::class)->group(function () {
    Route::post('add-all-to-cart', 'addWishlistToCart')->name('add-all-to-cart');
    Route::post('order-again', 'orderAgain')->name('order-again')->middleware('customer');
});

Route::prefix('coupon')->as('coupon.')->controller(CartCouponController::class)->group(function () {
    Route::post('apply', 'apply')->name('apply');
    Route::get('remove', 'remove')->name('remove');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(CartPageController::class)->group(function () {
    Route::get('shop-cart', 'index')->name('shop-cart');
    Route::post('order-note', 'updateOrderNote')->name('order-note');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->group(function () {
    Route::post('subscription', [NewsletterController::class, 'subscribe'])->name('subscription');
    Route::get('search-shop', [VendorController::class, 'index'])->name('search-shop');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(CheckoutController::class)->group(function () {
    Route::get('checkout-details', 'details')->name('checkout-details');
    Route::get('checkout-payment', 'payment')->name('checkout-payment');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(OrderConfirmationController::class)->group(function () {
    Route::get('order-placed', 'index')->name('order-placed');
    Route::get('order-placed-success', 'success')->name('order-placed-success');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(DigitalProductDownloadController::class)->group(function () {
    Route::get('digital-product-download/{id}', 'download')->name('digital-product-download');
    Route::post('digital-product-download-otp-verify', 'verifyOtp')->name('digital-product-download-otp-verify');
    Route::post('digital-product-download-otp-reset', 'resendOtp')->name('digital-product-download-otp-reset');

    Route::prefix('digital-product-download-pos')->as('digital-product-download-pos.')->group(function () {
        Route::get('/', 'index')->name('index');
    });
});

Route::middleware(['maintenance_mode', 'guestCheck', 'customer'])->prefix('customer/tickets')->as('support-ticket.')->controller(SupportTicketController::class)->group(function () {
    Route::get('delete/{id}', 'delete')->name('delete')->whereNumber('id');
    Route::get('close/{id}', 'close')->name('close')->whereNumber('id');
    Route::get('{id}', 'show')->name('index')->whereNumber('id');
    Route::post('{id}', 'reply')->name('comment')->whereNumber('id');
});

Route::middleware(['maintenance_mode', 'guestCheck'])
    ->prefix('customer/track-order')
    ->as('track-order.')
    ->controller(TrackOrderController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('result-view', 'result')->name('result-view');
        Route::get('last', 'last')->name('last');
        Route::any('result', 'result')->name('result');
    });

Route::middleware(['maintenance_mode', 'guestCheck'])
    ->get('customer/order-wise-result-view', [TrackOrderController::class, 'orderWiseResult'])
    ->name('track-order.order-wise-result-view');

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(ProfileController::class)->group(function () {
    Route::get('customer/profile', 'show')->name('user-profile')->middleware('customer');
    Route::get('customer/profile/edit', 'account')->name('user-account')->middleware('customer');
    Route::post('customer/profile/update', 'update')->name('user-update')->middleware('customer');
    Route::get('customer/profile/delete/{id}', 'delete')->name('account-delete');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(AddressController::class)->group(function () {
    Route::get('customer/address/add', 'create')->name('account-address-add');
    Route::get('customer/address', 'index')->name('account-address');
    Route::post('customer/address/store', 'store')->name('address-store');
    Route::get('customer/address/delete', 'delete')->name('address-delete');
    Route::get('customer/address/edit/{id}', 'edit')->name('address-edit');
    Route::post('customer/address/update', 'update')->name('address-update');
});

Route::middleware(['maintenance_mode', 'guestCheck', 'customer'])->controller(CustomerOrderController::class)->group(function () {
    Route::get('customer/orders', 'index')->name('account-oder');
    Route::get('customer/orders/details', 'details')->name('account-order-details');
    Route::get('customer/orders/details/vendor-info', 'vendorInfo')->name('account-order-details-vendor-info');
    Route::get('customer/orders/details/delivery-man-info', 'deliveryManInfo')->name('account-order-details-delivery-man-info');
    Route::get('customer/orders/details/reviews', 'reviews')->name('account-order-details-reviews');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(CustomerOrderActionController::class)->group(function () {
    Route::get('customer/orders/payment', 'payment')->name('account-payment');
    Route::get('customer/orders/invoice/{id}', 'invoice')->name('generate-invoice');
    Route::get('customer/orders/cancel/{id}', 'cancel')->name('order-cancel');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(RefundController::class)->group(function () {
    Route::get('customer/orders/refund-request/{id}', 'create')->name('refund-request');
    Route::get('customer/orders/refund-details/{id}', 'details')->name('refund-details');
    Route::post('customer/orders/refund-store', 'store')->name('refund-store');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(SupportTicketController::class)->group(function () {
    Route::get('customer/tickets', 'index')->name('account-tickets');
    Route::post('customer/tickets/submit', 'store')->name('ticket-submit');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->group(function () {
    Route::get('account-wishlist', fn () => auth('customer')->check() ? redirect()->route('wishlists.index') : redirect()->route('home'))->name('account-wishlist');
    Route::get('customer/refer-earn', [ReferEarnController::class, 'index'])->name('refer-earn')->middleware('customer');
    Route::get('customer/coupons', [CustomerCouponController::class, 'index'])->name('user-coupons')->middleware('customer');
});

Route::middleware(['maintenance_mode', 'guestCheck', 'customer'])->controller(RestockRequestController::class)->group(function () {
    Route::get('customer/restock-requests', 'index')->name('user-restock-requests');
    Route::get('customer/restock-requests/delete', 'delete')->name('user-restock-request-delete');
});

Route::prefix('cart')->as('cart.')->controller(RestockRequestController::class)->group(function () {
    Route::post('product-restock-request', 'store')->name('product-restock-request');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(ChattingController::class)->group(function () {
    Route::get('customer/inbox/{type?}', 'index')->name('chat')->middleware('customer');
    Route::get('message', 'getMessages')->name('messages');
    Route::post('message', 'store');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(WalletController::class)->group(function () {
    Route::get('customer/wallet/account', 'account')->name('wallet-account');
    Route::get('customer/wallet', 'index')->name('wallet')->middleware('customer');
});

Route::middleware(['maintenance_mode', 'guestCheck'])->controller(LoyaltyController::class)->group(function () {
    Route::get('customer/loyalty', 'index')->name('loyalty')->middleware('customer');
    Route::post('customer/loyalty/exchange', 'exchange')->name('loyalty-exchange-currency');
    Route::get('customer/loyalty/currency-amount', 'getCurrencyAmount')->name('ajax-loyalty-currency-amount');
});

Route::prefix('customer/reward-points')->name('customer.reward-points.')->middleware('auth:customer')->group(function () {
    Route::get('convert', [LoyaltyController::class, 'convertRewardPoints'])->name('convert');
});

Route::get('authentication-failed', [LoginController::class, 'authenticationFailed'])->name('authentication-failed');

Route::prefix('customer/auth')->as('customer.auth.')->controller(LoginController::class)->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', 'index')->name('login');
        Route::post('login', 'login');
        Route::get('get-login-modal-data', 'getModalData')->name('get-login-modal-data');
    });
    Route::get('logout', 'logout')->name('logout');
});

Route::middleware('guest:customer')->prefix('customer/auth/login')->name('customer.auth.')->controller(LoginVerificationController::class)->group(function () {
    Route::get('verify-account', 'index')->name('login.verify-account');
    Route::post('verify-account/submit', 'verify')->name('login.verify-account.submit');
    Route::get('update-info', 'editInfo')->name('login.update-info');
    Route::post('update-info', 'updateInfo');
    Route::post('resend-otp-code', 'resendOtp')->name('resend-otp-code');
});

Route::middleware('guest:customer')->prefix('customer/auth')->as('customer.auth.')->controller(RegisterController::class)->group(function () {
    Route::get('sign-up', 'index')->name('sign-up');
    Route::post('sign-up', 'register');
    Route::get('check-verification', 'checkVerification')->name('check-verification');
});

Route::middleware('guest:customer')->prefix('customer/auth')->as('customer.auth.')->controller(RegisterOtpController::class)->group(function () {
    Route::post('verify', 'verify')->name('verify');
    Route::post('ajax-verify', 'verifyAjax')->name('ajax-verify');
    Route::post('resend-otp', 'resendOtp')->name('resend-otp');
});

Route::prefix('customer/auth')->as('customer.auth.')->controller(SocialAuthController::class)->group(function () {
    Route::get('login/social/confirmation', 'confirmation')->name('social-login-confirmation');
    Route::post('login/social/confirmation/update', 'updateConfirmation')->name('social-login-confirmation.update');
    Route::post('login/social/verify-account', 'verify')->name('login.social.verify-account');
    Route::get('login/{service}', 'redirectToProvider')->name('service-login');
    Route::get('login/{service}/callback', 'handleCallback')->name('service-callback');
});

Route::middleware('guest:customer')->prefix('customer/auth')->as('customer.auth.')->controller(ForgotPasswordController::class)->group(function () {
    Route::get('recover-password', 'index')->name('recover-password');
    Route::post('forgot-password', 'sendResetCode')->name('forgot-password');
    Route::post('verify-recover-password', 'verifyResetCode')->name('verify-recover-password');
});

Route::middleware('guest:customer')->prefix('customer/auth')->name('customer.auth.')->controller(PasswordResetController::class)->group(function () {
    Route::get('otp-verification', 'showOtpForm')->name('otp-verification');
    Route::post('otp-verification', 'verifyOtp');
    Route::get('reset-password', 'showResetForm')->name('reset-password');
    Route::post('reset-password', 'reset')->name('password-recovery');
    Route::post('resend-otp-reset-password', 'resendOtp')->name('resend-otp-reset-password');
});

Route::prefix('customer')->as('customer.')->controller(CheckoutSelectionController::class)->group(function () {
    Route::get('set-payment-method/{name}', 'setPaymentMethod')->name('set-payment-method');
    Route::get('set-shipping-method', 'setShippingMethod')->name('set-shipping-method');
    Route::post('choose-shipping-address', 'chooseShippingAddress')->name('choose-shipping-address');
    Route::post('choose-shipping-address-other', 'chooseOtherShippingAddress')->name('choose-shipping-address-other');
});

Route::prefix('customer')->as('customer.')->controller(PaymentRequestController::class)->group(function () {
    Route::post('web-payment-request', 'checkout')->name('web-payment-request');
    Route::post('customer-add-fund-request', 'addFund')->name('add-fund-request');
    Route::post('customer-order-edit-pay-amount', 'payDueAmount')->name('customer-order-edit-pay-amount');
});

Route::controller(PaymentResultController::class)->group(function () {
    Route::get('web-payment', 'index')->name('web-payment-success');
    Route::get('payment-success', 'success')->name('payment-success');
    Route::get('payment-fail', 'fail')->name('payment-fail');
});

Route::permanentRedirect('user-profile', 'customer/profile');
Route::permanentRedirect('user-account', 'customer/profile/edit');
Route::permanentRedirect('account-delete/{id}', 'customer/profile/delete/{id}');
Route::permanentRedirect('account-address', 'customer/address');
Route::permanentRedirect('account-address-add', 'customer/address/add');
Route::permanentRedirect('account-address-edit/{id}', 'customer/address/edit/{id}');
Route::permanentRedirect('account-oder', 'customer/orders');
Route::permanentRedirect('account-order-details', 'customer/orders/details');
Route::permanentRedirect('account-payment', 'customer/orders/payment');
Route::permanentRedirect('generate-invoice/{id}', 'customer/orders/invoice/{id}');
Route::permanentRedirect('order-cancel/{id}', 'customer/orders/cancel/{id}');
Route::permanentRedirect('refund-request/{id}', 'customer/orders/refund-request/{id}');
Route::permanentRedirect('refund-details/{id}', 'customer/orders/refund-details/{id}');
Route::get('wishlists', fn () => redirect('customer/wishlist', 301));
Route::permanentRedirect('account-tickets', 'customer/tickets');
Route::permanentRedirect('support-ticket/{id}', 'customer/tickets/{id}');
Route::permanentRedirect('user-coupons', 'customer/coupons');
Route::permanentRedirect('refer-earn', 'customer/refer-earn');
Route::permanentRedirect('user-restock-requests', 'customer/restock-requests');
Route::permanentRedirect('chat/{type}', 'customer/inbox/{type}');
Route::permanentRedirect('wallet', 'customer/wallet');
Route::permanentRedirect('loyalty', 'customer/loyalty');
Route::permanentRedirect('track-order', 'customer/track-order');
