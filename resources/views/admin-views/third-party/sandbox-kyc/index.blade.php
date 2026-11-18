@extends('layouts.admin.app')

@section('title', translate('Sandbox KYC API'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <form action="{{ config('app.mode') != 'demo' ? route('admin.settings.third-party.sandbox-kyc') : 'javascript:' }}"
              method="POST" id="sandbox-kyc-status-form"
              class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-xl-9">
                            <div class="d-flex align-items-center gap-3">
                                <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                    <i class="fi fi-sr-shield-check d-flex"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <h2 class="mb-0">{{ translate('Sandbox KYC API') }}</h2>
                                        <span class="badge badge-soft-{{ ($config['status'] ?? 0) == 1 ? 'success' : 'secondary' }} fs-10">
                                            {{ ($config['status'] ?? 0) == 1 ? translate('Active') : translate('Inactive') }}
                                        </span>
                                    </div>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Use Sandbox API-based verification as the primary method for Indian identity/business documents (PAN, GSTIN, Bank), with manual upload as fallback.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-3">
                            <div class="mt-3 mt-md-0">
                                <div class="d-flex justify-content-between align-items-center gap-3 border rounded px-20 py-3 user-select-none">
                                    <span class="fw-medium text-dark">{{ translate('Status') }}</span>
                                    <label class="switcher" for="sandbox-kyc-status-id">
                                        <input class="switcher_input custom-modal-plugin" type="checkbox" value="1" name="status" id="sandbox-kyc-status-id"
                                               {{ ($config['status'] ?? 0) == 1 ? 'checked' : '' }}
                                               data-modal-type="input-change-form"
                                               data-modal-form="#sandbox-kyc-status-form"
                                               data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance-mode-on.png') }}"
                                               data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/maintenance-mode-off.png') }}"
                                               data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Sandbox KYC API')]) }}?"
                                               data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Sandbox KYC API')]) }}?"
                                               data-on-message="<p>{{ translate('If enabled, online API verification will be used as primary for KYC (with manual fallback if configured).') }}</p>"
                                               data-off-message="<p>{{ translate('If disabled, KYC will rely on manual document verification flow.') }}</p>"
                                               data-on-button-text="{{ translate('Turn On') }}"
                                               data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded-8 mt-3 d-flex gap-2 align-items-start">
                        <i class="fi fi-sr-info text-info mt-1"></i>
                        <div>
                            <span>{{ translate('Configure Sandbox credentials here. Secrets are stored securely in system settings and are not shown again once saved (leave blank to keep existing). Use Test mode for integration/testing.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3 justify-content-between align-items-center flex-wrap mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                <i class="fi fi-sr-key d-flex"></i>
                            </span>
                            <div>
                                <h2 class="mb-1">{{ translate('Sandbox API Credentials & Configuration') }}</h2>
                                <p class="mb-0 fs-12">{{ translate('Manage API key/secret, environment, and verification options.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-12 p-sm-20 bg-section rounded">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Mode') }}</label>
                                <select name="mode" class="form-control">
                                    <option value="test" {{ ($config['mode'] ?? 'test') === 'test' ? 'selected' : '' }}>{{ translate('Test (Sandbox)') }}</option>
                                    <option value="live" {{ ($config['mode'] ?? '') === 'live' ? 'selected' : '' }}>{{ translate('Live (Production)') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('API Version') }}</label>
                                <input type="text" class="form-control" name="api_version" value="{{ $config['api_version'] ?? '1.0' }}" placeholder="1.0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('Manual Fallback') }}</label>
                                <select name="manual_fallback" class="form-control">
                                    <option value="1" {{ (int)($config['manual_fallback'] ?? 1) === 1 ? 'selected' : '' }}>{{ translate('Enabled (allow manual upload if online fails/unavailable)') }}</option>
                                    <option value="0" {{ (int)($config['manual_fallback'] ?? 1) === 0 ? 'selected' : '' }}>{{ translate('Disabled') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Base URL (Test)') }}</label>
                                <input type="text" class="form-control" name="base_url_test" value="{{ $config['base_url_test'] ?? 'https://test-api.sandbox.co.in' }}" placeholder="https://test-api.sandbox.co.in">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Base URL (Live)') }}</label>
                                <input type="text" class="form-control" name="base_url_live" value="{{ $config['base_url_live'] ?? 'https://api.sandbox.co.in' }}" placeholder="https://api.sandbox.co.in">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ translate('API Key') }}
                                    @if(!empty($config['api_key']))<small class="text-muted">({{ translate('saved') }})</small>@endif
                                </label>
                                <input type="password" class="form-control" name="api_key" autocomplete="off"
                                       placeholder="{{ !empty($config['api_key']) ? translate('Leave blank to keep existing key') : translate('Enter Sandbox API key') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('API Secret') }}
                                    @if(!empty($config['api_secret']))<small class="text-muted">({{ translate('saved') }})</small>@endif
                                </label>
                                <div class="position-relative">
                                    <input type="password" class="form-control pe-5" name="api_secret" autocomplete="off"
                                           placeholder="{{ !empty($config['api_secret']) ? translate('Leave blank to keep existing secret') : translate('Enter Sandbox API secret') }}">
                                    <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                        <i class="fi fi-sr-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12"><h5 class="mb-0">{{ translate('Online Verification Options') }}</h5></div>
                            <div class="col-md-4">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="verify_pan" value="0">
                                    <input type="checkbox" name="verify_pan" value="1" {{ (int)($config['verify_pan'] ?? 1) === 1 ? 'checked' : '' }}>
                                    <span>{{ translate('Verify PAN online') }}</span>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="verify_gstin" value="0">
                                    <input type="checkbox" name="verify_gstin" value="1" {{ (int)($config['verify_gstin'] ?? 1) === 1 ? 'checked' : '' }}>
                                    <span>{{ translate('Verify GSTIN online') }}</span>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="verify_bank" value="0">
                                    <input type="checkbox" name="verify_bank" value="1" {{ (int)($config['verify_bank'] ?? 1) === 1 ? 'checked' : '' }}>
                                    <span>{{ translate('Verify Bank Account online') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary w-120 px-4">{{ translate('Reset') }}</button>
                        <button type="{{ config('app.mode') != 'demo' ? 'submit' : 'button' }}"
                                class="btn btn-primary px-4 {{ config('app.mode') != 'demo' ? '' : 'call-demo-alert' }}">
                            {{ translate('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
