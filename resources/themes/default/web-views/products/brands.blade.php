@extends('layouts.front-end.app')

@section('title', translate('All Brands'))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="Brands of {{$web_config['company_name']}} "/>
    <meta property="og:url" content="{{config('app.url')}}">
    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="Brands of {{$web_config['company_name']}}"/>
    <meta property="twitter:url" content="{{ config('app.url') }}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@section('content')

    <div class="container pb-3 mb-2 mb-md-4 rtl text-align-direction">
        <div class="bg-primary-light rounded-10 my-4 p-3 p-sm-4"
             data-bg-img="{{ theme_asset(path: 'public/assets/front-end/img/media/bg.png') }}">
             <div class="row align-items-center g-3">
                <div class="col-xl-8 col-lg-7 col-md-6">
                    <div class="d-flex flex-column gap-1 text-primary">
                        <h4 class="mb-0 text-start fw-bold text-primary text-uppercase">
                            {{ translate('Brands') }}
                        </h4>
                        <p class="fs-14 fw-semibold mb-0">
                            {{translate('Find your favourite brands and products')}}
                        </p>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 col-md-6">
                    <form action="{{ route('brands') }}" method="GET" class="catalog-search">
                        <i class="czi-search catalog-search__icon"></i>
                        <input class="form-control catalog-search__input" type="search" autocomplete="off"
                               placeholder="{{ translate('Search Brands') }}" name="search" value="{{ request('search') }}"
                               aria-label="{{ translate('Search Brands') }}">
                        <button type="submit" class="catalog-search__submit">{{ translate('Search') }}</button>
                    </form>
                </div>
            </div>
        </div>

        @if(count($brands) > 0)
            <div class="brand-grid">
                @foreach($brands as $brand)
                    <a href="{{ route('brand-products', ['slug' => $brand['slug']]) }}" class="brand-card">
                        <div class="brand-card__logo">
                            <img loading="lazy" alt="{{ $brand->image_alt_text ?? $brand->name }}"
                                 src="{{ getStorageImages(path: $brand->image_full_url, type: 'brand') }}">
                        </div>
                        <div class="brand-card__body">
                            <div class="brand-card__name">{{ $brand->name }}</div>
                            <span class="brand-card__count">
                                <span class="brand-card__count-value">{{ formatCompactNumber($brand->brand_products_count) }}</span>
                                {{ translate('Products') }}
                            </span>
                            <span class="brand-card__cta">{{ translate('View Products') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="d-flex justify-content-center align-items-center pt-3">
                <div class="d-flex flex-column justify-content-center align-items-center gap-3">
                    <img src="{{ dynamicAsset(path: 'public/assets/front-end/img/empty-icons/empty-brand.svg') }}"
                         alt="{{ translate('Brand') }}" class="img-fluid" width="100">
                    <h5 class="text-muted fs-14 font-semi-bold text-center">{{ translate('There is no brands') }}</h5>
                </div>
            </div>
        @endif


        <div class="row mx-n2">
            <div class="col-md-12">
                <div class="text-center">
                    {!! $brands->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{theme_asset(path: 'public/assets/front-end/vendor/nouislider/distribute/nouislider.min.js')}}" defer></script>
@endpush
