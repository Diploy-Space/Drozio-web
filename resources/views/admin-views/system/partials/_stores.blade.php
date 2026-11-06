<div class="card card-body">
    <h3 class="mb-3 fw-bold fs-16">{{ translate('Stores') }}</h3>
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card border h-100">
                <div class="card-header border-0 shadow-sm d-flex align-items-center justify-content-between gap-3">
                    <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                        <img loading="lazy" width="16"
                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/most-popular.png') }}" alt="">
                        {{ translate('Most Popular Stores') }}
                    </h4>
                    @if($topVendorListByWishlist && count($topVendorListByWishlist) > 0)
                        <a href="{{ route('admin.people.vendors.vendor-list', ['sort_by' => 'most-favorite']) }}"
                           class="fw-semibold text-primary">
                            {{ translate('View All') }}
                        </a>
                    @endif
                </div>
                <div class="card-body min-h-260 max-h-460 overflow-y-auto">
                    @if($topVendorListByWishlist && count($topVendorListByWishlist) > 0)
                        <div class="row g-3">
                            @foreach($topVendorListByWishlist as $vendorItem)
                                    <div class="col-sm-6">
                                        <a href="{{ route('admin.people.vendors.view', $vendorItem['id']) }}" class="store-tile">
                                            <div class="store-tile__rank">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                            <img loading="lazy" class="store-tile__avatar" alt=""
                                                 src="{{ getStorageImages(path: $vendorItem->shop->image_full_url, type:'backend-basic') }}">
                                            <div class="store-tile__info">
                                                <div class="d-flex align-items-center gap-1 min-w-0 mb-1">
                                                    <div class="store-tile__name mb-0 min-w-0">{{ $vendorItem->shop->name ?? translate('Not exist') }}</div>
                                                    @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $vendorItem->hasVerifiedBadge(), 'size' => 14])
                                                </div>
                                                <div class="store-tile__metric store-tile__metric--favorite">
                                                    <i class="fi fi-sr-heart"></i>
                                                    <span class="store-tile__metric-label">{{ translate('Wishlists') }}:</span>
                                                    <span class="store-tile__metric-value">{{ number_format($vendorItem['wishlist_count'] ?? 0) }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                            @endforeach
                        </div>
                    @else
                        <div class="d-flex justify-content-center align-items-center h-100">
                            @include('layouts.admin.partials._empty-state', [
                                'text' => 'No stores available',
                                'image' => 'store',
                                'width' => 45
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border h-100">
                <div class="card-header border-0 shadow-sm d-flex align-items-center justify-content-between gap-3">
                    <h4 class="d-flex align-items-center text-capitalize gap-10 mb-0">
                        <img loading="lazy" width="16" src="{{ dynamicAsset(path: 'public/assets/back-end/img/top-selling.png') }}" alt="">
                        {{ translate('Top selling stores') }}
                    </h4>
                    @if($topVendorByEarning && count($topVendorByEarning) > 0)
                        <a href="{{ route('admin.people.vendors.vendor-list', ['sort_by' => 'orders_count']) }}" class="fw-semibold text-primary">
                            {{ translate('View All') }}
                        </a>
                    @endif
                </div>

                <div class="card-body min-h-260 max-h-460 overflow-y-auto">
                    @if($topVendorByEarning && count($topVendorByEarning) > 0)
                        <div class="row g-3">
                            @foreach($topVendorByEarning as $vendor)
                                    <div class="col-sm-6">
                                        <a href="{{ route('admin.people.vendors.view', $vendor['id']) }}" class="store-tile">
                                            <div class="store-tile__rank">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                            <img loading="lazy" class="store-tile__avatar" alt=""
                                                 src="{{ getStorageImages(path: $vendor->shop->image_full_url, type:'backend-basic') }}">
                                            <div class="store-tile__info">
                                                <div class="d-flex align-items-center gap-1 min-w-0 mb-1">
                                                    <div class="store-tile__name mb-0 min-w-0">{{ $vendor->shop['name'] ?? translate('Not exist') }}</div>
                                                    @include('admin-views.vendor.partials._verified-badge', ['isVerified' => $vendor->hasVerifiedBadge(), 'size' => 14])
                                                </div>
                                                <div class="store-tile__metric store-tile__metric--earning">
                                                    <i class="fi fi-sr-shopping-cart"></i>
                                                    <span class="store-tile__metric-label">{{ translate('Earning') }}:</span>
                                                    <span class="store-tile__metric-value">{{ setCurrencySymbol(amount: currencyConverter(amount: $vendor['total_earning_sum'])) }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                            @endforeach
                        </div>
                    @else
                        <div class="d-flex justify-content-center align-items-center h-100">
                            @include('layouts.admin.partials._empty-state', [
                                'text' => 'No stores available',
                                'image' => 'store',
                                'width' => 45
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
