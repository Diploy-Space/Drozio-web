@php
    use Modules\Wholesale\app\Services\Product\ProductWholesaleService;

    $v2Wholesale = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Wholesale');
    $v2WsProductCounts = $sidebarVendorWholesaleProductCounts ?? [];
    $v2WsOrderCounts = $sidebarVendorWholesaleOrderCounts ?? [];
    $v2WsTotalOrders = $sidebarVendorWholesaleTotalOrders ?? 0;
    $v2WsPendingOrders = $v2WsOrderCounts['pending'] ?? 0;
    $v2WsRefundCounts = $sidebarVendorWholesaleRefundCounts ?? [];
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
            <a class="v2-nav-item {{ Request::is('vendor/wholesale/dashboard*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-dashboard" href="{{ route('vendor.wholesale.dashboard.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Dashboard') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-dashboard" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if ($v2Wholesale && businessModuleSectionVisible('orders'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'orders' ? 'v2-is-on' : '' }}" data-section="orders">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Order') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Sales') }}</span></div>

            <div class="v2-nav-item v2-has-children {{ Request::is('vendor/wholesale/orders*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-orders">
                <a class="v2-nav-btn" href="{{ route('vendor.wholesale.orders.list', ['all']) }}">
                    <span class="v2-nav-label">{{ translate('Orders') }}</span>
                </a>
                <div class="v2-nav-right">
                    @if ($v2WsPendingOrders > 0)
                        <span data-v2-tag="primary">{{ $v2WsPendingOrders }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="v-wholesale-orders" aria-label="Pin"></button>
                    <span class="v2-nav-chev" aria-label="{{ translate('Toggle') }}">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                    </span>
                </div>
            </div>
            <div class="v2-nav-children v2-is-collapsed" data-children-for="v-wholesale-orders">
                @foreach ([
                    ['status' => 'all', 'label' => translate('All'), 'tone' => 'primary', 'count' => $v2WsTotalOrders],
                    ['status' => 'pending', 'label' => translate('Pending'), 'tone' => 'primary', 'count' => $v2WsPendingOrders],
                    ['status' => 'confirmed', 'label' => translate('Confirmed'), 'tone' => 'success', 'count' => $v2WsOrderCounts['confirmed'] ?? 0],
                    ['status' => 'processing', 'label' => translate('Packaging'), 'tone' => 'warn', 'count' => $v2WsOrderCounts['processing'] ?? 0],
                    ['status' => 'out_for_delivery', 'label' => translate('Out For Delivery'), 'tone' => 'warn', 'count' => $v2WsOrderCounts['out_for_delivery'] ?? 0],
                    ['status' => 'delivered', 'label' => translate('Delivered'), 'tone' => 'success', 'count' => $v2WsOrderCounts['delivered'] ?? 0],
                    ['status' => 'returned', 'label' => translate('Returned'), 'tone' => 'danger', 'count' => $v2WsOrderCounts['returned'] ?? 0],
                    ['status' => 'failed', 'label' => translate('Failed to deliver'), 'tone' => 'danger', 'count' => $v2WsOrderCounts['failed'] ?? 0],
                    ['status' => 'canceled', 'label' => translate('Canceled'), 'tone' => 'danger', 'count' => $v2WsOrderCounts['canceled'] ?? 0],
                ] as $v2WsOrderItem)
                    <a class="v2-nav-child {{ Request::is('vendor/wholesale/orders/list/' . $v2WsOrderItem['status']) ? 'v2-is-on' : '' }}" href="{{ route('vendor.wholesale.orders.list', [$v2WsOrderItem['status']]) }}">
                        <span class="v2-nav-child-dot" data-tone="{{ $v2WsOrderItem['tone'] }}"></span>
                        <span class="v2-nav-child-label">{{ $v2WsOrderItem['label'] }}</span>
                        <span class="v2-nav-child-count" data-tone="{{ $v2WsOrderItem['tone'] }}">{{ $v2WsOrderItem['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="v2-nav-item v2-has-children {{ Request::is('vendor/wholesale/refund*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-refunds">
                <a class="v2-nav-btn" href="{{ route('vendor.wholesale.refund.list', ['pending']) }}">
                    <span class="v2-nav-label">{{ translate('Refund Requests') }}</span>
                </a>
                <div class="v2-nav-right">
                    @if (($v2WsRefundCounts['pending'] ?? 0) > 0)
                        <span data-v2-tag="warn">{{ $v2WsRefundCounts['pending'] }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="v-wholesale-refunds" aria-label="Pin"></button>
                    <span class="v2-nav-chev">
                        <svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 5 5 5-5 5"/></svg>
                    </span>
                </div>
            </div>
            <div class="v2-nav-children v2-is-collapsed" data-children-for="v-wholesale-refunds">
                @foreach ([
                    ['status' => 'pending', 'label' => translate('Pending'), 'tone' => 'primary'],
                    ['status' => 'approved', 'label' => translate('Approved'), 'tone' => 'success'],
                    ['status' => 'refunded', 'label' => translate('Refunded'), 'tone' => 'success'],
                    ['status' => 'rejected', 'label' => translate('Rejected'), 'tone' => 'danger'],
                ] as $v2WsRefundItem)
                    <a class="v2-nav-child {{ Request::is('vendor/wholesale/refund/list/' . $v2WsRefundItem['status']) ? 'v2-is-on' : '' }}" href="{{ route('vendor.wholesale.refund.list', [$v2WsRefundItem['status']]) }}">
                        <span class="v2-nav-child-dot" data-tone="{{ $v2WsRefundItem['tone'] }}"></span>
                        <span class="v2-nav-child-label">{{ $v2WsRefundItem['label'] }}</span>
                        <span class="v2-nav-child-count" data-tone="{{ $v2WsRefundItem['tone'] }}">{{ $v2WsRefundCounts[$v2WsRefundItem['status']] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if ($v2Wholesale && businessModuleSectionVisible('catalog'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'catalog' ? 'v2-is-on' : '' }}" data-section="catalog">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Catalog') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Products') }}</span></div>
            @foreach ([
                ['state' => ProductWholesaleService::REQUEST_STATE_ALL, 'label' => translate('Product list'), 'tone' => 'info'],
                ['state' => ProductWholesaleService::REQUEST_STATE_NEW, 'label' => translate('New product requests'), 'tone' => 'danger'],
                ['state' => ProductWholesaleService::REQUEST_STATE_APPROVED, 'label' => translate('Approved products'), 'tone' => 'success'],
                ['state' => ProductWholesaleService::REQUEST_STATE_DENIED, 'label' => translate('Denied products'), 'tone' => 'danger'],
            ] as $v2WsProductItem)
                <a class="v2-nav-item {{ Request::is('vendor/wholesale/products/list/' . $v2WsProductItem['state']) ? 'v2-is-active' : '' }}" data-item="v-wholesale-products-{{ $v2WsProductItem['state'] }}" href="{{ route('vendor.wholesale.products.list', [$v2WsProductItem['state']]) }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ $v2WsProductItem['label'] }}</span></span>
                    <div class="v2-nav-right">
                        <span data-v2-tag="{{ $v2WsProductItem['tone'] }}">{{ $v2WsProductCounts[$v2WsProductItem['state']] ?? 0 }}</span>
                        <button class="v2-pin-btn" type="button" data-pin="v-wholesale-products-{{ $v2WsProductItem['state'] }}" aria-label="Pin"></button>
                    </div>
                </a>
            @endforeach
            @if (businessModuleItemVisible('catalog.add_product_wholesale'))
                <a class="v2-nav-item {{ Request::is('vendor/wholesale/products/add*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-products-add" href="{{ route('vendor.wholesale.products.add') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Add New Product') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-products-add" aria-label="Pin"></button></div>
                </a>
            @endif
            <a class="v2-nav-item {{ Request::is('vendor/wholesale/products/product-gallery') ? 'v2-is-active' : '' }}" data-item="v-wholesale-product-gallery" href="{{ route('vendor.wholesale.products.product-gallery') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Gallery') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-product-gallery" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/wholesale/products/stock-limit-list') ? 'v2-is-active' : '' }}" data-item="v-wholesale-limited-stock" href="{{ route('vendor.wholesale.products.stock-limit-list') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Limited Stocks') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-limited-stock" aria-label="Pin"></button></div>
            </a>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Product Review') }}</span></div>
            <a class="v2-nav-item {{ Request::is('vendor/wholesale/reviews*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-reviews" href="{{ route('vendor.wholesale.reviews.index') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Reviews') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-reviews" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if ($v2Wholesale && businessModuleSectionVisible('marketing'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'marketing' ? 'v2-is-on' : '' }}" data-section="marketing">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
            @if (businessModuleItemVisible('marketing.promotions_wholesale'))
                <a class="v2-nav-item {{ Request::is('vendor/wholesale/coupon*') ? 'v2-is-active' : '' }}" data-item="v-wholesale-coupons" href="{{ route('vendor.wholesale.coupon.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Coupon') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-wholesale-coupons" aria-label="Pin"></button></div>
                </a>
            @endif
            @if (businessModuleItemVisible('marketing.campaign'))
                <a class="v2-nav-item {{ Request::is('vendor/wholesale/campaign*') ? 'v2-is-active' : '' }}" data-item="v-campaign" href="{{ route('vendor.wholesale.campaign.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Campaign') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-campaign" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif
