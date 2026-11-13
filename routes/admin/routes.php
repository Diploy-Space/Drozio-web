<?php

use App\Http\Controllers\Admin\AdvancedSearchController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Enums\BusinessModule;
use App\Http\Controllers\Admin\BusinessModuleController;
use App\Http\Controllers\Admin\BusinessSettings\WebsiteSetupController;
use App\Http\Controllers\Admin\CategoryShippingCostController;
use App\Http\Controllers\Admin\ChattingController;
use App\Http\Controllers\Admin\Customer\CustomerController;
use App\Http\Controllers\Admin\Customer\CustomerLoyaltyController;
use App\Http\Controllers\Admin\Customer\CustomerWalletController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Deliveryman\DeliveryManCashCollectController;
use App\Http\Controllers\Admin\Deliveryman\DeliveryManController;
use App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController;
use App\Http\Controllers\Admin\Deliveryman\EmergencyContactController;
use App\Http\Controllers\Admin\EarningReportController;
use App\Http\Controllers\Admin\EmailTemplatesController;
use App\Http\Controllers\Admin\Employee\CustomRoleController;
use App\Http\Controllers\Admin\Employee\EmployeeController;
use App\Http\Controllers\Admin\HelpAndSupport\ContactController;
use App\Http\Controllers\Admin\HelpAndSupport\HelpTopicController;
use App\Http\Controllers\Admin\HelpAndSupport\SupportTicketController;
use App\Http\Controllers\Admin\Notification\NotificationController;
use App\Http\Controllers\Admin\Notification\PushNotificationSettingsController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Order\OrderEditController;
use App\Http\Controllers\Admin\Order\RefundController;
use App\Http\Controllers\Admin\Payment\OfflinePaymentMethodController;
use App\Http\Controllers\Admin\People\WithdrawalMethodController;
use App\Http\Controllers\Admin\POS\CartController;
use App\Http\Controllers\Admin\POS\POSController;
use App\Http\Controllers\Admin\POS\POSOrderController;
use App\Http\Controllers\Admin\Product\AttributeController;
use App\Http\Controllers\Admin\Product\BrandController;
use App\Http\Controllers\Admin\Product\CategoryController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\ReviewController;
use App\Http\Controllers\Admin\Product\SubCategoryController;
use App\Http\Controllers\Admin\Product\SubSubCategoryController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Promotion\BannerController;
use App\Http\Controllers\Admin\Promotion\ClearanceSaleController;
use App\Http\Controllers\Admin\Promotion\ClearanceSalePrioritySetupController;
use App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController;
use App\Http\Controllers\Admin\Promotion\CouponController;
use App\Http\Controllers\Admin\Promotion\DealOfTheDayController;
use App\Http\Controllers\Admin\Promotion\FeaturedDealController;
use App\Http\Controllers\Admin\Promotion\FlashDealController;
use App\Http\Controllers\Admin\Promotion\MostDemandedController;
use App\Http\Controllers\Admin\Report\AdminEarningController;
use App\Http\Controllers\Admin\Report\AdminTaxController;
use App\Http\Controllers\Admin\Report\ExpenseTransactionController;
use App\Http\Controllers\Admin\Report\InhouseSaleController;
use App\Http\Controllers\Admin\Report\OrderController as ReportOrderController;
use App\Http\Controllers\Admin\Report\OrderTransactionController;
use App\Http\Controllers\Admin\Report\ProductController as ReportProductController;
use App\Http\Controllers\Admin\Report\ProductStockController;
use App\Http\Controllers\Admin\Report\ProductWishlistController;
use App\Http\Controllers\Admin\Report\RefundTransactionController;
use App\Http\Controllers\Admin\Report\VendorEarningController;
use App\Http\Controllers\Admin\Report\VendorSaleController;
use App\Http\Controllers\Admin\Report\VendorTaxController;
use App\Http\Controllers\Admin\Settings\AddonActivationController;
use App\Http\Controllers\Admin\Settings\AddonController;
use App\Http\Controllers\Admin\Settings\BusinessSettingsController;
use App\Http\Controllers\Admin\Settings\CurrencyController;
use App\Http\Controllers\Admin\Settings\DatabaseSettingController;
use App\Http\Controllers\Admin\Settings\DeliverymanSettingsController;
use App\Http\Controllers\Admin\Settings\DeliveryRestrictionController;
use App\Http\Controllers\Admin\Settings\EnvironmentSettingsController;
use App\Http\Controllers\Admin\Settings\ErrorLogsController;
use App\Http\Controllers\Admin\Settings\FeaturesSectionController;
use App\Http\Controllers\Admin\Settings\FileManagerController;
use App\Http\Controllers\Admin\Settings\FirebaseOTPVerificationController;
use App\Http\Controllers\Admin\Settings\InhouseShopController;
use App\Http\Controllers\Admin\Settings\InvoiceSettingsController;
use App\Http\Controllers\Admin\Settings\LanguageController;
use App\Http\Controllers\Admin\Settings\OrderSettingsController;
use App\Http\Controllers\Admin\Settings\PagesController;
use App\Http\Controllers\Admin\Settings\PrioritySetupController;
use App\Http\Controllers\Admin\Settings\RobotsMetaContentController;
use App\Http\Controllers\Admin\Settings\SEOSettingsController;
use App\Http\Controllers\Admin\Settings\SiteMapController;
use App\Http\Controllers\Admin\Settings\SocialMediaSettingsController;
use App\Http\Controllers\Admin\Settings\SoftwareUpdateController;
use App\Http\Controllers\Admin\Settings\StorageConnectionSettingsController;
use App\Http\Controllers\Admin\Settings\ThemeController;
use App\Http\Controllers\Admin\Settings\VendorRegistrationReasonController;
use App\Http\Controllers\Admin\Settings\VendorRegistrationSettingController;
use App\Http\Controllers\Admin\Settings\VendorSettingsController;
use App\Http\Controllers\Admin\Shipping\ShippingMethodController;
use App\Http\Controllers\Admin\Shipping\ShippingTypeController;
use App\Http\Controllers\Admin\SystemSetup\SystemLoginSetupController;
use App\Http\Controllers\Admin\ThirdParty\ErpController;
use App\Http\Controllers\Admin\ThirdParty\GoogleMapAPIController;
use App\Http\Controllers\Admin\ThirdParty\MailController;
use App\Http\Controllers\Admin\ThirdParty\PaymentMethodController;
use App\Http\Controllers\Admin\ThirdParty\RecaptchaController;
use App\Http\Controllers\Admin\ThirdParty\SMSModuleController;
use App\Http\Controllers\Admin\ThirdParty\SocialLoginSettingsController;
use App\Http\Controllers\Admin\ThirdParty\SocialMediaChatController;
use App\Http\Controllers\Admin\TransactionReportController;
use App\Http\Controllers\Admin\V2SidebarPinController;
use App\Http\Controllers\Admin\Vendor\VendorController;
use App\Http\Controllers\Admin\Vendor\VerificationController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\SharedController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

Route::controller(SharedController::class)->group(function () {
    Route::post('change-language', 'changeLanguage')->name('change-language');
    Route::post('get-session-recaptcha-code', 'getSessionRecaptchaCode')->name('get-session-recaptcha-code');
    Route::post('g-recaptcha-response-store', 'storeRecaptchaResponse')->name('g-recaptcha-response-store');
    Route::get('g-recaptcha-session-store', 'storeRecaptchaSession')->name('g-recaptcha-session-store');
    Route::get('activation-check', 'getActivationCheckView')->name('system.activation-check');
    Route::post('activation-check', 'activationCheck');
});

Route::controller(FirebaseController::class)->group(function () {
    Route::post('system/subscribe-to-topic', 'subscribeToTopic')
        ->name('system.subscribe-to-topic')
        ->middleware('throttle:'.RouteServiceProvider::LIMITER_PUBLIC_WRITE);
});

Route::group(['prefix' => 'login'], function () {
    if (config('app.development_environment')) {
        Route::group(['prefix' => 'forgot-password', 'as' => 'admin.auth.forgot-password.'], function () {
            Route::controller(ForgotPasswordController::class)->group(function () {
                Route::get('/', 'getForgotPasswordView')->name('index');
                Route::post('/', 'sendResetLink');
                Route::get('reset-password', 'getPasswordResetView')->name('reset-password');
                Route::post('reset-password', 'resetPassword');
            });
        });
    }

    Route::get('recaptcha/{tmp}', [LoginController::class, 'generateReCaptcha'])->name('recaptcha');
    Route::get('{loginUrl}', [LoginController::class, 'index']);
    Route::post('/', [LoginController::class, 'login'])->name('login');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin', 'actch:admin_panel']], function () {
    Route::post('business-module/switch/{module}', [BusinessModuleController::class, 'switch'])
        ->name('business-module.switch');

    Route::group(['prefix' => 'v2/sidebar-pins', 'as' => 'v2.sidebar-pins.'], function () {
        Route::get('/', [V2SidebarPinController::class, 'index'])->name('index');
        Route::post('toggle', [V2SidebarPinController::class, 'toggle'])->name('toggle');
        Route::post('replace', [V2SidebarPinController::class, 'replace'])->name('replace');
    });

    Route::get('component', [SharedController::class, 'getComponentView']);
    Route::get('component-snippets', [SharedController::class, 'getComponentSnippetsView']);

    Route::controller(AdvancedSearchController::class)->group(function () {
        Route::post('advanced-search-recent', 'recentSearch')->name('advanced-search-recent');
        Route::get('v2/advanced-search', 'getSearch')->name('v2.advanced-search');
    });
    Route::get('dashboard', [BusinessModuleController::class, 'dashboard'])->name('dashboard-legacy');

    Route::group(['prefix' => 'retail/dashboard', 'as' => 'retail.dashboard.', 'middleware' => ['module:retail.dashboard']], function () {
        Route::controller(DashboardController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('order-status', 'getOrderStatus')->name('order-status');
            Route::get('earning-statistics', 'getEarningStatistics')->name('earning-statistics');
            Route::get('order-statistics', 'getOrderStatistics')->name('order-statistics');
            Route::get('real-time-activities', 'getRealTimeActivities')->name('real-time-activities');
        });
    });

    Route::get('logout', [LoginController::class, 'logout'])->name('logout');

    Route::group(['prefix' => 'retail/pos', 'as' => 'retail.pos.', 'middleware' => ['module:retail.pos']], function () {
        Route::controller(POSController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('change-customer', 'changeCustomer')->name('change-customer');
            Route::post('update-discount', 'updateDiscount')->name('update-discount');
            Route::post('remove-discount', 'removeDiscount')->name('remove-discount');
            Route::post('coupon-discount', 'getCouponDiscount')->name('coupon-discount');
            Route::get('quick-view', 'getQuickView')->name('quick-view');
            Route::get('search-product', 'getSearchedProductsView')->name('search-product');
            Route::get('product-list', 'getProductListView')->name('product-list');
        });

        Route::controller(CartController::class)->group(function () {
            Route::post('get-variant-price', 'getVariantPrice')->name('get-variant-price');
            Route::post('quantity-update', 'updateQuantity')->name('update-quantity');
            Route::get('get-cart-ids', 'getCartIds')->name('get-cart-ids');
            Route::post('clear-cart-ids', 'clearSessionCartIds')->name('clear-cart-ids');
            Route::post('add-to-cart', 'addToCart')->name('add-to-cart');
            Route::post('cart-remove', 'removeCart')->name('remove-cart');
            Route::post('cart-empty', 'emptyCart')->name('empty-cart');
            Route::post('change-cart', 'changeCart')->name('change-cart');
            Route::post('new-cart-id', 'addNewCartId')->name('new-cart-id');
        });

        Route::controller(POSOrderController::class)->group(function () {
            Route::post('order-details/{id}', 'index')->name('order-details');
            Route::post('order-place', 'placeOrder')->name('place-order')->block(lockSeconds: 30, waitSeconds: 30);
            Route::post('cancel-order', 'cancelOrder')->name('cancel-order');
            Route::post('view-hold-orders', 'getAllHoldOrdersView')->name('view-hold-orders');
        });
    });

    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
        Route::controller(ProfileController::class)->group(function () {
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::patch('update/{id}', 'updatePassword');
        });
    });

    Route::group(['prefix' => 'retail/products', 'as' => 'retail.products.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(ProductController::class)->group(function () {
            Route::get('list/{type}', 'index')->name('list');
            Route::get('add', 'getAddView')->name('add');
            Route::post('add', 'add')->name('store');
            Route::get('view/{addedBy}/{id}', 'getView')->name('view');
            Route::post('sku-combination', 'getSkuCombinationView')->name('sku-combination');
            Route::post('color-wise-images', 'getColorWiseImageView')->name('color-wise-images');
            Route::post('digital-variation-combination', 'getDigitalVariationCombinationView')->name('digital-variation-combination');
            Route::post('digital-variation-file-delete', 'deleteDigitalVariationFile')->name('digital-variation-file-delete');
            Route::post('featured-status', 'updateFeaturedStatus')->name('featured-status');
            Route::get('get-categories', 'getCategories')->name('get-categories');
            Route::post('status-update', 'updateStatus')->name('status-update');
            Route::get('barcode/{id}', 'getBarcodeView')->name('barcode');
            Route::get('export-excel/{type}', 'exportList')->name('export-excel');
            Route::get('stock-limit-list', 'getStockLimitListView')->name('stock-limit-list');
            Route::delete('delete/{id}', 'delete')->name('delete');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::post('update-product-images/{id}', 'updateProductImages')->name('update-product-images');
            Route::get('get-variations', 'getVariations')->name('get-variations');
            Route::post('update-quantity', 'updateQuantity')->name('update-quantity');
            Route::get('bulk-import', 'getBulkImportView')->name('bulk-import');
            Route::post('bulk-import', 'importBulkProduct');
            Route::get('updated-product-list/{type}', 'updatedProductList')->name('updated-product-list');
            Route::post('updated-shipping', 'updatedShipping')->name('updated-shipping');
            Route::post('deny', 'deny')->name('deny');
            Route::post('approve-status', 'approveStatus')->name('approve-status');
            Route::get('product-gallery', 'getProductGalleryView')->name('product-gallery');
            Route::get('stock-limit-status/{type}', 'getStockLimitStatus')->name('stock-limit-status');
            Route::post('delete-preview-file', 'deletePreviewFile')->name('delete-preview-file');
            Route::get('request-restock-list', 'getRequestRestockListView')->name('request-restock-list');
            Route::get('export-restock', 'exportRestockList')->name('restock-export');
            Route::delete('restock-delete/{id}', 'deleteRestock')->name('restock-delete');
        });
    });

    Route::group(['prefix' => 'retail/products', 'as' => 'retail.products.'], function () {
        Route::controller(ProductController::class)->group(function () {
            Route::get('multiple-product-details', 'getMultipleProductDetailsView')->name('multiple-product-details');
            Route::get('search', 'getSearchedProductsView')->name('search-product');
            Route::get('search-all-product', 'getSearchedAllProductsView')->name('search-all-type-product');
        });
    });

    Route::group(['prefix' => 'retail/orders', 'as' => 'retail.orders.', 'middleware' => ['module:retail.orders']], function () {
        Route::controller(OrderController::class)->group(function () {
            Route::get('list/{status}', 'index')->name('list');
            Route::get('export-excel/{status}', 'exportList')->name('export-excel');
            Route::get('generate-invoice/{id}', 'generateInvoice')->name('generate-invoice')->withoutMiddleware(['module:retail.orders']);
            Route::get('details/{id}', 'getView')->name('details');
            Route::post('address-update', 'updateAddress')->name('address-update');
            Route::get('add-delivery-man/{order_id}/{d_man_id}', 'addDeliveryMan')->name('add-delivery-man');
            Route::post('amount-date-update', 'updateAmountDate')->name('amount-date-update');
            Route::get('customers', 'getCustomers')->name('customers');
            Route::post('payment-status', 'updatePaymentStatus')->name('payment-status');
            Route::get('inhouse-order-filter', 'filterInHouseOrder')->name('inhouse-order-filter');
            Route::post('digital-file-upload-after-sell', 'uploadDigitalFileAfterSell')->name('digital-file-upload-after-sell');
            Route::post('status', 'updateStatus')->name('status');
            Route::post('customer-return-amount', 'orderReturnAmountToCustomer')->name('customer-return-amount');
            Route::post('customer-due-amount', 'orderDueAmountSwitchToCOD')->name('customer-due-amount');
            Route::post('customer-due-amount-mark-as-paid', 'orderDueAmountMarkAsPaid')->name('customer-due-amount-mark-as-paid');
        });

        Route::controller(OrderEditController::class)->group(function () {
            Route::get('search-for-edit-order-product', 'getSearchEditOrderProductsView')->name('search-for-edit-order-product');
            Route::post('edit-order-product-modal-view', 'getEditOrderProductModalView')->name('edit-order-product-modal-view');
            Route::post('edit-order-product-add', 'addEditOrderProduct')->name('edit-order-product-add');
            Route::post('edit-order-product-variant-price', 'checkProductVariantPrice')->name('edit-order-product-variant-price');
            Route::post('edit-order-product-list-update', 'updateEditOrderProductList')->name('edit-order-product-list-update');
            Route::post('edit-order-product-remove', 'removeEditOrderProduct')->name('edit-order-product-remove');
            Route::post('edit-order-generate', 'generateEditOrderByProductList')->name('edit-order-generate');
        });
    });

    Route::group(['prefix' => 'retail/attribute', 'as' => 'retail.attribute.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(AttributeController::class)->group(function () {
            Route::get('view', 'index')->name('view');
            Route::post('store', 'add')->name('store');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
        });
    });

    Route::group(['prefix' => 'retail/brand', 'as' => 'retail.brand.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(BrandController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('add-new', 'getAddView')->name('add-new');
            Route::post('add-new', 'add');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::post('delete', 'delete')->name('delete');
            Route::get('export', 'exportList')->name('export');
            Route::post('status-update', 'updateStatus')->name('status-update');
            Route::post('load-more-brands', 'loadMoreBrands')->name('load-more-brands');
        });
    });

    Route::group(['prefix' => 'retail/category', 'as' => 'retail.category.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('view', 'index')->name('view');
            Route::post('add-new', 'add')->name('store');
            Route::get('update', 'getUpdateView')->name('update');
            Route::post('update', 'update');
            Route::post('delete', 'delete')->name('delete');
            Route::post('status', 'updateStatus')->name('status');
            Route::get('export', 'getExportList')->name('export');
        });
    });

    Route::group(['prefix' => 'retail/sub-category', 'as' => 'retail.sub-category.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(SubCategoryController::class)->group(function () {
            Route::get('view', 'index')->name('view');
            Route::post('store', 'add')->name('store');
            Route::post('update/{id}', 'update')->name('update');
            Route::get('update/{id}', fn () => redirect()->route('admin.retail.sub-category.view'));
            Route::post('delete', 'delete')->name('delete');
            Route::get('export', 'getExportList')->name('export');
        });
    });

    Route::group(['prefix' => 'retail/sub-sub-category', 'as' => 'retail.sub-sub-category.', 'middleware' => ['module:retail.catalog']], function () {
        Route::controller(SubSubCategoryController::class)->group(function () {
            Route::get('view', 'index')->name('view');
            Route::post('store', 'add')->name('store');
            Route::post('update/{id}', 'update');
            Route::post('delete', 'delete')->name('delete');
            Route::post('get-sub-category', 'getSubCategory')->name('get-sub-category');
            Route::get('export', 'getExportList')->name('export');
        });
    });

    Route::group(['prefix' => 'retail/banner', 'as' => 'retail.banner.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(BannerController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::post('add', 'add')->name('store');
            Route::post('delete', 'delete')->name('delete');
            Route::post('status', 'updateStatus')->name('status');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
        });
    });
    Route::group(['prefix' => 'people/customer', 'as' => 'people.customer.', 'middleware' => ['module:people.customers']], function () {
        Route::controller(CustomerController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('view/{user_id}', 'getView')->name('view');
            Route::get('order-list-export/{user_id}', 'exportOrderList')->name('order-list-export');
            Route::post('status-update', 'updateStatus')->name('status-update');
            Route::delete('delete/{id}', 'deleteCustomer')->name('delete');
            Route::get('subscriber-list', 'getSubscriberListView')->name('subscriber-list');
            Route::get('subscriber-list/export', 'exportSubscribersList')->name('subscriber-list.export');
            Route::get('export', 'exportList')->name('export');
            Route::get('customer-list-search', 'getCustomerList')->name('customer-list-search');
            Route::get('customer-list-without-all-customer', 'getCustomerListWithoutAllCustomerName')->name('customer-list-without-all-customer');
            Route::post('add', 'add')->name('add');
            Route::post('profile-update', 'updateProfile')->name('profile-update');
        });

        Route::group(['prefix' => 'wallet', 'as' => 'wallet.'], function () {
            Route::controller(CustomerWalletController::class)->group(function () {
                Route::get('report', 'index')->name('report');
                Route::post('add-fund', 'addFund')->name('add-fund');
                Route::get('export', 'exportList')->name('export');
                Route::get('bonus-setup', 'getBonusSetupView')->name('bonus-setup');
                Route::post('bonus-setup', 'addBonusSetup');
                Route::post('bonus-setup-update', 'update')->name('bonus-setup-update');
                Route::post('bonus-setup-status', 'updateStatus')->name('bonus-setup-status');
                Route::delete('bonus-setup-delete', 'deleteBonus')->name('bonus-setup-delete');
            });
        });

        Route::group(['prefix' => 'loyalty', 'as' => 'loyalty.'], function () {
            Route::controller(CustomerLoyaltyController::class)->group(function () {
                Route::get('report', 'index')->name('report');
                Route::get('export', 'exportList')->name('export');
            });
        });

        Route::group(['prefix' => 'withdraw', 'as' => 'withdraw-method.'], function () {
            Route::get('method-list', [WithdrawalMethodController::class, 'index'])->name('list')->defaults('userType', 'customer');
            Route::post('method-add', [WithdrawalMethodController::class, 'add'])->name('add')->defaults('userType', 'customer');
            Route::post('method-update', [WithdrawalMethodController::class, 'update'])->name('update')->defaults('userType', 'customer');
            Route::post('method-default-status-update', [WithdrawalMethodController::class, 'updateDefaultStatus'])->name('default-status')->defaults('userType', 'customer');
            Route::post('method-status-update', [WithdrawalMethodController::class, 'updateStatus'])->name('status-update')->defaults('userType', 'customer');
            Route::delete('method-delete/{id}', [WithdrawalMethodController::class, 'delete'])->name('delete')->defaults('userType', 'customer');
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.finance']], function () {
        Route::controller(InhouseSaleController::class)->group(function () {
            Route::get('inhouse-product-sale', 'index')->name('inhouse-product-sale');
            Route::get('inhouse-product-sale-excel-export', 'exportList')->name('inhouse-product-sale-excel-export');
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.tax']], function () {
        Route::controller(AdminTaxController::class)->group(function () {
            Route::get('get-tax-report', 'index')->name('get-tax-report');
            Route::get('admin-tax-report-export', 'exportList')->name('admin-tax-report-export');
            Route::get('get-tax-details', 'details')->name('get-tax-details');
            Route::get('tax-details-report-export', 'exportDetails')->name('get-tax-details-export');
        });
        Route::controller(VendorTaxController::class)->group(function () {
            Route::get('vendor-wise-taxes', 'index')->name('vendor-wise-taxes');
            Route::get('vendor-wise-taxes-export', 'exportList')->name('vendor-wise-tax-export');
            Route::get('vendor-tax-report', 'details')->name('vendor-tax');
            Route::get('vendor-tax-export', 'exportDetails')->name('vendor-tax-export');
        });
    });

    Route::group(['prefix' => 'people/vendors', 'as' => 'people.vendors.', 'middleware' => ['module:people.vendors']], function () {
        Route::controller(VendorController::class)->group(function () {
            Route::get('list', 'index')->name('vendor-list');
            Route::get('add', 'getAddView')->name('add');
            Route::POST('add', 'add');
            Route::get('order-list-export/{vendor_id}', 'exportOrderList')->name('order-list-export');
            Route::post('status', 'updateStatus')->name('update-status');
            Route::get('export', 'exportList')->name('export');

            Route::post('sales-commission-update/{id}', 'updateSalesCommission')->name('sales-commission-update');
            Route::get('order-details/{order_id}/{vendor_id}', 'getOrderDetailsView')->name('order-details');
            Route::get('view/{id}', 'getView')->name('view');
            Route::get('view/{id}/common/{tab}', 'getCommonTabView')->name('view.common');
            Route::get('view/{id}/retail/{tab}', 'getRetailTabView')->name('view.retail');
            Route::post('update-setting/{id}', 'updateSetting')->name('update-setting');

            Route::get('withdraw-list', 'getWithdrawListView')->name('withdraw-list');
            Route::get('withdraw-list-export-excel', 'exportWithdrawList')->name('withdraw-list-export-excel');
            Route::get('withdraw-view/{withdrawId}/{vendorId}', 'getWithdrawView')->name('withdraw-view');
            Route::post('withdraw-status/{id}', 'withdrawStatus')->name('withdraw-status');

            Route::post('load-more-stores', 'loadMoreStores')->name('load-more-stores');
        });

        Route::group(['prefix' => 'verification', 'as' => 'verification.'], function () {
            Route::controller(VerificationController::class)->group(function () {
                Route::get('criteria', 'getCriteriaView')->name('criteria');
                Route::get('candidates', 'getCandidateListView')->name('candidates');
                Route::post('grant', 'grant')->name('grant');
                Route::post('revoke', 'revoke')->name('revoke');
            });
        });

        Route::group(['prefix' => 'withdraw-method', 'as' => 'withdraw-method.'], function () {
            Route::get('list', [WithdrawalMethodController::class, 'index'])->name('list')->defaults('userType', 'vendor');
            Route::post('add', [WithdrawalMethodController::class, 'add'])->name('add')->defaults('userType', 'vendor');
            Route::post('update', [WithdrawalMethodController::class, 'update'])->name('update')->defaults('userType', 'vendor');
            Route::post('default-status-update', [WithdrawalMethodController::class, 'updateDefaultStatus'])->name('default-status')->defaults('userType', 'vendor');
            Route::post('status-update', [WithdrawalMethodController::class, 'updateStatus'])->name('status-update')->defaults('userType', 'vendor');
            Route::delete('delete/{id}', [WithdrawalMethodController::class, 'delete'])->name('delete')->defaults('userType', 'vendor');
        });
    });

    Route::group(['prefix' => 'people/employee', 'as' => 'people.employee.', 'middleware' => ['module:people.team']], function () {
        Route::controller(EmployeeController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('add', 'getAddView')->name('add-new');
            Route::post('add', 'add')->name('add-new-post');
            Route::get('export', 'exportList')->name('export');
            Route::get('quick-view', 'quickView')->name('quick-view');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::post('status', 'updateStatus')->name('status');
        });
    });

    Route::group(['prefix' => 'people/custom-role', 'as' => 'people.custom-role.', 'middleware' => ['module:people.team']], function () {
        Route::controller(CustomRoleController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('add', 'getAddView')->name('add-new');
            Route::post('add', 'add')->name('store');
            Route::get('quick-view', 'quickView')->name('quick-view');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::post('employee-role-status', 'updateStatus')->name('employee-role-status');
            Route::post('delete', 'delete')->name('delete');
            Route::get('export', 'exportList')->name('export');
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.finance']], function () {
        Route::group(['prefix' => 'transaction', 'as' => 'transaction.'], function () {
            Route::controller(RefundTransactionController::class)->group(function () {
                Route::get('refund-transaction-list', 'index')->name('refund-transaction-list');
                Route::get('refund-transaction-export', 'exportList')->name('refund-transaction-export');
                Route::get('refund-transaction-summary-pdf', 'downloadSummaryPdf')->name('refund-transaction-summary-pdf');
            });
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.finance']], function () {
        Route::controller(AdminEarningController::class)->group(function () {
            Route::get('earning', 'index')->name('earning');
            Route::get('admin-earning', 'index')->name('admin-earning');
            Route::get('admin-earning-excel-export', 'exportList')->name('admin-earning-excel-export');
            Route::post('admin-earning-duration-download-pdf', 'downloadDurationPdf')->name('admin-earning-duration-download-pdf');
        });

        Route::controller(VendorEarningController::class)->group(function () {
            Route::get('vendor-earning', 'index')->name('vendor-earning');
            Route::get('vendor-earning-excel-export', 'exportList')->name('vendor-earning-excel-export');
        });

        Route::controller(EarningReportController::class)->group(function () {
            Route::any('set-date', 'setDate')->name('set-date');
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.operations']], function () {
        Route::controller(ReportOrderController::class)->group(function () {
            Route::get('order', 'index')->name('order');
            Route::get('order-report-excel', 'exportList')->name('order-report-excel');
            Route::get('order-report-pdf', 'downloadSummaryPdf')->name('order-report-pdf');
        });

        Route::controller(ReportProductController::class)->group(function () {
            Route::get('all-product', 'index')->name('all-product');
            Route::get('all-product-excel', 'exportList')->name('all-product-excel');
        });
    });

    Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.', 'middleware' => ['module:reports.finance']], function () {
        Route::controller(VendorSaleController::class)->group(function () {
            Route::get('vendor-report', 'index')->name('vendor-report');
            Route::get('vendor-report-export', 'exportList')->name('vendor-report-export');
        });
    });

    Route::group(['prefix' => 'report/retail/transaction', 'as' => 'report.retail.transaction.', 'middleware' => ['module:reports.finance']], function () {
        Route::controller(OrderTransactionController::class)->group(function () {
            Route::get('order-transaction-list', 'index')->name('order-transaction-list');
            Route::get('pdf-order-wise-transaction', 'downloadOrderPdf')->name('pdf-order-wise-transaction');
            Route::get('order-transaction-export-excel', 'exportList')->name('order-transaction-export-excel');
            Route::get('order-transaction-summary-pdf', 'downloadSummaryPdf')->name('order-transaction-summary-pdf');
        });

        Route::controller(TransactionReportController::class)->group(function () {
            Route::get('wallet-bonus', 'walletBonus')->name('wallet-bonus');
        });

        Route::controller(ExpenseTransactionController::class)->group(function () {
            Route::get('expense-transaction-list', 'index')->name('expense-transaction-list');
            Route::get('pdf-order-wise-expense-transaction', 'downloadOrderPdf')->name('pdf-order-wise-expense-transaction');
            Route::get('expense-transaction-export-excel', 'exportList')->name('expense-transaction-export-excel');
            Route::get('expense-transaction-summary-pdf', 'downloadSummaryPdf')->name('expense-transaction-summary-pdf');
        });
    });

    Route::group(['prefix' => 'report/retail/stock', 'as' => 'report.retail.stock.', 'middleware' => ['module:reports.operations']], function () {
        Route::controller(ProductStockController::class)->group(function () {
            Route::get('product-stock', 'index')->name('product-stock');
            Route::get('product-stock-export', 'exportList')->name('product-stock-export');
        });

        Route::controller(ProductWishlistController::class)->group(function () {
            Route::get('product-in-wishlist', 'index')->name('product-in-wishlist');
            Route::get('wishlist-product-export', 'exportList')->name('wishlist-product-export');
        });
    });

    Route::group([
        'prefix' => 'people/{business_module}/reviews',
        'as' => 'people.reviews.',
        'middleware' => ['module:people.customers'],
        'where' => ['business_module' => implode('|', [BusinessModule::RETAIL, BusinessModule::WHOLESALE])],
    ], function () {
        Route::controller(ReviewController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::post('status', 'updateStatus')->name('status');
            Route::get('export', 'exportList')->name('export');
            Route::get('customer-list-search', 'getCustomerList')->name('customer-list-search');
            Route::any('search-product', 'search')->name('search-product');
            Route::post('add-review-reply', 'addReviewReply')->name('add-review-reply');
            Route::any('search-vendor', 'searchVendor')->name('search-vendor');
        });
    });

    Route::group(['prefix' => 'retail/coupon', 'as' => 'retail.coupon.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(CouponController::class)->group(function () {
            Route::get('add', 'getAddListView')->name('add');
            Route::post('add', 'add');
            Route::get('export', 'exportList')->name('export');
            Route::get('quick-view-details', 'quickView')->name('quick-view-details');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::get('status/{id}/{status}', 'updateStatus')->name('status');
            Route::post('ajax-get-vendor', 'getVendorList')->name('ajax-get-vendor');
            Route::delete('delete/{id}', 'delete')->name('delete');
        });
    });

    Route::group(['prefix' => 'retail/deal', 'as' => 'retail.deal.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(FlashDealController::class)->group(function () {
            Route::get('flash', 'index')->name('flash');
            Route::get('flash/add', 'getAddView')->name('flash-add');
            Route::post('flash', 'add');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update')->name('update-data');
            Route::post('status-update', 'updateStatus')->name('status-update');
            Route::post('delete-product/{deal_id}', 'delete')->name('delete-product');
            Route::get('add-product/{deal_id}', 'getAddProductView')->name('add-product');
            Route::post('add-product/{deal_id}', 'addProduct');
            Route::any('search-product', 'search')->name('search-product');
        });

        Route::controller(DealOfTheDayController::class)->group(function () {
            Route::get('day', 'index')->name('day');
            Route::post('day', 'add');
            Route::post('day-status-update', 'updateStatus')->name('day-status-update');
            Route::post('day-update/{id}', 'update')->name('day-update');
            Route::post('day-delete', 'delete')->name('day-delete');
        });

        Route::controller(FeaturedDealController::class)->group(function () {
            Route::get('feature', 'index')->name('feature');
            Route::get('feature/new', 'getAddView')->name('feature-add');
            Route::post('feature/new', 'add');
            Route::get('feature-update/{id}', 'getUpdateView')->name('edit');
            Route::post('feature-update/{id}', 'update')->name('featured-update');
            Route::post('feature-status', 'updateStatus')->name('feature-status');
            Route::get('feature-add-product/{deal_id}', 'getAddProductView')->name('feature-add-product');
            Route::post('feature-add-product/{deal_id}', 'addProduct');
            Route::post('feature-delete-product/{deal_id}', 'delete')->name('feature-delete-product');
            Route::any('feature-search-product', 'search')->name('feature-search-product');
        });

        Route::group(['prefix' => 'clearance-sale', 'as' => 'clearance-sale.'], function () {
            Route::controller(ClearanceSaleController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('status-update', 'updateStatus')->name('status-update');
                Route::post('update-config', 'updateClearanceConfig')->name('update-config');
                Route::post('update-seo-meta', 'updateClearanceSeoConfig')->name('update-seo-meta');
                Route::get('search', 'getSearchedProductsView')->name('search-product-for-clearance');
                Route::get('multiple-product-details', 'getMultipleProductDetailsView')->name('multiple-clearance-product-details');
                Route::post('add-clearance-product', 'addClearanceProduct')->name('add-product');
                Route::post('clearance-product-status-update', 'updateProductStatus')->name('product-status-update');
                Route::delete('clearance-delete/{product_id}', 'deleteClearanceProduct')->name('clearance-delete');
                Route::delete('clearance-products-delete', 'deleteClearanceAllProduct')->name('clearance-delete-all-product');
                Route::post('update-discount', 'updateDiscountAmount')->name('update-discount');
            });

            Route::controller(ClearanceSaleVendorOfferController::class)->group(function () {
                Route::get('vendor-offers', 'index')->name('vendor-offers');
                Route::get('vendor-search', 'getSearchedVendorsView')->name('search-vendor-for-clearance');
                Route::post('vendor-add', 'addClearanceVendorProduct')->name('vendor-add');
                Route::post('update-status', 'updateVendorStatus')->name('update-vendor-status');
                Route::post('update-offer-status', 'updateVendorOfferStatus')->name('update-vendor-offer-status');
                Route::delete('delete-vendor/{id}', 'deleteVendorOffer')->name('vendor-delete');
            });

            Route::controller(ClearanceSalePrioritySetupController::class)->group(function () {
                Route::get('priority-setup', 'index')->name('priority-setup');
                Route::post('priority-setup-config', 'updateConfig')->name('priority-setup-config');
            });
        });
    });

    Route::group(['prefix' => 'settings/system-setup/push-notification', 'as' => 'settings.system-setup.push-notification.', 'middleware' => ['module:settings.system']], function () {
        Route::controller(PushNotificationSettingsController::class)->group(function () {
            Route::get('index', 'index')->name('index');
            Route::post('update', 'updatePushNotificationMessage')->name('update');
        });
    });

    Route::group(['prefix' => 'retail/notification', 'as' => 'retail.notification.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(NotificationController::class)->group(function () {
            Route::get('index', 'index')->name('index');
            Route::post('index', 'add');
            Route::get('update/{id}', 'getUpdateView')->name('update');
            Route::post('update/{id}', 'update');
            Route::post('delete', 'delete')->name('delete');
            Route::post('update-status', 'updateStatus')->name('update-status');
            Route::post('resend-notification', 'resendNotification')->name('resend-notification');
        });
    });

    Route::group(['prefix' => 'people/support-ticket', 'as' => 'people.support-ticket.', 'middleware' => ['module:people.support']], function () {
        Route::controller(SupportTicketController::class)->group(function () {
            Route::get('view', 'index')->name('view');
            Route::post('status', 'updateStatus')->name('status');
            Route::get('single-ticket/{id}', 'getView')->name('single-ticket');
            Route::post('single-ticket/{id}', 'reply')->name('replay');
        });
    });

    Route::group(['prefix' => 'people/messages', 'as' => 'people.messages.', 'middleware' => ['module:people.support']], function () {
        Route::controller(ChattingController::class)->group(function () {
            Route::get('index/{type}', 'index')->name('index');
            Route::get('message', 'getMessageByUser')->name('message');
            Route::post('message', 'addAdminMessage');
        });
    });

    Route::group(['prefix' => 'people/contact', 'as' => 'people.contact.', 'middleware' => ['module:people.support']], function () {
        Route::controller(ContactController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('view/{id}', 'getView')->name('view');
            Route::post('filer', 'getListByFilter')->name('filter');
            Route::post('delete', 'delete')->name('delete');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('store', 'add')->name('store');
            Route::post('send-mail/{id}', 'sendMail')->name('send-mail');
        });
    });

    Route::group(['prefix' => 'people/delivery-man', 'as' => 'people.delivery-man.', 'middleware' => ['module:people.delivery_men']], function () {
        Route::controller(DeliveryManController::class)->group(function () {
            Route::get('list', 'index')->name('list');
            Route::get('add', 'getAddView')->name('add');
            Route::post('add', 'add');
            Route::post('status-update', 'updateStatus')->name('status-update');
            Route::get('export', 'exportList')->name('export');
            Route::get('update/{id}', 'getUpdateView')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::delete('delete/{id}', 'delete')->name('delete');
            Route::get('earning-statement-overview/{id}', 'getEarningOverview')->name('earning-statement-overview');
            Route::get('order-wise-earning/{id}', 'getOrderWiseEarningView')->name('order-wise-earning');
            Route::get('order-list-by-filer/{id}', 'getOrderWiseEarningListByFilter')->name('order-wise-earning-list-by-filter');
            Route::get('order-history-log/{id}', 'getOrderHistoryList')->name('order-history-log');
            Route::get('order-history-log-export/{id}', 'getOrderHistoryListExport')->name('order-history-log-export');
            Route::get('rating/{id}', 'getRatingView')->name('rating');
            Route::get('ajax-order-status-history/{order}', 'getOrderStatusHistory')->name('ajax-order-status-history');
        });

        Route::controller(DeliveryManCashCollectController::class)->group(function () {
            Route::get('collect-cash/{id}', 'index')->name('collect-cash');
            Route::post('cash-receive/{id}', 'getCashReceive')->name('cash-receive');
        });

        Route::controller(DeliverymanWithdrawController::class)->group(function () {
            Route::get('withdraw-list', 'index')->name('withdraw-list');
            Route::post('withdraw-list', 'getFiltered');
            Route::get('withdraw-list-export', 'exportList')->name('withdraw-list-export');
            Route::get('withdraw-view/{withdraw_id}', 'getView')->name('withdraw-view');
            Route::post('withdraw-update-status/{id}', 'updateStatus')->name('withdraw-update-status');
        });

        Route::group(['prefix' => 'emergency-contact', 'as' => 'emergency-contact.'], function () {
            Route::controller(EmergencyContactController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('add', 'add')->name('add');
                Route::get('update/{id}', 'getUpdateView')->name('update');
                Route::post('update/{id}', 'update');
                Route::post('ajax-status-change', 'updateStatus')->name('ajax-status-change');
                Route::delete('destroy', 'delete')->name('destroy');
            });
        });
    });

    Route::group(['prefix' => 'retail/most-demanded', 'as' => 'retail.most-demanded.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(MostDemandedController::class)->group(function () {
            Route::get('/', 'getListView')->name('index');
            Route::post('store', 'add')->name('store');
            Route::get('update/{id}', 'getUpdateView')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
            Route::post('status', 'updateStatus')->name('status-update');
        });
    });

    Route::get('addon', [AddonController::class, 'index'])
        ->middleware('module:settings.themes')
        ->name('addon.index');

    Route::group(['prefix' => 'settings/addon', 'as' => 'settings.addon.'], function () {
        Route::controller(AddonController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('publish', 'publish')->name('publish');
            Route::post('activation', 'activation')->name('activation');
            Route::post('upload', 'upload')->name('upload');
            Route::post('delete', 'delete')->name('delete');
        });
    });

    Route::group(['prefix' => 'settings/system-setup', 'as' => 'settings.system-setup.'], function () {
        Route::group(['middleware' => ['module:settings.themes']], function () {
            Route::group(['prefix' => 'theme', 'as' => 'theme.'], function () {
                Route::controller(ThemeController::class)->group(function () {
                    Route::get('setup', 'index')->name('setup');
                    Route::post('install', 'upload')->name('install');
                    Route::post('activation', 'activation')->name('activation');
                    Route::post('publish', 'publish')->name('publish');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('notify-all-the-vendors', 'notifyAllTheVendors')->name('notify-all-the-vendors');
                });
            });

            Route::group(['prefix' => 'addon', 'as' => 'addon.'], function () {
                Route::controller(AddonController::class)->group(function () {
                    Route::get('', 'index')->name('index');
                    Route::post('publish', 'publish')->name('publish');
                    Route::post('activation', 'activation')->name('activation');
                    Route::post('upload', 'upload')->name('upload');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('complete-installation', 'completeInstallation')->name('complete-installation');
                });
            });

            Route::group(['prefix' => 'addon-activation', 'as' => 'addon-activation.'], function () {
                Route::controller(AddonActivationController::class)->group(function () {
                    Route::get('', 'index')->name('index');
                    Route::post('activation', 'activation')->name('activation');
                    Route::post('activate-from-list', 'activateFromList')->name('activate-from-list');
                });
            });
        });

        Route::group(['middleware' => ['module:settings.system']], function () {
            Route::controller(EnvironmentSettingsController::class)->group(function () {
                Route::get('environment-setup', 'index')->name('environment-setup');
                Route::post('environment-setup', 'update');
                Route::post('environment-update-force-https', 'updateForceHttps')->name('environment-https-setup');
                Route::post('optimize-system', 'optimizeSystem')->name('optimize-system');
                Route::post('install-passport', 'installPassport')->name('install-passport');
            });

            Route::controller(BusinessSettingsController::class)->group(function () {
                Route::get('app-settings', 'getAppSettingsView')->name('app-settings');
                Route::post('app-settings', 'updateAppSettings');
                Route::get('app-deep-link', 'getAppDeepLinkView')->name('app-deep-link');
                Route::post('app-deep-link', 'updateAppDeepLink')->name('app-deep-link-store');
            });

            Route::controller(SoftwareUpdateController::class)->group(function () {
                Route::get('software-update', 'index')->name('software-update');
                Route::post('software-update', 'update');
            });

            Route::group(['prefix' => 'language', 'as' => 'language.', 'middleware' => ['module:settings.system']], function () {
                Route::controller(LanguageController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('add', 'add')->name('add-new');
                    Route::post('update-status', 'updateStatus')->name('update-status');
                    Route::post('update-default-status', 'updateDefaultStatus')->name('update-default-status');
                    Route::post('update', 'update')->name('update');
                    Route::get('delete/{lang}', 'delete')->name('delete');
                    Route::get('translate/{lang}', 'getTranslateView')->name('translate');
                    Route::post('translate-submit/{lang}', 'updateTranslate')->name('translate-submit');
                    Route::post('remove-key/{lang}', 'deleteTranslateKey')->name('remove-key');
                    Route::get('unused-keys/{lang}', 'getUnusedKeys')->name('unused-keys');
                    Route::post('remove-unused-keys/{lang}', 'removeUnusedKeys')->name('remove-unused-keys');
                    Route::post('sync-languages/{lang}', 'syncLanguages')->name('sync-languages');
                    Route::any('auto-translate/{lang}', 'getAutoTranslate')->name('auto-translate');
                    Route::any('auto-translate-all/{lang}', 'getAutoTranslateAllMessages')->name('auto-translate-all');
                });
            });

            Route::group(['prefix' => 'currency', 'as' => 'currency.', 'middleware' => ['module:settings.system']], function () {
                Route::controller(CurrencyController::class)->group(function () {
                    Route::get('view', 'index')->name('view');
                    Route::post('store', 'add')->name('store');
                    Route::post('update', 'update')->name('update');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('status', 'status')->name('status');
                    Route::post('check-currency-update', 'checkSystemCurrency')->name('check-currency-update');
                    Route::post('system-currency-update', 'updateSystemCurrency')->name('system-currency-update');
                });
            });

            Route::controller(DatabaseSettingController::class)->group(function () {
                Route::get('db-index', 'index')->name('db-index');
                Route::post('db-clean', 'delete')->name('clean-db');
            });

            Route::group(['prefix' => 'login-settings', 'as' => 'login-settings.'], function () {
                Route::controller(SystemLoginSetupController::class)->group(function () {
                    Route::get('customer-login-setup', 'getCustomerLoginSetupView')->name('customer-login-setup');
                    Route::post('customer-login-setup', 'updateCustomerLoginSetup');
                    Route::post('customer-config-validation', 'getConfigValidation')->name('config-status-validation');

                    Route::get('otp-setup', 'getOtpSetupView')->name('otp-setup');
                    Route::post('otp-setup', 'updateOtpSetup');

                    Route::get('login-url-setup', 'getLoginSetupView')->name('login-url-setup');
                    Route::post('login-url-setup', 'updateLoginSetupView');
                });
            });

            Route::group(['prefix' => 'email-templates', 'as' => 'email-templates.', 'middleware' => ['module:settings.system']], function () {
                Route::controller(EmailTemplatesController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('/'.'/{type}'.'/{tab}', 'getView')->name('view');
                    Route::post('update/{type}'.'/{tab}', 'update')->name('update');
                    Route::post('update-status/{type}'.'/{tab}', 'updateStatus')->name('update-status');
                });
            });

            Route::group(['prefix' => 'file-manager', 'as' => 'file-manager.', 'middleware' => ['module:settings.system']], function () {
                Route::controller(FileManagerController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('download/{file_name}', 'download')->name('download');
                    Route::post('image-upload', 'upload')->name('image-upload');
                });
            });
        });
    });

    Route::group(['prefix' => 'settings/third-party', 'as' => 'settings.third-party.'], function () {
        Route::group(['middleware' => ['module:settings.third_party']], function () {
            Route::group(['prefix' => 'payment-method', 'as' => 'payment-method.'], function () {
                Route::controller(PaymentMethodController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::put('addon-payment-set', 'UpdatePaymentConfig')->name('addon-payment-set');
                    Route::post('payment-status', 'UpdateStatus')->name('payment-status');
                });
            });

            Route::group(['prefix' => 'offline-payment-method', 'as' => 'offline-payment-method.'], function () {
                Route::controller(OfflinePaymentMethodController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('add', 'getAddView')->name('add');
                    Route::post('add', 'add');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('update-status', 'updateStatus')->name('update-status');
                });
            });

            Route::group(['prefix' => 'firebase-configuration', 'as' => 'firebase-configuration.'], function () {
                Route::controller(PushNotificationSettingsController::class)->group(function () {
                    Route::get('setup', 'getFirebaseConfigurationView')->name('setup');
                    Route::post('setup', 'getFirebaseConfigurationUpdate');
                });
                Route::controller(FirebaseOTPVerificationController::class)->group(function () {
                    Route::get('authentication', 'index')->name('authentication');
                    Route::post('update', 'updateAuthentication')->name('update');
                    Route::post('firebase-config-validation', 'getConfigValidation')->name('config-status-validation');
                });
            });

            Route::controller(BusinessSettingsController::class)->group(function () {
                Route::get('analytics-index', 'getAnalyticsView')->name('analytics-index');
                Route::post('analytics-update', 'updateAnalytics')->name('analytics-update');
            });

            Route::group(['prefix' => 'social-login', 'as' => 'social-login.'], function () {
                Route::controller(SocialLoginSettingsController::class)->group(function () {
                    Route::get('view', 'index')->name('view');
                    Route::post('update/{service}', 'update')->name('update');
                    Route::post('update-apple/{service}', 'updateAppleLogin')->name('update-apple');
                });
            });

            Route::group(['prefix' => 'storage-connection-settings', 'as' => 'storage-connection-settings.'], function () {
                Route::controller(StorageConnectionSettingsController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('update-storage-type', 'updateStorageType')->name('update-storage-type');
                    Route::post('s3-credential', 'updateS3Credential')->name('s3-credential');
                });
            });

            Route::group(['prefix' => 'erp', 'as' => 'erp.'], function () {
                Route::controller(ErpController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('store', 'store')->name('store');
                    Route::post('revoke', 'revoke')->name('revoke');
                    Route::post('delete', 'delete')->name('delete');
                });
            });

            Route::group(['prefix' => 'social-media-chat', 'as' => 'social-media-chat.'], function () {
                Route::controller(SocialMediaChatController::class)->group(function () {
                    Route::post('update/{service}', 'update')->name('update');
                });
            });

            Route::group(['prefix' => 'mail', 'as' => 'mail.'], function () {
                Route::controller(MailController::class)->group(function () {
                    Route::get('', 'index')->name('index');
                    Route::post('update', 'update')->name('update');
                    Route::post('update-sendgrid', 'updateSendGrid')->name('update-sendgrid');
                    Route::post('send', 'send')->name('send');
                });
            });

            Route::controller(SMSModuleController::class)->group(function () {
                Route::get('sms-module', 'index')->name('sms-module');
                Route::put('addon-sms-set', 'update')->name('addon-sms-set');
                Route::post('send-test-sms', 'sendSMS')->name('send-test-sms');
            });

            Route::controller(RecaptchaController::class)->group(function () {
                Route::get('recaptcha', 'index')->name('captcha');
                Route::post('recaptcha', 'update');
            });

            Route::controller(GoogleMapAPIController::class)->group(function () {
                Route::get('map-api', 'index')->name('map-api');
                Route::post('map-api', 'update');
            });

        });
    });

    Route::group(['prefix' => 'settings/business-settings', 'as' => 'settings.business-settings.'], function () {
        Route::group(['middleware' => ['module:settings.business']], function () {
            Route::group(['prefix' => 'web-config', 'as' => 'web-config.'], function () {
                Route::controller(BusinessSettingsController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'updateSettings')->name('update');
                });
            });

            Route::controller(BusinessSettingsController::class)->group(function () {
                Route::post('maintenance-mode', 'updateSystemMode')->name('maintenance-mode');
            });

            Route::controller(WebsiteSetupController::class)->group(function () {
                Route::get('website-setup', 'index')->name('website-setup');
                Route::post('website-setup', 'updateWebsiteSetup');
            });

            Route::group(['prefix' => 'vendor-settings', 'as' => 'vendor-settings.'], function () {
                Route::controller(VendorSettingsController::class)->group(function () {
                    Route::get('', 'index')->name('index');
                    Route::post('update-vendor-settings', 'update')->name('update-vendor-settings');
                });
            });

            Route::group(['prefix' => 'product-settings', 'as' => 'product-settings.'], function () {
                Route::controller(BusinessSettingsController::class)->group(function () {
                    Route::get('/', 'getProductSettingsView')->name('index');
                    Route::post('/', 'updateProductSettings');
                });
            });

            Route::group(['prefix' => 'delivery-man-settings', 'as' => 'delivery-man-settings.'], function () {
                Route::controller(DeliverymanSettingsController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('delivery-man-settings/update', 'update')->name('update');
                    Route::post('delivery-man-settings/upload-picture', 'uploadPicture')->name('upload-picture');
                });
            });

            Route::controller(CustomerController::class)->group(function () {
                Route::get('customer-settings', 'getCustomerSettingsView')->name('customer-settings');
                Route::post('customer-settings', 'updateCustomerSettings');
            });

            Route::group(['prefix' => 'order-settings', 'as' => 'order-settings.'], function () {
                Route::controller(OrderSettingsController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('update-order-settings', 'update')->name('update-order-settings');
                });
            });

            Route::controller(BusinessSettingsController::class)->group(function () {
                Route::get('refund-setup', 'getRefundSetupView')->name('refund-setup');
                Route::post('refund-setup', 'updateRefundSetup')->name('refund-setup-update');
            });

            Route::group(['prefix' => 'shipping-method', 'as' => 'shipping-method.'], function () {
                Route::controller(ShippingMethodController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('index', 'add');
                    Route::get('update'.'/{id}', 'getUpdateView')->name('update');
                    Route::post('update'.'/{id}', 'update');
                    Route::post('update-status', 'updateStatus')->name('update-status');
                    Route::post('delete', 'delete')->name('delete');
                    Route::post('update-shipping-responsibility', 'updateShippingResponsibility')->name('update-shipping-responsibility');
                    Route::post('update-third-party-mode', 'updateThirdPartyDeliveryMode')->name('update-third-party-mode');
                    Route::post('update-vendor-partner-setup', 'updateVendorDeliveryPartnerSetup')->name('update-vendor-partner-setup');
                });
            });

            Route::group(['prefix' => 'shipping-type', 'as' => 'shipping-type.'], function () {
                Route::post('index', [ShippingTypeController::class, 'addOrUpdate'])->name('index');
            });

            Route::group(['prefix' => 'category-shipping-cost', 'as' => 'category-shipping-cost.'], function () {
                Route::controller(CategoryShippingCostController::class)->group(function () {
                    Route::post('store', 'add')->name('store');
                });
            });

            Route::group(['prefix' => 'delivery-zone', 'as' => 'delivery-zone.'], function () {
                Route::controller(DeliveryRestrictionController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('add-delivery-country', 'add')->name('add-delivery-country');
                    Route::delete('delivery-country-delete', 'delete')->name('delivery-country-delete');
                    Route::post('add-zip-code', 'addZipCode')->name('add-zip-code');
                    Route::delete('zip-code-delete', 'deleteZipCode')->name('zip-code-delete');
                    Route::post('country-restriction-status-change', 'countryRestrictionStatusChange')->name('country-restriction-status-change');
                    Route::post('zipcode-restriction-status-change', 'zipcodeRestrictionStatusChange')->name('zipcode-restriction-status-change');
                });
            });

            Route::group(['prefix' => 'invoice-settings', 'as' => 'invoice-settings.'], function () {
                Route::controller(InvoiceSettingsController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'update')->name('update');
                });
            });
        });
    });

    Route::group(['prefix' => 'settings/retail-settings', 'as' => 'settings.retail-settings.'], function () {
        Route::group(['middleware' => ['module:settings.business']], function () {
            Route::group(['prefix' => 'vendor-settings', 'as' => 'vendor-settings.'], function () {
                Route::controller(VendorSettingsController::class)->group(function () {
                    Route::get('', 'retailIndex')->name('index');
                    Route::post('update-vendor-settings', 'updateRetail')->name('update-vendor-settings');
                });
            });
        });
    });

    Route::group(['prefix' => 'settings/business-settings', 'as' => 'settings.business-settings.'], function () {
        Route::group(['middleware' => ['module:settings.business']], function () {
            Route::controller(InhouseShopController::class)->group(function () {
                Route::get('inhouse-shop', 'index')->name('inhouse-shop');
                Route::post('inhouse-shop', 'update');
                Route::post('inhouse-shop-availability', 'updateAvailability')->name('inhouse-shop-availability');
                Route::post('vacation-update', 'updateVacation')->name('inhouse-shop-vacation-update');

                Route::get('inhouse-shop/setup', 'getSetupView')->name('inhouse-shop-setup');
                Route::post('inhouse-shop/setup', 'updateSetup');
            });
        });

        Route::group(['prefix' => 'priority-setup', 'as' => 'priority-setup.'], function () {
            Route::controller(PrioritySetupController::class)->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('', 'update');
                Route::post('update-by-type', 'updateByType')->name('update-by-type');
            });
        });
    });

    Route::group(['prefix' => 'settings/seo-settings', 'as' => 'settings.seo-settings.'], function () {
        Route::group(['middleware' => ['module:settings.business']], function () {
            Route::controller(SEOSettingsController::class)->group(function () {
                Route::get('web-master-tool', 'index')->name('web-master-tool');
                Route::post('web-master-tool', 'updateWebMasterTool');
                Route::get('robot-txt', 'getRobotTxtView')->name('robot-txt');
                Route::post('robot-text', 'updateRobotText')->name('update-robot-text');
            });

            Route::group(['prefix' => 'robots-meta-content', 'as' => 'robots-meta-content.'], function () {
                Route::controller(RobotsMetaContentController::class)->group(function () {
                    Route::get('', 'index')->name('index');
                    Route::post('add-page', 'addPage')->name('add-page');
                    Route::get('delete-page', 'getPageDelete')->name('delete-page');
                    Route::get('page-content-view', 'getPageAddContentView')->name('page-content-view');
                    Route::post('page-content-update', 'getPageContentUpdate')->name('page-content-update');
                });
            });

            Route::controller(SiteMapController::class)->group(function () {
                Route::get('sitemap', 'index')->name('sitemap');
                Route::get('sitemap-generate', 'getGenerate')->name('sitemap-generate');
                Route::get('sitemap-generate-upload', 'getGenerateAndUpload')->name('sitemap-generate-upload');
                Route::post('sitemap-manual-upload', 'getUpload')->name('sitemap-manual-upload');
                Route::get('sitemap-download', 'getDownload')->name('sitemap-download');
                Route::get('sitemap-delete', 'getDelete')->name('sitemap-delete');
            });

            Route::group(['prefix' => 'error-logs', 'as' => 'error-logs.'], function () {
                Route::controller(ErrorLogsController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('index', 'update');
                    Route::delete('index', 'delete');
                    Route::delete('delete-selected-error-logs', 'deleteSelectedErrorLogs')->name('delete-selected-error-logs');
                });
            });
        });
    });

    Route::group(['prefix' => 'settings/pages-and-media', 'as' => 'settings.pages-and-media.'], function () {
        Route::group(['middleware' => ['module:settings.content']], function () {
            Route::controller(PagesController::class)->group(function () {
                Route::get('list', 'index')->name('list');
                Route::get('add', 'getAddView')->name('add');
                Route::post('add', 'getAdd');
                Route::get('update', 'getUpdateView')->name('update');
                Route::post('update', 'getUpdate');
                Route::post('delete-image', 'getDeleteImage')->name('delete.image');
                Route::post('delete', 'getDelete')->name('delete');
                Route::post('update-status', 'updateStatus')->name('update-status');
            });

            Route::controller(FeaturesSectionController::class)->group(function () {
                Route::get('company-reliability', 'getCompanyReliabilityView')->name('company-reliability');
                Route::post('company-reliability', 'updateCompanyReliability');
            });

            Route::controller(SocialMediaSettingsController::class)->group(function () {
                Route::get('social-media', 'index')->name('social-media');
                Route::post('social-media-store', 'add')->name('social-media-store');
                Route::post('social-media-edit', 'getUpdate')->name('social-media-edit');
                Route::post('social-media-update', 'update')->name('social-media-update');
                Route::post('social-media-delete', 'delete')->name('social-media-delete');
                Route::post('social-media-status-update', 'updateStatus')->name('social-media-status-update');
            });

            Route::controller(FeaturesSectionController::class)->group(function () {
                Route::get('features-section', 'index')->name('features-section');
                Route::post('features-section/submit', 'update')->name('features-section.submit');
                Route::post('features-section/icon-remove', 'delete')->name('features-section.icon-remove');
            });
        });

        Route::group(['prefix' => 'vendor-registration-settings', 'as' => 'vendor-registration-settings.', 'middleware' => ['module:settings.business']], function () {
            Route::controller(VendorRegistrationSettingController::class)->group(function () {
                Route::get('index', 'index')->name('index');
                Route::post('index', 'updateHeaderSection');
                Route::get('with-us', 'getSellWithUsView')->name('with-us');
                Route::post('with-us', 'updateSellWithUsSection');
                Route::get('business-process', 'getBusinessProcessView')->name('business-process');
                Route::post('business-process', 'updateBusinessProcess');
                Route::get('download-app', 'getDownloadAppView')->name('download-app');
                Route::post('download-app', 'updateDownloadAppSection');
                Route::get('faq', 'getFAQView')->name('faq');
            });
        });

        Route::group(['prefix' => 'vendor-registration-reason', 'as' => 'vendor-registration-reason.', 'middleware' => ['module:settings.business']], function () {
            Route::controller(VendorRegistrationReasonController::class)->group(function () {
                Route::post('add', 'add')->name('add');
                Route::get('update', 'getUpdateView')->name('update');
                Route::post('update', 'update');
                Route::post('update-status', 'updateStatus')->name('update-status');
                Route::post('delete', 'delete')->name('delete');
            });
        });
    });

    Route::group(['prefix' => 'retail', 'as' => 'retail.', 'middleware' => ['module:retail.marketing']], function () {
        Route::controller(BusinessSettingsController::class)->group(function () {
            Route::get('announcement', 'getAnnouncementView')->name('announcement');
            Route::post('announcement', 'updateAnnouncement');
        });
    });

    Route::group(['prefix' => 'settings/helpTopic', 'as' => 'settings.help-topic.', 'middleware' => ['module:settings.content']], function () {
        Route::controller(HelpTopicController::class)->group(function () {
            Route::get('index', 'index')->name('list');
            Route::post('add-new', 'add')->name('add-new');
            Route::post('status/{id}', 'updateStatus')->name('status');
            Route::get('update/{id}', 'getUpdateResponse')->name('update');
            Route::post('feature-status-update', 'updateFeatureStatus')->name('feature-status-update');
            Route::post('update'.'/{id}', 'update');
            Route::post('delete', 'delete')->name('delete');
        });
    });

    Route::group(['prefix' => 'retail/refund-section', 'as' => 'retail.refund-section.', 'middleware' => ['module:retail.orders']], function () {
        Route::group(['prefix' => 'refund', 'as' => 'refund.'], function () {
            Route::controller(RefundController::class)->group(function () {
                Route::get('list/{status}', 'index')->name('list');
                Route::get('export/{status}', 'exportList')->name('export');
                Route::get('details/{id}', 'getDetailsView')->name('details');
                Route::post('refund-status-update', 'updateRefundStatus')->name('refund-status-update');
            });
        });
    });
});
