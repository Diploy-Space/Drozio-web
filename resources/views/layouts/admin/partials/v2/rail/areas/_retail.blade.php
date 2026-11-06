@php use App\Utils\Helpers; @endphp

@if (businessModuleSectionVisible('home'))
    <button class="v2-rail-btn" type="button" data-section="home" data-label="{{ translate('Home') }}" aria-label="{{ translate('Home') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-8.5Z"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('retail.orders') && businessModuleSectionVisible('orders'))
    <button class="v2-rail-btn" type="button" data-section="orders" data-label="{{ translate('Orders') }}" aria-label="{{ translate('Orders') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8h12l-1 11.2a1 1 0 0 1-1 .8H8a1 1 0 0 1-1-.8L6 8Z"/>
            <path d="M9 8V6a3 3 0 0 1 6 0v2"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('retail.catalog') && businessModuleSectionVisible('catalog'))
    <button class="v2-rail-btn" type="button" data-section="catalog" data-label="{{ translate('Catalog') }}" aria-label="{{ translate('Catalog') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/>
            <path d="M3 7.5 12 12l9-4.5M12 12v9"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('retail.marketing') && businessModuleSectionVisible('marketing'))
    <button class="v2-rail-btn" type="button" data-section="marketing" data-label="{{ translate('Marketing') }}" aria-label="{{ translate('Marketing') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 13V9l13-5v16L3 15Z"/>
            <path d="M16 9v6"/>
            <path d="M7 15v2a2 2 0 0 0 4 0v-1"/>
        </svg>
    </button>
@endif
