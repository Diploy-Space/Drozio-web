@php($moduleSwitcherItems = storefrontBusinessModules())

@if($moduleSwitcherItems)
    <aside class="sh-drawer sh-drawer--bottom" id="{{ $sheetId ?? 'storefrontModeSheet' }}">
        <div class="sh-drawer__head sh-drawer__head--bare">
            <button class="sh-drawer__close" type="button" data-sh-drawer-close aria-label="{{ translate('Close') }}">
                <i class="fi fi-rr-cross-small"></i>
            </button>
        </div>
        <div class="sh-drawer__body module-switcher__panel">
            @include('layouts.front-end.partials._module-switcher-options', ['moduleSwitcherItems' => $moduleSwitcherItems])
        </div>
    </aside>
@endif
