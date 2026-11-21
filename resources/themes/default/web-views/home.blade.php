@extends('layouts.front-end.app')

@section('title', ($isQuickDelivery ?? false) ? 'Quick Delivery' : ($web_config['meta_title'] ?? config('app.name')))

@push('css_or_js')
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/owl.theme.default.min.css') }}">
@endpush

@section('content')

    @if($isQuickDelivery ?? false)
        {{-- Quick Delivery reuses this homepage and the existing Retail product-card partial. --}}
        @php($decimalPointSettings = getWebConfig(name: 'decimal_point_settings') ?: 0)

        <div class="container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ translate('Quick Delivery') }}</h1>
                    <p class="text-muted mb-0">{{ translate('Products available near your delivery address') }}</p>
                </div>

                @if($quickAddress ?? null)
                    <form method="POST" action="{{ route('quick-delivery.change-address') }}" class="d-flex flex-wrap align-items-center gap-2">
                        @csrf
                        <span class="text-muted">{{ $quickAddress['address'] ?? '' }}</span>
                        <button type="submit" class="btn btn-outline-primary btn-sm">{{ translate('Change Address') }}</button>
                    </form>
                @endif
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            @if(!($quickAddress ?? null))
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-3">{{ translate('Choose your delivery address') }}</h2>
                        <form method="POST" action="{{ route('quick-delivery.select-address') }}" id="quickDeliveryAddressForm">
                            @csrf

                            @if(($savedAddresses ?? collect())->isNotEmpty())
                                <div class="mb-3">
                                    <label for="quickSavedAddress" class="form-label">{{ translate('Saved address') }}</label>
                                    <select class="form-control" id="quickSavedAddress" name="address_id">
                                        <option value="">{{ translate('Enter another address') }}</option>
                                        @foreach($savedAddresses as $savedAddress)
                                            <option value="{{ $savedAddress->id }}" @selected(old('address_id') == $savedAddress->id)>
                                                {{ $savedAddress->address }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="quickAddressText" class="form-label">{{ translate('Delivery address') }}</label>
                                <input type="text" id="quickAddressText" name="address" class="form-control" maxlength="500"
                                       placeholder="{{ translate('House, street, area, city') }}"
                                       value="{{ old('address') }}" autocomplete="street-address">
                            </div>

                            {{-- Private input fields used for a GPS-assisted lookup; never shown as text. --}}
                            <input type="hidden" id="quickLatitude" name="latitude">
                            <input type="hidden" id="quickLongitude" name="longitude">

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-secondary" id="quickGetLocation">
                                    {{ translate('Use Current Location') }}
                                </button>
                                <button type="submit" class="btn btn--primary">{{ translate('Show Products') }}</button>
                                <small id="quickLocationMessage" class="text-muted" role="status" aria-live="polite"></small>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif(count($quickProducts ?? []) === 0)
                <div class="alert alert-info" role="status">
                    {{ translate('No Quick Delivery products are available for this address.') }}
                </div>
            @else
                <section class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h4 mb-0">{{ translate('Available Products') }}</h2>
                        <span class="text-muted small">{{ count($quickProducts) }} {{ translate('products') }}</span>
                    </div>
                    <div class="row g-3">
                        @foreach($quickProducts as $product)
                            <div class="col-6 col-md-4 col-xl-3 mb-3">
                                @include('web-views.partials._product-card-2', [
                                    'product' => $product,
                                    'decimal_point_settings' => $decimalPointSettings,
                                ])
                            </div>
                        @endforeach
                    </div>

                    @if(method_exists($quickProducts, 'hasPages') && $quickProducts->hasPages())
                        <div class="mt-3">{{ $quickProducts->links() }}</div>
                    @endif
                </section>
            @endif
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const select = document.getElementById('quickSavedAddress');
                const addressInput = document.getElementById('quickAddressText');
                const locationButton = document.getElementById('quickGetLocation');
                const status = document.getElementById('quickLocationMessage');
                if (select && addressInput) {
                    select.addEventListener('change', function () {
                        if (select.value) addressInput.value = select.options[select.selectedIndex].text.trim();
                    });
                }
                if (!locationButton) return;
                locationButton.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        if (status) status.textContent = 'Your browser does not support location access.';
                        return;
                    }
                    if (status) status.textContent = 'Finding your location…';
                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            document.getElementById('quickLatitude').value = position.coords.latitude;
                            document.getElementById('quickLongitude').value = position.coords.longitude;
                            if (select) select.value = '';
                            if (status) status.textContent = 'Location selected. Enter the delivery address and continue.';
                        },
                        function () {
                            if (status) status.textContent = 'Location unavailable. Enter your delivery address instead.';
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
                    );
                });
            });
        </script>
    @else


            <?php
            $orderSuccessIds = session('order_success_ids');
            $isNewCustomerInSession = session('isNewCustomerInSession');
            session()->forget('order_success_ids');
            session()->forget('isNewCustomerInSession');
            ?>
        @include("web-views.partials._order-success-modal",['orderSuccessIds' => $orderSuccessIds,'isNewCustomerInSession' => $isNewCustomerInSession])

        <div class="__inline-61 d-flex flex-column gap-20">
            @php($decimalPointSettings = !empty(getWebConfig(name: 'decimal_point_settings')) ? getWebConfig(name: 'decimal_point_settings') : 0)

            @include('web-views.partials._home-top-slider',['bannerTypeMainBanner'=>$bannerTypeMainBanner])
            @if ($flashDeal['flashDeal'] && $flashDeal['flashDealProducts'] && count($flashDeal['flashDealProducts']) > 0)
                @include('web-views.partials._flash-deal', ['decimal_point_settings'=>$decimalPointSettings])
            @endif

            @if(function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction') && getWebConfig(name: 'auction_feature_status'))
                @include(auctionThemeView('web-views.partials._auction-home-slider'), ['auctionProducts' => $auctionProducts])
            @endif

            @if ($featuredProductsList->count() > 0 )
                <div class="container">
                    <div class="__inline-62 section-card-margin">
                        <div class="d-flex justify-content-between align-items-baseline px-3 pt-3">
                            <h2 class="feature-product-title font-bold m-0 text-capitalize h5 letter-spacing-0">
                                {{ translate('Featured Products') }}
                            </h2>
                            <div class="text-end">
                                <a class="text-capitalize view-all-btn-text" href="{{ route('featured-products') }}">
                                    <span class="view-btn-text">{{ translate('View All') }}</span>
                                    <i class="czi-view-list"></i>
                                </a>
                            </div>
                        </div>
                        <div class="feature-product">
                            <div class="carousel-wrap p-1">
                                <div class="owl-carousel featured_products_listSlide owl-theme" data-slide-items="{{ count($featuredProductsList) }}">
                                    @foreach($featuredProductsList as $product)
                                        <div>
                                            @include('web-views.partials._feature-product',['product'=>$product, 'decimal_point_settings'=>$decimalPointSettings])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @include('web-views.partials._category-section-home')

            @if(getFeaturedDealsProductList() && (count(getFeaturedDealsProductList()) > 0))
                <section class="featured_deal pb-3">
                    <div class="container">
                        <div class="__featured-deal-wrap bg--light px-0-mobile">
                            <div class="d-flex flex-wrap justify-content-between align-items-sm-start gap-8 mb-xxl-4 mb-3">
                                <div class="w-0 flex-grow-1">
                                    <span class="featured_deal_title font-bold text-dark">{{ translate('Featured Deal')}}</span>
                                    <br>
                                    <span class="text-left">{{ translate('See the latest deals and exciting new offers')}}!</span>
                                </div>
                                <div>
                                    <a class="text-capitalize view-all-btn-text" href="{{ route('featured-deal-products') }}">
                                        <span class="view-btn-text">{{ translate('View All') }}</span>
                                        <i class="czi-view-list"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-carousel owl-theme new-arrivals-product" data-slide-items="{{ count(getFeaturedDealsProductList()) }}">
                                @foreach(personalizeProducts(getFeaturedDealsProductList()) as $key=>$product)
                                    @include('web-views.partials._product-card-1',['product'=>$product, 'decimal_point_settings'=>$decimalPointSettings])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @include('web-views.partials._clearance-sale-products', ['clearanceSaleProducts' => $clearanceSaleProducts])

            @if ($bannerTypeMainSectionBanners->isNotEmpty())
                <div class="container rtl pt-0 px-0 px-md-3">
                    <div class="owl-theme owl-carousel main-section-banner-slider" data-loop="{{ $bannerTypeMainSectionBanners->count() > 1 ? 1 : 0 }}">
                        @foreach ($bannerTypeMainSectionBanners as $mainSectionBanner)
                            <a href="{{ $mainSectionBanner->url }}" target="_blank" class="cursor-pointer d-block">
                                <img loading="lazy" class="d-block footer_banner_img __inline-63" alt="{{ translate('Banner') }}"
                                     src="{{ getStorageImages(path: $mainSectionBanner->photo_full_url, type: 'wide-banner') }}">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @php($businessMode = getWebConfig(name: 'business_mode'))
            @if ($businessMode == 'multi' && count($topVendorsList) > 0)
                @include('web-views.partials._top-sellers')
            @endif

            @include('web-views.partials._deal-of-the-day', ['decimal_point_settings' => $decimalPointSettings])

            <section class="new-arrival-section">

                @if ($newArrivalProducts->count() >0 )
                    <div class="container rtl">
                        <div class="section-header">
                            <h2 class="arrival-title d-block mb-1">
                                <div class="text-capitalize">
                                    {{ translate('New arrivals')}}
                                </div>
                            </h2>
                        </div>
                    </div>
                    <div class="container rtl mb-3 overflow-hidden">
                        <div class="py-2">
                            <div class="new_arrival_product">
                                <div class="carousel-wrap">
                                    <div class="owl-carousel owl-theme new-arrivals-product" data-slide-items="{{ count($newArrivalProducts) }}">
                                        @foreach($newArrivalProducts as $key=> $product)
                                            @include('web-views.partials._product-card-2',['product'=>$product,'decimal_point_settings'=>$decimalPointSettings])
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="container rtl px-0 px-md-3">
                    <div class="row g-3 mx-max-md-0">

                        @if ($bestSellProduct->count() >0)
                            @include('web-views.partials._best-selling')
                        @endif

                        @if ($topRatedProducts->count() >0)
                            @include('web-views.partials._top-rated')
                        @endif
                    </div>
                </div>
            </section>


            @if (count($bannerTypeFooterBanner) > 1)
                <div class="container rtl">
                    <div class="promotional-banner-slider owl-carousel owl-theme">
                        @foreach($bannerTypeFooterBanner as $banner)
                            <a href="{{ $banner['url'] }}" class="d-block" target="_blank">
                                <img loading="lazy" class="footer_banner_img __inline-63"  alt="" src="{{ getStorageImages(path:$banner->photo_full_url, type: 'banner') }}">
                            </a>
                        @endforeach
                    </div>
                </div>
            @elseif (count($bannerTypeFooterBanner) > 0 && count($bannerTypeFooterBanner) == 1)
                <div class="container rtl">
                    <div class="row">
                        @foreach($bannerTypeFooterBanner as $banner)
                            <div class="col-md-6">
                                <a href="{{ $banner['url'] }}" class="d-block" target="_blank">
                                    <img loading="lazy" class="footer_banner_img __inline-63"  alt="" src="{{ getStorageImages(path:$banner->photo_full_url, type: 'banner') }}">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($web_config['brand_setting'] && $brands->count() > 0)
                <section class="container rtl">

                    <div class="section-header align-items-center mb-1">
                        <h2 class="text-black font-bold __text-22px mb-0">
                            <span> {{translate('Brands')}}</span>
                        </h2>
                        <div class="__mr-2px">
                            <a class="text-capitalize view-all-btn-text" href="{{route('brands')}}">
                                <span class="view-btn-text">{{ translate('View All') }}</span>
                                <i class="czi-view-list"></i>
                            </a>
                        </div>
                    </div>

                    <div class="mt-sm-3 mb-0 brand-slider">
                        <div class="owl-carousel owl-theme p-2 brands-slider" data-slide-items="{{ count($brands) }}">
                            @php($brandCount=0)
                            @foreach($brands as $brand)
                                @if($brandCount < 15 && !empty($brand['slug']))
                                    <div class="text-center">
                                        <a href="{{ route('brand-products', ['slug' => $brand['slug']]) }}"
                                           class="__brand-item">
                                            <img loading="lazy" alt="{{ $brand?->image_alt_text ?? translate('Brand') }}"
                                                 src="{{ getStorageImages(path: $brand->image_full_url, type: 'brand') }}">
                                        </a>
                                    </div>
                                @endif
                                @php($brandCount++)
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($homeCategories->count() > 0)
                @foreach($homeCategories as $category)
                    @include('web-views.partials._category-wise-product', ['decimal_point_settings'=>$decimalPointSettings])
                @endforeach
            @endif

            @php($companyReliability = getWebConfig(name: 'company_reliability'))
            @if($companyReliability != null)
                @include('web-views.partials._company-reliability')
            @endif
        </div>

        <span id="direction-from-session" data-value="{{ session()->get('direction') }}"></span>
    @endif {{-- isQuickDelivery / normal Retail homepage --}}
@endsection

@push('script')
    @if($orderSuccessIds ?? false)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('order_successfully');
                var orderModal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                orderModal.show();


                document.querySelectorAll('.copy-order-id').forEach(function(copyBtn) {
                    copyBtn.addEventListener('click', function() {
                        let orderTextEl = null;
                        orderTextEl = this.closest('tr')?.querySelector('.order-id-text');
                        if (!orderTextEl) {
                            orderTextEl = this.parentElement.querySelector('.order-id-text');
                        }
                        const orderText = orderTextEl?.textContent.trim();
                        if (orderText) {
                            navigator.clipboard.writeText(orderText).then(() => {
                                toastMagic.success('Order ID copied successfully!');
                            }).catch(err => {
                                console.warn('Clipboard error:', err);
                                toastMagic.warning('Unable to copy. Clipboard requires HTTPS or localhost.');
                            });
                        }
                    });
                });
                document.getElementById('modal-close-btn').addEventListener('click', function() {
                    setTimeout(() => { orderModal.hide(); }, 600);
                });
            });
        </script>
    @endif

    @if(!($isQuickDelivery ?? false) && Request::is('/') && \Illuminate\Support\Facades\Cookie::has('popup_banner') == false && empty($orderSuccessIds))
        <script>
            $(document).ready(function () {
                window.showPopupBanner && window.showPopupBanner();
            });
        </script>
        @php(\Illuminate\Support\Facades\Cookie::queue('popup_banner', 'off', 1))
    @endif
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/home.js') }}" defer></script>

@endpush

