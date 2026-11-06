@php
    $v2Seller = auth('seller')->user();
    $v2SellerPOS = getWebConfig('seller_pos');
    $v2HasPOS = ($v2SellerPOS == 1 && ($v2Seller['pos_status'] ?? 0) == 1);
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
            <a class="v2-nav-item {{ Request::is('vendor/*/dashboard*') ? 'v2-is-active' : '' }}" data-item="v-dashboard" href="{{ businessModuleDashboardUrl() }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Dashboard') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-dashboard" aria-label="Pin"></button></div>
            </a>
            @if ($v2HasPOS && businessModuleItemVisible('pos'))
                <a class="v2-nav-item {{ Request::is('vendor/retail/pos*') ? 'v2-is-active' : '' }}" data-item="v-pos" href="{{ route('vendor.retail.pos.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('POS') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-pos" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif

@include('layouts.vendor.partials.v2.sidebar.partials._retail-orders-section')

@include('layouts.vendor.partials.v2.sidebar.partials._retail-catalog-section')

@if (businessModuleSectionVisible('marketing'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'marketing' ? 'v2-is-on' : '' }}" data-section="marketing">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Promotions') }}</span></div>
            @if (businessModuleItemVisible('marketing.promotions_retail'))
                <a class="v2-nav-item {{ Request::is('vendor/retail/coupon*') ? 'v2-is-active' : '' }}" data-item="v-coupons" href="{{ route('vendor.retail.coupon.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Coupon') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-coupons" aria-label="Pin"></button></div>
                </a>
            @endif
            @if (businessModuleItemVisible('marketing.clearance_sale'))
                <a class="v2-nav-item {{ Request::is('vendor/retail/clearance-sale*') ? 'v2-is-active' : '' }}" data-item="v-clearance" href="{{ route('vendor.retail.clearance-sale.index') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Clearance Sale') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-clearance" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif
