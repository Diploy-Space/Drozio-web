<div class="container">
    <div class="rounded ov-hidden mb-3">
        @if(!$isInHouseShop)
            <div class="store-banner dark-support bg-badge overflow-hidden" data-bg-img="">
                <img src="{{ getStorageImages(path: $shopInfoArray['banner_full_url'], type:'shop-banner') }}"
                     class="w-100" alt="">
            </div>
        @else
            @php($banner = getWebConfig(name: 'shop_banner'))
            <div class="store-banner dark-support bg-badge overflow-hidden" data-bg-img="">
                <img class="w-100" alt="" src="{{ getStorageImages(path: $banner, type: 'shop-banner') }}">
            </div>
        @endif
        <div class="bg-primary-light p-3">
            <div class="d-flex gap-4 flex-wrap">
                @if(!$isInHouseShop)
                    <div class="media gap-3">
                        <div class="avatar rounded store-avatar overflow-hidden">
                            <div class="position-relative">
                                <img src="{{ getStorageImages(path:$shopInfoArray['image_full_url'], type:'shop') }}"
                                     class="dark-support rounded img-fit" alt="">
                                @if(checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $shopInfoArray))
                                    <span class="temporary-closed position-absolute">
                                        <span class="text-center px-1">{{ translate('Temporary OFF') }}</span>
                                    </span>
                                @elseif(checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $shopInfoArray))
                                    <span class="temporary-closed position-absolute">
                                        <span class="text-center px-1">{{ translate('Closed Now') }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="media-body d-flex flex-column gap-2">
                            <h4 class="max-w-280px">
                                {{ $shopInfoArray['name']}}
                                @include('theme-views.partials._verified-badge', ['isVerified' => $isShopVerified])
                            </h4>
                            <div class="d-flex gap-2 align-items-center">
                                @include('theme-views.partials._rating-display', [
                                    'rating' => $shopInfoArray['average_rating'],
                                    'count' => 0,
                                    'size' => 'md',
                                    'showEmpty' => true,
                                ])
                            </div>
                            <ul class="list-unstyled list-inline-dot fs-12">
                                <li>{{ $shopInfoArray['total_review']}} {{ translate('Ratings') }} </li>
                                <li>{{ $shopInfoArray['total_order']}} {{ translate('Orders') }} </li>
                                @php($minimumOrderAmount=getWebConfig(name: 'minimum_order_amount_status'))
                                @php($minimumOrderAmountBySeller=getWebConfig(name: 'minimum_order_amount_by_seller'))
                                @if ($minimumOrderAmount ==1 && $minimumOrderAmountBySeller == 1 && $shopInfoArray['minimum_order_amount'] > 0)
                                    <li>{{ webCurrencyConverter($shopInfoArray['minimum_order_amount']) }} {{ translate('Minimum Order Amount') }} </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @else
                    <div class="media gap-3">
                        <div class="avatar rounded store-avatar overflow-hidden">
                            <div class="position-relative">
                                <img class="dark-support rounded img-fit" alt=""
                                     src="{{ getStorageImages(path:getInHouseShopConfig(key:'image_full_url'), type:'shop') }}">

                                @if(checkVendorAbility(type: 'inhouse', status: 'temporary_close', vendor: $shopInfoArray))
                                    <span class="temporary-closed position-absolute px-1">
                                        <span class="text-center px-1">{{ translate('Temporary OFF') }}</span>
                                    </span>
                                @elseif(checkVendorAbility(type: 'inhouse', status: 'vacation_status', vendor: $shopInfoArray))
                                    <span class="temporary-closed position-absolute px-1">
                                        <span class="text-center px-1">{{ translate('Closed Now') }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="media-body d-flex flex-column gap-2">
                            <h4 class="max-w-280px">{{ getInHouseShopConfig(key:'name')}}</h4>
                            <div class="d-flex gap-2 align-items-center">
                                @include('theme-views.partials._rating-display', [
                                    'rating' => $shopInfoArray['average_rating'],
                                    'count' => 0,
                                    'size' => 'md',
                                    'showEmpty' => true,
                                ])
                            </div>
                            <ul class="list-unstyled list-inline-dot fs-12 mb-1">
                                <li>{{ $shopInfoArray['total_review']}} {{ translate('Ratings') }} </li>
                                <li>{{ $shopInfoArray['total_order']}} {{ translate('Orders') }} </li>
                            </ul>
                            @php($minimumOrderAmountStatus=getWebConfig(name: 'minimum_order_amount_status'))
                            @php($minimumOrderAmountBySeller=getWebConfig(name: 'minimum_order_amount_by_seller'))
                            @if ($minimumOrderAmountStatus ==1 && $minimumOrderAmountBySeller == 1 && $shopInfoArray['minimum_order_amount'] > 0)
                                <span class="text-sm-nowrap">
                                    {{ webCurrencyConverter($shopInfoArray['minimum_order_amount']) }}
                                    {{ translate('Minimum Order Amount') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="d-flex gap-3 flex-wrap flex-grow-1">
                    <div class="card flex-grow-1">
                        <div class="card-body grid-center">
                            <div class="text-center">
                                <h2 class="fs-28 text-primary fw-extra-bold mb-2">
                                    {{ round($rattingStatusArray['positive']) }}%</h2>
                                <p class="text-muted text-capitalize">
                                    {{ translate('Positive review') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card flex-grow-1">
                        <div class="card-body grid-center">
                            <div class="text-center">
                                <h2 class="fs-28 text-primary fw-extra-bold mb-2">
                                    {{ $products_for_review }}
                                </h2>
                                <p class="text-muted">
                                    {{ translate('Products') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap flex-lg-column flex-lg-down-grow-1 justify-content-center gap-3">
                    @if (auth('customer')->check())
                        <button class="btn btn-primary flex-lg-down-grow-1 fs-16" data-bs-toggle="modal"
                                data-bs-target="#contact_sellerModal"
                            @if(!$isInHouseShop && checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $shopInfoArray))
                                    disabled
                            @elseif($isInHouseShop && checkVendorAbility(type: 'inhouse', status: 'temporary_close'))
                                    disabled
                            @endif
                        >
                            <i class="bi bi-chat-square-fill text-capitalize"></i>
                            {{ translate('Chat with vendor') }}
                        </button>
                        @include('theme-views.layouts.partials.modal._chat-with-seller',['shop' => $shopInfoArray, 'user_type' => ($isInHouseShop ? 'admin' : 'seller'), 'isVerified' => $isShopVerified])
                    @else
                        <button class="btn btn-primary flex-lg-down-grow-1 fs-16" data-bs-toggle="modal"
                                data-bs-target="#loginModal">
                            <i class="bi bi-chat-square-fill text-capitalize"></i> {{ translate('Chat with vendor') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($shopInfoArray['bottom_banner'])
        <div class="max-height-420px overflow-hidden d-flex align-items-center">
            <img src="{{ getStorageImages(path: $shopInfoArray['bottom_banner_full_url'], type:'shop-banner') }}"
                 class="dark-support rounded img-fit" alt="">
        </div>
    @endif
</div>
