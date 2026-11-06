<?php

return [

    'default' => 'retail',

    'switcher_placeholder_icon' => 'select-business.svg',

    'module_items' => [
        'pos' => ['retail'],

        'catalog.in_house' => ['retail'],
        'catalog.vendor_products' => ['retail'],
        'catalog.wholesale' => ['wholesale'],
        'catalog.organization' => ['retail'],
        'catalog.organization_wholesale' => ['wholesale'],
        'catalog.category_retail' => ['retail'],
        'catalog.category_wholesale' => ['wholesale'],
        'catalog.brand_retail' => ['retail'],
        'catalog.brand_wholesale' => ['wholesale'],
        'catalog.attribute_retail' => ['retail'],
        'catalog.attribute_wholesale' => ['wholesale'],
        'catalog.product_gallery' => ['retail'],
        'catalog.product_gallery_wholesale' => ['wholesale'],
        'catalog.request_restock' => ['retail'],
        'catalog.bulk_import' => ['retail'],
        'catalog.add_product_retail' => ['retail'],
        'catalog.add_product_wholesale' => ['wholesale'],

        'orders.retail' => ['retail'],
        'orders.wholesale' => ['wholesale'],

        'marketing.promotions_retail' => ['retail'],
        'marketing.promotions_wholesale' => ['wholesale'],
        'marketing.communication' => ['retail'],
        'marketing.communication_wholesale' => ['wholesale'],
        'marketing.clearance_sale' => ['retail'],
        'marketing.campaign' => ['wholesale'],

        'reports.auction' => ['auction'],
    ],

    'modules' => [

        'retail' => [
            'label' => 'Retail',
            'icon' => 'retails-icon.svg',
            'sections' => ['home', 'catalog', 'orders', 'marketing'],
            'requires_addon' => null,
            'customer_push_topic' => 'sixvalley',
            'url_patterns' => [
                'admin/retail/dashboard', 'admin/retail/dashboard/*',
                'vendor/retail', 'vendor/retail/*',
                'admin/retail/pos', 'admin/retail/pos/*',
                'admin/retail/brand', 'admin/retail/brand/*',
                'admin/retail/attribute', 'admin/retail/attribute/*',
                'admin/retail/category', 'admin/retail/category/*',
                'admin/retail/sub-category', 'admin/retail/sub-category/*',
                'admin/retail/sub-sub-category', 'admin/retail/sub-sub-category/*',
                'admin/retail/announcement',
                'admin/retail/notification', 'admin/retail/notification/*',
            ],
            'dashboard_route' => 'admin.retail.dashboard.index',
            'landing' => 'admin/retail/dashboard',
            'panels' => [
                'vendor' => [
                    'landing' => 'vendor/retail/dashboard',
                    'dashboard_route' => 'vendor.retail.dashboard.index',
                ],
            ],
        ],

        'wholesale' => [
            'label' => 'Wholesale',
            'icon' => 'wholesale-icon.svg',
            'sections' => ['home', 'catalog', 'orders', 'marketing'],
            'requires_addon' => 'Wholesale',
            'storefront_requires_setting' => 'wholesale_feature_status',
            'customer_push_topic' => 'sixvalley_wholesale',
            'url_patterns' => [
                'admin/wholesale', 'admin/wholesale/*',
                'vendor/wholesale', 'vendor/wholesale/*',
            ],
            'dashboard_route' => 'admin.wholesale.dashboard.index',
            'landing' => 'admin/wholesale/dashboard',
            'panels' => [
                'vendor' => [
                    'landing' => 'vendor/wholesale/dashboard',
                    'dashboard_route' => 'vendor.wholesale.dashboard.index',
                    'sections' => ['home', 'orders', 'catalog', 'marketing'],
                    'url_patterns' => [
                        'vendor/wholesale', 'vendor/wholesale/*',
                    ],
                ],
            ],
        ],

        'auction' => [
            'label' => 'Auction',
            'icon' => 'auction-icon.svg',
            'sections' => ['home', 'auction-inhouse', 'auction-vendor', 'auction-catalog', 'auction-finance', 'auction-marketing'],
            'requires_addon' => 'Auction',
            'url_patterns' => [
                'admin/auction', 'admin/auction/*',
                'vendor/auction', 'vendor/auction/*',
            ],
            'dashboard_route' => 'admin.auction.dashboard.index',
            'landing' => 'admin/auction/dashboard',
            'panels' => [
                'vendor' => [
                    'landing' => 'vendor/auction/dashboard',
                    'dashboard_route' => 'vendor.auction.dashboard.index',
                    'requires_setting' => ['auction_feature_status', 'active_auction_for_vendor'],
                    'sections' => ['home', 'auction'],
                    'url_patterns' => [
                        'vendor/auction/dashboard', 'vendor/auction/dashboard/*',
                        'vendor/auction/product', 'vendor/auction/product/*',
                        'vendor/auction/products', 'vendor/auction/products/*',
                        'vendor/auction/withdraw', 'vendor/auction/withdraw/*',
                        'vendor/auction/cod-commission', 'vendor/auction/cod-commission/*',
                        'vendor/auction/notification', 'vendor/auction/notification/*',
                    ],
                ],
            ],
        ],

    ],

    'common' => [

        'people' => [
            'label' => 'People',
            'icon' => 'peoples.svg',
            'sections' => ['people-customer', 'people-vendors', 'people-deliveryman', 'people-team', 'people-support'],
            'requires_permission' => ['people.customers', 'people.vendors', 'people.delivery_men', 'people.team', 'people.support'],
            'landing_route' => 'admin.people.customer.list',
            'landing_routes' => [
                'people.customers' => 'admin.people.customer.list',
                'people.vendors' => 'admin.people.vendors.vendor-list',
                'people.delivery_men' => 'admin.people.delivery-man.list',
                'people.team' => 'admin.people.employee.list',
                'people.support' => 'admin.people.support-ticket.view',
            ],
            'url_patterns' => [
                'admin/people', 'admin/people/*',
            ],
            'panels' => [
                'vendor' => [
                    'label' => 'People & Settings',
                    'sections' => ['people', 'settings'],
                    'requires_permission' => [],
                    'landing' => 'vendor/messages',
                    'url_patterns' => [
                        'vendor/messages', 'vendor/messages/*',
                        'vendor/delivery-man', 'vendor/delivery-man/*',
                        'vendor/shop', 'vendor/shop/*',
                        'vendor/profile', 'vendor/profile/*',
                        'vendor/business-settings', 'vendor/business-settings/*',
                        'vendor/courier/config', 'vendor/courier/config/*',
                        'vendor/wholesale/shipping-method', 'vendor/wholesale/shipping-method/*',
                    ],
                ],
            ],
        ],

        'report' => [
            'label' => 'Report',
            'icon' => 'report.svg',
            'sections' => ['report-finance', 'report-operations', 'report-tax'],
            'requires_permission' => ['reports.finance', 'reports.operations', 'reports.tax'],
            'landing_route' => 'admin.report.retail.admin-earning',
            'landing_routes' => [
                'reports.finance' => 'admin.report.retail.admin-earning',
                'reports.operations' => 'admin.report.retail.all-product',
                'reports.tax' => 'admin.report.retail.get-tax-report',
            ],
            'url_patterns' => [
                'admin/report', 'admin/report/*',
            ],
            'panels' => [
                'vendor' => [
                    'sections' => ['reports'],
                    'requires_permission' => [],
                    'landing_route' => 'vendor.report.retail.transaction.order-list',
                    'url_patterns' => [
                        'vendor/report', 'vendor/report/*',
                        'vendor/auction/reports', 'vendor/auction/reports/*',
                    ],
                ],
            ],
        ],

        'settings' => [
            'label' => 'Settings',
            'icon' => 'settings.svg',
            'sections' => ['settings-business', 'settings-content', 'settings-system', 'settings-third-party', 'settings-themes'],
            'requires_permission' => ['settings.business', 'settings.content', 'settings.system', 'settings.third_party', 'settings.themes'],
            'landing_route' => 'admin.settings.business-settings.web-config.index',
            'landing_routes' => [
                'settings.business' => 'admin.settings.business-settings.web-config.index',
                'settings.content' => 'admin.settings.pages-and-media.list',
                'settings.system' => 'admin.settings.system-setup.environment-setup',
                'settings.third_party' => 'admin.settings.third-party.payment-method.index',
                'settings.themes' => 'admin.settings.system-setup.theme.setup',
            ],
            'url_patterns' => [
                'admin/settings', 'admin/settings/*',
            ],
            'panels' => [
                'vendor' => false,
            ],
        ],

    ],

    'shared_urls' => [
        [
            'patterns' => [
                'admin/retail/products', 'admin/retail/products/*',
                'admin/retail/orders', 'admin/retail/orders/*',
                'admin/retail/refund-section', 'admin/retail/refund-section/*',
                'admin/retail/banner', 'admin/retail/banner/*',
                'admin/retail/coupon', 'admin/retail/coupon/*',
                'admin/retail/deal', 'admin/retail/deal/*',
                'admin/retail/most-demanded', 'admin/retail/most-demanded/*',
                'vendor/retail/products', 'vendor/retail/products/*',
                'vendor/retail/orders', 'vendor/retail/orders/*',
                'vendor/retail/refund', 'vendor/retail/refund/*',
                'vendor/retail/coupon', 'vendor/retail/coupon/*',
                'vendor/retail/clearance-sale', 'vendor/retail/clearance-sale/*',
            ],
            'modules' => ['retail', 'wholesale'],
        ],
    ],

    'business_setup_tabs' => [

        'definitions' => [
            'general' => [
                'switch_page' => 'business-settings',
                'route' => 'admin.settings.business-settings.web-config.index',
                'patterns' => [
                    'admin/settings/business-settings/web-config',
                    'admin/settings/wholesale/wholesale-config',
                    'admin/settings/business-settings/auction-config',
                ],
            ],
            'website-setup' => [
                'switch_page' => 'website-setup',
                'route' => 'admin.settings.business-settings.website-setup',
                'patterns' => ['admin/settings/business-settings/website-setup'],
            ],
            'vendors' => [
                'switch_page' => 'vendor-settings',
                'route' => 'admin.settings.business-settings.vendor-settings.index',
                'patterns' => [
                    'admin/settings/business-settings/vendor-settings',
                    'admin/settings/retail-settings/vendor-settings',
                    'admin/settings/wholesale/vendor-config',
                ],
            ],
            'products' => [
                'switch_page' => 'product-settings',
                'route' => 'admin.settings.business-settings.product-settings.index',
                'patterns' => ['admin/settings/business-settings/product-settings', 'admin/settings/wholesale/product-config'],
            ],
            'delivery-men' => [
                'switch_page' => 'delivery-man-settings',
                'route' => 'admin.settings.business-settings.delivery-man-settings.index',
                'patterns' => ['admin/settings/business-settings/delivery-man-settings'],
            ],
            'customer' => [
                'switch_page' => 'customer-settings',
                'route' => 'admin.settings.business-settings.customer-settings',
                'patterns' => ['admin/settings/business-settings/customer-settings'],
            ],
            'orders' => [
                'switch_page' => 'order-settings',
                'route' => 'admin.settings.business-settings.order-settings.index',
                'patterns' => ['admin/settings/business-settings/order-settings/index', 'admin/settings/wholesale/order-config', 'admin/settings/business-settings/auction-order-config'],
            ],
            'refund' => [
                'switch_page' => 'refund-setup',
                'route' => 'admin.settings.business-settings.refund-setup',
                'patterns' => ['admin/settings/business-settings/refund-setup', 'admin/settings/wholesale/refund-config'],
            ],
            'shipping-method' => [
                'switch_page' => 'shipping-method',
                'route' => 'admin.settings.business-settings.shipping-method.index',
                'patterns' => ['admin/settings/business-settings/shipping-method/index', 'admin/settings/business-settings/shipping-method/update/*', 'admin/settings/business-settings/shipping-method/wholesale*'],
            ],
            'delivery-zone' => [
                'switch_page' => 'delivery-zone',
                'route' => 'admin.settings.business-settings.delivery-zone.index',
                'patterns' => ['admin/settings/business-settings/delivery-zone'],
            ],
            'others' => [
                'switch_page' => 'auction-others',
                'route' => 'admin.settings.business-settings.auction-others',
                'patterns' => ['admin/settings/business-settings/auction-others'],
                'requires_route' => 'admin.settings.business-settings.auction-others',
                'requires_addon' => 'Auction',
            ],
            'invoice' => [
                'switch_page' => 'invoice-settings',
                'route' => 'admin.settings.business-settings.invoice-settings.index',
                'patterns' => ['admin/settings/business-settings/invoice-settings'],
            ],
        ],

        'scopes' => [
            'common' => ['general', 'website-setup', 'vendors', 'delivery-men', 'customer', 'delivery-zone', 'invoice'],
            'retail' => ['vendors', 'products', 'orders', 'refund', 'shipping-method'],
            'wholesale' => ['general', 'vendors', 'products', 'orders', 'refund', 'shipping-method'],
            'auction' => ['general', 'orders', 'others'],
        ],
    ],

    'vendor_detail_tabs' => [

        'routes' => [
            'common' => 'admin.people.vendors.view.common',
            'retail' => 'admin.people.vendors.view.retail',
            'wholesale' => 'admin.people.vendors.view.wholesale',
            'auction' => 'admin.people.vendors.view.auction',
        ],

        'landing_routes' => [
            'common' => 'admin.people.vendors.view',
        ],

        'scopes' => [
            'common' => ['overview', 'transaction'],
            'retail' => ['order', 'product', 'clearance_sale', 'setting', 'review'],
            'wholesale' => ['order', 'product', 'setting', 'review'],
            'auction' => ['auction_list', 'setting'],
        ],
    ],

    'customer_detail_tabs' => [

        'routes' => [
            'retail' => 'admin.people.customer.view',
            'wholesale' => 'admin.people.customer.view.wholesale',
            'auction' => 'admin.people.customer.view.auction',
        ],
    ],

    'business_module_switch_pages' => [

        'business-settings' => [
            'common' => 'admin.settings.business-settings.web-config.index',
            'retail' => 'admin.settings.retail-settings.vendor-settings.index',
            'auction' => 'admin.settings.business-settings.auction-config',
            'wholesale' => 'admin.settings.wholesale.wholesale-config',
        ],
        'website-setup' => [
            'common' => 'admin.settings.business-settings.website-setup',
        ],
        'vendor-settings' => [
            'common' => 'admin.settings.business-settings.vendor-settings.index',
            'retail' => 'admin.settings.retail-settings.vendor-settings.index',
            'wholesale' => 'admin.settings.wholesale.vendor-config',
        ],
        'auction-others' => [
            'auction' => 'admin.settings.business-settings.auction-others',
        ],
        'product-settings' => [
            'retail' => 'admin.settings.business-settings.product-settings.index',
            'wholesale' => 'admin.settings.wholesale.product-config',
        ],
        'delivery-man-settings' => [
            'common' => 'admin.settings.business-settings.delivery-man-settings.index',
        ],
        'customer-settings' => [
            'common' => 'admin.settings.business-settings.customer-settings',
        ],
        'order-settings' => [
            'retail' => 'admin.settings.business-settings.order-settings.index',
            'wholesale' => 'admin.settings.wholesale.order-config',
            'auction' => 'admin.settings.business-settings.auction-order-config',
        ],
        'refund-setup' => [
            'retail' => 'admin.settings.business-settings.refund-setup',
            'wholesale' => 'admin.settings.wholesale.refund-config',
        ],
        'shipping-method' => [
            'retail' => 'admin.settings.business-settings.shipping-method.index',
            'wholesale' => 'admin.settings.business-settings.shipping-method.wholesale',
        ],
        'delivery-zone' => [
            'common' => 'admin.settings.business-settings.delivery-zone.index',
        ],
        'invoice-settings' => [
            'common' => 'admin.settings.business-settings.invoice-settings.index',
        ],
    ],

    'report_module_switch_pages' => [

        'admin' => [

            'admin-earning' => [
                'retail' => 'admin.report.retail.admin-earning',
                'wholesale' => 'admin.report.wholesale.admin-earning',
            ],
            'vendor-earning' => [
                'retail' => 'admin.report.retail.vendor-earning',
                'wholesale' => 'admin.report.wholesale.vendor-earning',
            ],
            'inhouse-sales' => [
                'retail' => 'admin.report.retail.inhouse-product-sale',
                'wholesale' => 'admin.report.wholesale.inhouse-product-sale',
            ],
            'vendor-sales' => [
                'retail' => 'admin.report.retail.vendor-report',
                'wholesale' => 'admin.report.wholesale.vendor-report',
            ],
            'order' => [
                'retail' => 'admin.report.retail.order',
                'wholesale' => 'admin.report.wholesale.order',
            ],
            'all-product' => [
                'retail' => 'admin.report.retail.all-product',
                'wholesale' => 'admin.report.wholesale.all-product',
            ],
            'product-stock' => [
                'retail' => 'admin.report.retail.stock.product-stock',
                'wholesale' => 'admin.report.wholesale.stock.product-stock',
            ],
            'product-in-wishlist' => [
                'retail' => 'admin.report.retail.stock.product-in-wishlist',
                'wholesale' => 'admin.report.wholesale.stock.product-in-wishlist',
            ],
            'order-transaction' => [
                'retail' => 'admin.report.retail.transaction.order-transaction-list',
                'wholesale' => 'admin.report.wholesale.transaction.order-transaction-list',
            ],
            'expense-transaction' => [
                'retail' => 'admin.report.retail.transaction.expense-transaction-list',
                'wholesale' => 'admin.report.wholesale.transaction.expense-transaction-list',
            ],
            'refund-transaction' => [
                'retail' => 'admin.report.retail.transaction.refund-transaction-list',
                'wholesale' => 'admin.report.wholesale.transaction.refund-transaction-list',
            ],
            'admin-tax' => [
                'retail' => 'admin.report.retail.get-tax-report',
                'wholesale' => 'admin.report.wholesale.get-tax-report',
                'auction' => 'admin.report.auction.get-tax-report',
            ],
            'vendor-tax' => [
                'retail' => 'admin.report.retail.vendor-wise-taxes',
                'wholesale' => 'admin.report.wholesale.vendor-wise-taxes',
                'auction' => 'admin.report.auction.vendor-wise-taxes',
            ],
            'customer-tax' => [
                'auction' => 'admin.report.auction.customer-wise-taxes',
            ],
        ],

        'vendor' => [

            'all-product' => [
                'retail' => 'vendor.report.retail.all-product',
                'wholesale' => 'vendor.report.wholesale.all-product',
            ],
            'product-stock' => [
                'retail' => 'vendor.report.retail.stock-product-report',
                'wholesale' => 'vendor.report.wholesale.stock-product-report',
            ],
            'order' => [
                'retail' => 'vendor.report.retail.order-report',
                'wholesale' => 'vendor.report.wholesale.order-report',
            ],
            'order-transaction' => [
                'retail' => 'vendor.report.retail.transaction.order-list',
                'wholesale' => 'vendor.report.wholesale.transaction.order-list',
            ],
            'expense-transaction' => [
                'retail' => 'vendor.report.retail.transaction.expense-list',
                'wholesale' => 'vendor.report.wholesale.transaction.expense-list',
            ],
            'vat-report' => [
                'retail' => 'vendor.report.retail.get-vat-report',
                'wholesale' => 'vendor.report.wholesale.get-vat-report',
            ],
        ],
    ],

    'customer_profile_menu' => [

        'profile' => [
            'label' => 'Profile Information',
            'drawer_label' => 'My Profile',
            'icon' => ['class' => 'fi-rr-user', 'image' => 'assets/img/icons/profile-icon.png'],
            'route' => 'user-profile',
            'patterns' => ['customer/profile', 'customer/profile/*'],
        ],

        'orders' => [
            'label' => 'My order',
            'drawer_label' => 'Orders',
            'icon' => ['class' => 'fi-rr-box-open', 'image' => 'assets/img/icons/profile-icon2.png'],
            'routes' => [
                'retail' => 'account-oder',
                'wholesale' => 'wholesale.orders.my-orders',
            ],
            'patterns' => ['customer/orders', 'customer/orders/*', 'customer/order-wise-result-view'],
        ],

        'restock-requests' => [
            'label' => 'Restock Requests',
            'icon' => ['class' => 'fi-rr-refresh', 'image' => 'assets/img/icons/restock-request.png'],
            'routes' => [
                'retail' => 'user-restock-requests',
            ],
            'patterns' => ['customer/restock-requests', 'customer/restock-requests/*'],
        ],

        'wishlist' => [
            'label' => 'Wish list',
            'drawer_label' => 'Wishlist',
            'icon' => ['class' => 'fi-rr-heart', 'image' => 'assets/img/icons/profile-icon3.png'],
            'routes' => [
                'retail' => 'wishlists.index',
                'wholesale' => 'wholesale.wishlist.index',
            ],
            'patterns' => ['customer/wishlist', 'customer/wishlist/*'],
        ],

        'compare' => [
            'label' => 'Compare list',
            'icon' => ['class' => 'fi-rr-exchange', 'image' => 'assets/img/icons/profile-icon4.png'],
            'route' => 'product-compare.index',
            'patterns' => ['product-compare', 'product-compare/*'],
            'requires_theme_view' => 'account_compare_list',
        ],

        'wallet' => [
            'label' => 'My wallet',
            'drawer_label' => 'Wallet',
            'icon' => ['class' => 'fi-rr-wallet', 'image' => 'assets/img/icons/profile-icon5.png'],
            'route' => 'wallet',
            'patterns' => ['customer/wallet', 'customer/wallet/*'],
            'requires_setting' => 'wallet_status',
        ],

        'loyalty' => [
            'label' => 'My loyalty point',
            'drawer_label' => 'Loyalty point',
            'icon' => ['class' => 'fi-rr-medal', 'image' => 'assets/img/icons/profile-icon6.png'],
            'route' => 'loyalty',
            'patterns' => ['customer/loyalty', 'customer/loyalty/*'],
            'requires_setting' => 'loyalty_point_status',
        ],

        'inbox' => [
            'label' => 'Inbox',
            'icon' => ['class' => 'fi-rr-envelope', 'image' => 'assets/img/icons/profile-icon7.png'],
            'routes' => [
                'retail' => 'chat',
                'wholesale' => 'wholesale.chat.index',
            ],
            'patterns' => ['customer/inbox', 'customer/inbox/*'],
        ],

        'address' => [
            'label' => 'My Address',
            'drawer_label' => 'Address',
            'icon' => ['class' => 'fi-rr-marker', 'image' => 'assets/img/icons/location.png'],
            'route' => 'account-address',
            'patterns' => ['customer/address', 'customer/address/*'],
        ],

        'tickets' => [
            'label' => 'Support ticket',
            'icon' => ['class' => 'fi-rr-ticket', 'image' => 'assets/img/icons/profile-icon8.png'],
            'routes' => [
                'retail' => 'account-tickets',
                'wholesale' => 'wholesale.tickets.index',
            ],
            'patterns' => ['customer/tickets', 'customer/tickets/*'],
        ],

        'refer-earn' => [
            'label' => 'Refer & Earn',
            'icon' => ['class' => 'fi-rr-share', 'image' => 'assets/img/icons/refer-and-earn.svg'],
            'route' => 'refer-earn',
            'patterns' => ['customer/refer-earn'],
            'requires_setting' => 'ref_earning_status',
        ],

        'coupons' => [
            'label' => 'Coupons',
            'drawer_label' => 'Coupon',
            'icon' => ['class' => 'fi-rr-badge-percent', 'image' => 'assets/img/icons/coupon.svg'],
            'routes' => [
                'retail' => 'user-coupons',
                'wholesale' => 'wholesale.coupons.index',
            ],
            'patterns' => ['customer/coupons', 'customer/coupons/*'],
        ],

        'track-order' => [
            'label' => 'Track Order',
            'icon' => ['class' => 'fi-rr-shipping-fast', 'image' => 'assets/img/icons/track-shopping-list.svg'],
            'routes' => [
                'retail' => 'track-order.index',
                'wholesale' => 'wholesale.orders.track-order',
            ],
            'patterns' => ['customer/track-order', 'customer/track-order/*'],
        ],
    ],

    'customer_order_pages' => [
        'retail' => [
            'details' => 'account-order-details',
            'vendor_info' => 'account-order-details-vendor-info',
            'delivery_man_info' => 'account-order-details-delivery-man-info',
            'reviews' => 'account-order-details-reviews',
            'track' => 'track-order.order-wise-result-view',
            'invoice' => 'generate-invoice',
            'delivery_man_review' => 'review.deliveryman.store',
        ],
        'wholesale' => [
            'details' => 'wholesale.orders.details',
            'vendor_info' => 'wholesale.orders.details.vendor-info',
            'delivery_man_info' => 'wholesale.orders.details.delivery-man-info',
            'reviews' => 'wholesale.orders.details.reviews',
            'track' => 'wholesale.orders.details.track-order',
            'invoice' => 'wholesale.orders.invoice',
            'delivery_man_review' => 'wholesale.orders.delivery-man-review',
        ],
    ],

    'admin_pending_orders' => [
        'retail' => [
            'permission' => 'retail.orders',
            'list_route' => 'admin.retail.orders.list',
            'details_route' => 'admin.retail.orders.details',
        ],
        'wholesale' => [
            'permission' => 'wholesale.orders',
            'list_route' => 'admin.wholesale.orders.list',
            'details_route' => 'admin.wholesale.orders.details',
        ],
    ],

    'vendor_pending_orders' => [
        'retail' => [
            'list_route' => 'vendor.retail.orders.list',
            'details_route' => 'vendor.retail.orders.details',
        ],
        'wholesale' => [
            'list_route' => 'vendor.wholesale.orders.list',
            'details_route' => 'vendor.wholesale.orders.details',
        ],
    ],
];
