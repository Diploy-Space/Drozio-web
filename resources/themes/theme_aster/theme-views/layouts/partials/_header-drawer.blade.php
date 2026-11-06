<aside class="aside header-mobile-flex flex-column">
    <div class="aside-head">
        <a href="{{ $homeRoute }}" class="aside-head__home">{{ translate('Home') }}</a>
        <button type="button" class="aside-head__close border-0 bg-transparent p-0"
                data-drawer-close aria-label="{{ translate('Close') }}">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="aside-body" data-trigger="scrollbar">
        @include($drawerNav ?? 'theme-views.layouts.partials._header-drawer-nav')
    </div>
</aside>
