@php
    use App\Utils\Helpers;
    use Illuminate\Support\Str;
    $isAuctionSurface = false;
    $categories = $headerCategories;
    $brands = $headerBrands;
    $homeRoute = route('home');
    $productsRoute = route('products');
    $categoryRouteName = 'category-products';
    $searchFieldName = 'product_name';
    $searchPlaceholder = translate('Search for retail products').' ...';
    $searchSuggestionUrl = route('product-search');
    $storefrontShoppingMode = activeStorefrontBusinessModule();
    $currentLanguageCode = Helpers::default_lang();
    $vendorRegistrationVisible = $web_config['business_mode'] == 'multi' && $web_config['seller_registration'];
    $isCustomerLoggedIn = auth('customer')->check();
@endphp

@include('theme-views.layouts.partials._header-announcement')

<header class="header">
    <div class="header-bar header-desktop-block">
        <div class="container">
            <div class="header-bar__inner d-flex align-items-center gap-4">
                <div class="logo-area d-flex align-items-center gap-2">
                    <a class="logo system-logo-element" href="{{ $homeRoute }}">
                        <img class="dark-support svg h-45" alt="{{ translate('Logo') }}"
                             src="{{ getStorageImages(path: $web_config['web_logo'], type:'logo') }}">
                    </a>

                </div>

                @include('theme-views.layouts.partials._header-search')

                <div class="header-bar__end d-flex align-items-center justify-content-end gap-3">
                    <ul class="list-unstyled list-separator header-actions mb-0">
                        <li class="header-desktop-block">
                            @if(auth('customer')->check())
                                <a href="{{ route('product-compare.index') }}" class="position-relative">
                                    <i class="bi bi-repeat fs-18"></i>
                                    <span class="count compare_list_count_status">
                                        {{ session()->has('compare_list') ? count(session('compare_list')) : 0 }}
                                    </span>
                                </a>
                            @else
                                <a href="javascript:" class="position-relative" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <i class="bi bi-repeat fs-18"></i>
                                </a>
                            @endif
                        </li>
                        <li class="header-desktop-block" id="wishlist_items" data-header-wishlist-url="{{ route('wishlists.header') }}">
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
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="header-main love-sticky py-2 py-lg-0">
        <div class="container">
            @include('theme-views.layouts.partials._header-drawer')
            <div class="aside-overlay"></div>

            @include('theme-views.layouts.partials._header-mobile-bar', [
                'accountOrders' => ['url' => route('account-oder'), 'icon' => 'bi-box-seam-fill', 'label' => translate('Orders')],
            ])

            <div class="header-desktop-flex justify-content-between gap-3 align-items-center position-relative">
                <div class="d-flex align-items-center gap-3">
                    @include('theme-views.layouts.partials._header-category-menu')
                    <div class="nav-wrapper">
                        <ul class="nav main-menu align-items-center header-desktop-flex flex-nowrap">
                            <li class="{{ request()->is('/') ? 'active' : '' }}">
                                <a href="{{ $homeRoute }}">{{ translate('Home') }}</a>
                            </li>

                                @if(($headerFeaturedDealCount ?? 0) > 0 || ($web_config['flash_deals'] && count($web_config['flash_deals_products']) > 0) || $web_config['discount_product'] > 0)
                                    <li>
                                        <span class="cursor-pointer no-follow-link" ref="nofollow">{{ translate('Offers') }}</span>
                                        <ul class="sub-menu">
                                            @if(($headerFeaturedDealCount ?? 0) > 0)
                                                <li>
                                                    <a class="text-capitalize" href="{{ route('featured-deal-products') }}">{{ translate('Featured Deal') }}</a>
                                                </li>
                                            @endif
                                            @if($web_config['flash_deals'] && count($web_config['flash_deals_products']) > 0)
                                                <li>
                                                    <a class="text-capitalize" href="{{ route('flash-deals',['id' => $web_config['flash_deals']['id'] ?? 0]) }}">{{ translate('Flash deal') }}</a>
                                                </li>
                                            @endif
                                            @if ($web_config['discount_product'] > 0)
                                                <li>
                                                    <a class="gap-2 d-flex align-items-center text-capitalize" href="{{ route('discounted-products') }}">
                                                        <span>{{ translate('Discounted Products') }}</span>
                                                        <span><i class="bi bi-patch-check-fill text-warning"></i></span>
                                                    </a>
                                                </li>
                                            @endif
                                            @if($web_config['clearance_sale_product_count'] > 0)
                                                <li>
                                                    <a class="gap-2 d-flex align-items-center" href="{{ route('clearance-sale-products') }}">
                                                        <span>{{ translate('Clearance Sale') }}</span>
                                                        <span><i class="bi bi-patch-check-fill text-warning"></i></span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif

                                @if($web_config['business_mode'] == 'multi')
                                    <li>
                                        <span class="cursor-pointer no-follow-link" ref="nofollow">{{ translate('Stores') }}</span>
                                        <div class="sub-menu megamenu p-3 bs-dropdown-min-width--max-content">
                                            <div class="d-flex gap-1">
                                                <div>
                                                    <div class="column-2 row-gap-3">
                                                        <a href="{{ route('vendor-shop', ['slug' => $headerInHouseShop?->slug]) }}"
                                                           class="media gap-3 align-items-center border-bottom">
                                                            <div class="avatar rounded size-2-5rem">
                                                                <img loading="lazy" alt="{{ translate('Image') }}"
                                                                     src="{{ getStorageImages(path: $headerInHouseShop?->image_full_url, type:'shop') }}"
                                                                     class="img-fit rounded dark-support overflow-hidden">
                                                            </div>
                                                            <div class="media-body text-truncate width--7rem" title="{{ $headerInHouseShop?->name }}">
                                                                {{ Str::limit($headerInHouseShop?->name, 14) }}
                                                                @include('theme-views.partials._verified-badge', ['isVerified' => isInHouseShopVerified(), 'size' => 14])
                                                            </div>
                                                        </a>
                                                        @foreach($web_config['shops'] as $shop)
                                                            <a href="{{ route('vendor-shop', ['slug' => $shop['slug']]) }}"
                                                               class="media gap-3 align-items-center border-bottom">
                                                                <div class="avatar rounded size-2-5rem">
                                                                    <img loading="lazy" alt="{{ translate('Image') }}"
                                                                         src="{{ getStorageImages(path: $shop->image_full_url, type: 'shop') }}"
                                                                         class="img-fit rounded dark-support overflow-hidden">
                                                                </div>
                                                                <div class="media-body text-truncate width--7rem" title="{{ $shop->name }}">
                                                                    {{ Str::limit($shop->name, 14) }}
                                                                    @include('theme-views.partials._verified-badge', ['isVerified' => $shop->hasVerifiedShopBadge(), 'size' => 14])
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                    <div class="d-flex">
                                                        <a href="{{ route('vendors') }}" class="fw-bold text-primary d-flex justify-content-center">
                                                            {{ translate('View All').'...' }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div>
                                                    <a href="javascript:">
                                                        <img width="277" src="{{ theme_asset('assets/img/media/super-market.webp') }}" class="dark-support" alt="{{ translate('Image') }}"/>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                @if($web_config['brand_setting'])
                                    <li>
                                        @if(count($brands) > 0)
                                        <span class="cursor-pointer no-follow-link" ref="nofollow">{{ translate('Brands') }}</span>
                                        <div class="sub-menu megamenu p-3 bs-dropdown-min-width--max-content">
                                            <div class="d-flex gap-4">
                                                <div class="column-2">
                                                    @foreach($brands->take(9) as $brand)
                                                        <a href="{{ route('brand-products', ['slug' => $brand['slug']]) }}"
                                                           class="media gap-3 align-items-center border-bottom">
                                                            <div class="avatar rounded-circle size-1-25rem">
                                                                <img class="img-fit rounded-circle dark-support"
                                                                     src="{{ getStorageImages(path: $brand->image_full_url, type: 'brand') }}"
                                                                     loading="lazy" alt="{{ $brand?->image_alt_text ?? translate('Brand') }}"/>
                                                            </div>
                                                            <div class="media-body text-truncate width--7rem">{{ $brand->name }}</div>
                                                        </a>
                                                    @endforeach
                                                    <div class="d-flex">
                                                        <a href="{{ route('brands') }}" class="fw-bold text-primary d-flex justify-content-center">
                                                            {{ translate('View All').'...' }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                            <a href="{{ route('brands') }}">{{ translate('Brands') }}</a>
                                        @endif
                                    </li>
                                @endif

                                @if ($web_config['digital_product_setting'] && count($web_config['publishing_houses_header']) == 1)
                                    <li>
                                        <a href="{{ route('products',['data_from' => 'publishing_house', 'publishing_house_id' => 0, 'product_type' => 'digital', 'page' => 1]) }}">
                                            {{ translate('Publication House') }}
                                        </a>
                                    </li>
                                @elseif ($web_config['digital_product_setting'] && count($web_config['publishing_houses_header']) > 1)
                                    <li>
                                        <a class="cursor-pointer" href="{{ route('products', ['product_type' => 'digital', 'page' => 1]) }}">
                                            {{ translate('Publication House') }}
                                        </a>
                                        <div class="sub-menu megamenu p-3 bs-dropdown-min-width--max-content">
                                            <div class="d-flex gap-4">
                                                <div class="column-2">
                                                    @foreach($web_config['publishing_houses_header'] as $publishingHouseItem)
                                                        @continue($publishingHouseItem['name'] == 'Unknown')
                                                        <a href="{{ route('products',['data_from' => 'publishing_house', 'publishing_house_id'=> $publishingHouseItem['id'], 'product_type' => 'digital', 'page' => 1]) }}"
                                                           class="media gap-3 align-items-center border-bottom">
                                                            <div class="media-body text-truncate width--7rem">{{ $publishingHouseItem['name'] }}</div>
                                                        </a>
                                                    @endforeach
                                                    <div class="d-flex">
                                                        <a href="{{ route('products', ['data_from' => 'publishing_house', 'product_type' => 'digital', 'page' => 1]) }}"
                                                           class="fw-bold text-primary d-flex justify-content-center">
                                                            {{ translate('View All').'...' }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                            @if($web_config['header_banner'])
                                <li class="header-menu-cta">
                                    <a href="{{ $web_config['header_banner']['url'] }}" class="header-promo">
                                        <img width="180" loading="lazy" class="dark-support img-fit" alt="{{ translate('Image') }}"
                                             src="{{ getStorageImages(path: $web_config['header_banner']['photo_full_url'], type:'wide-banner') }}">
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="header-main__end d-flex align-items-center justify-content-end gap-3">
                    @if($storefrontShoppingMode)
                        @include('theme-views.layouts.partials._module-switcher')
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>


@push('script')
    <script>
        $(document).on('click', '[data-module-cart-close]', function () {
            $(this).closest('.module-cart').addClass('is-dismissed');
        });

        $(document).on('mouseleave', '.module-cart', function () {
            $(this).removeClass('is-dismissed');
        });
    </script>
@endpush
