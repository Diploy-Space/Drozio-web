@php
    $v2OrderCounts = $sidebarVendorOrderCounts ?? collect();
    $v2RefundCounts = $sidebarVendorRefundCounts ?? collect();
    $v2OrderCountAll = $sidebarVendorTotalOrders ?? 0;
    $v2OrderCountPending = $v2OrderCounts['pending'] ?? 0;
    $v2RefundPending = $v2RefundCounts['pending'] ?? 0;
@endphp

@if (businessModuleSectionVisible('orders'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'orders' ? 'v2-is-on' : '' }}" data-section="orders">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Order') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Sales') }}</span></div>

            <div class="v2-nav-item v2-has-children {{ Request::is('vendor/retail/orders*') ? 'v2-is-active' : '' }}" data-item="v-orders">
                <a class="v2-nav-btn" href="{{ route('vendor.retail.orders.list', ['all']) }}">
                    <span class="v2-nav-label">{{ translate('Orders') }}</span>
                </a>
                <div class="v2-nav-right">
                    @if ($v2OrderCountPending > 0)
                        <span data-v2-tag="primary">{{ $v2OrderCountPending }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="v-orders" aria-label="Pin"></button>
                    <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                    </span>
                </div>
            </div>
            <div class="v2-nav-children v2-is-collapsed" data-children-for="v-orders">
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/all') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['all']) }}">
                    <span class="v2-nav-child-dot" data-tone="primary"></span>
                    <span class="v2-nav-child-label">{{ translate('All') }}</span>
                    <span class="v2-nav-child-count" data-tone="primary">{{ $v2OrderCountAll }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/pending') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['pending']) }}">
                    <span class="v2-nav-child-dot" data-tone="primary"></span>
                    <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                    <span class="v2-nav-child-count" data-tone="primary">{{ $v2OrderCountPending }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/confirmed') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['confirmed']) }}">
                    <span class="v2-nav-child-dot" data-tone="success"></span>
                    <span class="v2-nav-child-label">{{ translate('Confirmed') }}</span>
                    <span class="v2-nav-child-count" data-tone="success">{{ $v2OrderCounts['confirmed'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/processing') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['processing']) }}">
                    <span class="v2-nav-child-dot" data-tone="warn"></span>
                    <span class="v2-nav-child-label">{{ translate('Packaging') }}</span>
                    <span class="v2-nav-child-count" data-tone="warn">{{ $v2OrderCounts['processing'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/out_for_delivery') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['out_for_delivery']) }}">
                    <span class="v2-nav-child-dot" data-tone="warn"></span>
                    <span class="v2-nav-child-label">{{ translate('Out For Delivery') }}</span>
                    <span class="v2-nav-child-count" data-tone="warn">{{ $v2OrderCounts['out_for_delivery'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/delivered') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['delivered']) }}">
                    <span class="v2-nav-child-dot" data-tone="success"></span>
                    <span class="v2-nav-child-label">{{ translate('Delivered') }}</span>
                    <span class="v2-nav-child-count" data-tone="success">{{ $v2OrderCounts['delivered'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/returned') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['returned']) }}">
                    <span class="v2-nav-child-dot" data-tone="danger"></span>
                    <span class="v2-nav-child-label">{{ translate('Returned') }}</span>
                    <span class="v2-nav-child-count" data-tone="danger">{{ $v2OrderCounts['returned'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/failed') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['failed']) }}">
                    <span class="v2-nav-child-dot" data-tone="danger"></span>
                    <span class="v2-nav-child-label">{{ translate('Failed to deliver') }}</span>
                    <span class="v2-nav-child-count" data-tone="danger">{{ $v2OrderCounts['failed'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/orders/list/canceled') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.orders.list', ['canceled']) }}">
                    <span class="v2-nav-child-dot" data-tone="neutral"></span>
                    <span class="v2-nav-child-label">{{ translate('Canceled') }}</span>
                    <span class="v2-nav-child-count" data-tone="danger">{{ $v2OrderCounts['canceled'] ?? 0 }}</span>
                </a>
            </div>

            <div class="v2-nav-item v2-has-children {{ Request::is('vendor/retail/refund*') ? 'v2-is-active' : '' }}" data-item="v-refunds">
                <a class="v2-nav-btn" href="{{ route('vendor.retail.refund.index', ['pending']) }}">
                    <span class="v2-nav-label">{{ translate('Refund Requests') }}</span>
                </a>
                <div class="v2-nav-right">
                    @if ($v2RefundPending > 0)
                        <span data-v2-tag="warn">{{ $v2RefundPending }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="v-refunds" aria-label="Pin"></button>
                    <span class="v2-nav-chev">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                    </span>
                </div>
            </div>
            <div class="v2-nav-children v2-is-collapsed" data-children-for="v-refunds">
                <a class="v2-nav-child {{ Request::is('vendor/retail/refund/index/pending') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.refund.index', ['pending']) }}">
                    <span class="v2-nav-child-dot" data-tone="primary"></span>
                    <span class="v2-nav-child-label">{{ translate('Pending') }}</span>
                    <span class="v2-nav-child-count" data-tone="primary">{{ $v2RefundPending }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/refund/index/approved') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.refund.index', ['approved']) }}">
                    <span class="v2-nav-child-dot" data-tone="success"></span>
                    <span class="v2-nav-child-label">{{ translate('Approved') }}</span>
                    <span class="v2-nav-child-count" data-tone="success">{{ $v2RefundCounts['approved'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/refund/index/refunded') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.refund.index', ['refunded']) }}">
                    <span class="v2-nav-child-dot" data-tone="success"></span>
                    <span class="v2-nav-child-label">{{ translate('Refunded') }}</span>
                    <span class="v2-nav-child-count" data-tone="success">{{ $v2RefundCounts['refunded'] ?? 0 }}</span>
                </a>
                <a class="v2-nav-child {{ Request::is('vendor/retail/refund/index/rejected') ? 'v2-is-on' : '' }}" href="{{ route('vendor.retail.refund.index', ['rejected']) }}">
                    <span class="v2-nav-child-dot" data-tone="danger"></span>
                    <span class="v2-nav-child-label">{{ translate('Rejected') }}</span>
                    <span class="v2-nav-child-count" data-tone="danger">{{ $v2RefundCounts['rejected'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>
@endif
