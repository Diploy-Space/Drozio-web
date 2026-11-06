@php
    use App\Utils\Helpers;
    $v2HasAuction = function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction');
@endphp

@if (businessModuleSectionVisible('home'))
    <button class="v2-rail-btn" type="button" data-section="home" data-label="{{ translate('Home') }}" aria-label="{{ translate('Home') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-8.5Z"/>
        </svg>
    </button>
@endif

@if ($v2HasAuction && Helpers::module_permission_check('auction.inhouse') && businessModuleSectionVisible('auction-inhouse'))
    <button class="v2-rail-btn" type="button" data-section="auction-inhouse" data-label="{{ translate('In House Auction Products') }}" aria-label="{{ translate('In House Auction Products') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m14 5 5 5-4 4-5-5 4-4Z"/>
            <path d="m9 10-6 6 2 2 6-6"/>
            <path d="M4 20h10"/>
        </svg>
    </button>
@endif

@if ($v2HasAuction && Helpers::module_permission_check('auction.catalog') && businessModuleSectionVisible('auction-catalog'))
    <button class="v2-rail-btn" type="button" data-section="auction-catalog" data-label="{{ translate('catalog') }}" aria-label="{{ translate('catalog') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
            <path d="M3 7.5 12 12l9-4.5M12 12v9"/>
        </svg>
    </button>
@endif

@if ($v2HasAuction && Helpers::module_permission_check('auction.vendor') && businessModuleSectionVisible('auction-vendor'))
    <button class="v2-rail-btn" type="button" data-section="auction-vendor" data-label="{{ translate('Customer & Vendor Auction Products') }}" aria-label="{{ translate('Customer & Vendor Auction Products') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="8" r="3"/>
            <path d="M3 20c0-3 2.5-5 6-5s6 2 6 5"/>
            <path d="m16 4 4 4-3 3-4-4 3-3Z"/>
        </svg>
    </button>
@endif

@if ($v2HasAuction && Helpers::module_permission_check('auction.marketing') && businessModuleSectionVisible('auction-marketing'))
    <button class="v2-rail-btn" type="button" data-section="auction-marketing" data-label="{{ translate('Marketing') }}" aria-label="{{ translate('Marketing') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 13V9l13-5v16L3 15Z"/>
            <path d="M16 9v6"/>
            <path d="M7 15v2a2 2 0 0 0 4 0v-1"/>
        </svg>
    </button>
@endif

@if ($v2HasAuction && Helpers::module_permission_check('auction.finance') && businessModuleSectionVisible('auction-finance'))
    <button class="v2-rail-btn" type="button" data-section="auction-finance" data-label="{{ translate('Entry Fee Payments') }}" aria-label="{{ translate('Entry Fee Payments') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 8v8M9.5 10h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3h4"/>
        </svg>
    </button>
@endif
