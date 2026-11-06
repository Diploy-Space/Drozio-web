<div class="container rtl pt-0 px-0 px-md-3">
    <div class="seller-card">
        <div class="card __shadow h-100">
            <div class="card-body">
                <div class="row d-flex justify-content-between align-items-center">
                    <div class="seller-list-title">
                        <h2 class="font-bold m-0 text-capitalize h5">
                            {{ translate('Top sellers')}}
                        </h2>
                    </div>
                    <div class="seller-list-view-all d-flex align-items-center gap-3">
                        <div class="store-slider-nav d-none d-md-flex align-items-center gap-2">
                            <button type="button" class="store-nav-btn store-prev" aria-label="{{ translate('Previous') }}">
                                <i class="czi-arrow-{{ session('direction') === "rtl" ? 'right' : 'left' }}"></i>
                            </button>
                            <button type="button" class="store-nav-btn store-next" aria-label="{{ translate('Next') }}">
                                <i class="czi-arrow-{{ session('direction') === "rtl" ? 'left' : 'right' }}"></i>
                            </button>
                        </div>
                        <a class="text-capitalize view-all-btn-text" href="{{ route('vendors', ['filter' => 'top-vendors']) }}">
                            <span class="view-btn-text">{{ translate('View All') }}</span>
                            <i class="czi-view-list"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="others-store-slider owl-theme owl-carousel">

                        @foreach ($topVendorsList as $vendorData)
                            <a href="{{route('vendor-shop',['slug'=> $vendorData['slug']])}}" class="others-store-card text-capitalize">
                                <div class="overflow-hidden other-store-banner">
                                    <img loading="lazy" class="w-100 h-100 object-cover" alt="{{ $vendorData->name }}"
                                         src="{{ getStorageImages(path: $vendorData->banner_full_url, type: 'shop-banner') }}">
                                </div>
                                <div class="name-area">
                                    <div class="position-relative">
                                        <div class="overflow-hidden other-store-logo rounded-full">
                                            <img loading="lazy" class="rounded-full" alt="{{ translate('Store') }}"
                                                 src="{{ getStorageImages(path: $vendorData->image_full_url, type: 'shop') }}">
                                        </div>
                                    </div>
                                    <div class="info">
                                        <h3 class="h5">
                                            {{ $vendorData->name }}
                                            @include('web-views.partials._verified-badge', ['isVerified' => $vendorData->hasVerifiedShopBadge()])
                                        </h3>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            @if($vendorData->average_rating > 0)
                                                <span class="store-rating-chip">
                                                    <i class="tio-star"></i>
                                                    <strong>{{ number_format($vendorData->average_rating, 1) }}</strong>
                                                    <small>{{ translate('Rating') }}</small>
                                                </span>
                                            @else
                                                <span class="store-rating-chip store-rating-chip-new">{{ translate('New') }}</span>
                                            @endif
                                            @include('web-views.partials._store-status-chip', [
                                                'type' => $vendorData->author_type === \App\Enums\UserType::ADMIN ? 'inhouse' : 'vendor',
                                                'vendor' => $vendorData,
                                            ])
                                        </div>
                                    </div>
                                </div>
                                <div class="info-area">
                                    <div class="info-item">
                                        <span class="value">
                                            {{$vendorData->review_count < 1000 ? $vendorData->review_count : number_format($vendorData->review_count/1000 , 1).'K'}}
                                        </span>
                                        <span>{{ translate('Ratings') }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="value">
                                            {{$vendorData->products_count < 1000 ? $vendorData->products_count : number_format($vendorData->products_count/1000 , 1).'K'}}
                                        </span>
                                        <span>{{ translate('Products') }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
