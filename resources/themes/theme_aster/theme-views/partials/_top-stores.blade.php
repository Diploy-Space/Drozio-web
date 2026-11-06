@php use App\Utils\Helpers; @endphp
<section>
    <div class="container">
        <div class="card">
            <div class="p-3 p-sm-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-3 mb-sm-4">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <h2 class="text-uppercase fw-extra-bold"><span class="text-primary">{{ translate('Top') }}</span> {{ translate('Stores') }}</h2>
                    </div>
                    <div class="swiper-nav d-flex gap-2 gap-lg-4 align-items-center">
                        <a href="{{ route('vendors', ['filter'=>'top-vendors']) }}" class="btn-link text-capitalize">
                            {{ translate('View All') }}
                            <i class="bi bi-chevron-right text-primary"></i>
                        </a>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="swiper-button-prev top-stores-nav-prev position-static rounded-10"></div>
                            <div class="swiper-button-next top-stores-nav-next position-static rounded-10"></div>
                        </div>
                    </div>
                </div>
                <div class="swiper-container">
                    <div class="position-relative">
                        <div class="swiper top-stores-swiper" data-swiper-loop="false" data-swiper-margin="20"
                             data-swiper-pagination-el="null" data-swiper-navigation-next=".top-stores-nav-next"
                             data-swiper-navigation-prev=".top-stores-nav-prev"
                             data-swiper-breakpoints='{"0": {"slidesPerView": "1"}, "768": {"slidesPerView": "2"}, "992": {"slidesPerView": "3"}}'>
                            <div class="swiper-wrapper">
                                @foreach($topVendorsList as $vendorData)
                                    @if($vendorData && $vendorData->products_count > 0)
                                        @php($isInHouseShop = $vendorData->author_type === \App\Services\New\ShopService::AUTHOR_TYPE_ADMIN)
                                        @php($vendorItemType = $isInHouseShop ? 'inhouse' : 'vendor')
                                        @php($isTemporaryClosed = checkVendorAbility(type: $vendorItemType, status: 'temporary_close', vendor: $vendorData))
                                        @php($isOnVacation = !$isTemporaryClosed && checkVendorAbility(type: $vendorItemType, status: 'vacation_status', vendor: $vendorData))
                                        @php($couponCode = $vendorData->coupon_list?->firstWhere('coupon_type', 'free_delivery')?->code ?? ($vendorData->coupon_list[0]['code'] ?? null))
                                        <div class="swiper-slide">
                                            <article class="top-store {{ $isTemporaryClosed || $isOnVacation ? 'top-store--closed' : '' }}">
                                                <a href="{{ route('vendor-shop',['slug'=>$vendorData['slug']]) }}" class="top-store__head">
                                                    <div class="top-store__avatar">
                                                        <img src="{{ getStorageImages(path: $vendorData->image_full_url, type: 'shop') }}"
                                                             class="dark-support img-fit" alt="{{ $vendorData->name }}" loading="lazy">
                                                        @if($isTemporaryClosed)
                                                            <span class="top-store__status">{{ translate('Temporary OFF') }}</span>
                                                        @elseif($isOnVacation)
                                                            <span class="top-store__status">{{ translate('Closed Now') }}</span>
                                                        @endif
                                                    </div>

                                                    <div class="top-store__identity">
                                                        <div class="top-store__name">
                                                            {{ $vendorData->name }}
                                                            @include('theme-views.partials._verified-badge', ['isVerified' => $vendorData->hasVerifiedShopBadge()])
                                                        </div>

                                                        @include('theme-views.partials._rating-display', [
                                                            'rating' => $vendorData->average_rating,
                                                            'count' => $vendorData->review_count,
                                                            'size' => 'xs',
                                                            'reserve' => true,
                                                        ])

                                                        <div class="top-store__count">
                                                            {{ formatCompactNumber($vendorData->products_count) }} {{ translate('Products') }}
                                                        </div>
                                                    </div>

                                                    <i class="bi bi-chevron-right top-store__go"></i>
                                                </a>

                                                <div class="top-store__coupon-slot">
                                                    @if($couponCode)
                                                        <button type="button" class="top-store__coupon coupon-copy" data-copy-coupun="{{ $couponCode }}"
                                                                aria-label="{{ translate('Copy') }}">
                                                            <i class="bi bi-ticket-perforated"></i>
                                                            <span class="top-store__coupon-label">{{ translate('Coupon') }}</span>
                                                            <span class="top-store__coupon-code">{{ $couponCode }}</span>
                                                            <i class="bi bi-copy top-store__coupon-icon"></i>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($vendorData->products)
                                                    <div class="top-store__products">
                                                        @foreach($vendorData->products as $product)
                                                            <a href="{{ route('product',$product['slug']) }}"
                                                               class="store-product d-flex flex-column gap-2 align-items-center">
                                                                <div class="store-product__top border rounded">
                                                                    <span class="store-product__action preventDefault get-quick-view"
                                                                          data-product-id="{{ $product['id'] }}"
                                                                          data-action="{{ route('quick-view') }}">
                                                                        <i class="bi bi-eye fs-12"></i>
                                                                    </span>
                                                                    <img src="{{ getStorageImages(path:$product['thumbnail_full_url'], type: 'product') }}"
                                                                         alt="{{ Str::limit($product['name'], 25) }}" loading="lazy"
                                                                         class="dark-support rounded aspect-1 img-fit w-100">
                                                                </div>
                                                                <div class="product__price d-flex justify-content-center flex-wrap column-gap-2">
                                                                    @if($product['discount'] > 0)
                                                                        <del class="product__old-price">{{ webCurrencyConverter($product['unit_price']) }}</del>
                                                                    @endif
                                                                    <ins class="product__new-price text-dark fw-bold">
                                                                        {{ webCurrencyConverter($product['unit_price']-Helpers::getProductDiscount($product,$product['unit_price'])) }}
                                                                    </ins>
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </article>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if(isset($bannerTypeFooterBanner[1]))
            <div class="col-12 mt-3 d-sm-none">
                <a href="{{ $bannerTypeFooterBanner[1]['url'] }}" class="ad-hover">
                    <img src="{{ getStorageImages(path: $bannerTypeFooterBanner[1]['photo_full_url'], type:'banner') }}" loading="lazy"
                         class="dark-support rounded w-100 aspect-2" alt="{{ translate('Banner') }}">
                </a>
            </div>
        @endif
    </div>
</section>
