@extends('layouts.admin.app')

@section('title', translate('App Deep Link Setup'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/app-deep-link.css') }}">
@endpush

@php
    $androidFields = [
        [
            'name' => 'android_package_name',
            'label' => translate('Android package name for').' ('.translate('Android').')',
            'placeholder' => translate('Ex').' : com.example.app',
            'requiredMsg' => translate(':field is required', ['field' => translate('Android package name')]),
            'tooltip' => translate('Android package name, used to open the right app via deep links.'),
            'value' => $deeplink['android_package_name'] ?? '',
        ],
        [
            'name' => 'android_sha256_fingerprint',
            'label' => translate('Android sha256 fingerprint for user app').' ('.translate('Android').')',
            'placeholder' => translate('Ex').' : AA:BB:CC:DD:EE:FF:11:22:33:44:55:66:77:88:99:AA',
            'requiredMsg' => translate(':field is required', ['field' => translate('Android sha256 fingerprint')]),
            'tooltip' => translate('Android SHA-256 fingerprint, used to secure deep link access.'),
            'value' => $deeplink['android_sha256_fingerprint'] ?? '',
        ],
        [
            'name' => 'playstore_redirect_url',
            'label' => translate('Play store redirect url').' ('.translate('Android').')',
            'placeholder' => translate('Ex').' : https://play.google.com/store/apps/details?id=com.example.app',
            'requiredMsg' => translate(':field is required', ['field' => translate('Play store redirect url')]),
            'tooltip' => translate('Play Store link used when the app is missing or outdated.'),
            'value' => $deeplink['playstore_redirect_url'] ?? '',
        ],
    ];
    $iosFields = [
        [
            'name' => 'ios_bundle_id',
            'label' => translate('IOS bundle id for').' (iOS)',
            'placeholder' => translate('Ex').' : com.example.iosapp',
            'requiredMsg' => translate(':field is required', ['field' => translate('Ios bundle id')]),
            'tooltip' => translate('iOS bundle ID, so deep links open the app on iOS devices.'),
            'value' => $deeplink['ios_bundle_id'] ?? '',
        ],
        [
            'name' => 'ios_team_id',
            'label' => translate('IOS team id for').' (iOS)',
            'placeholder' => translate('Ex').' : ABCDE12345',
            'requiredMsg' => translate(':field is required', ['field' => translate('Ios team id')]),
            'tooltip' => translate('Apple developer team ID, used to verify ownership for iOS deep linking.'),
            'value' => $deeplink['ios_team_id'] ?? '',
        ],
        [
            'name' => 'app_store_redirect_url',
            'label' => translate('App store redirect url').' (iOS)',
            'placeholder' => translate('Ex').' : https://apps.apple.com/app/id1234567890',
            'requiredMsg' => translate(':field is required', ['field' => translate('App store redirect url')]),
            'tooltip' => translate('App Store link used when the app is missing or outdated.'),
            'value' => $deeplink['app_store_redirect_url'] ?? '',
        ],
    ];
    $platforms = [
        ['name' => translate('Android'), 'logo' => 'public/assets/back-end/img/play_store.png', 'fields' => $androidFields],
        ['name' => translate('Ios'), 'logo' => 'public/assets/back-end/img/apple.png', 'fields' => $iosFields],
    ];

    $verificationFiles = [
        ['label' => translate('Root').' .well-known/assetlinks.json', 'path' => '.well-known/assetlinks.json', 'exists' => $fileStatus['base_exists'] ?? false, 'writable' => $fileStatus['base_writable'] ?? false],
        ['label' => 'public/.well-known/assetlinks.json', 'path' => 'public/.well-known/assetlinks.json', 'exists' => $fileStatus['public_exists'] ?? false, 'writable' => $fileStatus['public_writable'] ?? false],
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

        <div class="row row-cols-1 row-cols-md-2 g-3 mb-3" id="dl-status-cards">
            @foreach($verificationFiles as $file)
                <div class="col">
                    <div class="card h-100 dl-status-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon bg-{{ $file['exists'] ? 'success' : 'warning' }} bg-opacity-10 text-{{ $file['exists'] ? 'success' : 'warning' }} rounded d-flex align-items-center justify-content-center">
                                <i class="fi fi-sr-{{ $file['exists'] ? 'memo-circle-check' : 'file' }} fs-5 d-flex"></i>
                            </div>
                            <div class="min-w-0">
                                <code class="d-block text-muted mb-1">{{ $file['path'] }}</code>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-{{ $file['exists'] ? 'success' : 'warning' }} bg-opacity-10 text-{{ $file['exists'] ? 'success' : 'warning' }} fw-semibold text-capitalize">
                                        {{ $file['exists'] ? translate('Generated') : translate('Not generated') }}
                                    </span>
                                    <span class="badge badge-soft-secondary fw-semibold text-capitalize">
                                        <i class="fi fi-sr-{{ $file['writable'] ? 'lock-open-alt' : 'lock' }}"></i>
                                        {{ $file['writable'] ? translate('Writable') : translate('Not writable') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded mb-3">
            <div class="d-flex gap-2 align-items-center mb-2">
                <i class="fi fi-sr-info text-warning"></i>
                <span>
                    {{ translate('Configure app deep links and store redirects for Android and iOS.') }}
                    {{ translate('Please provide correct app identifiers and store links for proper redirection.') }}
                </span>
            </div>
            <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                <li>{{ translate('Users without the app are sent to the app store.') }}</li>
                <li>{{ translate('This setup ensures smooth app opening experience through links on both platforms.') }}</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-link-alt text-primary d-flex"></i>
                        {{ translate('Customer app deep link settings') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('Configure your android and ios app identifiers and store redirect urls here') }}
                    </p>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.system-setup.app-deep-link-store') }}" method="post"
                      class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate"
                      novalidate="novalidate">
                    @csrf
                    <input type="hidden" name="type" value="app_deep_link">
                    <div class="row g-4">
                        @foreach($platforms as $platform)
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <img class="dl-platform-logo" src="{{ dynamicAsset(path: $platform['logo']) }}" alt="{{ $platform['name'] }}">
                                    <h3 class="mb-0 text-capitalize dl-platform-head">{{ translate('For').' '.$platform['name'] }}</h3>
                                </div>
                                <div class="dl-platform-panel bg-section rounded p-12 p-sm-20 d-flex flex-column gap-3">
                                    @foreach($platform['fields'] as $field)
                                        <div class="form-group mb-0">
                                            <label class="form-label mb-2 text-capitalize d-flex align-items-center gap-2">
                                                {{ $field['label'] }}
                                                <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                      aria-label="{{ $field['tooltip'] }}" data-bs-title="{{ $field['tooltip'] }}">
                                                    <i class="fi fi-sr-info"></i>
                                                </span>
                                            </label>
                                            <input type="text" class="form-control" name="{{ $field['name'] }}"
                                                   placeholder="{{ $field['placeholder'] }}"
                                                   data-required-msg="{{ $field['requiredMsg'] }}" required
                                                   value="{{ showDemoModeInputValue(value: $field['value']) }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                        <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                class="btn btn-primary px-3 px-sm-4 w-120 {{ getDemoModeFormButton(type: 'class') }}">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._app-deep-link-setting")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/vendor/swiper/swiper-bundle.min.js') }}"></script>
@endpush
