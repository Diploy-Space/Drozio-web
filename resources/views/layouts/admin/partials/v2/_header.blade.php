@php
    use App\Utils\Helpers;
    $v2Logo = getWebConfig(name: 'company_web_logo');
    $v2Favicon = getWebConfig(name: 'company_fav_icon');
    $v2CompanyName = getWebConfig(name: 'company_name');
    $v2PendingOrderCount = $headerPendingOrderCount ?? 0;
    $v2UnseenMessages = $headerUnseenMessages ?? 0;
    $v2Last5Orders = $headerRecentOrders ?? collect();
@endphp

<header class="v2-header">
    <div class="v2-header-brand">
        <a class="v2-brand-logo" href="{{ businessModuleDashboardUrl() }}" title="{{ translate('Dashboard') }}">
            <img src="{{ getStorageImages(path: $v2Favicon, type: 'backend-logo') }}" alt="{{ translate('Logo') }}">
            <span class="v2-brand-name">{{ $v2CompanyName }}</span>
        </a>
        <a class="v2-brand-logo-mini" href="{{ businessModuleDashboardUrl() }}" title="{{ translate('Dashboard') }}">
            <img src="{{ getStorageImages(path: $v2Favicon, type: 'backend-logo') }}" alt="{{ translate('Logo') }}">
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

    <div class="d-md-flex d-none align-items-center gap-20">
        @include('layouts.admin.partials.v2._area-nav', ['compact' => false])
    </div>

    <div class="v2-header-right">
        <button class="v2-search-btn" id="v2-header-search" type="button"
                data-v2-tooltip="{{ translate('Search') }} · ⌘K" aria-label="{{ translate('Search') }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <span>{{ translate('Search') }}…</span>
            <span class="v2-kbd-group"><span class="v2-kbd">⌘</span><span class="v2-kbd">K</span></span>
        </button>

        <a class="v2-icon-btn d-none d-lg-grid" href="{{ route('home') }}" target="_blank"
           data-v2-tooltip="{{ translate('Website') }}" aria-label="{{ translate('Website') }}">
            <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/front-end/img/w-link.svg') }}" alt="icon" class="svg">
        </a>

        @if ($headerShowOrderIcon ?? false)
            <div class="v2-dropdown">
                <button class="v2-icon-btn" type="button" data-v2-dropdown-toggle
                        data-v2-tooltip="{{ translate('Pending Orders') }}" aria-label="{{ translate('Pending Orders') }}">
                    <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/front-end/img/pendin-shop.svg') }}" alt="icon" class="svg">
                    @if ($v2PendingOrderCount > 0)
                        <span class="v2-badge-dot">{{ $v2PendingOrderCount > 99 ? '99+' : $v2PendingOrderCount }}</span>
                    @endif
                </button>
                <div class="v2-dropdown-menu v2-orders-menu">
                    <div class="v2-pop pt-1">
                        <div class="v2-pop-head">
                            <div class="v2-pop-head-title d-flex align-items-center">
                                <button class="backto_totalorder-btn fs-18 text-primary p-0 border-0">
                                    <i class="fi fi-sr-arrow-left d-flex"></i>
                                </button>
                                <h4>{{ translate('Total Orders') }}</h4>
                                <span>{{ $v2PendingOrderCount }}</span>
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
                                    @forelse ($v2Last5Orders as $v2Order)
                                        @php
                                            $v2Thumbs = [];
                                            foreach ($v2Order->details as $d) {
                                                $img = $d?->productAllStatus?->thumbnail_full_url;
                                                if ($img && ($img['status'] ?? 0) === 200) {
                                                    $v2Thumbs[] = $img;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="v2-pop-thumbs">
                                                    @if (count($v2Thumbs))
                                                        @foreach ($v2Thumbs as $idx => $v2ThumbItem)
                                                            <div class="v2-pop-thumb-wrap{{ $idx > 2 ? ' is-hidden' : '' }}">
                                                                <img src="{{ getStorageImages(path: $v2ThumbItem, type: 'backend-product') }}" alt="product image">
                                                                @if ($idx == 2 && count($v2Thumbs) > 3)
                                                                    <div class="v2-pop-thumb-more">
                                                                        <span>+ {{ count($v2Thumbs) - 3 }}</span>
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
                                                        <span class="v2-pop-value">{{ $v2Order->id }}</span>
                                                    </div>
                                                    <div class="v2-pop-row">
                                                        <div class="v2-pop-label">{{ translate('Order Amount') }}</div>
                                                        :
                                                        <span class="v2-pop-value">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $v2Order->order_amount), currencyCode: getCurrencyCode()) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="v2-pop-action-wrap">
                                                    <a class="v2-pop-action" href="{{ route($headerOrderDetailsRoute, ['id' => $v2Order->id]) }}"
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

        @if (Helpers::module_permission_check('people.support'))
            <a class="v2-icon-btn" href="{{ route('admin.people.contact.list') }}"
               data-v2-tooltip="{{ translate('Message') }}" aria-label="{{ translate('Message') }}">
                <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/front-end/img/h-message.svg') }}" alt="icon" class="svg">
                @if ($v2UnseenMessages > 0)
                    <span class="v2-badge-dot">{{ $v2UnseenMessages > 99 ? '99+' : $v2UnseenMessages }}</span>
                @endif
            </a>
        @endif

        @include('layouts.admin.partials.v2._business-switcher', [
            'iconOnly' => true,
            'id' => 'businessSwitcherHeader',
        ])

        <button class="v2-icon-btn d-grid d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#v2CommonMenuOffcanvas"
                aria-controls="v2CommonMenuOffcanvas"
                data-v2-tooltip="{{ translate('Common Menu') }}" aria-label="{{ translate('Common Menu') }}">
            <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/backend/media/icons/menu-nav-vector.svg') }}" alt="icon" class="svg">
        </button>
    </div>
</header>

@include('layouts.admin.partials.v2._common-menu-offcanvas')
