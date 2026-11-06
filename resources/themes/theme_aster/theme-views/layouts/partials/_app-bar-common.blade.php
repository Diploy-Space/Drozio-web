@php
    use App\Utils\CartManager;
    $usesModulePanel = $usesModuleCartPanel ?? storefrontUsesModuleHeaderPanel();
    $cartCount = $usesModulePanel ? ($headerModuleCartCount ?? 0) : CartManager::getCartListQuery()->count();
    $wishlistCount = $usesModulePanel
        ? ($headerModuleWishlistCount ?? 0)
        : ($headerWishlistCount ?? 0);
    $isCustomerLoggedIn = auth('customer')->check();
@endphp

<div class="container">
    <ul class="app-bar__list list-unstyled d-flex align-items-center justify-content-between mb-0">
        <li>
            <a href="{{ route('home') }}"
               class="app-bar__link {{ (Request::is('/') || Request::is('home')) ? 'active' : '' }}"
               aria-label="{{ translate('Home') }}">
                @include('theme-views.partials.icons._app-bar-home')
            </a>
        </li>
        <li>
            @if(! $isCustomerLoggedIn)
                <a href="javascript:" class="app-bar__link" data-bs-toggle="modal" data-bs-target="#loginModal"
                   aria-label="{{ translate('Wishlist') }}">
                    @include('theme-views.partials.icons._app-bar-wishlist')
                </a>
            @elseif($usesModulePanel && ! empty($headerModuleWishlists ?? []))
                <div class="dropup position-static">
                    <a href="javascript:" class="app-bar__link" aria-label="{{ translate('Wishlist') }}">
                        <span class="position-relative">
                            @include('theme-views.partials.icons._app-bar-wishlist')
                            <span class="count wishlist_count_status">{{ $wishlistCount }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu scrollY-60 bs-dropdown-min-width--100 shadow-sm cart-dropdown-mobile-view">
                        <li class="p-3">
                            @include('theme-views.layouts.partials._module-wishlist-panel')
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('wishlists.index') }}"
                   class="app-bar__link {{ Request::is('customer/wishlist') ? 'active' : '' }}"
                   aria-label="{{ translate('Wishlist') }}">
                    <span class="position-relative">
                        @include('theme-views.partials.icons._app-bar-wishlist')
                        <span class="count wishlist_count_status">{{ $wishlistCount }}</span>
                    </span>
                </a>
            @endif
        </li>
        <li>
            @if($usesModulePanel)
                <div class="dropup position-static">
                    <a href="javascript:" class="app-bar__link" aria-label="{{ translate('Shopping cart') }}">
                        <span class="position-relative">
                            @include('theme-views.partials.icons._app-bar-cart')
                            <span class="count">{{ $cartCount }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu scrollY-60 bs-dropdown-min-width--100 shadow-sm cart-dropdown-mobile-view"
                        id="cart_dropdown">
                        <li class="p-3">
                            @include('theme-views.layouts.partials._module-cart-panel')
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('shop-cart') }}"
                   class="app-bar__link {{ Request::is('shop-cart') ? 'active' : '' }}"
                   aria-label="{{ translate('Shopping cart') }}">
                    <span class="position-relative">
                        @include('theme-views.partials.icons._app-bar-cart')
                        <span class="count">{{ $cartCount }}</span>
                    </span>
                </a>
            @endif
        </li>
    </ul>
</div>
