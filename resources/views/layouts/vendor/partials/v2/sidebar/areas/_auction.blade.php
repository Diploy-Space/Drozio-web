@php
    $v2Auction = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction');
    $v2AuctionRoutes = $v2Auction ? include(base_path('Modules/Auction/Addon/vendor_routes.php')) : [];
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
        </div>
    </div>
@endif

@if ($v2Auction && businessModuleSectionVisible('auction'))
    <div class="v2-ctx-section {{ ($v2DefaultSection ?? 'home') === 'auction' ? 'v2-is-on' : '' }}" data-section="auction">
        <div class="v2-ctx-head"><div class="v2-ctx-title">{{ translate('Auction Management') }}</div></div>
        <div class="v2-ctx-group v2-is-pinned" style="display:none;">
            <div class="v2-ctx-group-head"><span>{{ translate('Pinned') }}</span></div>
            <div class="v2-ctx-group-body"></div>
        </div>

        @foreach ($v2AuctionRoutes as $v2AuctionGroup)
            <div class="v2-ctx-group">
                <div class="v2-ctx-group-head"><span>{{ translate($v2AuctionGroup['name']) }}</span></div>
                @foreach ($v2AuctionGroup['routes'] as $v2AuctionRoute)
                    @if(($v2AuctionRoute['path'] != 'vendor/auction/products/add') || (getWebConfig(name: 'auction_feature_status') == 1 && getWebConfig(name: 'active_auction_for_vendor') == 1))
                        @php
                            $badgeData = function_exists('getAuctionSidebarBadgeData')
                                ? getAuctionSidebarBadgeData($v2AuctionRoute['path'], $sidebarAuctionVendorOwnCounts ?? [])
                                : ['is_status_route' => false, 'badge_class' => 'neutral', 'count' => 0];
                            $v2Slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($v2AuctionGroup['name'] . '-' . $v2AuctionRoute['name']));
                            $v2Tone = match($badgeData['badge_class'] ?? 'neutral') {
                                'success' => 'success',
                                'danger' => 'danger',
                                'warning' => 'warn',
                                'info' => 'info',
                                default => 'primary',
                            };
                            $v2IsAllAuctionFallback = $v2AuctionRoute['path'] === 'vendor/auction/products/list/all'
                                && (Request::is('vendor/auction/products/view*')
                                    || Request::is('vendor/auction/products/edit*')
                                    || Request::is('vendor/auction/products/update/*')
                                    || Request::is('vendor/auction/products/recreate/*'));
                            $v2AuctionIsActive = strstr(Request::url(), $v2AuctionRoute['path']) || $v2IsAllAuctionFallback;
                        @endphp
                        <a class="v2-nav-item {{ $v2AuctionIsActive ? 'v2-is-active' : '' }}" data-item="v-auction-{{ $v2Slug }}" href="{{ $v2AuctionRoute['url'] }}">
                            <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2AuctionRoute['name']) }}</span></span>
                            <div class="v2-nav-right">
                                @if ($badgeData['is_status_route'])
                                    <span data-v2-tag="{{ $v2Tone }}">{{ $badgeData['count'] }}</span>
                                @endif
                                <button class="v2-pin-btn" type="button" data-pin="v-auction-{{ $v2Slug }}" aria-label="Pin"></button>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
@endif
