@php
    use App\Utils\Helpers;

    $v2Auction = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction');
    $v2AuctionGroups = $v2Auction ? include(base_path('Modules/Auction/Addon/admin_routes.php')) : [];
    $v2AuctionInhouseGroup = $v2AuctionGroups[0] ?? null;
    $v2AuctionVendorGroup = $v2AuctionGroups[1] ?? null;
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
            @if (Helpers::module_permission_check('auction.dashboard'))
                <a class="v2-nav-item {{ Request::is('admin/*/dashboard') ? 'v2-is-active' : '' }}" data-item="dashboard" href="{{ businessModuleDashboardUrl() }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Dashboard') }}</span></span>
                    <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="dashboard" aria-label="Pin"></button></div>
                </a>
            @endif
        </div>
    </div>
@endif

@if ($v2Auction && Helpers::module_permission_check('auction.catalog') && businessModuleSectionVisible('auction-catalog'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction-catalog' ? 'v2-is-on' : '' }}" data-section="auction-catalog">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('catalog') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('organization') }}</span></div>
            <a class="v2-nav-item {{ Request::is('admin/auction/category*') ? 'v2-is-active' : '' }}" data-item="auction-category" href="{{ route('admin.auction.category.view') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('category_Setup') }}</span></span>
                <div class="v2-nav-right">
                    @if (($sidebarAuctionCategoryCount ?? 0) > 0)
                        <span data-v2-tag="info">{{ $sidebarAuctionCategoryCount }}</span>
                    @endif
                    <button class="v2-pin-btn" type="button" data-pin="auction-category" aria-label="Pin"></button>
                </div>
            </a>
            @if (Route::has('admin.auction.brand.list'))
                <a class="v2-nav-item {{ Request::is('admin/auction/brand*') ? 'v2-is-active' : '' }}" data-item="auction-brand" href="{{ route('admin.auction.brand.list') }}">
                    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Brand Setup') }}</span></span>
                    <div class="v2-nav-right">
                        @if (($sidebarAuctionBrandCount ?? 0) > 0)
                            <span data-v2-tag="info">{{ $sidebarAuctionBrandCount }}</span>
                        @endif
                        <button class="v2-pin-btn" type="button" data-pin="auction-brand" aria-label="Pin"></button>
                    </div>
                </a>
            @endif
        </div>
    </div>
@endif

@if ($v2Auction && $v2AuctionInhouseGroup && Helpers::module_permission_check('auction.inhouse') && businessModuleSectionVisible('auction-inhouse'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction-inhouse' ? 'v2-is-on' : '' }}" data-section="auction-inhouse">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate($v2AuctionInhouseGroup['name']) }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            @foreach ($v2AuctionInhouseGroup['routes'] as $v2AuctionRoute)
                @include('layouts.admin.partials.v2.sidebar.partials._auction-nav-item', [
                    'v2AuctionRoute' => $v2AuctionRoute,
                    'v2AuctionGroupName' => $v2AuctionInhouseGroup['name'],
                ])
            @endforeach
        </div>
    </div>
@endif

@if ($v2Auction && $v2AuctionVendorGroup && Helpers::module_permission_check('auction.vendor') && businessModuleSectionVisible('auction-vendor'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction-vendor' ? 'v2-is-on' : '' }}" data-section="auction-vendor">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate($v2AuctionVendorGroup['name']) }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            @foreach ($v2AuctionVendorGroup['routes'] as $v2AuctionRoute)
                @include('layouts.admin.partials.v2.sidebar.partials._auction-nav-item', [
                    'v2AuctionRoute' => $v2AuctionRoute,
                    'v2AuctionGroupName' => $v2AuctionVendorGroup['name'],
                ])
            @endforeach
        </div>
    </div>
@endif

@if ($v2Auction && Helpers::module_permission_check('auction.marketing') && businessModuleSectionVisible('auction-marketing'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction-marketing' ? 'v2-is-on' : '' }}" data-section="auction-marketing">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Marketing') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <div class="v2-ctx-group-head"><span>{{ translate('Communication') }}</span></div>
            <a class="v2-nav-item {{ Request::is('admin/auction/announcement*') ? 'v2-is-active' : '' }}" data-item="auction-announcement" href="{{ route('admin.auction.announcement') }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Announcement') }}</span></span>
                <div class="v2-nav-right"><button class="v2-pin-btn" type="button" data-pin="auction-announcement" aria-label="Pin"></button></div>
            </a>
        </div>
    </div>
@endif

@if ($v2Auction && Helpers::module_permission_check('auction.finance') && businessModuleSectionVisible('auction-finance'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction-finance' ? 'v2-is-on' : '' }}" data-section="auction-finance">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Entry Fee Payments') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        <div class="v2-ctx-group">
            <a class="v2-nav-item {{ Request::is('admin/auction/entry-fee-payments/digital*') ? 'v2-is-active' : '' }}" data-item="auction-entry-fee-digital" href="{{ route('admin.auction.entry-fee-payments', ['method' => 'digital']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Digital Payments') }}</span></span>
                <div class="v2-nav-right">
                    <button class="v2-pin-btn" type="button" data-pin="auction-entry-fee-digital" aria-label="Pin"></button>
                </div>
            </a>
            <a class="v2-nav-item {{ Request::is('admin/auction/entry-fee-payments/offline*') ? 'v2-is-active' : '' }}" data-item="auction-entry-fee-offline" href="{{ route('admin.auction.entry-fee-payments', ['method' => 'offline']) }}">
                <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate('Offline Payments') }}</span></span>
                <div class="v2-nav-right">
                    <button class="v2-pin-btn" type="button" data-pin="auction-entry-fee-offline" aria-label="Pin"></button>
                </div>
            </a>
        </div>
    </div>
@endif
