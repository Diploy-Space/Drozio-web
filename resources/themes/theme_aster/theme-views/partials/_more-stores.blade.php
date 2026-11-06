<section>
    <div class="container">
        @if(auth('customer')->check() && count($order_again)>0)
            <div class="bg-primary-light rounded p-3 d-sm-none mb-4">
                <h3 class="text-primary mb-3 mt-2 text-capitalize">{{ translate('Order again') }}</h3>
                <p>{{ translate('Want to order your usuals') }}
                    ? {{ translate('Just reorder from your previous orders').'.' }}</p>
                <div class="d-flex flex-wrap gap-3 custom-scrollbar height-26-5-rem">
                    @foreach($order_again as $order)
                        <div class="card rounded-10 flex-grow-1">
                            <div class="p-3">
                                <h6 class="fs-12 text-primary mb-1">
                                    @if($order['order_status'] =='processing')
                                        {{ translate('Packaging') }}
                                    @elseif($order['order_status'] =='failed')
                                        {{ translate('Failed to deliver') }}
                                    @elseif($order['order_status'] == 'all')
                                        {{ translate('All') }}
                                    @else
                                        {{ translate(str_replace('_',' ',$order['order_status'])) }}
                                    @endif
                                </h6>
                                <div
                                    class="fs-10">{{ translate('On') }} {{date('d M Y',strtotime($order['updated_at']))}}</div>
                                <div class="bg-light my-2 rounded-10 p-4">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        @foreach($order['details']->take(3) as $key=>$detail)
                                            <div>
                                                <img width="42" loading="lazy" alt="{{ translate('Product') }}" class="dark-support rounded"
                                                     src="{{ getStorageImages(path: $detail?->productAllStatus?->thumbnail_full_url, type: 'product') }}">
                                            </div>
                                        @endforeach

                                        @if(count($order['details']) > 3)
                                            <h6 class="fw-medium fs-12 text-center">+{{ count($order['details'])-3 }}
                                                <br>
                                                <a href="{{ route('account-order-details', ['id'=>$order['id']]) }}">{{ translate('More') }}</a>
                                            </h6>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="">
                                        <h6 class="fs-10 mb-2">{{ translate('Order ID').':'. '#' }}{{ $order['id'] }}</h6>
                                        <h6>{{ translate('Final total').':' }}{{ webCurrencyConverter($order['order_amount']) }}</h6>
                                    </div>
                                    @if($order?->order_status=='delivered' &&  $order?->order_type == 'default_type')
                                        <a href="javascript:" data-order-id="{{ $order['id'] }}" data-action="{{ route('cart.order-again') }}"
                                           class="btn btn-primary order-again">{{ translate('Order again') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="d-sm-none mb-4">
                @if($bannerTypeSidebarBanner)
                    <a href="{{ $bannerTypeSidebarBanner['url'] }}">
                        <img
                            src="{{ getStorageImages(path: $bannerTypeSidebarBanner['photo_full_url'], type:'banner') }}"
                            alt="" class="dark-support rounded w-100">
                    </a>
                @else
                    <img src="{{ theme_asset('assets/img/top-side-banner-placeholder.png') }}"
                         class="dark-support rounded w-100" alt="">
                @endif
            </div>
        @endif
        @if(count($moreVendors ?? []) > 0)
        <div class="pb-3">
            <div class="">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                    <h2>{{ translate('More stores') }}</h2>
                    <a href="{{ route('vendors') }}" class="btn-link">{{ translate('View All') }}
                        <i class="bi bi-chevron-right text-primary"></i></a>
                </div>
                <div class="d-sm-none">
                    <div class="table-responsive hide-scrollbar">
                        <div class="d-flex gap-3 {{ count($moreVendors) > 2 ? 'justify-content-between' : '' }} store-list">
                            @foreach($moreVendors as $seller)
                                @if($seller?->shop)
                                    <a href="{{ route('vendor-shop',['slug'=>$seller?->shop?->slug]) }}"
                                       class="store-product d-flex flex-row align-items-center gap-3 p-2 border rounded bg-white shadow-sm">
                                        <div class="position-relative">
                                            <div class="avatar rounded-circle hover-zoom-in">
                                                <img class="dark-support img-fit rounded-circle img-w-h-80 border"
                                                     src="{{ getStorageImages(path: $seller?->shop?->image_full_url, type:'shop') }}"
                                                     alt="{{ $seller?->shop?->name }}"
                                                     loading="lazy">
                                            </div>
                                            @if(checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $seller->shop))
                                                <span class="temporary-closed position-absolute rounded-circle text-center fs-12" style="--size: 80px;">
                                                <span>{{ translate('Temporary OFF') }}</span>
                                            </span>
                                            @elseif(checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $seller->shop))
                                            <span class="temporary-closed position-absolute rounded-circle text-center fs-12" style="--size: 80px;">
                                                <span>{{ translate('Closed Now') }}</span>
                                            </span>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-column justify-content-center gap-1 min-w-130">
                                            <h5 class="line-clamp-1 mb-1">
                                                {{ $seller?->shop?->name }}
                                                @include('theme-views.partials._verified-badge', ['isVerified' => $seller->hasVerifiedBadge()])
                                            </h5>
                                            <div class="text-muted line-clamp-1">
                                                {{ $seller?->product_count }} {{ translate('Products') }}
                                            </div>
                                            @if($seller->average_rating != 0)
                                                <div class="d-flex gap-2 align-items-center mt-1">
                                                    @include('theme-views.partials._rating-display', [
                                                        'rating' => $seller->average_rating,
                                                        'count' => $seller->review_count,
                                                        'size' => 'sm',
                                                    ])
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="d-none d-sm-block">
                    <div class="row g-3 store-list">
                        @foreach($moreVendors as $seller)
                            @if($seller?->shop)
                            <div class="col-sm-6 col-lg-3">
                                <a href="{{ route('vendor-shop',['slug'=>$seller?->shop?->slug]) }}"
                                   class="store-product d-flex flex-row align-items-center gap-3 p-10px border rounded bg-white shadow-sm">
                                    <div class="position-relative">
                                        <div class="avatar rounded-circle hover-zoom-in" style="--size: 80px;">
                                            <img class="dark-support img-fit rounded-circle img-w-h-80 border border-black-50"
                                                 src="{{ getStorageImages(path: $seller?->shop?->image_full_url, type:'shop') }}"
                                                 alt="{{ $seller?->shop?->name }}"
                                                 loading="lazy">
                                        </div>
                                        @if(checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $seller->shop))
                                            <span class="temporary-closed position-absolute rounded-circle text-center fs-12" style="--size: 80px;">
                                            <span>{{ translate('Temporary OFF') }}</span>
                                        </span>
                                        @elseif(checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $seller->shop))
                                        <span class="temporary-closed position-absolute rounded-circle text-center fs-12" style="--size: 80px;">
                                            <span>{{ translate('Closed Now') }}</span>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-column justify-content-center gap-1">
                                        <h5 class="line-clamp-1 mb-1">
                                            {{ $seller?->shop?->name }}
                                            @include('theme-views.partials._verified-badge', ['isVerified' => $seller->hasVerifiedBadge()])
                                        </h5>
                                        <div class="text-muted line-clamp-1">
                                            {{ $seller?->product_count }} {{ translate('Products') }}
                                        </div>
                                        @if($seller->average_rating != 0)
                                            <div class="d-flex gap-2 align-items-center">
                                                @include('theme-views.partials._rating-display', [
                                                    'rating' => $seller->average_rating,
                                                    'count' => $seller->review_count,
                                                    'size' => 'sm',
                                                ])
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
