@php
    $isInhouseRoute = str_contains($v2AuctionRoute['path'], '/inhouse/');
    $badgeCounts = $isInhouseRoute ? ($sidebarAuctionInhouseCounts ?? []) : ($sidebarAuctionVendorCounts ?? []);
    $badgeData = function_exists('getAuctionSidebarBadgeData')
        ? getAuctionSidebarBadgeData($v2AuctionRoute['path'], $badgeCounts)
        : ['is_status_route' => false, 'badge_class' => 'neutral', 'count' => 0];
    $v2Slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($v2AuctionGroupName . '-' . $v2AuctionRoute['name']));
    $v2Tone = match($badgeData['badge_class'] ?? 'neutral') {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warn',
        'info' => 'info',
        default => 'primary',
    };
@endphp
<a class="v2-nav-item {{ (strstr(Request::url(), $v2AuctionRoute['path']) || (!empty($v2AuctionRoute['active_patterns']) && Request::is($v2AuctionRoute['active_patterns']))) ? 'v2-is-active' : '' }}"
   data-item="auction-{{ $v2Slug }}" href="{{ $v2AuctionRoute['url'] }}">
    <span class="v2-nav-btn"><span class="v2-nav-label">{{ translate($v2AuctionRoute['name']) }}</span></span>
    <div class="v2-nav-right">
        @if ($badgeData['is_status_route'])
            <span data-v2-tag="{{ $v2Tone }}">{{ $badgeData['count'] }}</span>
        @endif
        <button class="v2-pin-btn" type="button" data-pin="auction-{{ $v2Slug }}" aria-label="Pin"></button>
    </div>
</a>
