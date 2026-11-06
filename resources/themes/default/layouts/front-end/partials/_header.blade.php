@php($businessMode = getWebConfig(name: 'business_mode'))
@php($categories = $headerCategories)
@php($isSignedIn = auth('customer')->check())
@php($showsProfileMenu = $isSignedIn && View::hasSection('account-profile-menu'))
@php($accountLinks = [
    ['label' => translate('My Profile'), 'url' => route('user-account'), 'icon' => 'fi fi-rr-circle-user'],
    ['label' => translate('Orders'), 'url' => route('account-oder'), 'icon' => 'fi fi-rr-box-open'],
    ['label' => translate('Saved'), 'url' => route('wishlists.index'), 'icon' => 'fi fi-rr-heart'],
    ['label' => translate('Inbox'), 'url' => route('chat', ['type' => $businessMode === 'multi' ? 'vendor' : 'delivery-man']), 'icon' => 'fi fi-rr-envelope'],
    ['label' => translate('Wallet'), 'url' => route('wallet'), 'icon' => 'fi fi-rr-wallet'],
])

@include('layouts.front-end.partials._announcement', [
    'config' => getWebConfig(name: 'announcement'),
    'barId' => 'announcement',
    'storageKey' => 'offerBarHideUntil',
])

<header class="rtl __inline-10 site-header sh-header">
    <div class="mobile-head">
        <div class="sh-bar">
            <div class="container sh-bar__inner">
                <button class="sh-burger d-md-none" type="button" data-sh-drawer="#storefrontMenuDrawer"
                        aria-label="{{ translate('Menu') }}">
                    <i class="fi fi-rr-menu-burger"></i>
                </button>

                <a class="sh-brand d-none d-sm-flex system-logo-element" href="{{ route('home') }}">
                    <img src="{{ getStorageImages(path: $web_config['web_logo'], type: 'logo') }}"
                         alt="{{ $web_config['company_name'] }}">
                </a>
                <a class="sh-brand sh-brand--mark d-sm-none system-logo-element" href="{{ route('home') }}">
                    <img src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}"
                         alt="{{ $web_config['company_name'] }}">
                </a>

                @if(activeStorefrontBusinessModule())
                    <button class="sh-modepill__toggle d-md-none" type="button" data-sh-drawer="#storefrontModeSheet">
                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/'.activeStorefrontBusinessModule()['icon']) }}"
                             alt="" width="20" height="20">
                        <span class="sh-modepill__label">{{ activeStorefrontBusinessModule()['label'] }}</span>
                        <i class="fi fi-rr-angle-down" aria-hidden="true"></i>
                    </button>
                @endif

                <div class="sh-search search-form-mobile d-none d-md-block text-align-direction">
                    <form action="{{ route('products') }}" class="search_form">
                        <div class="sh-search__control">
                            <input class="form-control sh-search__field search-bar-input" type="search"
                                   autocomplete="off" data-given-value=""
                                   placeholder="{{ translate('Search for retail products') }} ..."
                                   name="name" value="{{ request('name') }}">
                            <button class="sh-search__submit" type="submit" aria-label="{{ translate('Search') }}">
                                <i class="fi fi-rr-search"></i>
                            </button>
                            <span class="sh-searchbar__cancel close-search-form-mobile d-md-none">
                                {{ translate('Cancel') }}
                            </span>
                        </div>
                        <input type="hidden" name="global_search_input" value="1">
                        <input name="data_from" value="search" hidden>
                        <input name="page" value="1" hidden>
                        <div class="search-card sh-search__panel">
                            <div class="search-result-box"></div>
                        </div>
                    </form>
                </div>

                <div class="sh-tools navbar-toolbar">
                    <a class="sh-tool open-search-form-mobile d-md-none" href="javascript:"
                       aria-label="{{ translate('Search') }}">
                        <i class="fi fi-rr-search"></i>
                    </a>

                    <div id="wishlist_items" data-header-wishlist-url="{{ route('wishlists.header') }}">
                        @include('layouts.front-end.partials._wishlist')
                    </div>

                    <div id="cart_items">
                        @include('layouts.front-end.partials._cart')
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
                                'scope' => 'RetailMenu',
                            ])
                        </div>
                    </div>

                    <a class="sh-tool {{ $isSignedIn ? 'sh-tool--avatar' : '' }} d-md-none" href="javascript:"
                       data-sh-drawer="#storefrontAccountDrawer" aria-label="{{ translate('My Account') }}">
                        @include('web-views.partials._account-icon')
                    </a>
                </div>
            </div>
        </div>

        <div class="sh-nav navbar-stuck-menu d-none d-md-block">
            <div class="container sh-nav__inner">
                <a class="sh-catalog {{ count($categories) > 0 ? 'category-menu-toggle-btn' : '' }}"
                   href="{{ count($categories) > 0 ? 'javascript:' : route('categories') }}">
                    <svg class="sh-catalog__icon" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                              d="M9.875 12.9195C9.875 12.422 9.6775 11.9452 9.32563 11.5939C8.97438 11.242 8.4975 11.0445 8 11.0445C6.75875 11.0445 4.86625 11.0445 3.625 11.0445C3.1275 11.0445 2.65062 11.242 2.29937 11.5939C1.9475 11.9452 1.75 12.422 1.75 12.9195V17.2945C1.75 17.792 1.9475 18.2689 2.29937 18.6202C2.65062 18.972 3.1275 19.1695 3.625 19.1695H8C8.4975 19.1695 8.97438 18.972 9.32563 18.6202C9.6775 18.2689 9.875 17.792 9.875 17.2945V12.9195ZM19.25 12.9195C19.25 12.422 19.0525 11.9452 18.7006 11.5939C18.3494 11.242 17.8725 11.0445 17.375 11.0445C16.1337 11.0445 14.2413 11.0445 13 11.0445C12.5025 11.0445 12.0256 11.242 11.6744 11.5939C11.3225 11.9452 11.125 12.422 11.125 12.9195V17.2945C11.125 17.792 11.3225 18.2689 11.6744 18.6202C12.0256 18.972 12.5025 19.1695 13 19.1695H17.375C17.8725 19.1695 18.3494 18.972 18.7006 18.6202C19.0525 18.2689 19.25 17.792 19.25 17.2945V12.9195ZM16.5131 9.66516L19.1206 7.05766C19.8525 6.32578 19.8525 5.13828 19.1206 4.4064L16.5131 1.79891C15.7813 1.06703 14.5937 1.06703 13.8619 1.79891L11.2544 4.4064C10.5225 5.13828 10.5225 6.32578 11.2544 7.05766L13.8619 9.66516C14.5937 10.397 15.7813 10.397 16.5131 9.66516ZM9.875 3.54453C9.875 3.04703 9.6775 2.57015 9.32563 2.2189C8.97438 1.86703 8.4975 1.66953 8 1.66953C6.75875 1.66953 4.86625 1.66953 3.625 1.66953C3.1275 1.66953 2.65062 1.86703 2.29937 2.2189C1.9475 2.57015 1.75 3.04703 1.75 3.54453V7.91953C1.75 8.41703 1.9475 8.89391 2.29937 9.24516C2.65062 9.59703 3.1275 9.79453 3.625 9.79453H8C8.4975 9.79453 8.97438 9.59703 9.32563 9.24516C9.6775 8.89391 9.875 8.41703 9.875 7.91953V3.54453Z"
                              fill="currentColor"/>
                    </svg>
                    <span class="category-menu-toggle-btn-text">{{ translate('Categories') }}</span>
                    @if(count($categories) > 0)
                        <i class="fi fi-rr-angle-down sh-catalog__caret"></i>
                    @endif
                </a>

                <ul class="sh-menu">
                    <li class="{{ request()->is('/') ? 'is-active' : '' }}">
                        <a class="sh-menu__link" href="{{ route('home') }}">{{ translate('Home') }}</a>
                    </li>

                    @if(getWebConfig(name: 'product_brand'))
                        <li class="dropdown">
                            <a class="sh-menu__link dropdown-toggle" href="javascript:" data-toggle="dropdown">
                                {{ translate('Brands') }}
                            </a>
                            <div class="text-align-direction dropdown-menu hm-panel hm-panel--wide dropdown-menu-{{ session('direction') === 'rtl' ? 'right' : 'left' }}">
                                @include('web-views.partials._brand-menu', [
                                    'brands' => $headerBrands,
                                    'countKey' => 'brand_products_count',
                                    'routeName' => 'brand-products',
                                    'viewAllUrl' => route('brands'),
                                ])
                            </div>
                        </li>
                    @endif

                    @php($offerMenuItems = array_values(array_filter([
                        ($headerFeaturedDealCount ?? 0) > 0 ? [
                            'label' => translate('Featured Deal'),
                            'url' => route('featured-deal-products'),
                            'icon' => 'fi fi-rr-star',
                            'count' => $headerFeaturedDealCount ?? 0,
                        ] : null,
                        ($web_config['flash_deals'] && count($web_config['flash_deals_products']) > 0) ? [
                            'label' => translate('Flash deal'),
                            'url' => route('flash-deals', ['id' => $web_config['flash_deals']['id'] ?? 0]),
                            'icon' => 'fi fi-rr-bolt',
                            'count' => count($web_config['flash_deals_products']),
                        ] : null,
                        $web_config['discount_product'] > 0 ? [
                            'label' => translate('Discounted Products'),
                            'url' => route('discounted-products'),
                            'icon' => 'fi fi-rr-badge-percent',
                            'count' => $web_config['discount_product'],
                        ] : null,
                        $web_config['clearance_sale_product_count'] > 0 ? [
                            'label' => translate('Clearance Sale'),
                            'url' => route('clearance-sale-products'),
                            'icon' => 'fi fi-rr-tags',
                            'count' => $web_config['clearance_sale_product_count'],
                        ] : null,
                    ])))

                    @if(count($offerMenuItems) === 1)
                        <li>
                            <a class="sh-menu__link" href="{{ $offerMenuItems[0]['url'] }}">
                                {{ $offerMenuItems[0]['label'] }}
                            </a>
                        </li>
                    @elseif(count($offerMenuItems) > 1)
                        <li class="dropdown">
                            <a class="sh-menu__link dropdown-toggle" href="javascript:" data-toggle="dropdown">
                                {{ translate('Offers') }}
                            </a>
                            <div class="text-align-direction dropdown-menu hm-panel dropdown-menu-{{ session('direction') === 'rtl' ? 'right' : 'left' }}">
                                <div class="hm-head">
                                    <span class="hm-head__title">{{ translate('Offers') }}</span>
                                </div>
                                @include('web-views.partials._header-menu-items', ['items' => $offerMenuItems])
                            </div>
                        </li>
                    @endif

                    @if ($web_config['digital_product_setting'] && count($web_config['publishing_houses_header']) == 1)
                        @php($firstPublisherID = is_array($web_config['publishing_houses_header']) && isset($web_config['publishing_houses_header']['id']) ? $web_config['publishing_houses_header']['id'] : $web_config['publishing_houses_header']?->first()?->id)
                        <li class="{{ request('data_from') === 'publishing_house' ? 'is-active' : '' }}">
                            <a class="sh-menu__link"
                               href="{{ route('products', ['data_from' => 'publishing_house', 'publishing_house_id' => $firstPublisherID, 'product_type' => 'digital', 'page' => 1]) }}">
                                {{ translate('Publication House') }}
                            </a>
                        </li>
                    @elseif ($web_config['digital_product_setting'] && count($web_config['publishing_houses_header']) > 1)
                        <li class="dropdown">
                            <a class="sh-menu__link dropdown-toggle" href="javascript:" data-toggle="dropdown">
                                {{ translate('Publication House') }}
                            </a>
                            <div class="text-align-direction dropdown-menu hm-panel dropdown-menu-{{ session('direction') === 'rtl' ? 'right' : 'left' }}">
                                <div class="hm-head">
                                    <span class="hm-head__title">{{ translate('Publication House') }}</span>
                                    <a class="hm-head__link"
                                       href="{{ route('products', ['data_from' => 'publishing_house', 'product_type' => 'digital', 'page' => 1]) }}">
                                        {{ translate('View All') }}
                                        <i class="fi fi-rr-angle-small-right"></i>
                                    </a>
                                </div>
                                <div class="hm-list">
                                    @foreach($web_config['publishing_houses_header']->take(10) as $publishingHouseItem)
                                        @continue($publishingHouseItem['name'] === 'Unknown')
                                        <a class="hm-item"
                                           href="{{ route('products', ['data_from' => 'publishing_house', 'publishing_house_id' => $publishingHouseItem['id'], 'product_type' => 'digital', 'page' => 1]) }}">
                                            <span class="hm-item__body">
                                                <span class="hm-item__label">{{ $publishingHouseItem['name'] }}</span>
                                            </span>
                                            @if($publishingHouseItem['publishing_house_products_count'] > 0)
                                                <span class="hm-item__count">{{ $publishingHouseItem['publishing_house_products_count'] }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endif

                    @if ($businessMode === 'multi')
                        <li class="{{ request()->is('vendors') ? 'is-active' : '' }}">
                            <a class="sh-menu__link" href="{{ route('vendors') }}">{{ translate('All Vendors') }}</a>
                        </li>

                        @if(getWebConfig(name: 'seller_registration'))
                            <li class="dropdown">
                                <a class="sh-menu__link dropdown-toggle" href="javascript:" data-toggle="dropdown">
                                    {{ translate('Vendor zone') }}
                                </a>
                                <div class="text-align-direction dropdown-menu hm-panel dropdown-menu-{{ session('direction') === 'rtl' ? 'right' : 'left' }}">
                                    <div class="hm-head">
                                        <span class="hm-head__title">{{ translate('Vendor zone') }}</span>
                                    </div>
                                    @include('web-views.partials._header-menu-items', ['items' => [
                                        [
                                            'label' => translate('Become a vendor'),
                                            'url' => route('vendor.auth.registration.index'),
                                            'icon' => 'fi fi-rr-shop',
                                            'description' => translate('Open your store and start selling'),
                                        ],
                                        [
                                            'label' => translate('Vendor Login'),
                                            'url' => route('vendor.auth.login'),
                                            'icon' => 'fi fi-rr-sign-in-alt',
                                            'description' => translate('Go to your vendor dashboard'),
                                        ],
                                    ]])
                                </div>
                            </li>
                        @endif
                    @endif
                </ul>

                <div class="sh-nav__aside">
                    @include('layouts.front-end.partials._module-switcher')
                </div>
            </div>
        </div>

        @if(count($categories) > 0)
        <div class="megamenu-wrap">
            <div class="container">
                <div class="category-menu-wrap">
                    <ul class="category-menu">
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('category-products', ['slug' => $category['slug']]) }}">
                                    <span class="d-flex gap-10px justify-content-start align-items-center">
                                        <img loading="lazy" decoding="async" class="aspect-1 rounded-circle" width="20"
                                             src="{{ getStorageImages(path: $category?->icon_full_url, type: 'category') }}"
                                             alt="{{ $category['name'] }}">
                                        <span class="line--limit-2">{{ $category->name }}</span>
                                    </span>
                                </a>
                                @if ($category->childes->count() > 0)
                                    <div class="mega_menu z-2">
                                        @foreach ($category->childes as $subCategory)
                                            <div class="mega_menu_inner">
                                                <h6>
                                                    <a href="{{ route('category-products', ['slug' => $subCategory['slug']]) }}">
                                                        {{ $subCategory->name }}
                                                    </a>
                                                </h6>
                                                @foreach ($subCategory->childes as $subSubCategory)
                                                    <div>
                                                        <a href="{{ route('category-products', ['slug' => $subSubCategory['slug']]) }}">
                                                            {{ $subSubCategory->name }}
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                        <li class="text-center">
                            <a href="{{ route('categories') }}"
                               class="text-primary font-weight-bold justify-content-center text-capitalize view-all-btn-text">
                                <span class="view-btn-text">{{ translate('View All') }}</span>
                                <i class="czi-view-list"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
</header>

@include('layouts.front-end.partials._module-sheet')

<aside class="sh-drawer sh-drawer--narrow d-md-none" id="storefrontMenuDrawer">
    <div class="sh-drawer__head">
        <h6 class="sh-drawer__title"><a href="{{ route('home') }}">{{ translate('Home') }}</a></h6>
        <button class="sh-drawer__close" type="button" data-sh-drawer-close aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross-small"></i>
        </button>
    </div>
    <div class="sh-drawer__body">
        <ul class="sh-tree">
            <li class="sh-tree__section">
                @if(count($categories) > 0)
                <button class="sh-tree__head" type="button" data-sh-tree="#storefrontMenuCategories" aria-expanded="true">
                    {{ translate('Categories') }}
                    <i class="fi fi-rr-angle-down"></i>
                </button>
                <div class="sh-tree__panel is-open" id="storefrontMenuCategories">
                    @foreach ($categories as $categoryIndex => $category)
                        <div class="sh-tree__row">
                            <a class="sh-tree__item" href="{{ route('category-products', ['slug' => $category['slug']]) }}">
                                <img loading="lazy" src="{{ getStorageImages(path: $category?->icon_full_url, type: 'category') }}"
                                     alt="{{ $category['name'] }}">
                                <span>{{ $category['name'] }}</span>
                            </a>
                            @if ($category->childes->count() > 0)
                                <button class="sh-tree__toggle" type="button"
                                        data-sh-tree="#storefrontMenuCategory{{ $categoryIndex }}" aria-expanded="false"
                                        aria-label="{{ $category['name'] }}">
                                    <i class="fi fi-rr-angle-small-right"></i>
                                </button>
                            @endif
                        </div>
                        @if ($category->childes->count() > 0)
                            <div class="sh-tree__panel" id="storefrontMenuCategory{{ $categoryIndex }}">
                                @foreach ($category->childes as $subCategoryIndex => $subCategory)
                                    <div class="sh-tree__row">
                                        <a class="sh-tree__item" href="{{ route('category-products', ['slug' => $subCategory['slug']]) }}">
                                            <span>{{ $subCategory['name'] }}</span>
                                        </a>
                                        @if ($subCategory->childes->count() > 0)
                                            <button class="sh-tree__toggle" type="button"
                                                    data-sh-tree="#storefrontMenuCategory{{ $categoryIndex }}Sub{{ $subCategoryIndex }}"
                                                    aria-expanded="false" aria-label="{{ $subCategory['name'] }}">
                                                <i class="fi fi-rr-angle-small-right"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if ($subCategory->childes->count() > 0)
                                        <div class="sh-tree__panel" id="storefrontMenuCategory{{ $categoryIndex }}Sub{{ $subCategoryIndex }}">
                                            @foreach ($subCategory->childes as $subSubCategory)
                                                <a class="sh-tree__item" href="{{ route('category-products', ['slug' => $subSubCategory['slug']]) }}">
                                                    <span>{{ $subSubCategory['name'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    <a class="sh-tree__item web-text-primary" href="{{ route('categories') }}">
                        <span>{{ translate('View All') }}</span>
                    </a>
                </div>
                @else
                    <a class="sh-tree__head" href="{{ route('categories') }}">{{ translate('Categories') }}</a>
                @endif
            </li>

            @if(getWebConfig(name: 'product_brand'))
                <li class="sh-tree__section">
                    <button class="sh-tree__head" type="button" data-sh-tree="#storefrontMenuBrands" aria-expanded="false">
                        {{ translate('Brands') }}
                        <i class="fi fi-rr-angle-down"></i>
                    </button>
                    <div class="sh-tree__panel" id="storefrontMenuBrands">
                        @foreach ($headerBrands as $brand)
                            @continue(empty($brand['slug']))
                            <a class="sh-tree__item" href="{{ route('brand-products', ['slug' => $brand['slug']]) }}">
                                <span>{{ $brand['name'] }}</span>
                            </a>
                        @endforeach
                        <a class="sh-tree__item web-text-primary" href="{{ route('brands') }}">
                            <span>{{ translate('View All') }}</span>
                        </a>
                    </div>
                </li>
            @endif

            @if ($businessMode === 'multi')
                <li class="sh-tree__section">
                    <a class="sh-tree__head" href="{{ route('vendors') }}">{{ translate('All Vendors') }}</a>
                </li>
                @if(getWebConfig(name: 'seller_registration'))
                    @include('web-views.partials._drawer-vendor-zone', ['panelId' => 'storefrontMenuVendorZone'])
                @endif
            @endif
        </ul>
    </div>
</aside>

<aside class="sh-drawer sh-drawer--end sh-drawer--narrow d-md-none" id="storefrontAccountDrawer">
    <div class="sh-drawer__head">
        <h6 class="sh-drawer__title"><a href="{{ route('home') }}">{{ translate('Home') }}</a></h6>
        <button class="sh-drawer__close" type="button" data-sh-drawer-close aria-label="{{ translate('Close') }}">
            <i class="fi fi-rr-cross-small"></i>
        </button>
    </div>
    <div class="sh-drawer__body sh-account">
        @include('web-views.partials._account-panel', [
            'homeUrl' => route('home'),
            'links' => $showsProfileMenu ? $profileMenu : $accountLinks,
            'showTitle' => false,
            'showAuctionLinks' => $showsProfileMenu,
            'scope' => 'RetailDrawer',
        ])
    </div>
</aside>

@push('script')
    <script>
        "use strict";

        $(".category-menu").find(".mega_menu").parents("li")
            .addClass("has-sub-item").find("> a")
            .append("<i class='czi-arrow-{{ session('direction') === 'rtl' ? 'left' : 'right' }}'></i>");

        (function () {
            const toolbar = document.querySelector('.site-header .sh-tools');
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
