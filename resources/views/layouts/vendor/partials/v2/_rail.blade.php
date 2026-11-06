@php
    $v2HasAuction = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction');
@endphp

<aside class="v2-rail">
    <div class="v2-rail-items">
        @if (businessModuleSectionVisible('home'))
            <button class="v2-rail-btn" type="button" data-section="home" data-label="{{ translate('Home') }}" aria-label="{{ translate('Home') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-8.5Z"/>
                </svg>
            </button>
        @endif

        @if (businessModuleSectionVisible('orders'))
            <button class="v2-rail-btn" type="button" data-section="orders" data-label="{{ translate('Order') }}" aria-label="{{ translate('Order') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 8h12l-1 11.2a1 1 0 0 1-1 .8H8a1 1 0 0 1-1-.8L6 8Z"/>
                    <path d="M9 8V6a3 3 0 0 1 6 0v2"/>
                </svg>
            </button>
        @endif

        @if (businessModuleSectionVisible('catalog'))
            <button class="v2-rail-btn" type="button" data-section="catalog" data-label="{{ translate('Catalog') }}" aria-label="{{ translate('Catalog') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
                    <path d="M3 7.5 12 12l9-4.5M12 12v9"/>
                </svg>
            </button>
        @endif

        @if (businessModuleSectionVisible('marketing'))
            <button class="v2-rail-btn" type="button" data-section="marketing" data-label="{{ translate('Marketing') }}" aria-label="{{ translate('Marketing') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 13V9l13-5v16L3 15Z"/>
                    <path d="M16 9v6"/>
                    <path d="M7 15v2a2 2 0 0 0 4 0v-1"/>
                </svg>
            </button>
        @endif

        @if ($v2HasAuction && businessModuleSectionVisible('auction'))
            <button class="v2-rail-btn" type="button" data-section="auction" data-label="{{ translate('Auction Management') }}" aria-label="{{ translate('Auction Management') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m14 5 5 5-4 4-5-5 4-4Z"/>
                    <path d="m9 10-6 6 2 2 6-6"/>
                    <path d="M4 20h10"/>
                </svg>
            </button>
        @endif


        @if (businessModuleSectionVisible('reports'))
            <button class="v2-rail-btn" type="button" data-section="reports" data-label="{{ translate('Reports') }}" aria-label="{{ translate('Reports') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 20V11M11 20V5M17 20v-6"/>
                    <path d="M3 20h18"/>
                </svg>
            </button>
        @endif

        @if (businessModuleSectionVisible('people'))
            <button class="v2-rail-btn" type="button" data-section="people" data-label="{{ translate('People') }}" aria-label="{{ translate('People') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="9" r="3"/>
                    <path d="M3 20c0-3 2.5-5 6-5s6 2 6 5"/>
                    <circle cx="17" cy="10" r="2.5"/>
                    <path d="M15 20c.4-2.2 2-3.5 4-3.5s3.6 1.3 4 3.5"/>
                </svg>
            </button>
        @endif

        @if (businessModuleSectionVisible('settings'))
            <button class="v2-rail-btn" type="button" data-section="settings" data-label="{{ translate('Settings') }}" aria-label="{{ translate('Settings') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                    <circle cx="9"  cy="7"  r="2" fill="currentColor" stroke="none"/>
                    <circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/>
                    <circle cx="7"  cy="17" r="2" fill="currentColor" stroke="none"/>
                </svg>
            </button>
        @endif
    </div>

    @php
        $v2SetupRail = function_exists('checkSetupGuideRequirements') ? checkSetupGuideRequirements(panel: 'vendor') : ['completePercent' => 100];
    @endphp
    @if (($v2SetupRail['completePercent'] ?? 100) < 100)
        <div class="v2-rail-foot">
            <button type="button" class="v2-rail-setup"
                    data-v2-setup-trigger
                    data-label="{{ translate('Setup Guide') }} · {{ $v2SetupRail['completePercent'] ?? 0 }}%"
                    aria-label="{{ translate('Setup Guide') }}">
                <img src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/setup_guide.png') }}" alt="">
                <span class="v2-rail-setup-badge">{{ $v2SetupRail['completePercent'] ?? 0 }}%</span>
            </button>
        </div>
    @endif

    <div class="v2-rail-account">
        @include('layouts.vendor.partials.v2._profile-menu')
    </div>
</aside>
