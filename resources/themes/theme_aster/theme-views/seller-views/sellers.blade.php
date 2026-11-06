@extends('theme-views.layouts.app')

@section('title', (request('filter') && request('filter') == 'top-vendors' ? translate('Top Stores') : translate('All Stores')).' | '.$web_config['company_name'].' '.translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-30">
        <div class="container">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row gy-2 align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-1 text-capitalize">
                                {{ (request('filter') && request('filter') == 'top-vendors' ? translate('Top Stores') : translate('All Stores')) }}
                            </h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb fs-12 mb-0">
                                    <li class="breadcrumb-item"><a href="{{route('home')}}">{{translate('Home')}}</a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <a href="{{route('vendors')}}">{{translate('Stores')}}</a></li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-4">
                            <div class="custom_search position-relative float-end">
                                <form action="{{ route('vendors') }}" method="get">
                                    @if(request('filter'))
                                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                                    @endif
                                    <div class="d-flex">
                                        <div
                                            class="select-wrap focus-border border border-end-logical-0 d-flex align-items-center">
                                            <input type="search"
                                                   class="form-control border-0 focus-input search-bar-input"
                                                   name="shop_name" placeholder="{{translate('Shop Name')}}" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                                <div
                                    class="card search-card __inline-13 position-absolute z-999 bg-white top-100 start-0 search-result-box"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="store-grid">
                        @foreach ($vendorsList as $vendor)
                            @php($isInHouseShop = $vendor->author_type === \App\Services\New\ShopService::AUTHOR_TYPE_ADMIN)
                            @php($vendorItemType = $isInHouseShop ? 'inhouse' : 'vendor')
                            @php($isTemporaryClosed = checkVendorAbility(type: $vendorItemType, status: 'temporary_close', vendor: $vendor))
                            @php($isOnVacation = !$isTemporaryClosed && checkVendorAbility(type: $vendorItemType, status: 'vacation_status', vendor: $vendor))

                            <a href="{{ route('vendor-shop',['slug' => $vendor['slug']]) }}"
                               class="store-card {{ $isTemporaryClosed || $isOnVacation ? 'store-card--closed' : '' }}">
                                <div class="store-card__banner">
                                    <img class="img-fit" loading="lazy" alt=""
                                         src="{{ getStorageImages(path: $vendor->banner_full_url, type:'shop-banner') }}">
                                    @if($isTemporaryClosed)
                                        <span class="store-card__status">{{ translate('Temporary OFF') }}</span>
                                    @elseif($isOnVacation)
                                        <span class="store-card__status">{{ translate('Closed Now') }}</span>
                                    @endif
                                </div>

                                <div class="store-card__avatar">
                                    <img class="dark-support img-fit" loading="lazy" alt="{{ $vendor->name }}"
                                         src="{{ getStorageImages(path: $vendor->image_full_url, type:'shop') }}">
                                </div>

                                <div class="store-card__body">
                                    <div class="store-card__name">
                                        {{ $vendor->name }}
                                        @include('theme-views.partials._verified-badge', ['isVerified' => $vendor->hasVerifiedShopBadge()])
                                    </div>

                                    @include('theme-views.partials._rating-display', [
                                        'rating' => $vendor->average_rating,
                                        'count' => $vendor->review_count,
                                        'size' => 'xs',
                                        'align' => 'center',
                                        'reserve' => true,
                                    ])

                                    <div class="store-card__meta">
                                        <span class="store-card__metric">
                                            <span class="store-card__metric-value">{{ formatCompactNumber($vendor['products_count']) }}</span>
                                            {{ translate('Products') }}
                                        </span>
                                        <span class="store-card__metric">
                                            <span class="store-card__metric-value">{{ formatCompactNumber($vendor['review_count']) }}</span>
                                            {{ translate('Reviews') }}
                                        </span>
                                    </div>

                                    <span class="store-card__cta">{{ translate('Visit Shop') }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    @if (count($vendorsList) == 0)
                        <div class="w-100 text-center pt-5">
                            <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-vendor.svg') }}" alt="">
                            <h5 class="text-center text-muted">{{ translate('There is no vendor') }}.</h5>
                        </div>
                    @endif
                    <div class="mt-5">
                        {{ $vendorsList->links() }}
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
