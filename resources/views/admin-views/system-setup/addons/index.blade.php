@extends('layouts.admin.app')

@section('title', translate('System Addons'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/addons.css') }}">
@endpush

@php($conditionOne = checkUploadMaxFileSizeLimit(limit: 20))
@php($conditionTwo = checkPostMaxFileSizeLimit(limit: 20))
@php($total = count($addonInfos))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-puzzle-piece text-primary d-flex"></i>
                {{ translate('System Addons') }}
            </h2>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-3" id="addon-status-cards">
            <div class="col">
                <div class="card h-100 addon-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-puzzle-piece fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold addon-status-value" data-addon-status="total">{{ $total }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Total addons') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 addon-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-check-circle fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold addon-status-value" data-addon-status="active">{{ $statusCounts['active'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 addon-status-card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary rounded d-flex align-items-center justify-content-center">
                            <i class="fi fi-sr-ban fs-5 d-flex"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="mb-0 fw-bold addon-status-value" data-addon-status="inactive">{{ $statusCounts['inactive'] }}</h3>
                            <span class="text-muted fs-12 text-capitalize">{{ translate('Inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-column gap-20">
                <div class="d-flex justify-content-between align-items-center gap-20 flex-wrap">
                    <h3 class="mb-0">
                        {{ translate('Available Addons') }}
                        <span class="badge text-dark bg-body-secondary fw-semibold rounded-50" data-addon-status="total">{{ $total }}</span>
                    </h3>
                    <button class="btn btn-primary" title="{{ translate('Upload Addon') }}"
                            data-bs-toggle="offcanvas" href="#uploadAddonOffcanvas">
                        <i class="fi fi-sr-cloud-upload-alt"></i>
                        {{ translate('Upload Addon') }}
                    </button>
                </div>

                <div id="addon-list-wrapper">
                    @include('admin-views.system-setup.addons.partials._addon-list-wrapper', ['addonInfos' => $addonInfos])
                </div>
            </div>
        </div>
    </div>

    <form enctype="multipart/form-data" id="addon-upload-form" action="{{ route('admin.settings.system-setup.addon.upload') }}" method="POST"
          class="form-advance-validation form-advance-inputs-validation form-advance-file-validation" novalidate>
        @csrf
        <div class="offcanvas offcanvas-end" tabindex="-1" id="uploadAddonOffcanvas" aria-labelledby="uploadAddonOffcanvasLabel">
            <div class="offcanvas-header bg-body">
                <div>
                    <h3 class="mb-1 text-capitalize" id="uploadAddonOffcanvasLabel">{{ translate('Upload Addons') }}</h3>
                    <p class="mb-0 fs-12 text-muted">
                        {{ translate('Upload add-ons for the customer website.') }}
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="d-flex flex-column gap-3">
                    <div class="bg-section rounded-8 p-12 p-sm-20">
                        <div class="file-upload-parent {{ $conditionOne && $conditionTwo ? '' : 'disabled' }}">
                            <div class="custom-file-upload mb-4">
                                <input type="file" data-required-msg="{{ translate('Only zip file is allowed') }}"
                                       data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                                       accept=".zip,application/zip,application/x-zip-compressed,application/octet-stream"
                                       name="file_upload" id="input-file" required/>
                                <div class="text-center">
                                    <div class="mb-20">
                                        <i class="fi fi-rr-cloud-upload-alt fs-1 text-black-50"></i>
                                    </div>
                                    <p class="mb-0 fs-14 mb-1">
                                        {{ translate('Select a file or') }}
                                        <span class="fw-semibold">{{ translate('Drag & Drop') }}</span>
                                        {{ translate('Here') }}
                                    </p>
                                    <div class="mb-0 fs-12 text-muted">
                                        {{ translate('Maximum file size') }}: {{ getFileUploadMaxSize(type: 'file') }} MB (.zip)
                                    </div>
                                    <div class="btn btn-outline-primary mt-30 trigger_input_btn">
                                        {{ translate('Select File') }}
                                    </div>
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

                    <div class="bg-section rounded-8 p-12 p-sm-20">
                        <h3 class="fs-14 mb-3 text-capitalize">{{ translate('Server requirements') }}</h3>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                            <li class="d-flex align-items-start gap-2">
                                <i class="fi fi-sr-{{ $conditionOne ? 'check-circle text-success' : 'cross-circle text-danger' }} d-flex mt-1"></i>
                                <span class="fs-12">
                                    <span class="fw-semibold">upload_max_filesize</span> — {{ translate('At least') }} 20MB
                                    <span class="text-muted">({{ translate('Current') }}: {{ ini_get('upload_max_filesize') }}B)</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="fi fi-sr-{{ $conditionTwo ? 'check-circle text-success' : 'cross-circle text-danger' }} d-flex mt-1"></i>
                                <span class="fs-12">
                                    <span class="fw-semibold">post_max_size</span> — {{ translate('At least') }} 20MB
                                    <span class="text-muted">({{ translate('Current') }}: {{ ini_get('post_max_size') }}B)</span>
                                </span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="fi fi-sr-info text-info d-flex mt-1"></i>
                                <span class="fs-12">
                                    {{ translate('Accepted') }}: .zip {{ translate('File') }}, {{ translate('Up to') }} {{ getFileUploadMaxSize(type: 'file') }} MB
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer shadow-popup">
                <div class="d-flex justify-content-end flex-wrap gap-3 bg-white px-3 py-2">
                    <button type="button" class="btn btn-secondary px-sm-4 min-w-120" data-bs-dismiss="offcanvas">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}"
                            {{ $conditionOne && $conditionTwo ? '' : 'disabled' }}
                            class="btn btn-primary px-sm-4 min-w-120 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="activatedThemeModal" tabindex="-1" role="dialog"
         aria-labelledby="activatedThemeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" id="activateData"></div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._system-addons")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/addon.js') }}"></script>
@endpush
