@extends('layouts.admin.app')

@section('title', translate('Environment Settings'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/environment-setup.css') }}">
@endpush

@php
    $appMode = config('app.mode');
    $isDemo = $appMode === 'demo';
    $appDebug = (bool) envFileValue('APP_DEBUG');
    $appUrl = (string) envFileValue('APP_URL');
    $isHttps = str_starts_with(strtolower($appUrl), 'https');
    $dbName = ! $isDemo ? envFileValue('DB_DATABASE') : '---';

    $applicationFields = [
        ['label' => 'app_name', 'value' => envFileValue('APP_NAME')],
        ['label' => 'app_url', 'value' => $appUrl],
    ];
    $databaseFields = [
        ['label' => 'DB_connection', 'value' => ! $isDemo ? envFileValue('DB_CONNECTION') : '---'],
        ['label' => 'DB_host', 'value' => ! $isDemo ? envFileValue('DB_HOST') : '---'],
        ['label' => 'DB_port', 'value' => ! $isDemo ? envFileValue('DB_PORT') : '---'],
        ['label' => 'DB_database', 'value' => $dbName],
        ['label' => 'DB_username', 'value' => ! $isDemo ? envFileValue('DB_USERNAME') : '---'],
    ];
    $secretFields = [
        ['label' => 'DB_password', 'value' => ! $isDemo ? envFileValue('DB_PASSWORD') : '---'],
        ['label' => 'purchase_code', 'value' => envFileValue('PURCHASE_CODE')],
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

        <div class="row row-cols-2 row-cols-xl-4 g-3 mb-3" id="env-status-cards">
            <div class="col">
                <div class="card h-100 env-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $isDemo ? 'info' : ($appMode === 'live' ? 'success' : 'warning') }} bg-opacity-10 text-{{ $isDemo ? 'info' : ($appMode === 'live' ? 'success' : 'warning') }} rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-{{ $appMode === 'live' ? 'rocket-lunch' : 'settings' }} fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold env-status-value text-capitalize">{{ translate($appMode ?: 'unknown') }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('App mode') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 env-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $appDebug ? 'warning' : 'success' }} bg-opacity-10 text-{{ $appDebug ? 'warning' : 'success' }} rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-bug fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold env-status-value text-capitalize">{{ $appDebug ? translate('On') : translate('OFF') }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Debug mode') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 env-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $isHttps ? 'success' : 'secondary' }} bg-opacity-10 text-{{ $isHttps ? 'success' : 'secondary' }} rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-{{ $isHttps ? 'shield-check' : 'lock' }} fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold env-status-value text-uppercase">{{ $isHttps ? 'https' : 'http' }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Connection') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 env-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-database fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold env-status-value text-truncate">{{ $dbName }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Database') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-info text-warning"></i>
            <span>{{ translate('These settings connect directly to your database. Change with care.') }}.</span>
        </div>

        <form action="{{ route('admin.settings.system-setup.environment-setup') }}" method="post" enctype="multipart/form-data" class="mb-3">
            @csrf
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                            <i class="fi fi-sr-sliders-v-square text-primary d-flex"></i>
                            {{ translate('Application configuration') }}
                        </h3>
                        <p class="mb-0 fs-12 text-muted">
                            {{ translate('Change the active field options to see your issue.') }}
                        </p>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">{{ translate('Editable') }}</span>
                </div>
                <div class="card-body">
                    <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded mb-3">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <i class="fi fi-sr-lightbulb-on text-info"></i>
                            <span>{{ translate('The app debug selection field is for debugging your system\'s technical issues') }}.</span>
                        </div>
                        <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                            <li>{{ translate('Debug mode helps check system issues. Choose live or dev mode.') }}.</li>
                        </ul>
                    </div>
                    <div class="row g-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="form-group">
                                <label class="form-label text-capitalize">{{ translate('App debug') }}</label>
                                <select name="app_debug" class="custom-select" data-placeholder="{{ translate('Select from dropdown') }}">
                                    <option></option>
                                    <option value="true" {{ envFileValue('APP_DEBUG') == 1 ? 'selected' : '' }}>{{ translate('True') }}</option>
                                    <option value="false" {{ envFileValue('APP_DEBUG') == 0 ? 'selected' : '' }}>{{ translate('False') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="form-group">
                                <label class="form-label text-capitalize">{{ translate('App mode') }}</label>
                                <select name="app_mode" class="custom-select" data-placeholder="{{ translate('Select from dropdown') }}">
                                    <option></option>
                                    <option value="live" {{ $appMode === 'live' ? 'selected' : '' }}>{{ translate('Live') }}</option>
                                    <option value="dev" {{ $appMode === 'dev' ? 'selected' : '' }}>{{ translate('Dev') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-sm-end justify-content-center gap-3 flex-wrap">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ ! $isDemo ? 'submit' : 'button' }}" class="btn btn-primary px-3 px-sm-4 {{ ! $isDemo ? '' : 'call-demo-alert' }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-check text-primary d-flex"></i>
                        {{ translate('System & database information') }}
                    </h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('These values are set during installation and cannot be edited here') }}.
                    </p>
                </div>
                <span class="badge badge-soft-secondary fw-semibold d-flex align-items-center gap-1">
                    <i class="fi fi-sr-lock"></i> {{ translate('Read only') }}
                </span>
            </div>
            <div class="card-body">
                <div class="env-subsection">
                    <h6 class="env-subsection-title mb-3">{{ translate('Application') }}</h6>
                    <div class="row g-4">
                        @foreach($applicationFields as $field)
                            <div class="col-xl-4 col-md-6 env-field">
                                <label class="form-label text-capitalize">{{ translate($field['label']) }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $field['value'] }}" readonly>
                                    <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ translate('This field is in read-only mode.') }}"
                                          data-bs-title="{{ translate('This field is in read-only mode.') }}">
                                        <i class="fi fi-sr-lock"></i>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="env-subsection">
                    <h6 class="env-subsection-title mb-3">{{ translate('Database') }}</h6>
                    <div class="row g-4">
                        @foreach($databaseFields as $field)
                            <div class="col-xl-4 col-md-6 env-field">
                                <label class="form-label text-capitalize">{{ translate($field['label']) }}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $field['value'] }}" readonly>
                                    <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ translate('This field is in read-only mode.') }}"
                                          data-bs-title="{{ translate('This field is in read-only mode.') }}">
                                        <i class="fi fi-sr-lock"></i>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-xl-4 col-md-6 env-field">
                            <label class="form-label text-capitalize">{{ translate('DB password') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control env-secret-input" value="{{ $secretFields[0]['value'] }}" readonly>
                                <button type="button" class="input-group-text env-reveal" aria-label="{{ translate('Show hide') }}">
                                    <i class="fi fi-sr-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="env-subsection">
                    <h6 class="env-subsection-title mb-3">{{ translate('License') }}</h6>
                    <div class="row g-4">
                        <div class="col-xl-4 col-md-6 env-field">
                            <label class="form-label text-capitalize">{{ translate('Buyer username') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ envFileValue('BUYER_USERNAME') }}" readonly>
                                <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                      aria-label="{{ translate('This field is in read-only mode.') }}"
                                      data-bs-title="{{ translate('This field is in read-only mode.') }}">
                                    <i class="fi fi-sr-lock"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 env-field">
                            <label class="form-label text-capitalize">{{ translate('Purchase code') }}</label>
                            <div class="input-group">
                                <input type="password" class="form-control env-secret-input" value="{{ $secretFields[1]['value'] }}" readonly>
                                <button type="button" class="input-group-text env-reveal" aria-label="{{ translate('Show hide') }}">
                                    <i class="fi fi-sr-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._environment-setup")
@endsection

@push('script')
    <script>
        "use strict";
        $('.env-reveal').on('click', function () {
            const input = $(this).closest('.input-group').find('.env-secret-input');
            const icon = $(this).find('i');
            const reveal = input.attr('type') === 'password';
            input.attr('type', reveal ? 'text' : 'password');
            icon.toggleClass('fi-sr-eye', !reveal).toggleClass('fi-sr-eye-crossed', reveal);
        });
    </script>
@endpush
