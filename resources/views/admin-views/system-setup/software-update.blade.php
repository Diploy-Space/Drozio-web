@extends('layouts.admin.app')

@section('title', translate('Software Update'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/software-update.css') }}">
@endpush

@php
    $requiredFileSizeLimit = 200;
    $conditionOne = checkUploadMaxFileSizeLimit(limit: $requiredFileSizeLimit);
    $conditionTwo = checkPostMaxFileSizeLimit(limit: $requiredFileSizeLimit);
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
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

        <div class="row row-cols-1 row-cols-md-3 g-3 mb-3" id="sw-status-cards">
            <div class="col">
                <div class="card h-100 sw-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-rocket-lunch fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold sw-status-value">{{ SOFTWARE_VERSION }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Current version') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 sw-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $conditionOne ? 'success' : 'danger' }} bg-opacity-10 text-{{ $conditionOne ? 'success' : 'danger' }} rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-{{ $conditionOne ? 'check-circle' : 'cross-circle' }} fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold sw-status-value">{{ $uploadMax }}B <span class="fs-12 fw-normal text-muted">({{ translate('Required') }}: {{ $requiredFileSizeLimit }}MB)</span></h3>
                            <span class="text-muted fs-12">upload_max_filesize <span class="text-{{ $conditionOne ? 'success' : 'danger' }}">({{ $conditionOne ? translate('Okay') : translate('Too low') }})</span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 sw-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $conditionTwo ? 'success' : 'danger' }} bg-opacity-10 text-{{ $conditionTwo ? 'success' : 'danger' }} rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-{{ $conditionTwo ? 'check-circle' : 'cross-circle' }} fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold sw-status-value">{{ $postMax }}B <span class="fs-12 fw-normal text-muted">({{ translate('Required') }}: {{ $requiredFileSizeLimit }}MB)</span></h3>
                            <span class="text-muted fs-12">post_max_size <span class="text-{{ $conditionTwo ? 'success' : 'danger' }}">({{ $conditionTwo ? translate('Okay') : translate('Too low') }})</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('Your current version is') }} {{ SOFTWARE_VERSION }}. {{ translate('To check our new version') }}
                <a class="text-decoration-underline fw-semibold" target="_blank"
                   href="https://codecanyon.net/item/6valley-multivendor-ecommerce-complete-ecommerce-mobile-app-web-and-admin-panel/31448597">
                    {{ translate('Click Here') }}
                </a>
            </span>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="mb-1 text-capitalize d-flex align-items-center gap-2">
                    <i class="fi fi-sr-cloud-upload-alt text-primary d-flex"></i>
                    {{ translate('Upload the updated file') }}
                </h3>
                <p class="mb-0 fs-12 text-muted">
                    {{ translate('Update your system by uploading our zip file.') }}
                </p>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.system-setup.software-update') }}" method="post"
                      enctype="multipart/form-data" id="software-update-form" data-redirect-route="{{ route('home') }}"
                      data-input-warning="{{ translate('Please upload the file first') }}"
                      data-request-failed="{{ translate('The update could not be completed. Please try again.') }}"
                      data-update-in-progress="{{ translate('System update is in progress. Please wait, the page will reload shortly.') }}">
                    @csrf
                    <div class="d-flex flex-column gap-3 gap-sm-20">
                        <div class="bg-section rounded-8 p-12 p-sm-20">
                            <div class="row g-4">
                                <div class="col-lg-6 sw-field">
                                    <div class="form-group">
                                        <label class="form-label" for="username">
                                            {{ translate('Codecanyon username') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="username"
                                                   value="{{ envFileValue('BUYER_USERNAME') }}" name="username" required readonly>
                                            <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('This field is in read-only mode.') }}"
                                                  data-bs-title="{{ translate('This field is in read-only mode.') }}">
                                                <i class="fi fi-sr-lock"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 sw-field">
                                    <div class="form-group">
                                        <label class="form-label" for="purchase_key">
                                            {{ translate('Purchase code') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="purchase_key"
                                                   value="{{ envFileValue('PURCHASE_CODE') }}" name="purchase_key" required readonly>
                                            <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('This field is in read-only mode.') }}"
                                                  data-bs-title="{{ translate('This field is in read-only mode.') }}">
                                                <i class="fi fi-sr-lock"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="bg-section rounded-8 p-12 p-sm-20">
                                    <div class="file-upload-parent {{ $conditionOne && $conditionTwo ? '' : 'disabled' }}">
                                        <div class="custom-file-upload mb-4">
                                            <input type="file" accept=".zip" data-max-file-size="512MB" name="update_file" id="input-file"/>
                                            <div class="text-center">
                                                <div class="mb-20">
                                                    <i class="fi fi-rr-cloud-upload-alt fs-1 text-black-50"></i>
                                                </div>
                                                <p class="mb-0 fs-14 mb-1">
                                                    {{ translate('Select a file or') }}
                                                    <span class="fw-semibold">{{ translate('Drag & Drop') }}</span>
                                                    {{ translate('Here') }}
                                                </p>
                                                <div class="mb-0 fs-12">{{ translate('Total file size no more than 512 MB') }}</div>
                                                <div class="btn btn-outline-primary mt-30 trigger_input_btn">{{ translate('Select File') }}</div>
                                            </div>
                                        </div>
                                        <div class="file-preview-list d-flex flex-column gap-4"></div>
                                        <div id="file-upload-config" data-icon-src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/icons/file-view.png') }}"></div>

                                        <div class="mt-4 d--none progress-bar-container">
                                            <div class="d-flex justify-content-between mb-1 flex-wrap gap-2">
                                                <span>{{ translate('Progress') }}...</span>
                                                <span class="upload-progress-label"></span>
                                            </div>
                                            <div class="progress" role="progressbar" aria-label="Success" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar progress-bar-striped bg-success upload-progress-bar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="bg-warning bg-opacity-10 fs-12 px-20 py-3 text-dark rounded-8 h-100 d-flex justify-content-center flex-column">
                                    <h3 class="text-info-dark">{{ translate('Instructions') }}</h3>
                                    <ul class="m-0 ps-20 d-flex flex-column gap-1 text-body">
                                        <li>{{ translate('Please make sure your server PHP :setting value is greater than or equal to :limit', ['setting' => '"'.translate('Upload max filesize').'"', 'limit' => $requiredFileSizeLimit.'MB']).'. '.translate('Current value is').' - '.$uploadMax.'B' }}</li>
                                        <li>{{ translate('Please make sure your server PHP :setting value is greater than or equal to :limit', ['setting' => '"'.translate('Post max size').'"', 'limit' => $requiredFileSizeLimit.'MB']).'. '.translate('Current value is').' - '.$postMax.'B' }}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap justify-content-end gap-3">
                                    <button type="reset" class="btn btn-secondary px-sm-4 min-w-120">{{ translate('Cancel') }}</button>
                                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                                            {{ $conditionOne && $conditionTwo ? '' : 'disabled' }}
                                            class="btn btn-primary px-sm-4 min-w-120 {{ getDemoModeFormButton(type: 'class') }}">
                                        <i class="fi fi-sr-disk"></i>
                                        {{ translate('Save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._software-update-setup")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/system-setup/system-setup.js') }}"></script>
@endpush
