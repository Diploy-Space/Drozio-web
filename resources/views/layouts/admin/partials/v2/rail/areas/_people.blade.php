@php
    use App\Utils\Helpers;
@endphp

@if (Helpers::module_permission_check('people.customers') && businessModuleSectionVisible('people-customer'))
    <button class="v2-rail-btn" type="button" data-section="people-customer" data-label="{{ translate('Customer') }}" aria-label="{{ translate('Customer') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="8" r="3.5"/>
            <path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('people.vendors') && businessModuleSectionVisible('people-vendors'))
    <button class="v2-rail-btn" type="button" data-section="people-vendors" data-label="{{ translate('Vendors') }}" aria-label="{{ translate('Vendors') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 9h16l-1 11H5L4 9Z"/>
            <path d="M9 9V6.5a3 3 0 0 1 6 0V9"/>
            <path d="M4 9 5.5 4h13L20 9"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('people.delivery_men') && businessModuleSectionVisible('people-deliveryman'))
    <button class="v2-rail-btn" type="button" data-section="people-deliveryman" data-label="{{ translate('Deliveryman') }}" aria-label="{{ translate('Deliveryman') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="18" r="2.5"/>
            <path d="M8.5 18h7"/><path d="M6 15.5V11h5l3 4.5"/><path d="M11 11l2-4h3"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('people.team') && businessModuleSectionVisible('people-team'))
    <button class="v2-rail-btn" type="button" data-section="people-team" data-label="{{ translate('Team') }}" aria-label="{{ translate('Team') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="9" r="3"/>
            <path d="M3 20c0-3 2.5-5 6-5s6 2 6 5"/>
            <circle cx="17" cy="10" r="2.5"/>
            <path d="M15 20c.4-2.2 2-3.5 4-3.5s3.6 1.3 4 3.5"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('people.support') && businessModuleSectionVisible('people-support'))
    <button class="v2-rail-btn" type="button" data-section="people-support" data-label="{{ translate('Support') }}" aria-label="{{ translate('Support') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 5h16a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H8l-4 3V6a1 1 0 0 1 1-1Z"/>
            <path d="M8 10h8M8 14h5"/>
        </svg>
    </button>
@endif
