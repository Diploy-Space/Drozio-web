<div class="position-relative z-index-99 rtl w-100 text-align-direction {{ $displayClass }}">
    <div class="__rounded-10 bg-white position-relative">
        <div class="d-flex flex-wrap justify-content-between seller-details">
            <div class="d-flex align-items-center p-2 flex-grow-1 store-media">
                <div class="">
                    <div
                        class="position-relative overflow-hidden d-flex align-items-center aspect-1 rounded w-90px store-logo-frame">
                        <img class="img-fluid h-100 object-cover" alt="{{ $shopInfoArray['name'] }}"
                             src="{{ getStorageImages(path: $shopInfoArray['image_full_url'], type: 'shop') }}">
                    </div>
                </div>
                <div
                    class="__w-100px flex-grow-1 {{Session::get('direction') === "rtl" ? ' pr-2 pr-sm-4' : ' pl-2 pl-sm-4'}}">
                    <div class="font-weight-bolder mb-2">
                        {{ $shopInfoArray['name'] }}
                        @include('web-views.partials._verified-badge', ['isVerified' => $shopInfoArray['is_verified']])
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            @include('web-views.partials._rating-display', [
                                'rating' => $shopInfoArray['average_rating'],
                                'count' => 0,
                                'size' => 'md',
                                'showEmpty' => true,
                            ])
                            <span class="text-nowrap fs-13 font-semibold text-base">
                                {{ $shopInfoArray['total_review'] }} {{ translate('Ratings') }}
                            </span>
                            @include('web-views.partials._store-status-chip', [
                                'type' => $shopInfoArray['is_in_house'] ? 'inhouse' : 'vendor',
                                'vendor' => $shopInfoArray,
                            ])
                        </div>
                        <div class="d-flex flex-wrap py-1 fs-12 web-text-primary">
                            <span class="text-nowrap">
                                {{ $shopInfoArray['total_order']}} {{ translate('Orders') }}
                            </span>
                            @php($minimum_order_amount_status = getWebConfig(name: 'minimum_order_amount_status'))
                            @php($minimum_order_amount_by_seller = getWebConfig(name: 'minimum_order_amount_by_seller'))
                            @if ($minimum_order_amount_status == 1 && $minimum_order_amount_by_seller == 1 && $shopInfoArray['minimum_order_amount'] > 0)
                                <span class="__inline-69"></span>
                                <span>{{ webCurrencyConverter(amount: $shopInfoArray['minimum_order_amount']) }} {{ translate('Minimum Order Amount') }}</span>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <div class="{{ session('direction') === "rtl" ? 'ml-sm-4' : 'mr-sm-4' }}">
                    @if (auth('customer')->check())
                        <div class="d-flex ml-4-mobile">
                            @if($shopInfoArray['is_in_house'])
                                <button
                                    class="btn btn--primary __inline-70 rounded-10 btn-sm text-capitalize chat-with-seller-button d-none d-sm-inline-block"
                                    data-toggle="modal"
                                    data-target="#exampleModal"
                                    @if($shopInfoArray['temporary_close'])
                                        disabled
                                    @endif
                                >
                                    <img src="{{ theme_asset(path: 'public/assets/front-end/img/shopview-chat.png') }}"
                                         loading="eager" class="" alt="">
                                    <span class="d-none d-sm-inline-block">
                                        {{ translate('Chat') }}
                                    </span>
                                </button>

                                <button
                                    class="btn bg-transparent border-0 __inline-70 rounded-10  text-capitalize chat-with-seller-button d-sm-inline-block d-md-none"
                                    data-toggle="modal" data-target="#exampleModal"
                                    @if($shopInfoArray['temporary_close'])
                                        disabled
                                    @endif
                                >
                                    <img
                                        src="{{ theme_asset(path: 'public/assets/front-end/img/icons/shopview-chat-blue.svg') }}"
                                        loading="eager" class="" alt="">
                                </button>
                            @else
                                <button data-toggle="modal" data-target="#exampleModal"
                                    class="btn btn--primary __inline-70 rounded-10 btn-sm text-capitalize chat-with-seller-button d-none d-sm-inline-block"
                                    @if($shopInfoArray['temporary_close'])
                                        disabled
                                    @endif
                                >
                                    <img src="{{ theme_asset(path: 'public/assets/front-end/img/shopview-chat.png') }}"
                                         loading="eager" class="" alt="">
                                    <span class="d-none d-sm-inline-block">
                                        {{ translate('Chat') }}
                                    </span>
                                </button>

                                <button data-toggle="modal" data-target="#exampleModal"
                                    class="btn bg-transparent border-0 __inline-70 rounded-10  text-capitalize chat-with-seller-button d-sm-inline-block d-md-none"
                                    @if($shopInfoArray['temporary_close'])
                                        disabled
                                    @endif
                                >
                                    <img loading="eager" class="" alt=""
                                        src="{{ theme_asset(path: 'public/assets/front-end/img/icons/shopview-chat-blue.svg') }}">
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="d-flex">
                            <a href="{{ route('customer.auth.login') }}"
                               class="btn btn--primary __inline-70 rounded-10 btn-sm text-capitalize chat-with-seller-button">
                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/shopview-chat.png') }}"
                                     loading="eager" class="" alt="">
                                <span class="d-none d-sm-inline-block">
                                    {{ translate('Chat') }}
                                </span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
