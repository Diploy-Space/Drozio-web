@php
    use App\Utils\Helpers;
@endphp

@if (businessModuleSectionVisible('home'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'home' ? 'v2-is-on' : '' }}" data-section="home">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Home') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Overview') }}</span></div>
            @if (Helpers::module_permission_check('wholesale.dashboard'))
                <a class="v2-nav-item {{ Request::is('admin/*/dashboard') ? 'v2-is-active' : '' }}" data-item="dashboard" href="{{ businessModuleDashboardUrl() }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Dashboard') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dashboard" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('wholesale.orders') && businessModuleSectionVisible('orders'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'orders' ? 'v2-is-on' : '' }}" data-section="orders">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Orders') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('orders.wholesale'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Sales') }}</span></div>
                <div class="v2-nav-item v2-has-children {{ Request::is('admin/wholesale/orders*') ? 'v2-is-active' : '' }}" data-item="wholesale-orders">
                    <a class="v2-nav-btn" href="{{ route('admin.wholesale.orders.list', ['all']) }}">
                        <span class="v2-nav-label">{{ translate('Orders') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        @if (($sidebarWholesaleOrderCounts['pending'] ?? 0) > 0)
                            <span data-v2-tag="primary">{{ $sidebarWholesaleOrderCounts['pending'] }}</span>
                        @endif
                        <button class="v2-pin-btn" type="button" data-pin="wholesale-orders" aria-label="Pin"></button>
                        <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="wholesale-orders">
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/all') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['all']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('All') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarWholesaleTotalOrders ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/pending') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['pending']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarWholesaleOrderCounts['pending'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/confirmed') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['confirmed']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Confirmed') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarWholesaleOrderCounts['confirmed'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/processing') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['processing']) }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Packaging') }}</span>
                        <span class="v2-nav-child-count" data-tone="warn">{{ $sidebarWholesaleOrderCounts['processing'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/out_for_delivery') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['out_for_delivery']) }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Out For Delivery') }}</span>
                        <span class="v2-nav-child-count" data-tone="warn">{{ $sidebarWholesaleOrderCounts['out_for_delivery'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/delivered') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['delivered']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Delivered') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarWholesaleOrderCounts['delivered'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/returned') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['returned']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Returned') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarWholesaleOrderCounts['returned'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/failed') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['failed']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Failed to deliver') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarWholesaleOrderCounts['failed'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/orders/list/canceled') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.orders.list', ['canceled']) }}">
                        <span class="v2-nav-child-dot" data-tone="neutral"></span>
                        <span class="v2-nav-child-label">{{ translate('Canceled') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarWholesaleOrderCounts['canceled'] ?? 0 }}</span>
                    </a>
                </div>

                <div class="v2-nav-item v2-has-children {{ Request::is('admin/wholesale/refund-section*') ? 'v2-is-active' : '' }}" data-item="wholesale-refunds">
                    <a class="v2-nav-btn" href="{{ route('admin.wholesale.refund-section.refund.list', ['pending']) }}">
                        <span class="v2-nav-label">{{ translate('Refund Requests') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        @if (($sidebarWholesaleRefundCounts['pending'] ?? 0) > 0)
                            <span data-v2-tag="warn">{{ $sidebarWholesaleRefundCounts['pending'] }}</span>
                        @endif
                        <button class="v2-pin-btn" type="button" data-pin="wholesale-refunds" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="wholesale-refunds">
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/refund-section/refund/list/pending') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.refund-section.refund.list', ['pending']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarWholesaleRefundCounts['pending'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/refund-section/refund/list/approved') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.refund-section.refund.list', ['approved']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Approved') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarWholesaleRefundCounts['approved'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/refund-section/refund/list/refunded') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.refund-section.refund.list', ['refunded']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Refunded') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarWholesaleRefundCounts['refunded'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/refund-section/refund/list/rejected') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.refund-section.refund.list', ['rejected']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Rejected') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarWholesaleRefundCounts['rejected'] ?? 0 }}</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
@endif

@if (Helpers::module_permission_check('wholesale.catalog') && businessModuleSectionVisible('catalog'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'catalog' ? 'v2-is-on' : '' }}" data-section="catalog">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Catalog') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('catalog.wholesale'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('In-house Products') }}</span></div>
                <a class="v2-nav-item {{ Request::is('admin/wholesale/products/list') ? 'v2-is-active' : '' }}" data-item="wholesale-product-list" href="{{ route('admin.wholesale.products.list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product List') }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="success">{{ $sidebarWholesaleProductCounts['in_house'] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="wholesale-product-list" aria-label="Pin"></button>
                    </div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/wholesale/products/add') ? 'v2-is-active' : '' }}" data-item="wholesale-add-product" href="{{ route('admin.wholesale.products.add') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Product') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-add-product" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/wholesale/products/stock-limit-list') ? 'v2-is-active' : '' }}" data-item="wholesale-limited-stock" href="{{ route('admin.wholesale.products.stock-limit-list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Limited Stock') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-limited-stock" aria-label="Pin"></button></div>
                </a>
            </div>

            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Vendor Products') }}</span></div>
                @foreach ([
                    ['status' => 'new', 'label' => translate('New product requests'), 'tone' => 'danger'],
                    ['status' => 'update', 'label' => translate('Product update requests'), 'tone' => 'info'],
                    ['status' => 'approved', 'label' => translate('Approved products'), 'tone' => 'success'],
                    ['status' => 'denied', 'label' => translate('Denied products'), 'tone' => 'danger'],
                ] as $v2WsVendorItem)
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/products/vendor/' . $v2WsVendorItem['status']) ? 'v2-is-active' : '' }}" data-item="wholesale-vendor-{{ $v2WsVendorItem['status'] }}" href="{{ route('admin.wholesale.products.vendor-list', [$v2WsVendorItem['status']]) }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ $v2WsVendorItem['label'] }}</span></span>
                        <div class="v2-nav-right">
                            @if (($sidebarWholesaleProductCounts[$v2WsVendorItem['status']] ?? 0) > 0)
                                <span data-v2-tag="{{ $v2WsVendorItem['tone'] }}">{{ $sidebarWholesaleProductCounts[$v2WsVendorItem['status']] }}</span>
                            @endif
                            <button class="v2-pin-btn" type="button" data-pin="wholesale-vendor-{{ $v2WsVendorItem['status'] }}" aria-label="Pin"></button>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if (businessModuleItemVisible('catalog.organization_wholesale'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Organization') }}</span></div>
                @if (businessModuleItemVisible('catalog.category_wholesale') && Route::has('admin.wholesale.category.view'))
                    <div class="v2-nav-item v2-has-children {{ (Request::is('admin/wholesale/category/*') || Request::is('admin/wholesale/sub-category/*') || Request::is('admin/wholesale/sub-sub-category/*')) ? 'v2-is-active' : '' }}" data-item="category-setup-wholesale">
                        <a class="v2-nav-btn" href="{{ route('admin.wholesale.category.view') }}">
                            <span class="v2-nav-label">{{ translate('Category Setup') }}</span>
                        </a>
                        <div class="v2-nav-right">
                            <button class="v2-pin-btn" type="button" data-pin="category-setup-wholesale" aria-label="Pin"></button>
                            <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                                <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                            </span>
                        </div>
                    </div>
                    <div class="v2-nav-children v2-is-collapsed" data-children-for="category-setup-wholesale">
                        <a class="v2-nav-child {{ Request::is('admin/wholesale/category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Categories') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/wholesale/sub-category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.sub-category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Sub Categories') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/wholesale/sub-sub-category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.sub-sub-category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Sub Sub Categories') }}</span>
                        </a>
                    </div>
                @endif
                @if (businessModuleItemVisible('catalog.brand_wholesale') && Route::has('admin.wholesale.brand.list'))
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/brand*') ? 'v2-is-active' : '' }}" data-item="brands-wholesale" href="{{ route('admin.wholesale.brand.list') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Brand Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="brands-wholesale" aria-label="Pin"></button></div>
                    </a>
                @endif
                @if (businessModuleItemVisible('catalog.attribute_wholesale') && Route::has('admin.wholesale.attribute.view'))
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/attribute*') ? 'v2-is-active' : '' }}" data-item="attributes-wholesale" href="{{ route('admin.wholesale.attribute.view') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Attribute Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="attributes-wholesale" aria-label="Pin"></button></div>
                    </a>
                @endif
                @if (businessModuleItemVisible('catalog.product_gallery_wholesale') && Route::has('admin.wholesale.products.product-gallery'))
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/products/product-gallery') ? 'v2-is-active' : '' }}" data-item="product-gallery-wholesale" href="{{ route('admin.wholesale.products.product-gallery') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Gallery') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="product-gallery-wholesale" aria-label="Pin"></button></div>
                    </a>
                @endif
            </div>
        @endif
    </div>
@endif

@if (Helpers::module_permission_check('wholesale.marketing') && businessModuleSectionVisible('marketing'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'marketing' ? 'v2-is-on' : '' }}" data-section="marketing">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('marketing.promotions_wholesale'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
                <a class="v2-nav-item {{ Request::is('admin/wholesale/banner*') ? 'v2-is-active' : '' }}" data-item="wholesale-banners" href="{{ route('admin.wholesale.banner.list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Banner Setup') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-banners" aria-label="Pin"></button></div>
                </a>

                <div class="v2-nav-item v2-has-children {{ (Request::is('admin/wholesale/coupon*') || Request::is('admin/wholesale/campaign*')) ? 'v2-is-active' : '' }}" data-item="wholesale-offers">
                    <a class="v2-nav-btn" href="{{ route('admin.wholesale.coupon.add') }}">
                        <span class="v2-nav-label">{{ translate('Offers') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="wholesale-offers" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="wholesale-offers">
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/coupon*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.coupon.add') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Coupon') }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/wholesale/campaign*') ? 'v2-is-on' : '' }}" href="{{ route('admin.wholesale.campaign.index') }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Campaign') }}</span>
                    </a>
                </div>
            </div>

            @if (businessModuleItemVisible('marketing.communication_wholesale') && Route::has('admin.wholesale.notification.index'))
                <div class="v2-ctx-group">
                    <div class="v2-ctx-group-head"><span>{{ translate('Communication') }}</span></div>
                    <a class="v2-nav-item {{ Request::is('admin/wholesale/notification*') ? 'v2-is-active' : '' }}" data-item="wholesale-send-notification" href="{{ route('admin.wholesale.notification.index') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Send notification') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-send-notification" aria-label="Pin"></button></div>
                    </a>
                    @if (Route::has('admin.wholesale.announcement'))
                        <a class="v2-nav-item {{ Request::is('admin/wholesale/announcement*') ? 'v2-is-active' : '' }}" data-item="wholesale-announcement" href="{{ route('admin.wholesale.announcement') }}">
                            <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Announcement') }}</span></span>
                            <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="wholesale-announcement" aria-label="Pin"></button></div>
                        </a>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endif
