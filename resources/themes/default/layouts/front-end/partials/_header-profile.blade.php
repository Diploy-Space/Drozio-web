@php($businessMode = getWebConfig(name: 'business_mode'))
@php($isSignedIn = auth('customer')->check())
@php($accountLinks = [
    ['label' => translate('My Profile'), 'url' => route('user-account'), 'icon' => 'fi fi-rr-circle-user'],
    ['label' => translate('Orders'), 'url' => route('account-oder'), 'icon' => 'fi fi-rr-box-open'],
    ['label' => translate('Saved'), 'url' => route('wishlists.index'), 'icon' => 'fi fi-rr-heart'],
    ['label' => translate('Inbox'), 'url' => route('chat'), 'icon' => 'fi fi-rr-envelope'],
    ['label' => translate('Wallet'), 'url' => route('wallet'), 'icon' => 'fi fi-rr-wallet'],
])

<header class="rtl __inline-10 site-header sh-header sh-header--profile">
    <div class="sh-bar">
        <div class="container sh-bar__inner">
            <a class="sh-brand d-none d-sm-flex system-logo-element" href="{{ route('home') }}">
                <img src="{{ getStorageImages(path: $web_config['web_logo'], type: 'logo') }}"
                     alt="{{ $web_config['company_name'] }}">
            </a>
            <a class="sh-brand sh-brand--mark d-sm-none system-logo-element" href="{{ route('home') }}">
                <img src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}"
                     alt="{{ $web_config['company_name'] }}">
            </a>

            <div class="sh-tools navbar-toolbar">
                <div id="wishlist_items" data-header-wishlist-url="{{ route('wishlists.header', empty($headerModuleWishlists ?? []) ? [] : ['panel' => 'module']) }}">
                    @include(empty($headerModuleWishlists ?? [])
                        ? 'layouts.front-end.partials._wishlist'
                        : 'layouts.front-end.partials._module-wishlist')
                </div>

                <div id="cart_items">
                    @include(empty($headerModuleCarts ?? [])
                        ? 'layouts.front-end.partials._cart'
                        : 'layouts.front-end.partials._module-cart')
                </div>

                @include('layouts.front-end.partials._header-tools-hover')

                <div class="dropdown d-none d-md-block">
                    <a class="sh-tool {{ $isSignedIn ? 'sh-tool--avatar' : '' }}" href="javascript:"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       aria-label="{{ translate('My Account') }}">
                        @include('web-views.partials._account-icon')
                    </a>
                    <div class="text-align-direction dropdown-menu hm-panel sh-account dropdown-menu-{{ session('direction') === 'rtl' ? 'left' : 'right' }}">
                        @include('web-views.partials._account-panel', [
                            'homeUrl' => route('home'),
                            'links' => $accountLinks,
                            'scope' => 'ProfileMenu',
                        ])
                    </div>
                </div>

                <a class="sh-tool {{ $isSignedIn ? 'sh-tool--avatar' : '' }} d-md-none" href="javascript:"
                   data-sh-drawer="#storefrontProfileAccountDrawer" aria-label="{{ translate('My Account') }}">
                    @include('web-views.partials._account-icon')
                </a>
            </div>
        </div>
    </div>
</header>

<aside class="sh-drawer sh-drawer--end d-md-none" id="storefrontProfileAccountDrawer">
    <div class="sh-drawer__head">
        <h6 class="sh-drawer__title"><a href="{{ route('home') }}">{{ translate('Home') }}</a></h6>
        <button class="sh-drawer__close" type="button" data-sh-drawer-close aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross-small"></i>
        </button>
    </div>
    <div class="sh-drawer__body sh-account">
        @include('web-views.partials._account-panel', [
            'homeUrl' => route('home'),
            'links' => $isSignedIn ? $profileMenu : $accountLinks,
            'showTitle' => false,
            'showAuctionLinks' => $isSignedIn,
            'scope' => 'ProfileDrawer',
        ])
    </div>
</aside>

@push('script')
    <script>
        "use strict";

        (function () {
            const toolbar = document.querySelector('.sh-header--profile .sh-tools');
            if (!toolbar) {
                return;
            }

            const syncBadges = function () {
                toolbar.querySelectorAll('.sh-tool__count').forEach(function (badge) {
                    badge.classList.toggle('is-zero', (parseInt(badge.textContent.trim(), 10) || 0) === 0);
                });
            };

            syncBadges();
            new MutationObserver(syncBadges).observe(toolbar, {
                childList: true,
                characterData: true,
                subtree: true,
            });
        })();
    </script>
@endpush
