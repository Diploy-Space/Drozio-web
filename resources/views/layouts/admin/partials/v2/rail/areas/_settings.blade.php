@php
    use App\Utils\Helpers;
@endphp

@if (Helpers::module_permission_check('settings.business') && businessModuleSectionVisible('settings-business'))
    <button class="v2-rail-btn" type="button" data-section="settings-business" data-label="{{ translate('Business Settings') }}" aria-label="{{ translate('Business Settings') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 21V8l6-4 6 4v13"/><path d="M15 21V12h6v9"/><path d="M2 21h20"/>
            <path d="M7 11h2M7 15h2M18 15h0"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('settings.content') && businessModuleSectionVisible('settings-content'))
    <button class="v2-rail-btn" type="button" data-section="settings-content" data-label="{{ translate('Content Setup') }}" aria-label="{{ translate('Content Setup') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 4h9l5 5v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
            <path d="M14 4v5h5"/><path d="M8 13h7M8 17h5"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('settings.system') && businessModuleSectionVisible('settings-system'))
    <button class="v2-rail-btn" type="button" data-section="settings-system" data-label="{{ translate('System Settings') }}" aria-label="{{ translate('System Settings') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16"/>
            <circle cx="9"  cy="7"  r="2" fill="currentColor" stroke="none"/>
            <circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/>
            <circle cx="7"  cy="17" r="2" fill="currentColor" stroke="none"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('settings.third_party') && businessModuleSectionVisible('settings-third-party'))
    <button class="v2-rail-btn" type="button" data-section="settings-third-party" data-label="{{ translate('3rd Party Setup') }}" aria-label="{{ translate('3rd Party Setup') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="7" cy="7" r="3"/><circle cx="17" cy="17" r="3"/>
            <path d="M10 7h4a3 3 0 0 1 3 3v4"/><path d="M14 17h-4a3 3 0 0 1-3-3v-4"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('settings.themes') && businessModuleSectionVisible('settings-themes'))
    <button class="v2-rail-btn" type="button" data-section="settings-themes" data-label="{{ translate('Themes & Addons') }}" aria-label="{{ translate('Themes & Addons') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 3a9 9 0 1 0 0 18c1 0 1.7-.8 1.7-1.7 0-.5-.2-.9-.5-1.2a1.7 1.7 0 0 1 1.2-2.9H17A4 4 0 0 0 21 11c0-4.4-4-8-9-8Z"/>
            <circle cx="7.5" cy="11.5" r="1"/><circle cx="11" cy="7.5" r="1"/><circle cx="15.5" cy="9.5" r="1"/>
        </svg>
    </button>
@endif
