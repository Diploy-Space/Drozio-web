<div class="header-mobile-bar header-mobile-flex align-items-center justify-content-between">
    <div class="header-mobile-bar__start d-flex align-items-center">
        <div class="menu-btn" role="button" tabindex="0" aria-label="{{ translate('Menu') }}">
            <i class="bi bi-list" aria-hidden="true"></i>
        </div>

        <a class="header-mobile-bar__logo system-logo-element" href="{{ $homeRoute }}">
            <img class="dark-support" alt="{{ translate('Logo') }}"
                 src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}">
        </a>
    </div>

    <ul class="header-mobile-bar__end header-actions list-unstyled mb-0 d-flex align-items-center">
        @if($storefrontShoppingMode)
            <li>
                @include('theme-views.layouts.partials._module-switcher')
            </li>
        @endif

        <li>
            <button type="button" class="header-mobile-search-toggle border-0 p-0"
                    data-mobile-search-toggle aria-label="{{ translate('Search') }}">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
        </li>

        @include('theme-views.layouts.partials._header-account-panel')
    </ul>
</div>

@include($mobileSearchView ?? 'theme-views.layouts.partials._header-mobile-search')
