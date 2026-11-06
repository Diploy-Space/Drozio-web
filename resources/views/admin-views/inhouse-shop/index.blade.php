@extends('layouts.admin.app')

@section('title', translate('Shop Settings'))

@section('content')
    <div class="content container-fluid">

        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-12">
                <h1 class="mb-3 sm-sm-20">
                    {{ translate('Shop Setup') }}
                </h1>
                @include('admin-views.inhouse-shop._inhouse-shop-menu')
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <div>
                        <h3 class="fs-18">{{ translate('Store Availability') }}</h3>
                        <p class="mb-0 fs-12">
                            {{ translate('Temporarily closes the in-house shop in the app and website.') }}
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary lh-1 px-4" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasStoreAvailability">
                        {{ translate('Availability') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-3 justify-content-between">
                    <div class="d-flex flex-column gap-1">
                        <h2 class="mb-1">{{ translate('Shop Details') }}</h2>
                        <p class="fs-12 mb-0">
                            {{ translate('Basic information about this shop for your quick review') }}
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-secondary lh-1" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasVacationMode">
                            <i class="fi fi-sr-umbrella-beach"></i>
                            {{ translate('Go to Vacation Mode') }}
                        </button>
                        <a href="{{ route('admin.settings.business-settings.inhouse-shop') . '?action=edit' }}"
                            class="btn btn-primary lh-1">
                            <i class="fi fi-sr-pen-circle"></i>
                            {{ translate('Edit Information') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">

                @if (!$vacationSchedule && !$closedBusinesses)
                    <div
                        class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-20">
                        <i class="fi fi-sr-info text-info"></i>
                        <span>
                            {{ translate('Your shop is now on live status.') }}
                            {{ translate('All functions are work properly.') }}
                            {{ translate('To turn vacation mode click') }} <b>{{ translate('Go to Vacation Mode') }}</b>
                            {{ translate('Button') }}
                        </span>

                    </div>
                @endif

                @if ($closedBusinesses)
                    <div
                        class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-20">
                        <i class="fi fi-sr-info text-danger"></i>
                        <span>
                            {{ translate('Your :business shop is currently inactive.', ['business' => $closedBusinesses]) }}
                            {{ translate('To activate it, go to Availability button and turn on the status switch.') }}
                        </span>
                    </div>
                @endif

                @if ($vacationSchedule)
                    <div
                        class="bg-danger bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-20">
                        <i class="fi fi-sr-info text-danger"></i>
                        <span>
                            @if ($vacationSchedule['is_open_ended'])
                                {{ translate('Your :business shop is on vacation mode until you turn it off.', ['business' => $vacationSchedule['businesses']]) }}
                            @elseif ($vacationSchedule['is_upcoming'])
                                {{ translate('Vacation mode for your :business shop is scheduled from', ['business' => $vacationSchedule['businesses']]) }}
                                <span class="text-danger fw-semibold">
                                    {{ $vacationSchedule['start_date'] }} - {{ $vacationSchedule['end_date'] }}.
                                </span>
                                {{ translate('Your shop stays live until then.') }}
                            @else
                                {{ translate('Your :business shop is now vacation mode from', ['business' => $vacationSchedule['businesses']]) }}
                                <span class="text-danger fw-semibold">
                                    {{ $vacationSchedule['start_date'] }} - {{ $vacationSchedule['end_date'] }}.
                                </span>
                            @endif
                            {{ translate('To turn off vacation mode click') }} <strong>
                                {{ translate('Go to Vacation Mode') }}</strong>
                            {{ translate('Button and turn the status switch off.') }}
                        </span>
                    </div>
                @endif

                <div class="position-relative">
                    <div class="cover-img h-550 h-md-350">
                        <img class="w-100 h-100 object-fit-cover rounded-10" alt=""
                            src="{{ getStorageImages(path: $shop?->banner_full_url, type: 'backend-banner', source: dynamicAsset('public/assets/new/back-end/img/inhouse-banner.webp')) }}">
                    </div>

                    <div
                        class="d-flex align-items-start flex-wrap column-gap-4 px-2 px-sm-20 bg-blur position-absolute bottom-0 start-0 w-100 z-1">
                        <div class="position-relative z-1">
                            <img height="120" width="120" class="shadow-lg rounded object-fit-cover bg-white mt-n50"
                                src="{{ ($shop?->image ?? 'def.png') === 'def.png' ? dynamicAsset(path: 'public/assets/back-end/img/shop.png') : getStorageImages(path: getInHouseShopConfig(key: 'image_full_url'), type: 'shop') }}"
                                alt="">
                        </div>
                        <div class="flex-grow-1 py-4">
                            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                <div>
                                    <h3 class="text-capitalize">
                                        {{ getInHouseShopConfig(key: 'name') }}
                                    </h3>
                                    <p class="fs-12 mb-0">
                                        {{ translate('Created At') }}
                                        {{ date('d M, Y', strtotime($shop['created_at'])) }}
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

                @include('admin-views.inhouse-shop.partials._overview-modules')

            </div>
        </div>

        @include('admin-views.inhouse-shop._vacation-mode-offcanvas')
        @include('admin-views.inhouse-shop._availability-offcanvas')

    </div>

    @include('layouts.admin.partials.offcanvas._inhouse-shop-setup')
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/inhouse-shop.js') }}"></script>
@endpush
