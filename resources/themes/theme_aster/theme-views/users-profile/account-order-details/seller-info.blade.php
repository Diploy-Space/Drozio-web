@extends('theme-views.layouts.profile')

@section('title', translate('Order details') . ' | ' . $web_config['company_name'] . ' ' . translate('eCommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-sm-4">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            @include(
                                'theme-views.users-profile.account-order-details._order-details-head',
                                ['order' => $order]
                            )
                            <div class="mt-4 card">
                                <div class="card-body">
                                    @if ($vendorCard !== null)
                                        <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                                            <div class="media align-items-center gap-3">
                                                <div class="position-relative">
                                                    <div class="avatar rounded border" style="--size: 6.875rem">
                                                        <img class="dark-support img-fit rounded img-w-h-100" alt="{{ $vendorCard['name'] }}" src="{{ $vendorCard['image'] }}" loading="lazy">
                                                    </div>
                                                    @if ($vendorCard['closed_label'] !== null)
                                                        <span class="temporary-closed position-absolute rounded">
                                                            <span class="px-1 text-center">{{ $vendorCard['closed_label'] }}</span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="media-body d-flex flex-column gap-2">
                                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                                        <a href="{{ $vendorCard['shop_url'] }}" class="fs-18 fw-bold text-dark line-limit-2">{{ $vendorCard['name'] }}</a>
                                                        @include('theme-views.partials._verified-badge', ['isVerified' => $vendorCard['is_verified']])
                                                    </div>
                                                    @include('theme-views.partials._rating-display', [
                                                        'rating' => $avg_rating,
                                                        'count' => 0,
                                                        'size' => 'sm',
                                                        'showEmpty' => true,
                                                    ])
                                                    <ul class="list-unstyled list-inline-dot fs-12 mb-0">
                                                        <li>{{ $rating_count }} {{ translate('Ratings') }}</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-primary px-4 rounded-10 fw-medium"
                                                        data-bs-toggle="modal" data-bs-target="#contact_sellerModal"
                                                        @disabled(! $vendorCard['is_chat_open'])>
                                                    <i class="bi bi-chat-square-fill"></i>
                                                    {{ translate('Chat with vendor') }}
                                                </button>
                                                <a href="{{ $vendorCard['shop_url'] }}" class="btn btn-outline-primary px-4 rounded-10 fw-medium">
                                                    {{ translate('Visit Shop') }}
                                                </a>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-1">
                                            <div class="col-6">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3 text-center h-100">
                                                    <h3 class="text-primary mb-1">{{ round($rating_percentage) }}%</h3>
                                                    <span class="fs-14 text-muted">{{ translate('Positive review') }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="section-bg-cmn rounded-2 py-3 px-3 text-center h-100">
                                                    <h3 class="text-primary mb-1">{{ $product_count }}</h3>
                                                    <span class="fs-14 text-muted">{{ translate('Products') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3 w-100">
                                            <img width="80" class="mb-3" alt="" src="{{ theme_asset('assets/img/empty-state/empty-vendor.svg') }}">
                                            <h5 class="text-center text-muted">{{ translate('Shop not found') }}</h5>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if ($vendorCard !== null && $vendorCard['is_chat_open'])
        @include('theme-views.layouts.partials.modal._chat-with-seller', [
            'shop' => $vendorCard['chat_shop'],
            'user_type' => $vendorCard['chat_user_type'],
            'isVerified' => $vendorCard['is_verified'],
        ])
    @endif
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/order-summary.js') }}" defer></script>
@endpush
