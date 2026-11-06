<?php

use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\Vendor\AdvancedSearchController;
use App\Http\Controllers\Vendor\Auth\ForgotPasswordController;
use App\Http\Controllers\Vendor\Auth\LoginController;
use App\Http\Controllers\Vendor\Auth\RegisterController;
use App\Http\Controllers\Vendor\BusinessModuleController;
use App\Http\Controllers\Vendor\ChattingController;
use App\Http\Controllers\Vendor\Coupon\CouponController;
use App\Http\Controllers\Vendor\CustomerController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController;
use App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController;
use App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController;
use App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController;
use App\Http\Controllers\Vendor\NotificationController;
use App\Http\Controllers\Vendor\Order\OrderController;
use App\Http\Controllers\Vendor\Order\OrderEditController;
use App\Http\Controllers\Vendor\POS\CartController;
use App\Http\Controllers\Vendor\POS\POSController;
use App\Http\Controllers\Vendor\POS\POSOrderController;
use App\Http\Controllers\Vendor\Product\ProductController;
use App\Http\Controllers\Vendor\ProfileController;
use App\Http\Controllers\Vendor\Promotion\ClearanceSaleController;
use App\Http\Controllers\Vendor\RefundController;
use App\Http\Controllers\Vendor\Report\ExpenseTransactionController as ReportExpenseTransactionController;
use App\Http\Controllers\Vendor\Report\OrderController as ReportOrderController;
use App\Http\Controllers\Vendor\Report\OrderTransactionController as ReportOrderTransactionController;
use App\Http\Controllers\Vendor\Report\ProductController as ReportProductController;
use App\Http\Controllers\Vendor\Report\ProductStockController as ReportProductStockController;
use App\Http\Controllers\Vendor\Report\VatController as ReportVatController;
use App\Http\Controllers\Vendor\ReviewController;
use App\Http\Controllers\Vendor\Shipping\CategoryShippingCostController;
use App\Http\Controllers\Vendor\Shipping\ShippingMethodController;
use App\Http\Controllers\Vendor\Shipping\ShippingTypeController;
use App\Http\Controllers\Vendor\ShopController;
use App\Http\Controllers\Vendor\SystemController;
use App\Http\Controllers\Vendor\V2SidebarPinController;
use App\Http\Controllers\Vendor\VendorPaymentInfoController;
use App\Http\Controllers\Vendor\WithdrawController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['maintenance_mode', 'actch:admin_panel']], function () {

    Route::group(['prefix' => 'vendor', 'as' => 'vendor.'], function () {
        Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
            Route::controller(LoginController::class)->group(function () {
                Route::get('login', 'getLoginView');
                Route::post('login', 'login')->name('login');
                Route::get('logout', 'logout')->name('logout');
            });
            Route::group(['prefix' => 'forgot-password', 'as' => 'forgot-password.'], function () {
                Route::controller(ForgotPasswordController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('index', 'getPasswordResetRequest');
                    Route::get('otp-verification', 'getOTPVerificationView')->name('otp-verification');
                    Route::post('otp-verification', 'submitOTPVerificationCode');
                    Route::get('reset-password', 'getPasswordResetView')->name('reset-password');
                    Route::post('reset-password', 'resetPassword');
                });
            });
            Route::group(['prefix' => 'registration', 'as' => 'registration.'], function () {
                Route::controller(RegisterController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('add', 'add')->name('add');
                });
            });
        });

        Route::group(['middleware' => ['seller']], function () {
            Route::controller(FirebaseController::class)->group(function () {
                Route::post('system/save-fcm-web-token', 'saveSellerWebToken')->name('system.save-fcm-web-token');
            });

            Route::post('business-module/switch/{module}', [BusinessModuleController::class, 'switch'])
                ->name('business-module.switch');

            Route::controller(AdvancedSearchController::class)->group(function () {
                Route::get('advanced-search', 'getSearch')->name('advanced-search');
                Route::post('advanced-search-recent', 'recentSearch')->name('advanced-search-recent');
            });

            Route::group(['prefix' => 'v2/sidebar-pins', 'as' => 'v2.sidebar-pins.'], function () {
                Route::get('/', [V2SidebarPinController::class, 'index'])->name('index');
                Route::post('toggle', [V2SidebarPinController::class, 'toggle'])->name('toggle');
                Route::post('replace', [V2SidebarPinController::class, 'replace'])->name('replace');
            });

            Route::get('dashboard', [BusinessModuleController::class, 'dashboard'])->name('dashboard-legacy');

            Route::group(['prefix' => 'retail/dashboard', 'as' => 'retail.dashboard.'], function () {
                Route::controller(DashboardController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('order-status/{type}', 'getOrderStatus')->name('order-status');
                    Route::get('earning-statistics', 'getEarningStatistics')->name('earning-statistics');
                    Route::get('real-time-activities', 'getRealTimeActivities')->name('real-time-activities');
                });
            });
            Route::group(['prefix' => 'retail/pos', 'as' => 'retail.pos.'], function () {
                Route::controller(POSController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::any('change-customer', 'changeCustomer')->name('change-customer');
                    Route::post('update-discount', 'updateDiscount')->name('update-discount');
                    Route::post('coupon-discount', 'getCouponDiscount')->name('coupon-discount');
                    Route::get('quick-view', 'getQuickView')->name('quick-view');
                    Route::get('search-product', 'getSearchedProductsView')->name('search-product');
                    Route::get('product-list', 'getProductListView')->name('product-list');
                });
                Route::controller(CartController::class)->group(function () {
                    Route::post('get-variant-price', 'getVariantPrice')->name('get-variant-price');
                    Route::post('quantity-update', 'updateQuantity')->name('quantity-update');
                    Route::get('get-cart-ids', 'getCartIds')->name('get-cart-ids');
                    Route::get('clear-cart-ids', 'clearSessionCartIds')->name('clear-cart-ids');
                    Route::post('add-to-cart', 'addToCart')->name('add-to-cart');
                    Route::post('cart-remove', 'removeCart')->name('cart-remove');
                    Route::any('cart-empty', 'emptyCart')->name('cart-empty');
                    Route::any('change-cart', 'changeCart')->name('change-cart');
                    Route::get('new-cart-id', 'addNewCartId')->name('new-cart-id');
                });
                Route::controller(POSOrderController::class)->group(function () {
                    Route::post('order-details/{id}', 'index')->name('order-details');
                    Route::post('order-place', 'placeOrder')->name('order-place');
                    Route::any('cancel-order', 'cancelOrder')->name('cancel-order');
                    Route::any('view-hold-orders', 'getAllHoldOrdersView')->name('view-hold-orders');
                });
            });
            Route::group(['prefix' => 'retail/refund', 'as' => 'retail.refund.'], function () {
                Route::controller(RefundController::class)->group(function () {
                    Route::get('index/{status}', 'index')->name('index');
                    Route::get('details/{id}', 'getDetailsView')->name('details');
                    Route::post('update-status', 'updateStatus')->name('update-status');
                    Route::get('export/{status}', 'exportList')->name('export');
                });
            });

            Route::group(['prefix' => 'retail/products', 'as' => 'retail.products.'], function () {
                Route::controller(ProductController::class)->group(function () {
                    Route::get('list/{type}', 'index')->name('list');
                    Route::get('add', 'getAddView')->name('add');
                    Route::post('add', 'add');
                    Route::get('get-categories', 'getCategories')->name('get-categories');
                    Route::post('sku-combination', 'getSkuCombinationView')->name('sku-combination');
                    Route::post('color-wise-images', 'getColorWiseImageView')->name('color-wise-images');
                    Route::post('digital-variation-combination', 'getDigitalVariationCombinationView')->name('digital-variation-combination');
                    Route::post('digital-variation-file-delete', 'deleteDigitalVariationFile')->name('digital-variation-file-delete');
                    Route::post('status-update', 'updateStatus')->name('status-update');
                    Route::get('export-excel/{type}', 'exportList')->name('export-excel');
                    Route::get('view/{id}', 'getView')->name('view');
                    Route::get('barcode/{id}', 'getBarcodeView')->name('barcode');
                    Route::delete('delete/{id}', 'delete')->name('delete');
                    Route::get('stock-limit-list', 'getStockLimitListView')->name('stock-limit-list');
                    Route::post('update-quantity', 'updateQuantity')->name('update-quantity');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::post('update-product-images/{id}', 'updateProductImages')->name('update-product-images');
                    Route::get('get-variations', 'getVariations')->name('get-variations');
                    Route::get('bulk-import', 'getBulkImportView')->name('bulk-import');
                    Route::post('bulk-import', 'importBulkProduct');
                    Route::get('search', 'getSearchedProductsView')->name('search-product');
                    Route::get('product-gallery', 'getProductGalleryView')->name('product-gallery');
                    Route::get('stock-limit-status', 'getStockLimitStatus')->name('stock-limit-status');
                    Route::post('delete-preview-file', 'deletePreviewFile')->name('delete-preview-file');
                    Route::get('request-restock-list', 'getRequestRestockListView')->name('request-restock-list');
                    Route::get('export-restock', 'exportRestockList')->name('restock-export');
                    Route::delete('delete-restock/{id}', 'deleteRestock')->name('restock-delete');

                    Route::post('load-more-brands', 'loadMoreBrands')->name('load-more-brands');
                });
            });

            Route::group(['prefix' => 'retail/orders', 'as' => 'retail.orders.'], function () {
                Route::controller(OrderController::class)->group(function () {
                    Route::get('list/{status}', 'index')->name('list');
                    Route::get('customers', 'getCustomers')->name('customers');
                    Route::get('export-excel/{status}', 'exportList')->name('export-excel');
                    Route::get('generate-invoice/{id}', 'generateInvoice')->name('generate-invoice');
                    Route::get('details/{id}', 'getView')->name('details');
                    Route::post('address-update', 'updateAddress')->name('address-update');
                    Route::post('payment-status', 'updatePaymentStatus')->name('payment-status');
                    Route::get('add-delivery-man/{order_id}/{d_man_id}', 'addDeliveryMan')->name('add-delivery-man');
                    Route::post('amount-date-update', 'updateAmountDate')->name('amount-date-update');
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

            Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
                Route::controller(CustomerController::class)->group(function () {
                    Route::get('list', 'getList')->name('list');
                    Route::post('add', 'add')->name('add');
                });
            });

            Route::group(['prefix' => 'reviews', 'as' => 'reviews.'], function () {
                Route::controller(ReviewController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('update-status/{id}/{status}', 'updateStatus')->name('update-status');
                    Route::get('export', 'exportList')->name('export');
                    Route::post('add-review-reply', 'addReviewReply')->name('add-review-reply');
                });
            });

            Route::group(['prefix' => 'retail/coupon', 'as' => 'retail.coupon.'], function () {
                Route::controller(CouponController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('add', 'add')->name('add');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::get('update-status/{id}/{status}', 'updateStatus')->name('update-status');
                    Route::delete('delete/{id}', 'delete')->name('delete');
                    Route::get('quick-view', 'getQuickView')->name('quick-view');
                    Route::get('export', 'exportList')->name('export');
                });
            });

            Route::group(['prefix' => 'retail/clearance-sale', 'as' => 'retail.clearance-sale.'], function () {
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
            });

            Route::group(['prefix' => 'messages', 'as' => 'messages.'], function () {
                Route::controller(ChattingController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('index/{type}', 'getChatView')->name('chat');
                    Route::get('message', 'getMessageByUser')->name('message');
                    Route::post('message', 'addVendorMessage');
                    Route::get('new-notification', 'getNewNotification')->name('new-notification');
                });
            });

            Route::group(['prefix' => 'retail/notification', 'as' => 'retail.notification.'], function () {
                Route::post('index', [NotificationController::class, 'getNotificationModalView'])->name('index');
            });

            Route::group(['prefix' => 'delivery-man', 'as' => 'delivery-man.'], function () {
                Route::controller(DeliveryManController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::post('index', 'add');
                    Route::get('list', 'getListView')->name('list');
                    Route::get('export', 'exportList')->name('export');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::post('update-status/{id}', 'updateStatus')->name('update-status');
                    Route::delete('delete/{id}', 'delete')->name('delete');
                    Route::get('rating/{id}', 'getRatingView')->name('rating');
                });

                Route::group(['prefix' => 'wallet', 'as' => 'wallet.'], function () {
                    Route::controller(DeliveryManWalletController::class)->group(function () {
                        Route::get('index/{id}', 'index')->name('index');
                        Route::get('order-history/{id}', 'getOrderHistory')->name('order-history');
                        Route::get('order-history-status/{order}', 'getOrderStatusHistory')->name('order-status-history');
                        Route::get('earning/{id}', 'getEarningListView')->name('earning');
                        Route::get('cash-collect/{id}', 'getCashCollectView')->name('cash-collect');
                        Route::post('cash-collect/{id}', 'collectCash');
                    });
                });

                Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.'], function () {
                    Route::controller(DeliveryManWithdrawController::class)->group(function () {
                        Route::get('index', 'index')->name('index');
                        Route::post('index', 'getFiltered');
                        Route::get('details/{withdrawId}', 'getDetails')->name('details');
                        Route::post('update-status/{withdrawId}', 'updateStatus')->name('update-status');
                        Route::any('export', 'exportList')->name('export');
                    });
                });

                Route::group(['prefix' => 'emergency-contact', 'as' => 'emergency-contact.'], function () {
                    Route::controller(EmergencyContactController::class)->group(function () {
                        Route::get('index', 'index')->name('index');
                        Route::post('index', 'add');
                        Route::get('update/{id}', 'getUpdateView')->name('update');
                        Route::post('update/{id}', 'update');
                        Route::patch('index', 'updateStatus');
                        Route::delete('index', 'delete');
                    });
                });
            });

            Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
                Route::controller(ProfileController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::patch('update/{id}', 'updatePassword');
                    Route::post('update-bank-info/{id}', 'updateBankInfo')->name('update-bank-info');
                });
            });

            Route::group(['prefix' => 'shop', 'as' => 'shop.'], function () {
                Route::controller(ShopController::class)->group(function () {
                    Route::get('index', 'index')->name('index');
                    Route::get('update/{id}', 'getUpdateView')->name('update');
                    Route::post('update/{id}', 'update');
                    Route::post('add-vacation', 'updateVacation')->name('update-vacation');
                    Route::post('availability', 'updateAvailability')->name('availability');
                    Route::post('update-other-settings', 'updateOtherSettings')->name('update-other-settings');

                    Route::get('other-setup', 'getOtherSetupView')->name('other-setup');
                });

                Route::group(['prefix' => 'payment-information', 'as' => 'payment-information.'], function () {
                    Route::controller(VendorPaymentInfoController::class)->group(function () {
                        Route::get('', 'index')->name('index');
                        Route::post('add', 'add')->name('add');
                        Route::post('update', 'update')->name('update');
                        Route::get('edit/{id?}', 'getUpdateView')->name('update-view');
                        Route::get('delete/{id?}', 'delete')->name('delete');
                        Route::post('default', 'updateDefault')->name('default');
                        Route::post('status', 'updateStatus')->name('update-status');
                        Route::get('dynamic-fields', 'getDynamicPaymentInformationView')->name('dynamic-fields');
                    });
                });
            });

            Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
                Route::group(['prefix' => 'shipping-method', 'as' => 'shipping-method.'], function () {
                    Route::controller(ShippingMethodController::class)->group(function () {
                        Route::get('retail', 'index')->name('index');
                        Route::post('retail', 'add');
                        Route::get('update/{id}', 'getUpdateView')->name('update');
                        Route::post('update/{id}', 'update');
                        Route::post('update-status', 'updateStatus')->name('update-status');
                        Route::post('delete', 'delete')->name('delete');
                    });
                });

                Route::group(['prefix' => 'shipping-type', 'as' => 'shipping-type.'], function () {
                    Route::post('index', [ShippingTypeController::class, 'addOrUpdate'])->name('index');
                });

                Route::group(['prefix' => 'category-wise-shipping-cost', 'as' => 'category-wise-shipping-cost.'], function () {
                    Route::post('index', [CategoryShippingCostController::class, 'update'])->name('index');
                });

                Route::group(['prefix' => 'withdraw', 'as' => 'withdraw.'], function () {
                    Route::controller(WithdrawController::class)->group(function () {
                        Route::get('index', 'index')->name('index');
                        Route::post('index', 'getListByStatus');
                        Route::post('add', 'add')->name('add');
                        Route::post('update', 'update')->name('update');
                        Route::get('close/{id}', 'closeWithdrawRequest')->name('close');
                        Route::get('export', 'exportList')->name('export-withdraw-list');
                        Route::post('render-withdraw-method-infos', 'renderInfosView')->name('render-withdraw-method-infos');
                    });
                });
            });

            Route::controller(SystemController::class)->group(function () {
                Route::get('/get-order-data', 'getOrderData')->name('get-order-data');
            });

            Route::group(['prefix' => 'report/retail', 'as' => 'report.retail.'], function () {
                Route::controller(ReportProductController::class)->group(function () {
                    Route::get('all-product', 'index')->name('all-product');
                    Route::get('all-product-excel', 'exportList')->name('all-product-excel');
                });

                Route::controller(ReportProductStockController::class)->group(function () {
                    Route::get('stock-product-report', 'index')->name('stock-product-report');
                    Route::get('product-stock-export', 'exportList')->name('product-stock-export');
                });

                Route::controller(ReportOrderController::class)->group(function () {
                    Route::get('order-report', 'index')->name('order-report');
                    Route::get('order-report-excel', 'exportList')->name('order-report-excel');
                    Route::get('order-report-pdf', 'downloadSummaryPdf')->name('order-report-pdf');
                });

                Route::controller(ReportVatController::class)->group(function () {
                    Route::get('get-vat-report', 'index')->name('get-vat-report');
                    Route::get('get-vat-report-export', 'exportList')->name('get-vat-report-export');
                });
            });

            Route::group(['prefix' => 'report/retail/transaction', 'as' => 'report.retail.transaction.'], function () {
                Route::controller(ReportOrderTransactionController::class)->group(function () {
                    Route::get('order-list', 'index')->name('order-list');
                    Route::get('pdf-order-wise-transaction', 'downloadOrderPdf')->name('pdf-order-wise-transaction');
                    Route::get('order-transaction-export-excel', 'exportList')->name('order-transaction-export-excel');
                    Route::get('order-transaction-summary-pdf', 'downloadSummaryPdf')->name('order-transaction-summary-pdf');
                });

                Route::controller(ReportExpenseTransactionController::class)->group(function () {
                    Route::get('expense-list', 'index')->name('expense-list');
                    Route::get('pdf-order-wise-expense-transaction', 'downloadOrderPdf')->name('pdf-order-wise-expense-transaction');
                    Route::get('expense-transaction-summary-pdf', 'downloadSummaryPdf')->name('expense-transaction-summary-pdf');
                    Route::get('expense-transaction-export-excel', 'exportList')->name('expense-transaction-export-excel');
                });
            });
        });
    });

});
