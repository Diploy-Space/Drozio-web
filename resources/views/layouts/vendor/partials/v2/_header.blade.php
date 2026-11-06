@php($v2Brand = $headerBrand ?? ['logo' => '', 'logo_mini' => '', 'name' => '', 'alt' => translate('Logo'), 'shop_url' => 'javascript:'])
@php($v2BrandLogoMiniSrc = $v2Brand['logo_mini'])
@php($v2BrandLogoAlt = $v2Brand['alt'])

<header class="v2-header">
    <div class="v2-header-brand">
        <a class="v2-brand-logo" href="{{ businessModuleDashboardUrl() }}" title="{{ $v2BrandLogoAlt }}">
            <img src="{{ $v2BrandLogoMiniSrc }}" alt="{{ $v2BrandLogoAlt }}">
            <span class="v2-brand-name">{{ $v2Brand['name'] }}</span>
        </a>
        <a class="v2-brand-logo-mini" href="{{ businessModuleDashboardUrl() }}" title="{{ $v2BrandLogoAlt }}">
            <img src="{{ $v2BrandLogoMiniSrc }}" alt="{{ $v2BrandLogoAlt }}">
        </a>
        <button class="v2-sidebar-toggle" id="v2-sidebar-toggle" type="button"
                data-v2-tooltip="{{ translate('Collapse sidebar') }}" aria-label="{{ translate('Toggle sidebar') }}"
                data-v2-tip-expanded="{{ translate('Collapse sidebar') }}"
                data-v2-tip-collapsed="{{ translate('Expand sidebar') }}">
            <svg class="v2-sb-icon v2-sb-collapse" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 6l-6 6 6 6"/>
                <path d="M21 4v16"/>
            </svg>
            <svg class="v2-sb-icon v2-sb-expand" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 6l6 6-6 6"/>
                <path d="M3 4v16"/>
            </svg>
        </button>
    </div>

    @include('layouts.vendor.partials.v2._area-nav')

    <div class="v2-header-right">
        @if ($isAdvancedSearchAvailable)
            <button class="v2-search-btn" id="v2-header-search" type="button"
                    data-v2-tooltip="{{ translate('Search') }} · ⌘K" aria-label="{{ translate('Search') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
                <span>{{ translate('Search') }}…</span>
                <span class="v2-kbd-group"><span class="v2-kbd">⌘</span><span class="v2-kbd">K</span></span>
            </button>
        @endif

        <a class="v2-icon-btn"
           href="{{ $headerWholesaleShopUrl ?? $v2Brand['shop_url'] }}"
           target="_blank"
           data-v2-tooltip="{{ translate('Website shop view') }}"
           aria-label="{{ translate('Website shop view') }}">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="M3 12h18"/>
                <path d="M12 3a13.5 13.5 0 0 1 0 18"/>
                <path d="M12 3a13.5 13.5 0 0 0 0 18"/>
            </svg>
        </a>

        @php($v2VendorPendingCount = $headerPendingOrderCount ?? 0)
        @php($v2VendorPendingOrders = $headerRecentOrders ?? collect())
        @if ($headerShowOrderIcon ?? false)
        <div class="v2-dropdown">
            <button class="v2-icon-btn" type="button" data-v2-dropdown-toggle
                    data-v2-tooltip="{{ translate('Pending Orders') }}"
                    aria-label="{{ translate('Pending Orders') }}">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 8h14l-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 8Z"/>
                    <path d="M9 8V6a3 3 0 0 1 6 0v2"/>
                </svg>
                @if ($v2VendorPendingCount > 0)
                    <span class="v2-badge-dot">{{ $v2VendorPendingCount > 99 ? '99+' : $v2VendorPendingCount }}</span>
                @endif
            </button>
            <div class="v2-dropdown-menu v2-orders-menu">
                <div class="v2-pop">
                    <div class="v2-pop-head">
                        <div class="v2-pop-head-title">
                            <h4>{{ translate('Total Orders') }}</h4>
                            <span>{{ $v2VendorPendingCount }}</span>
                        </div>
                        <a class="v2-pop-view-all" href="{{ $headerOrderListUrl }}">
                            {{ translate('View All') }}
                            <svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 10h10"/><path d="m11 6 4 4-4 4"/>
                            </svg>
                        </a>
                    </div>
                    <div class="v2-pop-scroll">
                        <table class="v2-pop-table">
                            <tbody>
                                @forelse ($v2VendorPendingOrders as $v2VendorOrder)
                                    @php($v2VendorThumbs = $v2VendorOrder['thumbnails'])
                                    <tr>
                                        <td>
                                            <div class="v2-pop-thumbs">
                                                @if (count($v2VendorThumbs))
                                                    @foreach ($v2VendorThumbs as $v2VendorThumbIdx => $v2VendorThumb)
                                                        <div class="v2-pop-thumb-wrap{{ $v2VendorThumbIdx > 2 ? ' is-hidden' : '' }}">
                                                            <img src="{{ getStorageImages(path: $v2VendorThumb, type: 'backend-product') }}" alt="product image">
                                                            @if ($v2VendorThumbIdx == 2 && count($v2VendorThumbs) > 3)
                                                                <div class="v2-pop-thumb-more">
                                                                    <span>+ {{ count($v2VendorThumbs) - 3 }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <img src="{{ getStorageImages(path: '', type: 'backend-product') }}" alt="placeholder">
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="v2-pop-info">
                                                <div class="v2-pop-row">
                                                    <div class="v2-pop-label">{{ translate('Order ID') }}</div>
                                                    :
                                                    <span class="v2-pop-value">{{ $v2VendorOrder['id'] }}</span>
                                                </div>
                                                <div class="v2-pop-row">
                                                    <div class="v2-pop-label">{{ translate('Order Amount') }}</div>
                                                    :
                                                    <span class="v2-pop-value">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $v2VendorOrder['amount']), currencyCode: getCurrencyCode()) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="v2-pop-action-wrap">
                                                <a class="v2-pop-action" href="{{ route($headerOrderDetailsRoute, ['id' => $v2VendorOrder['id']]) }}"
                                                   title="{{ translate('View') }}">
                                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M2 10s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6Z"/><circle cx="10" cy="10" r="2.5"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="v2-pop-empty">{{ translate('No pending orders') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @php($v2VendorMsgCounts = $headerUnseenMessageCounts ?? [])
        @php($v2VendorMsgTotal = $v2VendorMsgCounts['total'] ?? 0)
        @php($v2VendorMsgCustomer = $v2VendorMsgCounts['customer'] ?? 0)
        @php($v2VendorMsgDeliveryMan = $v2VendorMsgCounts['delivery_man'] ?? 0)
        <div class="v2-dropdown">
            <button class="v2-icon-btn" type="button" data-v2-dropdown-toggle
                    data-v2-tooltip="{{ translate('Inbox') }}"
                    aria-label="{{ translate('Inbox') }}">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 5h16a1 1 0 0 1 1 1v12a2 2 0 0 1-2 2H8l-4 3V6a1 1 0 0 1 1-1Z"/>
                    <path d="M8 11h8M8 15h5"/>
                </svg>
                @if ($v2VendorMsgTotal > 0)
                    <span class="v2-badge-dot">{{ $v2VendorMsgTotal > 99 ? '99+' : $v2VendorMsgTotal }}</span>
                @endif
            </button>
            <div class="v2-dropdown-menu v2-msg-menu">
                <a class="v2-dropdown-item" href="{{ route('vendor.messages.index') }}#customer">
                    <span>{{ translate('Customer') }}</span>
                    @if ($v2VendorMsgCustomer > 0)
                        <span class="v2-item-count">{{ $v2VendorMsgCustomer }}</span>
                    @endif
                </a>
                <a class="v2-dropdown-item" href="{{ route('vendor.messages.index') }}#delivery-man">
                    <span>{{ translate('Delivery Man') }}</span>
                    @if ($v2VendorMsgDeliveryMan > 0)
                        <span class="v2-item-count">{{ $v2VendorMsgDeliveryMan }}</span>
                    @endif
                </a>
            </div>
        </div>

        @php($v2VendorNotifList = $headerNotifications ?? collect())
        @php($v2VendorWholesaleNotifList = $headerWholesaleNotifications ?? collect())
        @php($v2AuctionSellerNotifs = $headerAuctionNotifications ?? collect())
        @php($v2VendorNotifNew = ($headerUnseenNotificationCount ?? 0) + ($headerWholesaleUnseenNotificationCount ?? 0) + ($headerAuctionUnreadNotificationCount ?? 0))
        <div class="v2-dropdown">
            <button class="v2-icon-btn" type="button" data-v2-dropdown-toggle
                    data-v2-tooltip="{{ translate('Notifications') }}"
                    aria-label="{{ translate('Notifications') }}">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                    <path d="M10 21a2 2 0 0 0 4 0"/>
                </svg>
                @if ($v2VendorNotifNew > 0)
                    <span class="v2-badge-dot notification_data_new_count">{{ $v2VendorNotifNew > 99 ? '99+' : $v2VendorNotifNew }}</span>
                @endif
            </button>
            <div class="v2-dropdown-menu v2-notif-menu">
                @include('layouts.vendor.partials.v2._header-notification-items', ['v2NotifItems' => $v2VendorNotifList])
                @if ($v2VendorWholesaleNotifList->isNotEmpty())
                    @include('layouts.vendor.partials.v2._header-notification-items', ['v2NotifItems' => $v2VendorWholesaleNotifList, 'v2NotifViewUrl' => $headerWholesaleNotificationViewUrl ?? null])
                @endif

                @foreach ($v2AuctionSellerNotifs as $v2AuctNotif)
                    <button type="button" class="v2-dropdown-item auction-notif-read w-100"
                            data-auction-notif-id="{{ $v2AuctNotif['id'] }}"
                            data-title="{{ $v2AuctNotif['label'] }}"
                            data-message="{{ $v2AuctNotif['message'] }}"
                            data-product-url="{{ $v2AuctNotif['product_url'] }}">
                        <span class="v2-notif-title">{{ $v2AuctNotif['label'] }}</span>
                        <span class="v2-notif-time">{{ $v2AuctNotif['time'] }}</span>
                        @if (!$v2AuctNotif['is_read'])
                            <span class="v2-notif-new auction_notif_badge_{{ $v2AuctNotif['id'] }}">
                                {{ translate('New') }}
                            </span>
                        @endif
                    </button>
                @endforeach

                @if ($v2VendorNotifList->isEmpty() && $v2VendorWholesaleNotifList->isEmpty() && $v2AuctionSellerNotifs->isEmpty())
                    <div class="v2-dropdown-empty">{{ translate('No notifications') }}</div>
                @endif
            </div>
        </div>


    </div>
</header>
