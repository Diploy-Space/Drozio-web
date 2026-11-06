@extends('layouts.front-end.profile')

@section('title', translate('Order details'))

@section('content')
    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl __inline-47 text-start">
        <div class="row g-3">
            @include('web-views.partials._profile-aside')

            <section class="col-lg-9">
                @include('web-views.users-profile.account-details.partial')

                <div class="card mt-3">
                    <div class="card-body">
                        @if ($vendorCard !== null)
                            <div class="media flex-wrap gap-2 gap-sm-3 border rounded p-3 align-items-center store-media">
                                <div class="position-relative">
                                    <div class="overflow-hidden other-store-logo aspect-1 border">
                                        <img width="77" alt="{{ $vendorCard['name'] }}" src="{{ $vendorCard['image'] }}">
                                    </div>
                                </div>
                                <div class="media-body">
                                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-capitalize seller-info-title mb-1 mb-sm-2 d-flex flex-wrap align-items-center gap-1">
                                                <a href="{{ $vendorCard['shop_url'] }}">{{ $vendorCard['name'] }}</a>
                                                @include('web-views.partials._verified-badge', ['isVerified' => $vendorCard['is_verified']])
                                            </h6>
                                            @include('web-views.partials._rating-display', [
                                                'rating' => $avg_rating,
                                                'count' => 0,
                                                'size' => 'sm',
                                            ])
                                            <ul class="list-unstyled list-inline-dot fs-12 mb-0">
                                                <li class="mb-0">{{ $rating_count }} {{ translate('Ratings') }}</li>
                                            </ul>
                                            @include('web-views.partials._store-status-chip', [
                                                'type' => $vendorCard['ability_type'],
                                                'vendor' => $order->seller?->shop,
                                                'chipClass' => 'mt-1',
                                            ])
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-sm rounded btn-soft-info text-capitalize px-3"
                                                    data-toggle="modal" data-target="#chatting_modal"
                                                    @disabled($vendorCard['is_temporarily_closed'])>
                                                <img alt="" src="{{ theme_asset(path: 'public/assets/front-end/img/seller-info-chat.png') }}">
                                                <span class="d-none d-sm-inline-block">{{ translate('Chat with vendor') }}</span>
                                            </button>
                                            <a href="{{ $vendorCard['shop_url'] }}" class="btn btn-sm rounded btn--primary text-capitalize px-3 d-flex align-items-center">
                                                {{ translate('Visit Shop') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-sm-6">
                                    <div class="light-box rounded p-3 text-center h-100">
                                        <h3 class="fs-20 font-bold web-text-primary mb-1">{{ round($rating_percentage) }}%</h3>
                                        <span class="fs-14 title-semidark">{{ translate('Positive review') }}</span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="light-box rounded p-3 text-center h-100">
                                        <h3 class="fs-20 font-bold web-text-primary mb-1">{{ $product_count }}</h3>
                                        <span class="fs-14 title-semidark">{{ translate('Products') }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5 text-capitalize">
                                <img width="70" class="mb-4" alt="" src="{{ theme_asset(path: 'public/assets/front-end/img/empty-icons/nodata.svg') }}">
                                <h5 class="fs-14 text-muted">{{ translate('Shop not found') }}</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if ($vendorCard !== null)
        @include('layouts.front-end.partials.modal._chatting', [
            'seller' => $order->seller,
            'user_type' => $order->seller_is,
        ])
    @endif
@endsection
