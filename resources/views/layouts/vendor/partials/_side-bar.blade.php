@php
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Shop;
$shop = Shop::where(['seller_id' => auth('seller')->id()])->first();
@endphp
<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between side-logo dashboard-navbar-side-logo-wrapper">
                <a class="navbar-brand" href="{{ businessModuleDashboardUrl() }}" aria-label="Front">
                    @if (isset($shop))
                        <img class="navbar-brand-logo-mini for-seller-logo"
                            src="{{ getStorageImages(path: $shop->image_full_url, type: 'backend-logo') }}"
                            alt="{{ translate('Logo') }}">
                    @else
                        <img class="navbar-brand-logo-mini for-seller-logo"
                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/900x400/img1.jpg') }}"
                            alt="{{ translate('Logo') }}">
                    @endif
                </a>
                <button type="button"
                    class="d-none js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>

                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                    <i class="fi fi-rr-menu-burger"></i>
                </button>
            </div>
            <div class="navbar-vertical-footer-offset pb-0">
                <div class="navbar-vertical-content">
                    <div class="sidebar--search-form pb-3 pt-4 mx-3">
                        <div class="search--form-group">
                            <button type="button" class="btn"><i class="tio-search"></i></button>
                            <input type="text" class="js-form-search form-control form--control"
                                id="search-bar-input" placeholder="{{ translate('Search menu') . '...' }}">
                        </div>
                    </div>
                    <ul class="navbar-nav navbar-nav-lg nav-tabs">
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor/*/dashboard*') ? 'show' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ businessModuleDashboardUrl() }}" title="{{ translate('Dashboard') }}">
                                <i class="tio-home-vs-1-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Dashboard') }}
                                </span>
                            </a>
                        </li>
                        @php($seller = auth('seller')->user())
                        @php($sellerId = $seller['id'])
                        @php($sellerPOS = getWebConfig('seller_pos'))
                        @if ($sellerPOS == 1 && $seller['pos_status'] == 1)
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/pos*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.retail.pos.index') }}" title="{{ translate('POS') }}">
                                    <i class="tio-shopping nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('POS') }}</span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('Order management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/orders*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('Orders') }}">
                                <i class="tio-shopping-cart nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Orders') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('vendor/order*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/all') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['all']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('All') }}
                                            <span
                                                class="badge badge-soft-info badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/pending') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['pending']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Pending') }}
                                            <span
                                                class="badge badge-soft-info badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'pending'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/confirmed') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['confirmed']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Confirmed') }}
                                            <span
                                                class="badge badge-soft-info badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'confirmed'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('vendor/retail/orders/list/processing') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['processing']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Packaging') }}
                                            <span
                                                class="badge badge-soft-warning badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'processing'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('vendor/retail/orders/list/out_for_delivery') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['out_for_delivery']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">
                                            {{ translate('Out For Delivery') }}
                                            <span
                                                class="badge badge-soft-warning badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'out_for_delivery'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/delivered') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['delivered']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Delivered') }}
                                            <span
                                                class="badge badge-soft-success badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'delivered'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/returned') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['returned']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Returned') }}
                                            <span class="badge badge-soft-danger badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'returned'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/failed') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['failed']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Failed to deliver') }}
                                            <span class="badge badge-soft-danger badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'failed'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/orders/list/canceled') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.orders.list', ['canceled']) }}"
                                        title="">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Canceled') }}
                                            <span class="badge badge-soft-danger badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                {{ Order::where(['seller_is' => 'seller'])->where(['seller_id' => $sellerId])->where(['order_status' => 'canceled'])->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/refund*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('Refund Requests') }}">
                                <i class="tio-receipt-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Refund Requests') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('vendor/retail/refund*') ? 'block' : 'none' }}">
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/refund/index/pending') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.retail.refund.index', ['pending']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Pending') }}
                                            <span class="badge badge-soft-danger badge-pill ml-1">
                                                {{ RefundRequest::whereHas('order', function ($query) {
                                                    $query->where('seller_is', 'seller')->where('seller_id', auth('seller')->id());
                                                })->where('status', 'pending')->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('vendor/retail/refund/index/approved') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.retail.refund.index', ['approved']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Approved') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ RefundRequest::whereHas('order', function ($query) {
                                                    $query->where('seller_is', 'seller')->where('seller_id', auth('seller')->id());
                                                })->where('status', 'approved')->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/refund/index/refunded') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.retail.refund.index', ['refunded']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Refunded') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{ RefundRequest::whereHas('order', function ($query) {
    $query->where('seller_is', 'seller')->where('seller_id', auth('seller')->id());
})->where('status', 'refunded')->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/refund/index/rejected') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.retail.refund.index', ['rejected']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">
                                            {{ translate('Rejected') }}
                                            <span class="badge badge-danger badge-pill ml-1">
                                                {{ RefundRequest::whereHas('order', function ($query) {
    $query->where('seller_is', 'seller')->where('seller_id', auth('seller')->id());
})->where('status', 'rejected')->count() }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('Product management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/product*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('Products') }}">
                                <i class="tio-premium-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Products') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('vendor/retail/products*') ? 'block' : '' }}">
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/list/all') || Request::is('vendor/retail/products/update*') || Request::is('vendor/retail/products/view*') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.retail.products.list', ['type' => 'all']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('Product List') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/list/approved') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.retail.products.list', ['type' => 'approved']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('Approved Product List') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/list/new-request') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.retail.products.list', ['type' => 'new-request']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('New Product Request') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor/retail/products/list/denied') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.retail.products.list', ['type' => 'denied']) }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('Denied Product Request') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/add') || (Request::is('vendor/retail/products/update/*') && request()->has('product-gallery')) ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.products.add') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">
                                            {{ translate('Add New Product') }}
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/product-gallery') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.products.product-gallery') }}"
                                        title="{{ translate('Product Gallery') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('Product Gallery') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/stock-limit-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.products.stock-limit-list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">
                                            {{ translate('Limited Stock') }}
                                        </span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.products.bulk-import') }}"
                                        title="{{ translate('Bulk Import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('Bulk Import') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/retail/products/request-restock-list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.retail.products.request-restock-list') }}"
                                        title="{{ translate('Request Restock List') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('Request Restock List') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/reviews/index*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.reviews.index') }}"
                                title="{{ translate('Product Reviews') }}">
                                <i class="tio-star nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Product Reviews') }}
                                </span>
                            </a>
                        </li>


                            @if(businessModuleAvailable(\App\Enums\BusinessModule::AUCTION))
                            <li class="nav-item">
                                <small class="nav-subtitle">{{ translate('Auction Management') }}</small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                                <?php
                                $auctionRoutes = include(base_path("Modules/Auction/Addon/vendor_routes.php"));
                                ?>
                                @foreach($auctionRoutes as $auctionRoute)
                                    <li class="navbar-vertical-aside-has-menu
                                        @foreach($auctionRoute['routes'] as $route)
                                                    {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}
                                        @endforeach">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate($auctionRoute['name']) }}">
                                            <i class="tio-chart-bar-1 nav-icon"></i>
                                            <span
                                                class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <span class="text-truncate">
                                                {{ translate($auctionRoute['name']) }}
                                            </span>
                                        </span>
                                        </a>

                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display:
                        @foreach($auctionRoute['routes'] as $route)
                                            {{ strstr(Request::url(), $route['path']) ? 'block' : '' }}
                        @endforeach">
                                            @foreach($auctionRoute['routes'] as $route)
                                                <?php
                                                    $badgeData = getAuctionSidebarBadgeData($route['path'], $sidebarAuctionVendorOwnCounts ?? []);
                                                ?>

                                                @if((str_contains($route['path'], 'vendor/auction/products/add') && getWebConfig(name: 'active_auction_for_vendor') == 1) || !str_contains($route['path'], 'vendor/auction/products/add'))
                                                    <li class="nav-item {{strstr(Request::url(), $route['path']) ? 'active' : '' }}">
                                                    <a class="nav-link"
                                                       href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate">
                                                        {{ translate($route['name']) }}
                                                        @if($badgeData['is_status_route'])
                                                            <span class="badge badge-soft-{{ $badgeData['badge_class'] }} badge-pill {{ Session::get('direction') === 'rtl' ? 'mr-1' : 'ml-1' }}">
                                                                {{ $badgeData['count'] }}
                                                            </span>
                                                        @endif
                                                    </span>
                                                    </a>
                                                </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            @endif

                            @if(getCheckAddonPublishedStatus(moduleName: 'B2BManager'))
                                <li class="nav-item">
                                    <small class="nav-subtitle">{{ translate('Wholesale management') }}</small>
                                    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                                </li>

                                <?php
                                    $b2bRoutes = include(base_path("Modules/B2BManager/Addon/vendor_routes.php"));
                                ?>
                                @foreach($b2bRoutes as $b2bRoute)
                                    <li class="navbar-vertical-aside-has-menu
                                        @foreach($b2bRoute['routes'] as $route)
                                            {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}
                                        @endforeach">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="{{ translate($b2bRoute['name']) }}">
                                            <i class="tio-shop nav-icon"></i>
                                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                                <span class="text-truncate">
                                                    {{ translate($b2bRoute['name']) }}
                                                </span>
                                            </span>
                                        </a>

                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                            style="display:
                                            @foreach($b2bRoute['routes'] as $route)
                                                {{ strstr(Request::url(), $route['path']) ? 'block' : '' }}
                                            @endforeach">
                                            @foreach($b2bRoute['routes'] as $route)
                                                <li class="nav-item {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}">
                                                    <a class="nav-link" href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                                        <span class="tio-circle nav-indicator-icon"></span>
                                                        <span class="text-truncate">
                                                            {{ translate($route['name']) }}
                                                        </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            @endif

                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('Promotion management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        @if (businessModuleItemVisible('marketing.promotions_retail'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/coupon*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.retail.coupon.index') }}"
                                    title="{{ translate('Coupons') }}">
                                    <i class="tio-users-switch nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate('Coupons') }}
                                    </span>
                                </a>
                            </li>
                        @endif

                        @if (getCheckAddonPublishedStatus(moduleName: 'Wholesale') && businessModuleItemVisible('marketing.promotions_wholesale'))
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('vendor/wholesale/coupon*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vendor.wholesale.coupon.index') }}"
                                    title="{{ translate('Coupons') }}">
                                    <i class="tio-users-switch nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        {{ translate('Coupons') }}
                                    </span>
                                </a>
                            </li>
                        @endif

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/retail/clearance-sale*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.retail.clearance-sale.index') }}"
                                title="{{ translate('Clearance Sale') }}">
                                <i class="tio-notice nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Clearance Sale') }}
                                </span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('Help & support') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/messages*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('vendor.messages.index') }}"
                                title="{{ translate('Inbox') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Inbox') }}
                                </span>
                            </a>
                        </li>
                        <li
                            class="nav-item {{ Request::is('vendor/report/*/transaction/order-list') ? 'scroll-here' : '' }}">
                            <small class="nav-subtitle">{{ translate('Reports & analytics') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/report/*/transaction/order-list') || Request::is('vendor/report/*/transaction/expense-list') || Request::is('vendor/report/*/transaction/order-history-log*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route(reportModuleRoute('transaction.order-list')) }}"
                                title="{{ translate('Transaction report') }}">
                                <i class="tio-chart-bar-3 nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                    {{ translate('Transaction report') }}
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/report/*/all-product') || Request::is('vendor/report/*/stock-product-report') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link text-capitalize"
                                href="{{ route(reportModuleRoute('all-product')) }}"
                                title="{{ translate('Product Report') }}">
                                <i class="tio-chart-bar-4 nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    <span class="position-relative text-capitalize">
                                        {{ translate('Product Report') }}
                                    </span>
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/report/*/order-report') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link text-capitalize"
                                href="{{ route(reportModuleRoute('order-report')) }}"
                                title="{{ translate('Order Report') }}">
                                <i class="tio-chart-bar-1 nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Order Report') }}
                                </span>
                            </a>
                        </li>

                        @if (getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                            @foreach (include base_path('Modules/TaxModule/Addon/vendor_tax_report_routes.php') as $route)
                                <li
                                    class="navbar-vertical-aside-has-menu
                                    {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}

                                    @if (isset($route['sub_routes'])) @foreach ($route['sub_routes'] as $subRoute)
                                            {{ strstr(Request::url(), $subRoute['path']) ? 'active' : '' }}
                                        @endforeach @endif
                                ">
                                    <a class="js-navbar-vertical-aside-menu-link nav-link text-capitalize"
                                        href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                        <i class="tio-chart-bar-1 nav-icon"></i>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate($route['name']) }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        <li class="nav-item {{ Request::is('vendor/business-settings*') ? 'scroll-here' : '' }}">
                            <small class="nav-subtitle" title="">{{ translate('Business section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/business-settings/shipping-method*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.business-settings.shipping-method.index') }}"
                                title="{{ translate('Shipping Methods') }}">
                                <i class="tio-settings nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize text-capitalize">
                                    {{ translate('Shipping Methods') }}
                                </span>
                            </a>
                        </li>
                        @php($vendorCourierModule = vendorDeliveryPartnerSetupModule())
                        @if ($vendorCourierModule !== null)
                            <li
                                class="navbar-vertical-aside-has-menu {{ Request::is('vendor/courier/config*') ? 'active' : '' }}">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                    href="{{ route('vendor.courier.config.index', ['business_module' => $vendorCourierModule]) }}"
                                    title="{{ translate('3rd Party Delivery Partner') }}">
                                    <i class="tio-truck nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                        {{ translate('3rd Party Delivery Partner') }}
                                    </span>
                                </a>
                            </li>
                        @endif
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/business-settings/withdraw*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.business-settings.withdraw.index') }}"
                                title="{{ translate('Withdraws') }}">
                                <i class="tio-wallet-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                    {{ translate('Withdraws') }}
                                </span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/profile/index') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.profile.index') }}"
                                title="{{ translate('Bank information') }}">
                                <i class="tio-shop nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                    {{ translate('Bank information') }}
                                </span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor/shop*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.shop.index') }}" title="{{ translate('Shop Settings') }}">
                                <i class="tio-home nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                    {{ translate('Shop Settings') }}
                                </span>
                            </a>
                        </li>

                        <li class="nav-item {{ Request::is('vendor/delivery-man*') ? 'scroll-here' : '' }}">
                            <small class="nav-subtitle">{{ translate('Delivery man management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor/delivery-man*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Delivery Man') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('vendor/delivery-man*') ? 'block' : 'none' }}">
                                <li class="nav-item {{ Request::is('vendor/delivery-man/index') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.delivery-man.index') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate text-capitalize">{{ translate('Add New') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/delivery-man/list') || Request::is('vendor/delivery-man/update') || Request::is('vendor/delivery-man/rating/*') || Request::is('vendor/delivery-man/wallet*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.delivery-man.list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('List') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor/delivery-man/withdraw/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.delivery-man.withdraw.index') }}"
                                        title="{{ translate('Withdraws') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('Withdraws') }}</span>
                                    </a>
                                </li>

                                <li
                                    class="nav-item {{ Request::is('vendor/delivery-man/emergency-contact/*') ? 'active' : '' }}">
                                    <a class="nav-link "
                                        href="{{ route('vendor.delivery-man.emergency-contact.index') }}"
                                        title="{{ translate('Withdraws') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('Emergency Contact') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <?php $checkSetupGuideRequirements = checkSetupGuideRequirements(panel: 'vendor'); ?>

                        <li class="nav-item ">

                            @if ($checkSetupGuideRequirements['completePercent'] < 100)
                        <li class="pb-5">
                            <div class="py-5"></div>
                        </li>
                    @else
                        <li>
                            <div class="py-2"></div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </aside>
</div>

@include('layouts.vendor.partials._setup-guide')
