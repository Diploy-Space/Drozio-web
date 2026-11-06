@php
    $bmArea = activeAdminArea();
    $bmInCommon = inCommonAdminArea();
    $bmActive = activeBusinessModule();
    $bmAvailable = businessModuleManager()->availableInPanel();
    $bmCommonAreas = businessModuleManager()->permittedCommonAreas();
    $bmSwitcherIcon = $bmInCommon
        ? config('business_modules.switcher_placeholder_icon', 'select-business.svg')
        : $bmActive->icon;
@endphp

@if (count($bmAvailable) > 1)
    <div class="v2-dropdown v2-module-switcher">
        <button class="v2-switcher-btn {{ $bmInCommon ? 'v2-switcher-unset' : '' }}" type="button" data-v2-dropdown-toggle
                aria-label="{{ translate('Business Module') }}">
            <img class="v2-switcher-icon" width="20" height="20"
                 src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmSwitcherIcon) }}"
                 alt="{{ $bmInCommon ? translate('Select Business') : translate($bmActive->label) }}">
            <span class="v2-switcher-label">{{ $bmInCommon ? translate('Select Business') : translate($bmActive->label) }}</span>
            <svg class="v2-switcher-caret" width="10" height="6" viewBox="0 0 10 6" fill="currentColor" aria-hidden="true">
                <path d="M0 0h10L5 6 0 0Z"/>
            </svg>
        </button>
        <div class="v2-dropdown-menu v2-switcher-menu">
            <div class="v2-switcher-panel">
                @foreach ($bmAvailable as $bmModule)
                    <form method="POST" action="{{ route('vendor.business-module.switch', ['module' => $bmModule->key]) }}" class="m-0">
                        @csrf
                        <button type="submit"
                                class="v2-switcher-option {{ !$bmInCommon && $bmActive->key === $bmModule->key ? 'v2-is-active' : '' }}">
                            <img width="20" height="20"
                                 src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmModule->icon) }}"
                                 alt="{{ translate($bmModule->label) }}">
                            <span>{{ translate($bmModule->label) }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@elseif (count($bmAvailable) === 1)
    <div class="v2-module-switcher">
        <a class="v2-switcher-btn v2-switcher-link {{ $bmInCommon ? 'v2-switcher-unset' : '' }}" href="{{ businessModuleDashboardUrl() }}"
           aria-label="{{ translate('Dashboard') }}">
            <img class="v2-switcher-icon" width="20" height="20"
                 src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmActive->icon) }}"
                 alt="{{ translate('Dashboard') }}">
            <span class="v2-switcher-label">{{ translate('Dashboard') }}</span>
        </a>
    </div>
@endif

<nav class="v2-area-nav" aria-label="{{ translate('Areas') }}">
    @foreach ($bmCommonAreas as $bmAreaKey => $bmCommonArea)
        @php $bmAreaUrl = adminAreaUrl($bmAreaKey); @endphp
        @continue($bmAreaUrl === '')
        <a href="{{ $bmAreaUrl }}"
           class="v2-area-link {{ $bmArea->key === $bmAreaKey ? 'v2-area-link-active' : '' }}">
            <img width="18" height="18" src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/' . $bmCommonArea->icon) }}"
                 alt="{{ translate($bmCommonArea->label) }}">
            <span>{{ translate($bmCommonArea->label) }}</span>
        </a>
    @endforeach
</nav>
