@php use App\Utils\Helpers; @endphp

@if (businessModuleSectionVisible('home'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'home' ? 'v2-is-on' : '' }}" data-section="home">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Home') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Overview') }}</span></div>
            @if (Helpers::module_permission_check('retail.dashboard'))
                <a class="v2-nav-item {{ Request::is('admin/*/dashboard') ? 'v2-is-active' : '' }}" data-item="dashboard" href="{{ businessModuleDashboardUrl() }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Dashboard') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dashboard" aria-label="Pin"></button></div>
                </a>
            @endif
            @if (Helpers::module_permission_check('retail.pos') && businessModuleItemVisible('pos'))
                <a class="v2-nav-item {{ Request::is('admin/retail/pos*') ? 'v2-is-active' : '' }}" data-item="pos" href="{{ route('admin.retail.pos.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('POS') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="pos" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif

@if (Helpers::module_permission_check('retail.orders') && businessModuleSectionVisible('orders'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'orders' ? 'v2-is-on' : '' }}" data-section="orders">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Orders') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('orders.retail'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Sales') }}</span></div>

                <div class="v2-nav-item v2-has-children {{ Request::is('admin/retail/orders/*') ? 'v2-is-active' : '' }}" data-item="orders-all">
                    <a class="v2-nav-btn" href="{{ route('admin.retail.orders.list', ['all']) }}">
                        <span class="v2-nav-label">{{ translate('Orders') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        @if (($sidebarOrderCounts['pending'] ?? 0) > 0)
                            <span data-v2-tag="primary">{{ $sidebarOrderCounts['pending'] }}</span>
                        @endif
                        <button class="v2-pin-btn" type="button" data-pin="orders-all" aria-label="Pin"></button>
                        <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="orders-all">
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/all') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['all']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('All') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarTotalOrders ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/pending') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['pending']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarOrderCounts['pending'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/confirmed') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['confirmed']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Confirmed') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarOrderCounts['confirmed'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/processing') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['processing']) }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Packaging') }}</span>
                        <span class="v2-nav-child-count" data-tone="warn">{{ $sidebarOrderCounts['processing'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/out_for_delivery') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['out_for_delivery']) }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Out For Delivery') }}</span>
                        <span class="v2-nav-child-count" data-tone="warn">{{ $sidebarOrderCounts['out_for_delivery'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/delivered') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['delivered']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Delivered') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarOrderCounts['delivered'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/returned') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['returned']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Returned') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarOrderCounts['returned'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/failed') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['failed']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Failed to deliver') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarOrderCounts['failed'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/orders/list/canceled') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.orders.list', ['canceled']) }}">
                        <span class="v2-nav-child-dot" data-tone="neutral"></span>
                        <span class="v2-nav-child-label">{{ translate('Canceled') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarOrderCounts['canceled'] ?? 0 }}</span>
                    </a>
                </div>

                <div class="v2-nav-item v2-has-children {{ Request::is('admin/retail/refund-section/refund/*') ? 'v2-is-active' : '' }}" data-item="refunds">
                    <a class="v2-nav-btn" href="{{ route('admin.retail.refund-section.refund.list', ['pending']) }}">
                        <span class="v2-nav-label">{{ translate('Refund Requests') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        @if (($sidebarRefundCounts['pending'] ?? 0) > 0)
                            <span data-v2-tag="warn">{{ $sidebarRefundCounts['pending'] }}</span>
                        @endif
                        <button class="v2-pin-btn" type="button" data-pin="refunds" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="refunds">
                    <a class="v2-nav-child {{ Request::is('admin/retail/refund-section/refund/list/pending') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.refund-section.refund.list', ['pending']) }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                        <span class="v2-nav-child-count" data-tone="primary">{{ $sidebarRefundCounts['pending'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/refund-section/refund/list/approved') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.refund-section.refund.list', ['approved']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Approved') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarRefundCounts['approved'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/refund-section/refund/list/refunded') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.refund-section.refund.list', ['refunded']) }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Refunded') }}</span>
                        <span class="v2-nav-child-count" data-tone="success">{{ $sidebarRefundCounts['refunded'] ?? 0 }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/refund-section/refund/list/rejected') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.refund-section.refund.list', ['rejected']) }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Rejected') }}</span>
                        <span class="v2-nav-child-count" data-tone="danger">{{ $sidebarRefundCounts['rejected'] ?? 0 }}</span>
                    </a>
                </div>
            </div>
        @endif
    </div>
@endif

@if (Helpers::module_permission_check('retail.catalog') && businessModuleSectionVisible('catalog'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'catalog' ? 'v2-is-on' : '' }}" data-section="catalog">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Catalog') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('catalog.in_house'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('In-house Products') }}</span></div>
                <a class="v2-nav-item {{ (Request::is('admin/retail/products/list/in-house*') || Request::is('admin/retail/products/view/in-house/*') || Request::is('admin/retail/products/barcode/*') || Request::is('admin/retail/products/update/*')) ? 'v2-is-active' : '' }}" data-item="inhouse-products" href="{{ route('admin.retail.products.list', ['in-house']) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product List') }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="success">{{ $sidebarRetailProductCounts['in_house'] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="inhouse-products" aria-label="Pin"></button>
                    </div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/retail/products/add') ? 'v2-is-active' : '' }}" data-item="add-product" href="{{ route('admin.retail.products.add') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Product') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="add-product" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/retail/products/stock-limit-list') ? 'v2-is-active' : '' }}" data-item="limited-stock" href="{{ route('admin.retail.products.stock-limit-list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Limited Stock') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="limited-stock" aria-label="Pin"></button></div>
                </a>
                @if (businessModuleItemVisible('catalog.request_restock'))
                    <a class="v2-nav-item {{ Request::is('admin/retail/products/request-restock-list') ? 'v2-is-active' : '' }}" data-item="restock-requests" href="{{ route('admin.retail.products.request-restock-list') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Request Restock List') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="restock-requests" aria-label="Pin"></button></div>
                    </a>
                @endif
                @if (businessModuleItemVisible('catalog.bulk_import'))
                    <a class="v2-nav-item {{ Request::is('admin/retail/products/bulk-import') ? 'v2-is-active' : '' }}" data-item="bulk-import" href="{{ route('admin.retail.products.bulk-import') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Bulk Import') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="bulk-import" aria-label="Pin"></button></div>
                    </a>
                @endif
            </div>
        @endif

        @if (businessModuleItemVisible('catalog.vendor_products'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Vendor Products') }}</span></div>
                <a class="v2-nav-item {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'admin/retail/products/list/vendor?request_status=0') ? 'v2-is-active' : '' }}" data-item="vendor-new-products" href="{{ route('admin.retail.products.list', ['vendor', 'request_status' => '0']) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('New Products Requests') }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="danger">{{ $sidebarRetailProductCounts['new'] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="vendor-new-products" aria-label="Pin"></button>
                    </div>
                </a>
                @if (getWebConfig(name: 'product_wise_shipping_cost_approval') == 1)
                    <a class="v2-nav-item {{ Request::is('admin/retail/products/updated-product-list/vendor') ? 'v2-is-active' : '' }}" data-item="vendor-updates" href="{{ route('admin.retail.products.updated-product-list', ['type' => 'vendor']) }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product update requests') }}</span></span>
                        <div class="v2-nav-right">
                            <span data-v2-tag="info">{{ $sidebarRetailProductCounts['update'] ?? 0 }}</span>
                            <button class="v2-pin-btn" type="button" data-pin="vendor-updates" aria-label="Pin"></button>
                        </div>
                    </a>
                @endif
                <a class="v2-nav-item {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/retail/products/list/vendor?request_status=1') ? 'v2-is-active' : '' }}" data-item="vendor-approved" href="{{ route('admin.retail.products.list', ['vendor', 'request_status' => '1']) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Approved Products') }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="success">{{ $sidebarRetailProductCounts['approved'] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="vendor-approved" aria-label="Pin"></button>
                    </div>
                </a>
                <a class="v2-nav-item {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/retail/products/list/vendor?request_status=2') ? 'v2-is-active' : '' }}" data-item="vendor-denied" href="{{ route('admin.retail.products.list', ['vendor', 'request_status' => '2']) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Denied Products') }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="danger">{{ $sidebarRetailProductCounts['denied'] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="vendor-denied" aria-label="Pin"></button>
                    </div>
                </a>
            </div>
        @endif

        @if (businessModuleItemVisible('catalog.organization'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Organization') }}</span></div>
                @if (businessModuleItemVisible('catalog.category_retail'))
                    <div class="v2-nav-item v2-has-children {{ (Request::is('admin/retail/category/*') || Request::is('admin/retail/sub-category/*') || Request::is('admin/retail/sub-sub-category/*')) ? 'v2-is-active' : '' }}" data-item="category-setup">
                        <a class="v2-nav-btn" href="{{ route('admin.retail.category.view') }}">
                            <span class="v2-nav-label">{{ translate('Category Setup') }}</span>
                        </a>
                        <div class="v2-nav-right">
                            <button class="v2-pin-btn" type="button" data-pin="category-setup" aria-label="Pin"></button>
                            <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                                <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                            </span>
                        </div>
                    </div>
                    <div class="v2-nav-children v2-is-collapsed" data-children-for="category-setup">
                        <a class="v2-nav-child {{ Request::is('admin/retail/category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Categories') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/retail/sub-category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.sub-category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Sub Categories') }}</span>
                        </a>
                        <a class="v2-nav-child {{ Request::is('admin/retail/sub-sub-category/*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.sub-sub-category.view') }}">
                            <span class="v2-nav-child-dot" data-tone="primary"></span>
                            <span class="v2-nav-child-label">{{ translate('Sub Sub Categories') }}</span>
                        </a>
                    </div>
                @endif
                @if (businessModuleItemVisible('catalog.brand_retail'))
                    <a class="v2-nav-item {{ Request::is('admin/retail/brand*') ? 'v2-is-active' : '' }}" data-item="brands" href="{{ route('admin.retail.brand.list') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Brand Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="brands" aria-label="Pin"></button></div>
                    </a>
                @endif
                @if (businessModuleItemVisible('catalog.attribute_retail'))
                    <a class="v2-nav-item {{ Request::is('admin/retail/attribute*') ? 'v2-is-active' : '' }}" data-item="attributes" href="{{ route('admin.retail.attribute.view') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Attribute Setup') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="attributes" aria-label="Pin"></button></div>
                    </a>
                @endif
                @if (businessModuleItemVisible('catalog.product_gallery'))
                    <a class="v2-nav-item {{ Request::is('admin/retail/products/product-gallery') ? 'v2-is-active' : '' }}" data-item="product-gallery" href="{{ route('admin.retail.products.product-gallery') }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Gallery') }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="product-gallery" aria-label="Pin"></button></div>
                    </a>
                @endif
            </div>
        @endif
    </div>
@endif

@if (Helpers::module_permission_check('retail.marketing') && businessModuleSectionVisible('marketing'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'marketing' ? 'v2-is-on' : '' }}" data-section="marketing">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @if (businessModuleItemVisible('marketing.promotions_retail'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
                <a class="v2-nav-item {{ Request::is('admin/retail/banner*') ? 'v2-is-active' : '' }}" data-item="banners" href="{{ route('admin.retail.banner.list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Banner Setup') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="banners" aria-label="Pin"></button></div>
                </a>

                <div class="v2-nav-item v2-has-children {{ (Request::is('admin/retail/coupon*') || Request::is('admin/retail/deal*')) ? 'v2-is-active' : '' }}" data-item="offers">
                    <a class="v2-nav-btn" href="{{ route('admin.retail.coupon.add') }}">
                        <span class="v2-nav-label">{{ translate('Offers & Deals') }}</span>
                    </a>
                    <div class="v2-nav-right">
                        <button class="v2-pin-btn" type="button" data-pin="offers" aria-label="Pin"></button>
                        <span class="v2-nav-chev">
                            <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                        </span>
                    </div>
                </div>
                <div class="v2-nav-children v2-is-collapsed" data-children-for="offers">
                    <a class="v2-nav-child {{ Request::is('admin/retail/coupon*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.coupon.add') }}">
                        <span class="v2-nav-child-dot" data-tone="primary"></span>
                        <span class="v2-nav-child-label">{{ translate('Coupon') }}</span>
                    </a>
                    <a class="v2-nav-child {{ (Request::is('admin/retail/deal/flash*') || Request::is('admin/retail/deal/update*')) ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.flash') }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Flash Deals') }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/deal/day') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.day') }}">
                        <span class="v2-nav-child-dot" data-tone="warn"></span>
                        <span class="v2-nav-child-label">{{ translate('Deal of the day') }}</span>
                    </a>
                    <a class="v2-nav-child {{ (Request::is('admin/retail/deal/feature') || Request::is('admin/retail/deal/feature/new') || Request::is('admin/retail/deal/feature-update*')) ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.feature') }}">
                        <span class="v2-nav-child-dot" data-tone="success"></span>
                        <span class="v2-nav-child-label">{{ translate('Featured Deal') }}</span>
                    </a>
                    <a class="v2-nav-child {{ Request::is('admin/retail/deal/clearance-sale*') ? 'v2-is-on' : '' }}" href="{{ route('admin.retail.deal.clearance-sale.index') }}">
                        <span class="v2-nav-child-dot" data-tone="danger"></span>
                        <span class="v2-nav-child-label">{{ translate('Clearance Sale') }}</span>
                    </a>
                </div>
            </div>
        @endif

        @if (businessModuleItemVisible('marketing.communication'))
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Communication') }}</span></div>
                <a class="v2-nav-item {{ (!Request::is('admin/retail/notification/push') && Request::is('admin/retail/notification/*')) ? 'v2-is-active' : '' }}" data-item="send-notification" href="{{ route('admin.retail.notification.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Send notification') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="send-notification" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('admin/retail/announcement*') ? 'v2-is-active' : '' }}" data-item="announcement" href="{{ route('admin.retail.announcement') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Announcement') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="announcement" aria-label="Pin"></button></div>
                </a>
            </div>
        @endif
    </div>
@endif
