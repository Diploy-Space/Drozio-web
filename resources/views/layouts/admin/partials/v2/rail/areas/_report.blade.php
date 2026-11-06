@php
    use App\Utils\Helpers;
@endphp

@if (Helpers::module_permission_check('reports.finance') && businessModuleSectionVisible('report-finance'))
    <button class="v2-rail-btn" type="button" data-section="report-finance" data-label="{{ translate('Finance') }}" aria-label="{{ translate('Finance') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="8"/>
            <path d="M12 8v8M9.5 10h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3h4"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('reports.operations') && businessModuleSectionVisible('report-operations'))
    <button class="v2-rail-btn" type="button" data-section="report-operations" data-label="{{ translate('Operations') }}" aria-label="{{ translate('Operations') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 20V11M11 20V5M17 20v-6"/>
            <path d="M3 20h18"/>
        </svg>
    </button>
@endif

@if (Helpers::module_permission_check('reports.tax') && businessModuleSectionVisible('report-tax'))
    <button class="v2-rail-btn" type="button" data-section="report-tax" data-label="{{ translate('Tax') }}" aria-label="{{ translate('Tax') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 4h12v16l-3-2-3 2-3-2-3 2V4Z"/>
            <path d="M9.5 14.5l5-5"/><circle cx="9.75" cy="9.75" r=".75" fill="currentColor"/><circle cx="14.25" cy="14.25" r=".75" fill="currentColor"/>
        </svg>
    </button>
@endif
