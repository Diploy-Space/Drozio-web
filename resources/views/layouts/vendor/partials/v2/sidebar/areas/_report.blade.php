@php
    $v2Auction = businessModuleAvailable(\App\Enums\BusinessModule::AUCTION);
    $v2Tax = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'TaxModule');
    $v2TaxReportRoutes = $v2Tax ? include(base_path('Modules/TaxModule/Addon/vendor_tax_report_routes.php')) : [];
@endphp

@if (businessModuleSectionVisible('reports'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'reports' ? 'v2-is-on' : '' }}" data-section="reports">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Reports') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>
        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Reports & Analytics') }}</span></div>
            <a class="v2-nav-item {{ (Request::is('vendor/report/*/transaction/order-list') || Request::is('vendor/report/*/transaction/expense-list')) ? 'v2-is-active' : '' }}" data-item="v-transactions" href="{{ route(reportModuleRoute('transaction.order-list')) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Transaction Report') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-transactions" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ (Request::is('vendor/report/*/all-product') || Request::is('vendor/report/*/stock-product-report')) ? 'v2-is-active' : '' }}" data-item="v-product-report" href="{{ route(reportModuleRoute('all-product')) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Product Report') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-product-report" aria-label="Pin"></button></div>
            </a>
            <a class="v2-nav-item {{ Request::is('vendor/report/*/order-report') ? 'v2-is-active' : '' }}" data-item="v-order-report" href="{{ route(reportModuleRoute('order-report')) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Order Report') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-order-report" aria-label="Pin"></button></div>
            </a>

            @if ($v2Tax)
                @foreach ($v2TaxReportRoutes as $v2TaxRoute)
                    @php
                        $v2TaxActive = (bool) strstr(Request::url(), $v2TaxRoute['path']);
                        if (!$v2TaxActive && !empty($v2TaxRoute['sub_routes'])) {
                            foreach ($v2TaxRoute['sub_routes'] as $v2TaxSub) {
                                if (strstr(Request::url(), $v2TaxSub['path'])) { $v2TaxActive = true; break; }
                            }
                        }
                        $v2TaxSlug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($v2TaxRoute['name']));
                    @endphp
                    <a class="v2-nav-item {{ $v2TaxActive ? 'v2-is-active' : '' }}" data-item="v-tax-{{ $v2TaxSlug }}" href="{{ $v2TaxRoute['url'] }}">
                        <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2TaxRoute['name']) }}</span></span>
                        <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-tax-{{ $v2TaxSlug }}" aria-label="Pin"></button></div>
                    </a>
                @endforeach
            @endif
        </div>

        @if ($v2Auction)
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate('Auction Report') }}</span></div>
                <a class="v2-nav-item {{ Request::is('vendor/auction/reports/sales') ? 'v2-is-active' : '' }}" data-item="v-auction-sales-report" href="{{ route('vendor.auction.reports.sales') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Auction Sales Report') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-auction-sales-report" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('vendor/auction/reports/vat-report*') ? 'v2-is-active' : '' }}" data-item="v-auction-vat-report" href="{{ route('vendor.auction.reports.vat-report') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Auction VAT Report') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-auction-vat-report" aria-label="Pin"></button></div>
                </a>
                <a class="v2-nav-item {{ Request::is('vendor/auction/reports/transaction-history*') ? 'v2-is-active' : '' }}" data-item="v-auction-transaction-history" href="{{ route('vendor.auction.reports.transaction-history') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Transaction With Admin') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="v-auction-transaction-history" aria-label="Pin"></button></div>
                </a>
            </div>
        @endif
    </div>
@endif
