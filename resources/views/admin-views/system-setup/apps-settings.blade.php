@extends('layouts.admin.app')

@section('title', translate('App Settings'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/apps-settings.css') }}">
@endpush

@php
    $appConfigs = [
        [
            'type' => 'user_app_version_control',
            'title' => translate('Customer app version control'),
            'subtitle' => translate('Here you setup your customer app version and app download url'),
            'icon' => 'fi-sr-smartphone',
            'color' => 'primary',
            'data' => $userAppVersionControl,
        ],
        [
            'type' => 'seller_app_version_control',
            'title' => translate('Vendor app version control'),
            'subtitle' => translate('Here you setup your vendor app version & app download url'),
            'icon' => 'fi-sr-shop',
            'color' => 'success',
            'data' => $sellerAppVersionControl,
        ],
        [
            'type' => 'delivery_man_app_version_control',
            'title' => translate('Delivery man app version control'),
            'subtitle' => translate('Set the delivery man app version and download URL.'),
            'icon' => 'fi-sr-moped',
            'color' => 'info',
            'data' => $deliverymanAppVersionControl,
        ],
    ];
@endphp

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/system-setting.png') }}" alt="">
                {{ translate('System Setup') }}
            </h2>
        </div>

        @include('admin-views.system-setup.system-settings-inline-menu')

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-3" id="app-overview-cards" role="tablist">
            @foreach($appConfigs as $index => $config)
                @php
                    $androidVersion = $config['data']['for_android']['version'] ?? null;
                    $iosVersion = $config['data']['for_ios']['version'] ?? null;
                    $isConfigured = ! empty($androidVersion) || ! empty($iosVersion);
                @endphp
                <div class="col">
                    <a class="card h-100 app-overview-card text-reset text-decoration-none {{ $index === 0 ? 'active' : '' }}"
                       id="app-tab-{{ $config['type'] }}" data-bs-toggle="tab" role="tab"
                       href="#app-pane-{{ $config['type'] }}"
                       aria-controls="app-pane-{{ $config['type'] }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-{{ $config['color'] }} bg-opacity-10 text-{{ $config['color'] }} rounded d-flex align-items-center justify-content-center">
                                <i class="fi {{ $config['icon'] }} fs-4 d-flex"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="mb-1 fw-bold text-capitalize app-tab-title">{{ $config['title'] }}</h3>
                                <span class="d-block text-muted fs-12 mb-1 text-capitalize">{{ translate('Minimum app version') }}</span>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @if($isConfigured)
                                        <span class="badge badge-soft-secondary fw-semibold">{{ translate('Android') }} {{ $androidVersion ?: '—' }}</span>
                                        <span class="badge badge-soft-secondary fw-semibold">{{ translate('Ios') }} {{ $iosVersion ?: '—' }}</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold text-capitalize">{{ translate('Not Configured') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded mb-3">
            <div class="d-flex gap-2 align-items-center mb-2">
                <i class="fi fi-sr-info text-warning"></i>
                <span>
                    {{ translate('Force users onto the latest app version.') }}. {{ translate('Please input proper data for the app link & versions') }}.
                </span>
            </div>
            <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                <li>{{ translate('Older app versions can crash on start.') }}.</li>
                <li>{{ translate('Helps users get the updated features in their app.') }}.</li>
            </ul>
        </div>

        <div class="tab-content" id="app-tab-content">
            @foreach($appConfigs as $index => $config)
                <div class="tab-pane {{ $index === 0 ? 'active' : '' }}"
                     id="app-pane-{{ $config['type'] }}" role="tabpanel" aria-labelledby="app-tab-{{ $config['type'] }}">
                    @include('admin-views.system-setup.partials._app-version-control-card', $config)
                </div>
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="readInstructionsModal" tabindex="-1" aria-labelledby="readInstructionsModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0" data-bs-dismiss="modal" aria-label="Close"><i class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="swiper instruction-carousel pb-3">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80" class="mb-3" src="{{ dynamicAsset(path: 'public/assets/back-end/img/what_app_version.png') }}" loading="lazy" alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{ translate('What is app version').'?' }}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4">
                                        <li>{{ translate('Minimum version required for the vendor, delivery man and customer apps.') }}</li>
                                        <li>{{ translate('It does not represent the Play Store or App Store version') }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img width="80" class="mb-3" src="{{ dynamicAsset(path: 'public/assets/back-end/img/what_app_version.png') }}" loading="lazy" alt="">
                                    <h4 class="lh-md mb-3 text-capitalize">{{ translate('App download link') }}</h4>
                                    <ul class="d-flex flex-column px-4 gap-2 mb-4">
                                        <li>{{ translate('URL the Update App button opens inside the app.') }}</li>
                                    </ul>
                                    <button class="btn btn-primary px-10 mt-3 text-capitalize" data-bs-dismiss="modal">{{ translate('Got it') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="instruction-pagination-custom my-2"></div>
                </div>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._app-settings")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/vendor/swiper/swiper-bundle.min.js') }}"></script>
@endpush
