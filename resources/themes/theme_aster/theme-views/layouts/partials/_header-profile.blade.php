@include('theme-views.layouts.partials._header-announcement')

<header class="header header--account">
    <div class="header-main love-sticky py-2 py-lg-3">
        <div class="container">
            <div class="d-flex justify-content-between gap-3 align-items-center position-relative">
                <div class="logo-area d-flex align-items-center gap-2">
                    <a class="logo system-logo-element header-desktop-block" href="{{ route('home') }}">
                        <img class="dark-support svg h-45" alt="{{ translate('Logo') }}"
                             src="{{ getStorageImages(path: $web_config['web_logo'], type:'logo') }}">
                    </a>
                    <a class="system-logo-element flex-shrink-0 header-mobile-block" href="{{ route('home') }}">
                        <img class="dark-support" alt="{{ translate('Logo') }}"
                             src="{{ getStorageImages(path: $web_config['mob_logo'], type:'logo') }}">
                    </a>
                </div>

                <ul class="list-unstyled list-separator header-actions mb-0">
                    <li class="header-desktop-block" id="wishlist_items" data-header-wishlist-url="{{ route('wishlists.header', ['panel' => 'module']) }}">
                        @include('theme-views.layouts.partials._wishlist')
                    </li>
                    <li class="header-desktop-block" id="cart_items">
                        @include(storefrontUsesModuleHeaderPanel()
                            ? 'theme-views.layouts.partials._module-cart'
                            : 'theme-views.layouts.partials._cart')
                    </li>

                    @include('theme-views.layouts.partials._header-account-menu', ['accountOrders' => [
                        'url' => route('account-oder'),
                        'icon' => 'bi-box-seam-fill',
                        'label' => translate('Orders'),
                    ]])

                    @include('theme-views.layouts.partials._header-account-panel')
                </ul>
            </div>
        </div>
    </div>
</header>
