@extends('layouts.front-end.app')

@section('title',translate('Shop Page'))

@push('css_or_js')
    @if($shopInfoArray['author_type'] != "admin")
        <meta property="og:image" content="{{ $shopInfoArray['image_full_url']['path'] }}"/>
        <meta property="og:title" content="{{ $shopInfoArray['name'] }} "/>
        <meta property="og:url" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}">
    @else
        <meta property="og:image" content="{{ getStorageImages(path: getInHouseShopConfig(key: 'image_full_url'), type: 'shop') }}"/>
        <meta property="og:title" content="{{ $shopInfoArray['name'] }} "/>
        <meta property="og:url" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}">
    @endif

    @if($shopInfoArray['author_type'] != "admin")
        <meta property="twitter:card" content="{{ $shopInfoArray['image_full_url']['path'] }}"/>
        <meta property="twitter:title" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}"/>
        <meta property="twitter:url" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}">
    @else
        <meta property="twitter:card" content="{{ getStorageImages(path: getInHouseShopConfig(key: 'image_full_url'), type: 'shop') }}"/>
        <meta property="twitter:title" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}"/>
        <meta property="twitter:url" content="{{ route('vendor-shop',['slug' => $shopInfoArray['slug']]) }}">
    @endif

    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@section('content')
    @php($decimalPointSettings = getWebConfig(name: 'decimal_point_settings'))

    <div class="container py-4 __inline-67">
        <div class="rtl">
            <div class="bg-white __shop-banner-main">
                <img class="__shop-page-banner" alt=""
                     src="{{ getStorageImages(path: $shopInfoArray['banner_full_url'], type: 'wide-banner') }}">
                @include('web-views.seller-view.shop-info-card', ['displayClass' => 'd-none d-md-block max-width-500px'])

            </div>
        </div>

        @include('web-views.seller-view.shop-info-card', ['displayClass' => 'd-md-none border mt-3'])

        <form method="POST" action="{{ url()->current() }}" class="product-list-filter">
            <input hidden name="offer_type" value="{{ $data['offer_type'] }}">
            <input hidden name="data_from" value="{{ request('data_from') }}">
            <input hidden name="category_id" value="{{ request('category_id') }}">
            <input hidden name="brand_id" value="{{ request('brand_id') }}">

            @csrf
            @include('web-views.products.partials._product-list-header', [
                    'pageProductsCount' => $products->total(),
                    'searchBarSection' => true,
                    'sortBySection' => true,
                    'showProductsFilter' => true,
                    'shopViewPageHeader' => true,
                    'filterPageType' => 'shop',
            ])

            <div class="py-3 mb-2 mb-md-4 rtl __inline-35" dir="{{ session('direction') }}">
                <div class="row">
                    <aside class="col-lg-3 hidden-xs col-md-3 col-sm-4 SearchParameters __search-sidebar" id="SearchParameters">
                        <div class="cz-sidebar __inline-35 p-4 overflow-hidden" id="shop-sidebar">
                            <div class="cz-sidebar-header p-0">
                                <button class="close ms-auto fs-18-mobile" type="button" data-dismiss="sidebar" aria-label="Close">
                                    <i class="tio-clear"></i>
                                </button>
                            </div>

                            <div class="pb-0 shop-sidebar-scroll">
                                <div class="d-flex gap-3 flex-column">
                                    <h5 class="fs-16 font-weight-bold m-0">{{ translate('Filter By') }}</h5>
                                    <hr>
                                    @include('web-views.products.partials._filter-product-type')
                                    @include('web-views.products.partials._filter-product-sort')
                                    @include('web-views.products.partials._filter-product-price')
                                    @include('web-views.products.partials._filter-product-categories', [
                                        'productCategories' => $categories,
                                        'dataFrom' => 'shop-view',
                                        'shopSlug' => $shopInfoArray['slug'],
                                    ])
                                    @include('web-views.products.partials._filter-product-brands', [
                                        'productBrands' => $activeBrands,
                                        'dataFrom' => 'shop-view',
                                        'shopSlug' => $shopInfoArray['slug'],
                                    ])
                                    @include('web-views.products.partials._filter-publishing-houses', [
                                        'productPublishingHouses' => $shopPublishingHouses,
                                        'dataFrom' => 'shop-view',
                                        'shopSlug' => $shopInfoArray['slug'],
                                    ])
                                    @include('web-views.products.partials._filter-product-authors', [
                                        'productAuthors' => $digitalProductAuthors,
                                        'dataFrom' => 'shop-view',
                                        'shopSlug' => $shopInfoArray['slug'],
                                    ])
                                </div>
                            </div>

                        </div>
                        <div class="sidebar-overlay"></div>
                    </aside>

                    <section class="col-lg-9">
                        <div class="row" id="ajax-products-view">
                            @include('web-views.products._ajax-products', ['products' => $products])
                        </div>
                    </section>
                </div>
            </div>

        </form>

    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered shop-chat-modal" role="document">
            <div class="modal-content">
                <div class="shop-chat-modal__header">
                    <div class="shop-chat-modal__avatar">
                        <img src="{{ getStorageImages(path: $shopInfoArray['image_full_url'], type: 'shop') }}"
                             alt="{{ $shopInfoArray['name'] }}">
                    </div>
                    <div class="shop-chat-modal__heading">
                        <h5 class="shop-chat-modal__title" id="exampleModalLabel">
                            {{ $shopInfoArray['name'] }}
                            @include('web-views.partials._verified-badge', ['isVerified' => $shopInfoArray['is_verified']])
                        </h5>
                        <p class="shop-chat-modal__subtitle">{{ translate('Send Message to vendor') }}</p>
                    </div>
                    <button type="button" class="shop-chat-modal__close" data-dismiss="modal"
                            aria-label="{{ translate('Close') }}">
                        <i class="czi-close"></i>
                    </button>
                </div>
                <form action="{{ route('messages') }}" method="post" id="shop-view-chat-form">
                    @csrf
                    <input value="{{ $shopInfoArray['seller_id'] }}" name="vendor_id" hidden>
                    <div class="shop-chat-modal__body">
                        <label for="shop-chat-message" class="shop-chat-modal__label">{{ translate('Message') }}</label>
                        <textarea id="shop-chat-message" name="message" rows="4" required
                                  class="form-control shop-chat-modal__textarea"
                                  placeholder="{{ translate('Write here') }}..."></textarea>
                    </div>
                    <div class="shop-chat-modal__footer">
                        <a href="{{ route('chat', ['type' => 'vendor']) }}" class="shop-chat-modal__link">
                            <i class="czi-chat"></i>
                            <span>{{ translate('Go to chatbox') }}</span>
                        </a>
                        <button type="submit" class="btn btn--primary shop-chat-modal__send">
                            <span>{{ translate('Send') }}</span>
                            <i class="czi-send"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <span id="products-search-data-backup"
          data-page="{{ request('page') ?? 1 }}"
          data-url="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug']]) }}"
          data-brand="{{ $data['brand_id'] ?? '' }}"
          data-category="{{ $data['category_id'] ?? '' }}"
          data-name="{{ request('search') ?? request('name') }}"
          data-from="{{ request('data_from') }}"
          data-offer-type = "{{ request('offer_type') }}"
          data-product-check="clearance_sale"
          data-sort="{{ request('sort_by') }}"
          data-product-type="{{ request('product_type') ?? 'all' }}"
          data-message="{{ translate('Items found') }}"
          data-publishing-house-id="{{ request('publishing_house_id') }}"
          data-author-id="{{ request('author_id') }}"
          data-offer="{{ request('offer_type') ?? '' }}"
    ></span>

@endsection
@push('script')
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/product-list-filter.js') }}" defer></script>
    <script>
        $('.close-icon').on('click', function () {
            $("#shop-sidebar").toggleClass("show active");
        });

        $('.product-list-filter-input').on('change', function () {
            const inputName = $(this).attr('name');
            const inputValue = $(this).val();
            const baseUrl = window.location.origin + window.location.pathname;
            const newUrl = new URL(baseUrl);
            if (inputValue) {
                newUrl.searchParams.set(inputName, inputValue);
            } else {
                newUrl.searchParams.delete(inputName);
            }
            window.location.href = newUrl.toString();
        });
    </script>
@endpush


