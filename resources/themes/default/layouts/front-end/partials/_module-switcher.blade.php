@php($moduleSwitcherItems = storefrontBusinessModules())
@php($activeModule = activeStorefrontBusinessModule())

@if($moduleSwitcherItems && $activeModule)
    <div class="dropdown module-switcher {{ ($stacked ?? false) ? 'module-switcher--stacked' : '' }}">
        <a href="{{ $activeModule['url'] }}" role="button" class="module-switcher__toggle"
           data-toggle="dropdown" data-display="static"
           data-bs-toggle="dropdown" data-bs-display="static"
           aria-haspopup="true" aria-expanded="false">
            <span class="module-switcher__toggle-icon" aria-hidden="true">
                <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/'.$activeModule['icon']) }}" alt="" width="20" height="20">
            </span>
            <span class="module-switcher__toggle-label">{{ $activeModule['label'] }}</span>
            <i class="fi fi-rr-angle-down module-switcher__toggle-caret" aria-hidden="true"></i>
        </a>
        <div class="dropdown-menu hm-panel module-switcher__panel text-align-direction">
            @include('layouts.front-end.partials._module-switcher-options', ['moduleSwitcherItems' => $moduleSwitcherItems])
        </div>
    </div>
@endif
