@extends('layouts.vendor.app')

@section('title', translate('Business Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h1 mb-0 text-capitalize d-flex">{{ translate('Business Setup') }}</h2>
                @include('vendor-views.shop.inline-menu')
            </div>

            @if ($verification['show_prompt'])
                @include('vendor-views.shop.partials._verification-prompt')
            @else
                <div class="d-flex gap-2 alert alert-soft-warning max-w-500" role="alert">
                    <i class="fi fi-sr-lightbulb-on"></i>
                    <p class="fs-12 mb-0 text-dark">
                        {{ translate('Here you can setup you shop decoration and basic information.') }}
                        {{ translate('To create new product got to product add') }}
                        <a href="{{ route('vendor.retail.products.add') }}" class="text-underline font-weight-bold"
                           target="_blank">
                            {{ translate('New Product') }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <div>
                        <h3>{{ translate('Store Availability') }}</h3>
                        <p class="fs-12 mb-0">
                            {{ translate('Temporarily closes the shop in the customer app and website.') }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary px-4 text-white" data-toggle="offcanvas"
                            data-target=".offcanvas-store-availability">
                        {{ translate('Availability') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex-wrap gap-3">
                <div>
                    <h3 class="text-capitalize mb-1">{{ translate('Shop Details') }}</h3>
                    <p class="fs-12 mb-0">
                        {{ translate('Created At') }}
                        {{ date('d M, Y', strtotime($shop['created_at'] ?? $shop['updated_at'])) }}
                    </p>
                </div>

                <div class="d-inline-flex gap-3 flex-wrap">
                    <button class="btn btn-outline-primary text-capitalize" data-toggle="offcanvas"
                            data-target=".offcanvas-vacation-mode">
                        <i class="fi fi-sr-umbrella-beach"></i>
                        {{ translate('Go Vacation Mode') }}
                    </button>

                    <a class="btn btn-primary px-4 text-white" href="{{ route('vendor.shop.update', [$shop->id]) }}">
                        <i class="fi fi-sr-pen-circle"></i>
                        {{ translate('Edit Information') }}
                    </a>
                </div>
            </div>

            <div class="card-body d-flex flex-column">

                @if(!$vacationSchedule && !$closedBusinesses)
                    <div class="d-flex gap-2 alert alert-soft-info" role="alert">
                        <i class="fi fi-sr-info text-info"></i>
                        <p class="fs-12 mb-0 text-dark">
                            {{ translate('Your shop is now on live status.') }}
                            {{ translate('All functions are work properly.') }}
                            {{ translate('To turn vacation mode click') }}
                            <strong>{{ translate('Go Vacation Mode') }}</strong>
                            {{ translate('Button') }}
                        </p>
                    </div>
                @endif

                @if($closedBusinesses)
                    <div class="d-flex gap-2 alert alert-soft-danger" role="alert">
                        <i class="fi fi-sr-info text-danger"></i>
                        <p class="fs-12 mb-0 text-dark">
                            {{ translate('Your :business shop is currently inactive.', ['business' => $closedBusinesses]) }}
                            {{ translate('To activate it, go to Availability button and turn on the status switch.') }}
                        </p>
                    </div>
                @endif

                @if($vacationSchedule)
                    <div class="d-flex gap-2 alert alert-soft-danger" role="alert">
                        <i class="fi fi-sr-info text-danger"></i>
                        <p class="fs-12 mb-0 text-dark">
                            @if($vacationSchedule['is_open_ended'])
                                {{ translate('Your :business shop is on vacation mode until you turn it off.', ['business' => $vacationSchedule['businesses']]) }}
                            @elseif($vacationSchedule['is_upcoming'])
                                {{ translate('Vacation mode for your :business shop is scheduled from', ['business' => $vacationSchedule['businesses']]) }}
                                <span class="text-danger font-weight-bold">
                                    {{ $vacationSchedule['start_date'] }} - {{ $vacationSchedule['end_date'] }}.
                                </span>
                                {{ translate('Your shop stays live until then.') }}
                            @else
                                {{ translate('Your :business shop is now vacation mode from', ['business' => $vacationSchedule['businesses']]) }}
                                <span class="text-danger font-weight-bold">
                                    {{ $vacationSchedule['start_date'] }} - {{ $vacationSchedule['end_date'] }}.
                                </span>
                            @endif
                            {{ translate('To turn off vacation mode click') }}
                            <strong>{{ translate('Go Vacation Mode') }}</strong>
                            {{ translate('Button and turn the status switch off.') }}
                        </p>
                    </div>
                @endif

                <div class="position-relative">
                    <div class="cover-img h-550 h-md-350">
                        <img class="w-100 h-100 object-fit-cover rounded-10" alt=""
                             src="{{ getStorageImages(path: $shop->banner_full_url, type:'backend-banner', source: dynamicAsset('public/assets/new/back-end/img/inhouse-banner.webp')) }}">
                    </div>

                    <div class="d-flex align-items-start flex-wrap column-gap-4 px-2 px-sm-4 bg-blur position-absolute bottom-0 start-0 w-100 z-1">
                        <div class="position-relative z-1">
                            <img height="120" width="120" class="shadow-lg rounded object-fit-cover bg-white mt-n50"
                                 src="{{ $shop->image === 'def.png' ? dynamicAsset(path: 'public/assets/back-end/img/shop.png') : getStorageImages(path: $shop->image_full_url, type: 'backend-basic') }}"
                                 alt="">
                        </div>

                        <div class="flex-grow-1 py-4">
                            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                <div>
                                    <h3 class="text-capitalize d-flex align-items-center flex-wrap gap-1">
                                        <span>{{ $shop['name'] }}</span>
                                        @include('vendor-views.shop.partials._verified-badge', ['isVerified' => $vendor?->hasVerifiedBadge()])
                                    </h3>
                                    <p class="fs-12 mb-0">
                                        {{ translate('Created At') }}
                                        {{ date('d M, Y', strtotime($shop['created_at'] ?? $shop['updated_at'])) }}
                                    </p>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($storefrontLinks as $storefrontLink)
                                        <a class="btn btn-outline-primary" target="_blank"
                                           href="{{ $storefrontLink['url'] }}">
                                            <i class="fi fi-rr-globe"></i>
                                            {{ $storefrontLink['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('vendor-views.shop.partials._overview-modules')

            </div>
        </div>

        @include("vendor-views.shop.partials._verification-criteria-offcanvas")
        @include("vendor-views.shop.partials._vacation-mode-offcanvas")
        @include("vendor-views.shop.partials._availability-offcanvas")
        @include("layouts.vendor.partials.offcanvas._shop_settings")
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/vendor/js/business-settings/shop-settings.js') }}"></script>
@endpush
