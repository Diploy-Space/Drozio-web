@extends('layouts.front-end.app')

@section('title', (request('filter') && request('filter') == 'top-vendors' ? translate('Top Stores') : translate('All Stores')))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="Brands of {{$web_config['company_name']}} "/>
    <meta property="og:url" content="{{config('app.url')}}">
    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="Brands of {{$web_config['company_name']}}"/>
    <meta property="twitter:url" content="{{config('app.url')}}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@section('content')

    <div class="container mb-md-4 {{Session::get('direction') === "rtl" ? 'rtl' : ''}} __inline-65">
        <div class="bg-primary-light rounded-10 my-4 p-3 p-sm-4" data-bg-img="{{ theme_asset(path: 'public/assets/front-end/img/media/bg.png') }}">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8 col-md-6">
                    <div class="d-flex flex-column gap-1 text-primary">
                        <h4 class="mb-0 text-start fw-bold text-primary text-uppercase">
                            {{ (request('filter') && request('filter') == 'top-vendors' ? translate('Top Stores') : translate('All Stores')) }}
                        </h4>
                        <p class="fs-14 fw-semibold mb-0">{{translate('Find your desired stores and shop your favourite products')}}</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <form action="{{ route('vendors') }}" method="get" class="catalog-search">
                        @if(request('filter'))
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        <i class="czi-search catalog-search__icon"></i>
                        <input class="form-control catalog-search__input" type="search" autocomplete="off"
                               placeholder="{{ translate('Search Store') }}" name="shop_name" value="{{ request('shop_name') }}"
                               aria-label="{{ translate('Search Store') }}">
                        <button type="submit" class="catalog-search__submit">{{ translate('Search') }}</button>
                    </form>
                </div>
            </div>
        </div>

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

                            @include('web-views.products.partials.vendor-partials._filter-product-filter')
                            @include('web-views.products.partials.vendor-partials._filter-product-type')
                            @include('web-views.products.partials.vendor-partials._filter-product-categories', [
                                'productCategories' => $categories,
                                'dataFrom' => request('data_from'),
                            ])
                            @include('web-views.products.partials.vendor-partials._filter-product-brands', [
                                'productBrands' => $activeBrands,
                                'dataFrom' => request('data_from'),
                            ])

                            @if(request('product_type') == 'digital')
                            @include('web-views.products.partials.vendor-partials._filter-publishing-houses', [
                                     'productPublishingHouses' => $web_config['publishing_houses'],
                                     'dataFrom' => request('data_from'),
                                 ])
                            @include('web-views.products.partials.vendor-partials._filter-product-authors', [
                                'productAuthors' => $web_config['digital_product_authors'],
                                'dataFrom' => request('data_from'),
                            ])
                            @endif
                        </div>
                    </div>

                </div>
                <div class="sidebar-overlay"></div>
            </aside>
            <section class="col-lg-9 vendor-list-content">
                <div>
                    @if(count($vendorsList) > 0)
                        <div class="row mx-n2 __min-h-200px">
                            @foreach ($vendorsList as $vendorItem)
                                <div class="col-lg-4 col-md-6 col-sm-12 px-2 pb-4 text-center">
                                    <a href="{{route('vendor-shop',['slug' => $vendorItem['slug']])}}" class="others-store-card text-capitalize">
                                        <div class="overflow-hidden other-store-banner">
                                            <img loading="lazy" class="w-100 h-100 object-cover" alt=""
                                                 src="{{ getStorageImages(path: $vendorItem->banner_full_url, type: 'shop-banner') }}">
                                        </div>
                                        <div class="name-area">
                                            <div class="position-relative">
                                                <div class="overflow-hidden other-store-logo rounded-full">
                                                    <img loading="lazy" class="rounded-full" alt="{{ translate('Store') }}"
                                                         src="{{ getStorageImages(path: $vendorItem->image_full_url, type: 'shop') }}">
                                                </div>
                                            </div>
                                            <div class="info pt-2">
                                                <h5 class="text-start">
                                                    {{ $vendorItem['name'] }}
                                                    @include('web-views.partials._verified-badge', ['isVerified' => $vendorItem->hasVerifiedShopBadge()])
                                                </h5>
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    @if($vendorItem['average_rating'] > 0)
                                                        <span class="store-rating-chip">
                                                            <i class="tio-star"></i>
                                                            <strong>{{ number_format($vendorItem['average_rating'], 1) }}</strong>
                                                            <small>{{ translate('Rating') }}</small>
                                                        </span>
                                                    @else
                                                        <span class="store-rating-chip store-rating-chip-new">{{ translate('New') }}</span>
                                                    @endif
                                                    @include('web-views.partials._store-status-chip', [
                                                        'type' => $vendorItem->author_type === \App\Enums\UserType::ADMIN ? 'inhouse' : 'vendor',
                                                        'vendor' => $vendorItem,
                                                    ])
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-area">
                                            <div class="info-item">
                                                <span class="value">{{ formatCompactNumber($vendorItem['products_count']) }}</span>
                                                <span>{{ translate('Products') }}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="value">{{ formatCompactNumber($vendorItem['review_count']) }}</span>
                                                <span>{{ translate('Reviews') }}</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mx-n2">
                            <div class="col-md-12">
                                <div class="text-center">
                                    {{ $vendorsList->links() }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="d-flex flex-column justify-content-center align-items-center text-center text-muted w-100" style="min-height: 400px;">
                            <div class="my-2">
                                <img alt="" src="{{ theme_asset(path: 'public/assets/front-end/img/media/seller.svg') }}">
                            </div>
                            <h4 class="text-muted">{{ translate('Vendor not available') }}</h4>
                            <p>{{ translate('Sorry no data found related to your search') }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
