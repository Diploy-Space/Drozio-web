@php
    $bmSwitcherCompact = $compact ?? false;
    $bmSwitcherIconOnly = $iconOnly ?? false;
    $bmSwitcherId = $id ?? 'businessSwitcher';
    $bmSwitcherInCommon = inCommonAdminArea();
    $bmSwitcherActive = activeBusinessModule();
    $bmSwitcherAvailable = businessModuleManager()->permittedBusinessModules();
    $bmSwitcherPlaceholder = config('business_modules.switcher_placeholder_icon', 'select-business.svg');
@endphp

@if (count($bmSwitcherAvailable) > 1)
<div class="dropdown {{ $bmSwitcherIconOnly ? 'd-grid d-lg-none' : 'retail_switcher_options' }}">
    @if ($bmSwitcherIconOnly)
        <button class="v2-icon-btn" type="button" id="{{ $bmSwitcherId }}"
                data-bs-toggle="dropdown" aria-expanded="false"
                data-v2-tooltip="{{ translate('Switch Business') }}" aria-label="{{ translate('Switch Business') }}">
            <img width="18" height="18" alt="icon" class="svg"
                 src="{{ $bmSwitcherInCommon
                    ? dynamicAsset(path: 'public/assets/backend/media/icons/menu-module-switch.svg')
                    : dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmSwitcherActive->icon) }}">
        </button>
    @else
        <button class="v2-biz-switcher d-flex py-2 {{ $bmSwitcherCompact ? 'px-sm-3 px-2 border-0 gap-1 fs-14' : 'px-3 gap-2 fs-16' }} rounded align-items-center fw-medium text-dark dropdown-toggle {{ $bmSwitcherInCommon ? 'v2-biz-switcher-unset' : '' }}"
                type="button" id="{{ $bmSwitcherId }}"
                data-bs-toggle="dropdown" aria-expanded="false">
            <img src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . ($bmSwitcherInCommon ? $bmSwitcherPlaceholder : $bmSwitcherActive->icon)) }}" alt="icon" class="svg">
            <span>{{ $bmSwitcherInCommon ? translate('Select Business') : translate($bmSwitcherActive->label) }}</span>
        </button>
    @endif

    <div class="dropdown-menu v2-switcher-menu{{ $bmSwitcherIconOnly ? ' dropdown-menu-end' : '' }}" aria-labelledby="{{ $bmSwitcherId }}">
        <div class="v2-switcher-panel">
            @foreach ($bmSwitcherAvailable as $bmSwitcherModule)
                <form method="POST" action="{{ route('admin.business-module.switch', ['module' => $bmSwitcherModule->key]) }}" class="m-0">
                    @csrf
                    <button type="submit"
                            class="dropdown-item v2-switcher-option {{ !$bmSwitcherInCommon && $bmSwitcherActive->key === $bmSwitcherModule->key ? 'v2-is-active' : '' }}">
                        <img width="20" height="20" src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmSwitcherModule->icon) }}"
                             alt="{{ translate($bmSwitcherModule->label) }}">
                        <span>{{ translate($bmSwitcherModule->label) }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>
@elseif (count($bmSwitcherAvailable) === 1)
    @if ($bmSwitcherIconOnly)
        <a class="v2-icon-btn d-grid d-lg-none" href="{{ businessModuleDashboardUrl() }}" id="{{ $bmSwitcherId }}"
           data-v2-tooltip="{{ translate('Dashboard') }}" aria-label="{{ translate('Dashboard') }}">
            <img width="18" height="18" alt="icon" class="svg"
                 src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmSwitcherActive->icon) }}">
        </a>
    @else
        <a class="v2-biz-switcher d-flex py-2 {{ $bmSwitcherCompact ? 'px-sm-3 px-2 border-0 gap-1 fs-14' : 'px-3 gap-2 fs-16' }} rounded align-items-center fw-medium text-dark text-decoration-none {{ $bmSwitcherInCommon ? 'v2-biz-switcher-unset' : '' }}"
           href="{{ businessModuleDashboardUrl() }}" id="{{ $bmSwitcherId }}">
            <img src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmSwitcherActive->icon) }}" alt="icon" class="svg">
            <span>{{ translate('Dashboard') }}</span>
        </a>
    @endif
@endif
