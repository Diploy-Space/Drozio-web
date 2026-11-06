@php
    use App\Utils\Helpers;
    use App\Enums\EmailTemplateKey;
    $eCommerceLogo = getWebConfig(name: 'company_web_logo');
@endphp

<aside class="js-aside aside d-none d-lg-block">
    <div class="aside-header d-flex align-items-center gap-2 justify-content-between">
        <a class="navbar-logo" href="{{ businessModuleDashboardUrl() }}">
            <img height="24" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                 alt="{{ translate('Logo') }}">
        </a>
        <button type="button" class="js-aside-toggle navbar-aside-toggle btn-icon border-0">
            <i class="fi fi-rr-menu-burger"></i>
        </button>
    </div>
    <div class="aside-body search-aside-attribute-container py-4 pt-0">
        <div class="aside-search-form pt-lg-3 pb-3">
            <div class="input-group flex-nowrap">
                <input type="text" class="form-control search-aside-attribute"
                       placeholder="{{ translate('Search menu') }}">
                <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
            </div>
        </div>

        <ul class="aside-nav navbar-nav gap-2">
            <li>
                <a class="nav-link {{ Request::is('admin/*/dashboard') ? 'active' : '' }}"
                   title="{{ translate('Dashboard') }}" href="{{ businessModuleDashboardUrl() }}">
                    <i class="fi fi-sr-home"></i>
                    <span class="aside-mini-hidden-element text-truncate">
                        {{ translate('Dashboard') }}
                    </span>
                </a>
            </li>
            @if (Helpers::module_permission_check('retail.pos'))
                <li>
                    <a class="nav-link {{ Request::is('admin/retail/pos*') ? 'active' : '' }}" title="{{ translate('POS') }}"
                       href="{{ route('admin.retail.pos.index') }}">
                        <i class="fi fi-sr-point-of-sale-bill"></i>
                        <span class="aside-mini-hidden-element text-truncate">{{ translate('POS') }}</span>
                    </a>
                </li>
            @endif
            @if(Helpers::module_permission_check('retail.orders'))
                <li class="nav-item nav-item_title {{ Request::is('admin/retail/orders*')?((Request::is('admin/retail/orders/details/*') && request()->has('vendor-order-list')) ? '' : 'scroll-here'):''}}">
                    <small class="nav-subtitle" title="">{{ translate('Order management') }}</small>
                </li>
                <li class="{{ Request::is('admin/retail/orders*') ? 'sub-menu-opened' : ''}}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/retail/orders*')?((Request::is('admin/retail/orders/details/*') && request()->has('vendor-order-list')) ? '' : 'active'):''}}"
                       href="javascript:" title="{{ translate('Orders') }}">
                        <i class="fi fi-sr-shopping-cart"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('Orders') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Orders') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/all') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list', ['all']) }}" title="{{ translate('All') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('All') }}
                                </span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ $sidebarTotalOrders }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/pending') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['pending']) }}" title="{{ translate('Pending') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Pending') }}
                                </span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                  {{ $sidebarOrderCounts['pending'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/confirmed') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['confirmed']) }}"
                               title="{{ translate('Confirmed') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Confirmed') }}
                                </span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                  {{ $sidebarOrderCounts['confirmed'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/processing') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['processing']) }}"
                               title="{{ translate('Packaging') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Packaging') }}
                                </span>
                                <span class="badge fw-bold badge-warning badge-sm text-bg-warning">
                                 {{ $sidebarOrderCounts['processing'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/out_for_delivery') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['out_for_delivery']) }}"
                               title="{{ translate('Out For Delivery') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Out For Delivery') }}
                                </span>
                                <span class="badge fw-bold badge-warning badge-sm text-bg-warning">
                                   {{ $sidebarOrderCounts['out_for_delivery'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/delivered') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['delivered']) }}"
                               title="{{ translate('Delivered') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Delivered') }}
                                </span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                  {{ $sidebarOrderCounts['delivered'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/returned') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['returned']) }}" title="{{ translate('Returned') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Returned') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                 {{ $sidebarOrderCounts['returned'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/failed') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['failed']) }}" title="{{ translate('Failed') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Failed to deliver') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                  {{ $sidebarOrderCounts['failed'] ?? 0 }}
                                </span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/orders/list/canceled') ? 'active' : '' }}"
                               href="{{ route('admin.retail.orders.list',['canceled']) }}" title="{{ translate('Canceled') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Canceled') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                {{ $sidebarOrderCounts['canceled'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::is('admin/retail/refund-section/*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/retail/refund-section/refund/*') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Refund Requests') }}">
                        <i class="fi fi-sr-refund-alt"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Refund Requests') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Refund Requests') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/refund-section/refund/list/pending') ? 'active' : '' }}"
                               href="{{ route('admin.retail.refund-section.refund.list',['pending']) }}"
                               title="{{ translate('Pending') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Pending') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                 {{ $sidebarRefundCounts['pending'] ?? 0 }}
                                </span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/refund-section/refund/list/approved') ? 'active' : '' }}"
                               href="{{ route('admin.retail.refund-section.refund.list',['approved']) }}"
                               title="{{ translate('Approved') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Approved') }}
                                </span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ $sidebarRefundCounts['approved'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/refund-section/refund/list/refunded') ? 'active' : '' }}"
                               href="{{ route('admin.retail.refund-section.refund.list',['refunded']) }}"
                               title="{{ translate('Refunded') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Refunded') }}
                                </span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                  {{ $sidebarRefundCounts['refunded'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/refund-section/refund/list/rejected') ? 'active' : '' }}"
                               href="{{ route('admin.retail.refund-section.refund.list',['rejected']) }}"
                               title="{{ translate('Rejected') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Rejected') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                 {{ $sidebarRefundCounts['rejected'] ?? 0 }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            @if(Helpers::module_permission_check('retail.catalog'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/retail/brand*') || Request::is('admin/retail/category*') || Request::is('admin/sub*') || Request::is('admin/retail/attribute*') || Request::is('admin/retail/products*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="">{{ translate('Product management') }}</small>
                </li>
                <li class="{{ (Request::is('admin/retail/category*') || Request::is('admin/retail/sub-category*') || Request::is('admin/retail/sub-sub-category*'))  ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/retail/category*') || Request::is('admin/retail/sub-category*') || Request::is('admin/retail/sub-sub-category*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Category Setup') }}">
                        <i class="fi fi-sr-apps"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Category Setup') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">
                            {{ translate('Category Setup') }}
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/category/*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.category.view') }}" title="{{ translate('Categories') }}">
                                <span class="text-truncate">{{ translate('Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/sub-category/*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.sub-category.view') }}" title="{{ translate('Sub Categories') }}">
                                <span class="text-truncate">{{ translate('Sub Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/sub-sub-category/*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.sub-sub-category.view') }}"
                               title="{{ translate('Sub Sub Categories') }}">
                                <span class="text-truncate">{{ translate('Sub Sub Categories') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/retail/brand/list') ? 'active' : '' }}"
                       href="{{ route('admin.retail.brand.list') }}" title="{{ translate('Brand setup') }}">
                        <i class="fi fi-sr-brand"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center text-truncate max-w-180">
                            {{ translate('Brand Setup') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/retail/attribute*') ? 'active' : '' }}"
                       href="{{ route('admin.retail.attribute.view') }}" title="{{ translate('Product Attribute Setup') }}">
                        <i class="fi fi-sr-sitemap"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center text-truncate max-w-180">
                            {{ translate('Product Attribute Setup') }}
                        </span>
                    </a>
                </li>
                <li class="{{ (Request::is('admin/retail/products/list/in-house*') || Request::is('admin/retail/products/bulk-import') || Request::is('admin/retail/products/request-restock-list')  || (Request::is('admin/retail/products/add')) || (Request::is('admin/retail/products/view/in-house/*')) || (Request::is('admin/retail/products/barcode/*'))|| (Request::is('admin/retail/products/stock-limit-list')) || (Request::is('admin/retail/products/update/*') || request()->has('product-gallery'))) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/retail/products/list/in-house*') || Request::is('admin/retail/products/bulk-import') || Request::is('admin/retail/products/request-restock-list')  || (Request::is('admin/retail/products/add')) || (Request::is('admin/retail/products/view/in-house/*')) || (Request::is('admin/retail/products/barcode/*'))|| Request::is('admin/retail/products/stock-limit-list') || (Request::is('admin/retail/products/update/*') || request()->has('product-gallery'))) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('In-house Products') }}">
                        <i class="fi fi-sr-box-open"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('In-house Products') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('In-house Products') }}</li>
                        <li class="nav-item ">
                            <a class="nav-link {{ (Request::is('admin/retail/products/list/in-house*') || (Request::is('admin/retail/products/view/in-house/*')) || (Request::is('admin/retail/products/stock-limit-list')) || (Request::is('admin/retail/products/barcode/*'))) ? 'active' : '' }}"
                               href="{{ route('admin.retail.products.list', ['in-house']) }}"
                               title="{{ translate('Product List') }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Product List') }}
                                </span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{getAdminProductsCount('all') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/products/add') || (Request::is('admin/retail/products/update/*') || request()->has('product-gallery')) ? 'active' : '' }}"
                               href="{{ route('admin.retail.products.add') }}" title="{{ translate('Add New Product') }}">
                                <span class="text-truncate">{{ translate('Add New Product') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/products/stock-limit-list') ? 'active' : '' }}"
                               href="{{ route('admin.retail.products.stock-limit-list') }}" title="{{ translate('Limited stock') }}">
                                <span class="text-truncate">{{ translate('Limited Stock') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/products/bulk-import') ? 'active' : '' }}"
                               href="{{ route('admin.retail.products.bulk-import') }}" title="{{ translate('Bulk Import') }}">
                                <span class="text-truncate">{{ translate('Bulk Import') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/retail/products/request-restock-list') ? 'active' : '' }}"
                               href="{{ route('admin.retail.products.request-restock-list') }}"
                               title="{{ translate('Request Restock List') }}">
                                <span class="text-truncate">{{ translate('Request Restock List') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::is('admin/retail/products/list/vendor*')||(Request::is('admin/retail/products/view/vendor/*'))||Request::is('admin/retail/products/updated-product-list') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/retail/products/list/vendor*')||(Request::is('admin/retail/products/view/vendor/*'))||Request::is('admin/retail/products/updated-product-list') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Vendor Products') }}">
                        <i class="fi fi-sr-seller"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Vendor Products') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Vendor Products') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{str_contains(url()->current().'?request_status='.request()->get('request_status'),'admin/retail/products/list/vendor?request_status=0') == 1 ? 'active' : '' }}"
                               title="{{ translate('New Products Requests') }}"
                               href="{{ route('admin.retail.products.list',['vendor', 'request_status'=>'0']) }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ Str::limit(translate('New Products Requests'), 18, '...') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ getVendorProductsCount(type: 'new-product', panel: 'admin') }}
                                </span>
                            </a>
                        </li>
                        @if (getWebConfig(name: 'product_wise_shipping_cost_approval')==1)
                            <li class="nav-item">
                                <a class="nav-link text-capitalize {{ Request::is('admin/retail/products/updated-product-list/vendor') ? 'active' : '' }}"
                                   title="{{ translate('Product update requests') }}"
                                   href="{{ route('admin.retail.products.updated-product-list',['type' => 'vendor']) }}">
                                    <span class="flex-grow-1 text-truncate">
                                        {{Str::limit(translate('Product update requests'), 18, '...') }}
                                    </span>
                                    <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                        {{getVendorProductsCount(type: 'product-updated-request', panel: 'admin') }}
                                    </span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link {{str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/retail/products/list/vendor?request_status=1')==1? 'active' : '' }}"
                               title="{{ translate('Approved Products') }}"
                               href="{{ route('admin.retail.products.list',['vendor', 'request_status'=>'1']) }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Approved Products') }}
                                </span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{getVendorProductsCount(type: 'approved', panel: 'admin') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/retail/products/list/vendor?request_status=2')==1? 'active' : '' }}"
                               title="{{ translate('Denied Products') }}"
                               href="{{ route('admin.retail.products.list',['vendor', 'request_status'=>'2']) }}">
                                <span class="flex-grow-1 text-truncate">
                                    {{ translate('Denied Products') }}
                                </span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{getVendorProductsCount(type: 'denied', panel: 'admin') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a class="nav-link {{ Request::is('admin/retail/products/product-gallery') ? 'active' : '' }}"
                       href="{{ route('admin.retail.products.product-gallery') }}" title="{{ translate('Product Gallery') }}">
                        <i class="fi fi-sr-boxes"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            {{ translate('Product Gallery') }}
                        </span>
                    </a>
                </li>
            @endif

            @if(Helpers::module_permission_check('retail.marketing'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/retail/banner*') || (Request::is('admin/retail/coupon*')) || (Request::is('admin/retail/notification*')) || (Request::is('admin/retail/deal*'))) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="">{{ translate('Promotion management') }}</small>
                </li>
                <li>
                    <a class="nav-link {{ Request::is('admin/retail/banner*') ? 'active' : '' }}"
                       href="{{ route('admin.retail.banner.list') }}" title="{{ translate('Banner Setup') }}">
                        <i class="fi fi-sr-pennant"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Banner Setup') }}
                        </span>
                    </a>
                </li>

                <li class="{{ (Request::is('admin/retail/coupon*') || Request::is('admin/retail/deal*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/retail/coupon*') || Request::is('admin/retail/deal*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Offers & Deals') }}">
                        <i class="fi fi-sr-badge-percent"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Offers & Deals') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Offers & Deals') }}</li>
                        <li>
                            <a class="nav-link {{ Request::is('admin/retail/coupon*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.coupon.add') }}" title="{{ translate('Coupon') }}">
                                <span class="text-truncate">{{ translate('Coupon') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/retail/deal/flash*') || (Request::is('admin/retail/deal/update*'))) ? 'active' : '' }}"
                               href="{{ route('admin.retail.deal.flash') }}" title="{{ translate('Flash Deals') }}">
                                <span class="text-truncate">{{ translate('Flash Deals') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ Request::is('admin/retail/deal/day') ? 'active' : '' }}"
                               href="{{ route('admin.retail.deal.day') }}" title="{{ translate('Deal of the day') }}">
                                <span class="text-truncate">
                                    {{ translate('Deal of the day') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/retail/deal/feature') || Request::is('admin/retail/deal/feature/new') || Request::is('admin/retail/deal/feature-update*')) ? 'active' : '' }}"
                               href="{{ route('admin.retail.deal.feature') }}" title="{{ translate('Featured Deal') }}">
                                <span class="text-truncate">
                                    {{ translate('Featured Deal') }}
                                </span>
                            </a>
                        </li>

                        <li>
                            <a class="nav-link {{ Request::is('admin/retail/deal/clearance-sale') || Request::is('admin/retail/deal/clearance-sale*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.deal.clearance-sale.index') }}"
                               title="{{ translate('Clearance Sale') }}">
                                <span class="text-truncate">
                                    {{ translate('Clearance Sale') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::is('admin/retail/notification*') || Request::is('admin/settings/system-setup/push-notification/index*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/retail/notification*') || Request::is('admin/settings/system-setup/push-notification/index*') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Notifications') }}">
                        <i class="fi fi-sr-paper-plane"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Notifications') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Notifications') }}</li>
                        <li>
                            <a class="nav-link {{!Request::is('admin/retail/notification/push') && Request::is('admin/retail/notification/*') ? 'active' : '' }}"
                               href="{{ route('admin.retail.notification.index') }}"
                               title="{{ translate('Send notification') }}">
                                <span class="text-truncate text-capitalize">
                                    {{ translate('Send notification') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link text-capitalize {{ Request::is('admin/settings/system-setup/push-notification/index*') ? 'active' : '' }}"
                               href="{{ route('admin.settings.system-setup.push-notification.index') }}"
                               title="{{ translate('Push notifications setup') }}">
                                <span class="text-truncate text-capitalize">
                                    {{ translate('Push notifications setup') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::is('admin/retail/announcement*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/retail/announcement*') ? 'active' : '' }}"
                       href="{{ route('admin.retail.announcement') }}"
                       title="{{ translate('Announcement') }}">
                        <i class="fi fi-sr-megaphone-sound-waves"></i>
                        <span class="aside-mini-hidden-element text-truncate max-w-180"> {{ translate('Announcement') }} </span>
                    </a>
                </li>
            @endif

            @php($getEnabledThemeRoutes=0)
            @if (count(config('get_theme_routes')) > 0)
                @foreach (config('get_theme_routes')['route_list'] as $route)
                    @if(isset($route['module_permission']) && Helpers::module_permission_check($route['module_permission']))
                        @php($getEnabledThemeRoutes++)
                    @endif
                @endforeach
            @endif

            @if($getEnabledThemeRoutes > 0)
                @if (count(config('get_theme_routes')) > 0)
                    <li class="nav-item nav-item_title {{ (Request::is('admin/retail/banner*') || (Request::is('admin/retail/coupon*')) || (Request::is('admin/retail/notification*')) || (Request::is('admin/retail/deal*'))) ? 'scroll-here' : '' }}">
                        <small class="nav-subtitle" title="">
                            {{ config('get_theme_routes')['name'] }} {{ translate('Menu') }}
                        </small>
                    </li>
                    @foreach (config('get_theme_routes')['route_list'] as $route)
                        @if(isset($route['module_permission']) && Helpers::module_permission_check($route['module_permission']))
                            <li class="{{ (Request::is($route['path']) || Request::is($route['path'].'*')) ? 'active' : '' }} @foreach ($route['route_list'] as $sub_route){{ (Request::is($sub_route['path']) || Request::is($sub_route['path'].'*')) ? 'active' : '' }}@endforeach">
                                <a class="nav-link {{ count($route['route_list']) > 0 ? 'nav-link-toggle':'' }}"
                                   href="{{ count($route['route_list']) > 0 ? 'javascript:':$route['url'] }}"
                                   title="{{ translate($route['name']) }}">
                                    {!! $route['icon'] !!}
                                    <span
                                        class="aside-mini-hidden-element text-truncate">{{translate($route['name']) }}</span>
                                </a>

                                @if (count($route['route_list']) > 0)
                                    <ul class="aside-submenu navbar-nav">
                                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('System Settings') }}</li>
                                        @foreach ($route['route_list'] as $sub_route)
                                            <li class="{{ (Request::is($sub_route['path']) || Request::is($sub_route['path'].'*')) ? 'active' : '' }}">
                                                <a class="nav-link" href="{{$sub_route['url']}}"
                                                   title="{{ translate($sub_route['name']) }}">
                                                    <span
                                                        class="text-truncate">{{ translate($sub_route['name']) }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endif
                    @endforeach
                @endif
            @endif

            @if(Helpers::module_permission_check('people.support'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/people/support-ticket*') || Request::is('admin/people/contact*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="">{{ translate('Help & support') }}</small>
                </li>
                <li>
                    <a class="nav-link {{ Request::is('admin/people/messages*') ? 'active' : '' }}"
                       title="{{ translate('Inbox') }}"
                       href="{{ route('admin.people.messages.index', ['type' => 'customer']) }}">
                        <i class="fi fi-sr-envelope"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            {{ translate('Inbox') }}
                        </span>
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ Request::is('admin/people/contact*') ? 'active' : '' }}"
                       href="{{ route('admin.people.contact.list') }}" title="{{ translate('Messages') }}">
                        <i class="fi fi-sr-comment-alt-dots"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            <span class="position-relative">
                                {{ translate('Messages') }}
                                @if($sidebarUnseenMessages != 0)
                                    <span
                                        class="btn-status btn-xs-status btn-status-danger position-absolute top-0 menu-status"></span>
                                @endif
                            </span>
                        </span>
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ Request::is('admin/people/support-ticket*') ? 'active' : '' }}"
                       href="{{ route('admin.people.support-ticket.view') }}" title="{{ translate('Support ticket') }}">
                        <i class="fi fi-sr-headphones"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            <span class="position-relative">
                                {{ translate('Support ticket') }}
                            </span>
                        </span>
                    </a>
                </li>
            @endif

            @if(Helpers::module_permission_check('reports.finance', 'reports.operations', 'reports.tax'))
                <li class="nav-item nav-item_title
                {{ (Request::is('admin/report/*/earning') ||
                    Request::is('admin/report/*/inhouse-product-sale') ||
                    Request::is('admin/report/*/vendor-report') ||
                    Request::is('admin/report/*/earning') ||
                    Request::is('admin/report/*/transaction/list') ||
                    Request::is('admin/retail/refund-section/refund-list') ||
                    Request::is('admin/report/*/stock/product-in-wishlist') ||
                    Request::is('admin/people/*/reviews*') ||
                    Request::is('admin/report/*/stock/product-stock') ||
                    Request::is('admin/report/*/transaction/wallet-bonus') ||
                    Request::is('admin/report/*/order')) ? 'scroll-here' : ''
                    }}

                @if(getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                    @foreach(include(base_path("Modules/TaxModule/Addon/tax_report_routes.php")) as $route)
                        {{ strstr(Request::url(), $route['path']) ? 'scroll-here' : '' }}

                        @if(isset($route['sub_routes']))
                            @foreach($route['sub_routes'] as $subRoute)
                                {{ strstr(Request::url(), $subRoute['path']) ? 'scroll-here' : '' }}
                            @endforeach
                        @endif
                    @endforeach
                @endif
                ">
                    <small class="nav-subtitle" title="">
                        {{ translate('Reports & Analysis') }}
                    </small>
                </li>

                <li class="{{ (Request::is('admin/report/*/admin-earning') || Request::is('admin/report/*/vendor-earning') || Request::is('admin/report/*/inhouse-product-sale') || Request::is('admin/report/*/vendor-report') || Request::is('admin/report/*/earning') || Request::is('admin/report/*/transaction/order-transaction-list') || Request::is('admin/report/*/transaction/expense-transaction-list') || Request::is('admin/report/*/transaction/refund-transaction-list') || Request::is('admin/report/*/transaction/wallet-bonus')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/report/*/admin-earning') || Request::is('admin/report/*/vendor-earning') || Request::is('admin/report/*/inhouse-product-sale') || Request::is('admin/report/*/vendor-report') || Request::is('admin/report/*/earning') || Request::is('admin/report/*/transaction/order-transaction-list') || Request::is('admin/report/*/transaction/expense-transaction-list') || Request::is('admin/report/*/transaction/refund-transaction-list') || Request::is('admin/report/*/transaction/wallet-bonus')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Sales & Transaction Report') }}">
                        <i class="fi fi-sr-stats"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Sales & Transaction Report') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Sales & Transaction Report') }}</li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/report/*/admin-earning') || Request::is('admin/report/*/vendor-earning')) ? 'active' : '' }}"
                               href="{{ route(reportModuleRoute('admin-earning')) }}"
                               title="{{ translate('Earning Reports') }}">
                                <span class="text-truncate">
                                    {{ translate('Earning Reports') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/report/*/inhouse-product-sale') ? 'active' : '' }}"
                               href="{{ route(reportModuleRoute('inhouse-product-sale')) }}"
                               title="{{ translate('Inhouse Sales') }}">
                                <span class="text-truncate">
                                    {{ translate('Inhouse Sales') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/report/*/vendor-report') ? 'active' : '' }}"
                               href="{{ route(reportModuleRoute('vendor-report')) }}" title="{{ translate('Vendor Sales') }}">
                                <span class="text-truncate text-capitalize">
                                    {{ translate('Vendor Sales') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/report/*/transaction/order-transaction-list') || Request::is('admin/report/*/transaction/expense-transaction-list') || Request::is('admin/report/*/transaction/refund-transaction-list') || Request::is('admin/report/*/transaction/refund-transaction-list') || Request::is('admin/report/*/transaction/wallet-bonus')) ? 'active' : '' }}"
                               href="{{ route(reportModuleRoute('transaction.order-transaction-list')) }}"
                               title="{{ translate('Transaction Report') }}">
                                <span class="text-truncate">
                                    {{ translate('Transaction report') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a class="nav-link {{ (Request::is('admin/report/*/all-product') || Request::is('admin/report/*/stock/product-in-wishlist') || Request::is('admin/report/*/stock/product-stock')) ? 'active' : '' }}"
                       href="{{ route(reportModuleRoute('all-product')) }}" title="{{ translate('Product Report') }}">
                        <i class="fi fi-sr-stats"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            <span class="position-relative">
                                {{ translate('Product Report') }}
                            </span>
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/report/*/order') ? 'active' : '' }}"
                       href="{{ route(reportModuleRoute('order')) }}" title="{{ translate('Order Report') }}">
                        <i class="fi fi-sr-rectangle-list"></i>
                        <span class="aside-mini-hidden-element text-truncate">
                            {{ translate('Order Report') }}
                        </span>
                    </a>
                </li>

                @if(getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                    @foreach(include(base_path("Modules/TaxModule/Addon/tax_report_routes.php")) as $route)
                        <li>
                            <a class="nav-link
                            {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}

                            @if(isset($route['sub_routes']))
                                @foreach($route['sub_routes'] as $subRoute)
                                    {{ strstr(Request::url(), $subRoute['path']) ? 'active' : '' }}
                                @endforeach
                            @endif
                            "
                               href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                <i class="fi fi-sr-rectangle-list"></i>
                                <span class="aside-mini-hidden-element text-truncate">
                                     {{ translate($route['name']) }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                @endif
            @endif

            @if (Helpers::module_permission_check('retail.marketing'))
                @if(Route::has('admin.settings.content.blog.view'))
                    <li class="nav-item nav-item_title {{ Request::is('admin/settings/content/blog*') ? 'scroll-here' : '' }}">
                        <small class="nav-subtitle" title="">
                            {{ translate('Blog management') }}
                        </small>
                    </li>

                    <li class="{{ Request::is('admin/settings/content/blog/*') ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ Request::is('admin/settings/content/blog*') ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('Blog') }}">
                            <i class="fi fi-sr-layout-fluid"></i>
                            <span
                                class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">
                                    {{ translate('Blog') }}
                                </span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Blog') }}</li>
                            <li class="nav-item" title="{{ translate('Add New') }}">
                                <a class="nav-link {{ Request::is('admin/settings/content/blog/add') ? 'active' : '' }}"
                                   href="{{ route('admin.settings.content.blog.add') }}">
                                    <span class="text-truncate">{{ translate('Add New') }}</span>
                                </a>
                            </li>
                            <li class="nav-item "
                                title="{{ translate('List') }}">
                                <a class="nav-link {{ Request::is('admin/settings/content/blog/view') || Request::is('admin/settings/content/blog/app-download-setup') || Request::is('admin/settings/content/blog/priority-setup')  ? 'active' : '' }}"
                                   href="{{ route('admin.settings.content.blog.view') }}">
                                    <span class="text-truncate">{{ translate('List') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endif

            @if(Helpers::module_permission_check('people.customers', 'people.vendors', 'people.delivery_men'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/people/customer/list') || Request::is('admin/people/customer/view*') || Request::is('admin/people/customer/subscriber-list')||Request::is('admin/people/vendors/add') || Request::is('admin/people/vendors/list') || Request::is('admin/people/delivery-man*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="">{{ translate('User management') }}</small>
                </li>

                <li class="{{ (Request::is('admin/people/customer/wallet*') || Request::is('admin/people/customer/list') || Request::is('admin/people/customer/view*') || Request::is('admin/people/*/reviews*') || Request::is('admin/people/customer/loyalty/report')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/people/customer/wallet*') || Request::is('admin/people/customer/list') || Request::is('admin/people/customer/view*') || Request::is('admin/people/*/reviews*') || Request::is('admin/people/customer/loyalty/report')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Customers') }}">
                        <i class="fi fi-sr-user"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                               {{ translate('Customers') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Customers') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/customer/list') || Request::is('admin/people/customer/view*') ? 'active' : '' }}"
                               href="{{ route('admin.people.customer.list') }}" title="{{ translate('Customer List') }}">
                                <span class="text-truncate">{{ translate('Customer List') }} </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/*/reviews*') ? 'active' : '' }}"
                               href="{{ route('admin.people.reviews.list', ['business_module' => \App\Enums\BusinessModule::RETAIL]) }}" title="{{ translate('Customer Reviews') }}">
                                <span class="text-truncate">
                                    {{ translate('Customer Reviews') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/customer/wallet/report') ? 'active' : '' }}"
                               title="{{ translate('Wallet') }}" href="{{ route('admin.people.customer.wallet.report') }}">
                                <span class="text-truncate">
                                    {{ translate('Wallet') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/customer/wallet/bonus-setup') || Request::is('admin/people/customer/wallet/bonus-setup/edit/*')  ? 'active' : '' }}"
                               title="{{ translate('Wallet Bonus Setup') }}"
                               href="{{ route('admin.people.customer.wallet.bonus-setup') }}">
                                <span class="text-truncate">
                                    {{ translate('Wallet Bonus Setup') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/customer/loyalty/report') ? 'active' : '' }}"
                               title="{{ translate('Loyalty Points') }}"
                               href="{{ route('admin.people.customer.loyalty.report') }}">
                                <span class="text-truncate">
                                    {{ translate('Loyalty Points') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::is('admin/people/vendors*') || (Request::is('admin/retail/orders/details/*') && request()->has('vendor-order-list')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/people/vendors*') || (Request::is('admin/retail/orders/details/*') && request()->has('vendor-order-list')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Vendors') }}">
                        <i class="fi fi-sr-seller"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Vendors') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Vendors') }}</li>
                        <li class="nav-item ">
                            <a class="nav-link {{ Request::is('admin/people/vendors/add') ? 'active' : '' }}"
                               title="{{ translate('Add New Vendor') }}"
                               href="{{ route('admin.people.vendors.add') }}">
                                <span class="text-truncate">
                                    {{ translate('Add New Vendor') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/vendors/list') ||Request::is('admin/people/vendors/view*') ? 'active' : '' }}"
                               title="{{ translate('Vendor List') }}" href="{{ route('admin.people.vendors.vendor-list') }}">
                                <span class="text-truncate">
                                    {{ translate('Vendor List') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/vendors/withdraw-list')|| Request::is('admin/people/vendors/withdraw-view/*') ? 'active' : '' }}"
                               href="{{ route('admin.people.vendors.withdraw-list') }}" title="{{ translate('Withdraws') }}">
                                <span class="text-truncate">{{ translate('Withdraws') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::is('admin/people/delivery-man*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle text-capitalize {{ Request::is('admin/people/delivery-man*') ? 'active' : '' }}"
                       href="javascript:"
                       title="{{ translate('Delivery Men') }}">
                        <i class="fi fi-sr-person-carry-box"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Delivery Men') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Delivery Men') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/delivery-man/add') ? 'active' : '' }}"
                               href="{{ route('admin.people.delivery-man.add') }}" title="{{ translate('Add New') }}">
                                <span class="text-truncate">{{ translate('Add New') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/delivery-man/list') || Request::is('admin/people/delivery-man/update*')  || Request::is('admin/people/delivery-man/order-history-log*') || Request::is('admin/people/delivery-man/order-wise-earning*') ? 'active' : '' }}"
                               href="{{ route('admin.people.delivery-man.list') }}"
                               title="{{ translate('List') }}">
                                <span class="text-truncate">{{ translate('List') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/people/delivery-man/withdraw-list') || Request::is('admin/people/delivery-man/withdraw-view*') ? 'active' : '' }}"
                               href="{{ route('admin.people.delivery-man.withdraw-list') }}"
                               title="{{ translate('Withdraws') }}">
                                <span class="text-truncate">{{ translate('Withdraws') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link  {{ Request::is('admin/people/delivery-man/emergency-contact') ? 'active' : '' }}"
                               href="{{ route('admin.people.delivery-man.emergency-contact.index') }}"
                               title="{{ translate('Emergency Contact') }}">
                                <span class="text-truncate">{{ translate('Emergency Contact') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                @if(auth('admin')->user()->admin_role_id==1)
                    <li class=" {{ (Request::is('admin/people/employee*') || Request::is('admin/people/custom-role*')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/people/employee*') || Request::is('admin/people/custom-role*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('Employees') }}">
                            <i class="fi fi-sr-employee-man-alt"></i>
                            <span
                                class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">
                                    {{ translate('Employees') }}
                                </span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Employees') }}</li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/people/custom-role*') ? 'active' : '' }}"
                                   href="{{ route('admin.people.custom-role.list') }}"
                                   title="{{ translate('Employee Role Setup') }}">
                                    <span class="text-truncate">{{ translate('Employee Role Setup') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ (Request::is('admin/people/employee/list') || Request::is('admin/people/employee/add') || Request::is('admin/people/employee/update*')) ? 'active' : '' }}"
                                   href="{{ route('admin.people.employee.list') }}" title="{{ translate('Employees') }}">
                                    <span class="text-truncate">{{ translate('Employees') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                <li>
                    <a class="nav-link {{ Request::is('admin/people/customer/subscriber-list') ? 'active' : '' }}"
                       href="{{ route('admin.people.customer.subscriber-list') }}" title="{{ translate('Subscribers') }}">
                        <i class="fi fi-sr-user"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Subscribers') }}
                        </span>
                    </a>
                </li>
            @endif

            @if(Helpers::module_permission_check('settings.business'))
                <li class="nav-item nav-item_title">
                    <small class="nav-subtitle" title="">
                        {{ translate('Business Settings') }}
                    </small>
                </li>

                <li>
                    <a class="nav-link {{
                                (Request::is('admin/settings/business-settings/web-config') ||
                                Request::is('admin/settings/business-settings/web-config/refund-setup') ||
                                Request::is('admin/settings/business-settings/website-setup') ||
                                Request::is('admin/product-settings')||
                                Request::is('admin/settings/business-settings/payment-method/payment-option') ||
                                Request::is('admin/settings/business-settings/vendor-settings') ||
                                Request::is('admin/people/customer/customer-settings') ||
                                Request::is('admin/settings/business-settings/delivery-man-settings') ||
                                Request::is('admin/settings/business-settings/shipping-method/update'.'*') ||
                                Request::is('admin/settings/business-settings/shipping-method/index') ||
                                Request::is('admin/settings/business-settings/order-settings/index') ||
                                Request::is('admin/settings/business-settings/invoice-settings') ||
                                Request::is('admin/settings/business-settings/delivery-zone')) ? 'active' : '' }}"
                       href="{{ route('admin.settings.business-settings.web-config.index') }}"
                       title="{{ translate('Business Setup') }}">
                        <i class="fi fi-sr-settings"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Business Setup') }}
                        </span>
                    </a>
                </li>


                @if(getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                    <li>
                        <a class="nav-link nav-link-toggle
                        @foreach(include(base_path("Modules/TaxModule/Addon/tax_routes.php")) as $route)
                            {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}
                        @endforeach" href="javascript:" title="{{ translate('System Vat/Tax') }}">
                            <i class="fi fi-rr-calculator-math-tax"></i>
                            <span
                                class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('System Vat/Tax') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">
                                {{ translate('System Vat/Tax') }}
                            </li>

                            @foreach(include(base_path("Modules/TaxModule/Addon/tax_routes.php")) as $route)
                                <li>
                                    <a class="nav-link {{strstr(Request::url(), $route['path']) ? 'active' : '' }}"
                                       href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                    <span class="text-truncate">
                                        {{ translate($route['name']) }}
                                    </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/business-settings/inhouse-shop') ? 'active' : '' }}"
                       href="{{ route('admin.settings.business-settings.inhouse-shop') }}"
                       title="{{ translate('In-house Shop') }}">
                        <i class="fi fi-sr-shop"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('In-house Shop') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{
                            (Request::is('admin/settings/seo-settings/web-master-tool') ||
                            Request::is('admin/settings/seo-settings/robot-txt') ||
                            Request::is('admin/settings/seo-settings/sitemap') ||
                            Request::is('admin/settings/seo-settings/robots-meta-content*') ||
                            Request::is('admin/settings/seo-settings/error-logs/index')) ? 'active' : ''
                        }}"
                       href="{{ route('admin.settings.seo-settings.web-master-tool') }}" title="{{ translate('SEO Settings') }}">
                        <i class="fi fi-sr-analyse"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('SEO Settings') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/business-settings/priority-setup') ? 'active' : '' }}"
                       href="{{ route('admin.settings.business-settings.priority-setup.index') }}"
                       title="{{ translate('Priority Setup') }}">
                        <i class="fi fi-sr-list-timeline"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Priority Setup') }}
                        </span>
                    </a>
                </li>

                <li class="{{ Request::is('admin/settings/pages-and-media*') || Request::is('admin/settings/pages-and-media/social-media') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/settings/pages-and-media*') || Request::is('admin/settings/pages-and-media/social-media') || Request::is('admin/settings/helpTopic/*') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Pages & Media') }}">
                        <i class="fi fi-sr-document"></i>
                        <span
                            class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Pages & Media') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Pages & Media') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{(
                            Request::is('admin/settings/pages-and-media/list') ||
                            Request::is('admin/settings/pages-and-media/page*') ||
                            Request::is('admin/settings/pages-and-media/privacy-policy') ||
                            Request::is('admin/settings/pages-and-media/about-us') ||
                            Request::is('admin/settings/helpTopic/index') ||
                            Request::is('admin/settings/pages-and-media/features-section') ||
                            Request::is('admin/settings/pages-and-media/company-reliability')) ? 'active' : '' }}"
                               href="{{ route('admin.settings.pages-and-media.list') }}"
                               title="{{ translate('Business Pages') }}">
                                <span class="text-truncate">
                                    {{ translate('Business Pages') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/social-media') ? 'active' : '' }}"
                               href="{{ route('admin.settings.pages-and-media.social-media') }}"
                               title="{{ translate('Social Media Links') }}">
                                <span class="text-truncate">
                                    {{ translate('Social Media Links') }}
                                </span>
                            </a>
                        </li>


                        <li>
                            <a class="nav-link {{ Request::is('admin/settings/pages-and-media/vendor-registration-settings/*') ? 'active' : '' }}"
                               href="{{ route('admin.settings.pages-and-media.vendor-registration-settings.index') }}"
                               title="{{ translate('Vendor Registration') }}">
                                <span class="text-truncate">
                                    {{ translate('Vendor Registration') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(Helpers::module_permission_check('settings.system'))
                <li class="nav-item nav-item_title">
                    <small class="nav-subtitle" title="">
                        {{ translate('System Settings') }}
                    </small>
                </li>

                <li>
                    <a class="nav-link
                {{
                    (Request::is('admin/settings/system-setup/environment-setup') ||
                    Request::is('admin/settings/system-setup/app-settings') ||
                    Request::is('admin/settings/system-setup/sitemap') ||
                    Request::is('admin/settings/system-setup/currency/view') ||
                    Request::is('admin/settings/system-setup/web-config/db-index') ||
                    Request::is('admin/settings/system-setup/language*') ||
                    Request::is('admin/settings/system-setup/software-update') ||
                    Request::is('admin/settings/system-setup/web-config/app-settings') ||
                    Request::is('admin/settings/system-setup/invoice-settings/') ||
                    Request::is('admin/settings/business-settings/delivery-zone')) ||
                    Request::is('admin/settings/system-setup/db-index') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.environment-setup') }}"
                       title="{{ translate('System Setup') }}">
                        <i class="fi fi-sr-customize"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('System Setup') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link
                {{
                    Request::is('admin/settings/system-setup/login-settings/login-url-setup')  ||
                    Request::is('admin/settings/system-setup/login-settings/customer-login-setup') ||
                    Request::is('admin/settings/system-setup/login-settings/otp-setup') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.login-settings.customer-login-setup') }}"
                       title="{{ translate('Login Settings') }}">
                        <i class="fi fi-sr-user-skill-gear"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                        {{ translate('Login Settings') }}
                    </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/system-setup/email-templates/*') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.email-templates.view', ['admin', EmailTemplateKey::ADMIN_EMAIL_LIST[0]]) }}"
                       title="{{ translate('Email Template') }}">
                        <i class="fi fi-sr-template"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Email Template') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/system-setup/file-manager*') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.file-manager.index') }}" title="{{ translate('Gallery') }}">
                        <i class="fi fi-sr-copy-image"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Gallery') }}
                        </span>
                    </a>
                </li>
            @endif

            @if(Helpers::module_permission_check('settings.third_party'))
                <li class="nav-item nav-item_title">
                    <small class="nav-subtitle" title="">
                        {{ translate('3rd Party Setup') }}
                    </small>
                </li>

                <li>
                    <a class="nav-link
                {{ Request::is('admin/settings/third-party/payment-method') ||
                Request::is('admin/settings/third-party/offline-payment-method/index') ||
                Request::is('admin/settings/third-party/offline-payment-method*') ? 'active' : '' }}"
                       href="{{ route('admin.settings.third-party.payment-method.index') }}"
                       title="{{ translate('Payment Methods') }}">
                        <i class="fi fi-sr-credit-card"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Payment Methods') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link
                {{ Request::is('admin/settings/third-party/firebase-configuration/setup') ||
                    Request::is('admin/settings/third-party/firebase-configuration/authentication') ? 'active' : '' }}"
                       href="{{ route('admin.settings.third-party.firebase-configuration.setup') }}"
                       title="{{ translate('Firebase') }}">
                        <i class="fi fi-sr-database"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Firebase') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/third-party/analytics-index') ? 'active' : '' }}"
                       href="{{ route('admin.settings.third-party.analytics-index') }}"
                       title="{{ translate('Marketing Tools') }}">
                        <i class="fi fi-sr-tools"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Marketing Tools') }}
                        </span>
                    </a>
                </li>

                @if(getCheckAddonPublishedStatus(moduleName: 'AI'))
                    <li>
                        <a class="nav-link nav-link-toggle {{
                                Request::is('admin/settings/third-party/ai-setting') || Request::is('admin/settings/third-party/ai-setting/vendors-usage-limits') || Request::is('admin/settings/third-party/ai-setting/customers-usage-limits') ? 'active' : '' }}"
                           href="{{ route('admin.settings.third-party.ai-setting.index') }}" title="{{ translate('AI Setup') }}">
                            <i class="fi fi-rr-microchip-ai"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between
                            align-items-center">
                                <span class="text-truncate max-w-180">
                                    {{ translate('AI Setup') }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endif

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/third-party/mail') ||
                            Request::is('admin/settings/third-party/sms-module') ||
                            Request::is('admin/settings/third-party/recaptcha') ||
                            Request::is('admin/settings/third-party/social-login/view') ||
                            Request::is('admin/settings/third-party/social-media-chat/view') ||
                            Request::is('admin/settings/third-party/storage-connection-settings/index') ||
                            Request::is('admin/settings/third-party/map-api') ? 'active'  :'' }}"
                       href="{{ route('admin.settings.third-party.social-login.view') }}"
                       title="{{ translate('Other Configuration') }}">
                        <i class="fi fi-sr-workflow-setting-alt"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Other Configuration') }}
                        </span>
                    </a>
                </li>
            @endif

            @if(Helpers::module_permission_check('settings.themes'))
                <li class="nav-item nav-item_title">
                    <small class="nav-subtitle" title="">
                        {{ translate('Themes & Addons') }}
                    </small>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/system-setup/theme/setup') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.theme.setup') }}" title="{{ translate('Theme Setup') }}">
                        <i class="fi fi-sr-palette"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Theme Setup') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/system-setup/addon') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.addon.index') }}" title="{{ translate('System Addons') }}">
                        <i class="fi fi-sr-book-plus"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('System Addons') }}
                        </span>
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ Request::is('admin/settings/system-setup/addon-activation') ? 'active' : '' }}"
                       href="{{ route('admin.settings.system-setup.addon-activation.index') }}" title="{{ translate('Addon Activation') }}">
                        <i class="fi fi-rr-customize"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Addon Activation') }}
                        </span>
                    </a>
                </li>

                @if(count(config('addon_admin_routes'))>0)
                    <li>
                        <a class="nav-link nav-link-toggle @foreach(config('addon_admin_routes') as $routes)
                            @foreach($routes as $route)
                                {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}
                            @endforeach
                        @endforeach" href="javascript:" title="{{ translate('Addon Menus') }}">
                            <i class="fi fi-rr-home"></i>
                            <span
                                class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">
                                {{ translate('Addon Menus') }}
                            </span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">
                                {{ translate('Addon Menus') }}
                            </li>

                            @foreach(config('addon_admin_routes') as $routes)
                                @foreach($routes as $route)
                                    <li>
                                        <a class="nav-link {{strstr(Request::url(), $route['path']) ? 'active' : '' }}"
                                           href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                            <span class="text-truncate">
                                                {{ translate($route['name']) }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </li>
                @endif

            @endif

            <?php $checkSetupGuideRequirements = checkSetupGuideRequirements(panel: 'admin'); ?>

            <li class="nav-item {{ $checkSetupGuideRequirements['completePercent'] < 100 ? 'pt-5 mt-5 d-none d-lg-block' : '' }}">
                <div class="pt-4"></div>
            </li>
        </ul>
    </div>
</aside>

@include("layouts.admin.partials._setup-guide")

<div class="offcanvas offcanvas-start bg-panel d-lg-none w-280" tabindex="-1" id="offcanvasAside"
     aria-labelledby="offcanvasAsideLabel">
    <div class="offcanvas-header d-flex align-items-center gap-2 justify-content-between">
        <a class="navbar-logo" href="{{ businessModuleDashboardUrl() }}">
            <img height="24" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                 alt="{{ translate('Logo') }}">
        </a>

        <button type="button" class="bg-transparent p-0 text-white border-0" data-bs-dismiss="offcanvas"
                aria-label="Close">
            <i class="fi fi-rr-cross"></i>
        </button>
    </div>

    <div class="offcanvas-body js-offcanvas-body pt-0">

    </div>
</div>
