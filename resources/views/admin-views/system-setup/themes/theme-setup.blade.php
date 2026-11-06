@extends('layouts.admin.app')

@section('title', translate('Theme Setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/theme-setup.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-palette text-primary d-flex"></i>
                {{ translate('Theme Setup') }}
            </h2>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 g-3 mb-3" id="theme-status-cards">
            <div class="col">
                <div class="card h-100 theme-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-palette fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold">{{ $totalThemes }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Installed themes') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 theme-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-star fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold text-truncate text-capitalize">{{ $currentThemeName }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Current theme') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                    <i class="fi fi-sr-palette text-primary d-flex"></i>
                    {{ translate('Available Themes') }}
                </h3>
                <p class="mb-0 fs-12 text-muted">
                    {{ translate('Select the theme you want to use for your system') }}
                </p>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    @foreach($themes as $key => $theme)
                        @if(isset($theme['software_id']))
                            <div class="col-sm-6 col-xl-4">
                            <div class="card border shadow-none h-100 overflow-hidden {{ theme_root_path() == $key ? 'theme-active' : '' }}">
                                <div class="bg-section p-12 p-sm-20 d-flex justify-content-between gap-3 align-items-start">
                                    <div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                            <h3 class="fw-bold mb-0">
                                                {{ ucwords(str_replace('_', ' ', $key =='default' ? 'default_theme' : $theme['name'] ?? '')) }}
                                            </h3>
                                            @if(theme_root_path() == $key)
                                                <div class="text-white px-2 py-1 fs-12 lh-1 fw-semibold rounded bg-success text-capitalize">
                                                    {{ translate('Current') }}
                                                </div>
                                            @elseif($theme['is_active'])
                                                <span class="badge badge-soft-secondary fw-semibold text-capitalize">
                                                    {{ translate('Installed') }}
                                                </span>
                                            @endif
                                        </div>
                                        <h5 class="text-info-dark mb-0">
                                            {{ translate('Version') }} {{ $theme['version'] ?? '1.0' }}
                                        </h5>
                                    </div>

                                    @if(($key == 'default' || $key == 'theme_aster') || $theme['comfortable_panel_version'] == SOFTWARE_VERSION)
                                        <div class="d-flex gap-2 gap-sm-3 align-items-center">
                                            @if(($key != 'default' && $key != 'theme_aster') && theme_root_path() != $key)
                                                <button class="btn btn-outline-danger bg-danger bg-opacity-10 icon-btn" data-bs-toggle="modal"
                                                        data-bs-target="#deleteThemeModal_{{ $key }}">
                                                    <i class="fi fi-sr-trash"></i>
                                                </button>
                                            @endif

                                            @if(theme_root_path() == $key)
                                                <input class="form-check-input radio--input radio--input_lg" type="radio" checked>
                                            @else
                                                <input class="form-check-input radio--input radio--input_lg theme-publish-status theme-publish-status-{{ $key }}"
                                                       type="radio" data-bs-toggle="modal"
                                                       data-bs-target="#shiftThemeModal_{{ $key }}">
                                            @endif
                                        </div>
                                    @else
                                        <div class="max-w-150px text-white px-2 py-1 fs-12 fw-semibold rounded bg-warning">
                                            {{ translate('Please ues panel comfortable version') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="ratio-3-2 border rounded-10">
                                        <?php
                                            if (DOMAIN_POINTED_DIRECTORY == 'public') {
                                                $themeImage = dynamicAsset(path: 'public/themes/'.$key.'/public/addon/'.($theme['image'] ?? ''));
                                            } else {
                                                $themeImage = dynamicAsset(path: 'resources/themes/'.$key.'/public/addon/'.$theme['image'] ?? '');
                                            }
                                        ?>
                                        <img class="img-fit rounded-10" alt=""
                                             src="{{ getStorageImages(path: null, type: 'backend-basic', source: $themeImage) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach

                    @include('admin-views.system-setup.themes.theme-activate-modal')
                </div>
            </div>
        </div>

        @include("admin-views.system-setup.themes._theme-modals")
    </div>

    <span id="get-theme-publish-route"
          data-action="{{ route('admin.settings.system-setup.theme.publish') }}"></span>
    <span id="get-theme-delete-route"></span>
    <span id="get-notify-all-vendor-route-and-img-src"
          data-csrf="{{ csrf_token() }}"
          data-src="{{ dynamicAsset(path: 'public/assets/back-end/img/notify_success.png') }}"
          data-action="{{ route('admin.settings.system-setup.theme.notify-all-the-vendors') }}">
    </span>

    @include("layouts.admin.partials.offcanvas._theme-setup")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/addon.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/business-setting/theme-setup.js') }}"></script>
@endpush
