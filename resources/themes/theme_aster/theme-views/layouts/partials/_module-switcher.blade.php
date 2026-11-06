@php($storefrontShoppingMode = activeStorefrontBusinessModule())

@if($storefrontShoppingMode)
    <div class="shopping-mode-switcher">
        <a href="{{ $storefrontShoppingMode['url'] }}" class="shopping-mode-toggle">
            <span class="shopping-mode-toggle__icon d-center rounded-circle">
                <img width="20" height="20" class="svg" alt="{{ $storefrontShoppingMode['label'] }}"
                     src="{{ dynamicAsset(path: 'public/assets/front-end/img/icons/'.$storefrontShoppingMode['icon']) }}">
            </span>
            <span>{{ $storefrontShoppingMode['label'] }}</span>
            <i class="bi bi-chevron-down shopping-mode-toggle__caret" aria-hidden="true"></i>
        </a>
        <div class="shopping-mode-backdrop" data-shopping-mode-dismiss></div>
        <ul class="shopping-mode-menu">
            <li class="shopping-mode-menu__close">
                <button type="button" class="shopping-mode-close" data-shopping-mode-dismiss
                        aria-label="{{ translate('Close') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </li>
            @include('theme-views.layouts.partials._module-switcher-options')
        </ul>
    </div>
@endif
