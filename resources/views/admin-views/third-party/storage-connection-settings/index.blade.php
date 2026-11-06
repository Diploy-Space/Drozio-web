@extends('layouts.admin.app')

@section('title', translate('Storage connection settings'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-settings-sliders text-primary d-flex"></i>
                {{ translate('3rd Party') }} - {{ translate('Other Configurations') }}
            </h2>
        </div>
        @include('admin-views.third-party._third-party-others-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
                {{ translate('You can manage all your storage files from') }}
                <a href="{{ route('admin.settings.system-setup.file-manager.index') }}" target="_blank"
                   class="text-decoration-underline fw-semibold">{{ translate('Gallery') }}</a>.
            </span>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                                <i class="fi fi-sr-database d-flex"></i>
                            </span>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h2 class="mb-0">{{ translate('Storage Connection') }}</h2>
                                <span class="badge badge-soft-{{ $storageConnectionType == 's3' ? 'success' : 'info' }} fs-10">
                                    {{ $storageConnectionType == 's3' ? translate('3rd Party Storage') : translate('Local Storage') }}
                                </span>
                            </div>
                        </div>
                        <p class="fs-12 mb-0">
                            {{ translate('Choose where newly uploaded files and data will be stored') }}.
                        </p>
                        <div
                            class="bg-warning bg-opacity-10 fs-12 px-12 py-20 text-dark rounded d-flex gap-2 align-items-center mt-5">
                            <i class="fi fi-sr-info text-warning"></i>
                            <span>
                                {{ translate('Third-party storage is not configured yet. Set it up first.') }}.
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="p-12 p-sm-20 bg-section rounded-8">
                            <div class="form-group">
                                <label class="form-label" for="">{{ translate('Select Storage Connection Model') }}
                                    <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                          aria-label="{{ translate('Select Storage Connection Model') }}"
                                          data-bs-title="{{ translate('Select Storage Connection Model') }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <div class="bg-white p-3 rounded">
                                    <div class="row g-4">
                                        <div class="col-xl-6 col-md-6">
                                            <div class="form-check d-flex gap-3">
                                                <form
                                                    action="{{ route('admin.settings.third-party.storage-connection-settings.update-storage-type') }}"
                                                    method="post" id="storage-connection-local-form"
                                                    data-from="storage-connection-type">
                                                    @csrf
                                                    <input type="hidden" name="type" value="public">
                                                    <input
                                                        class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                        type="radio"
                                                        name="status" id="storage-connection-local"
                                                        {{ $storageConnectionType == null || $storageConnectionType == 'public' ? 'checked' : '' }}
                                                        value="1"
                                                        data-modal-id="toggle-status-modal"
                                                        data-toggle-id="storage-connection-local"
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#storage-connection-local-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/local-storage.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/local-storage.png') }}"
                                                        data-on-title="{{ translate('Do you want to switch 3rd party storage to local storage') }} ?"
                                                        data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Local Storage')]) }}"
                                                        data-on-message="<p>{{ translate('Newly uploaded files are stored locally.') }}</p>"
                                                        data-off-message="<p>{{ translate('System will store all files and images to 3rd party storage') }}</p>"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}"
                                                    >
                                                </form>
                                                <div class="flex-grow-1">
                                                    <label for="verification_by_email"
                                                           class="form-label text-dark fw-semibold mb-1">
                                                        {{ translate('Local Storage') }}
                                                    </label>
                                                    <p class="fs-12 mb-3">
                                                        {{ translate('Newly uploaded files are stored locally.') }}
                                                    </p>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-md-6"
                                             @if(!$storageConnectionS3Credential) data-bs-toggle="tooltip"
                                             data-bs-placement="top" aria-label="Select Business Model"
                                             data-bs-title="{{ translate('3rd party storage is currently disabled, please configure the following data first') }}" @endif>
                                            <div
                                                class="form-check d-flex gap-3 {{ !$storageConnectionS3Credential ? 'disabled' : '' }}">
                                                <form
                                                    action="{{ route('admin.settings.third-party.storage-connection-settings.update-storage-type') }}"
                                                    method="post" id="storage-connection-s3-form"
                                                    data-from="storage-connection-type">
                                                    @csrf
                                                    <input type="hidden" name="type" value="s3">
                                                    <input
                                                        class="form-check-input radio--input radio--input_lg custom-modal-plugin"
                                                        type="radio"
                                                        name="status" id="storage-connection-local"
                                                        {{ $storageConnectionType == 's3' ? 'checked' : '' }}
                                                        value="1"
                                                        data-modal-id="toggle-status-modal"
                                                        data-toggle-id="storage-connection-local"
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#storage-connection-s3-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/3rd-party-storage-image.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/3rd-party-storage-image.png') }}"
                                                        data-on-title="{{translate('Do you want to switch local storage to 3rd party storage').'?'}}"
                                                        data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('3rd Party Storage')]).'?'}}"
                                                        data-on-message="<p>{{translate('Newly uploaded files are stored on third-party storage.')}}</p>"
                                                        data-off-message="<p>{{translate('System will store all files and images to local storage')}}</p>"
                                                        data-on-button-text="{{ translate('Turn On') }}"
                                                        data-off-button-text="{{ translate('Turn Off') }}"
                                                    >
                                                </form>
                                                <div class="flex-grow-1">
                                                    <label for="verification_by_phone"
                                                           class="form-label text-dark fw-semibold mb-1 d-flex align-items-center gap-2">
                                                        {{ translate('3rd Party Storage') }}
                                                        @if(!$storageConnectionS3Credential)
                                                            <span class="badge badge-soft-secondary fs-10 d-inline-flex align-items-center gap-1">
                                                                <i class="fi fi-sr-lock d-flex"></i>{{ translate('Locked') }}
                                                            </span>
                                                        @endif
                                                    </label>
                                                    <p class="fs-12 mb-0">
                                                        {{ translate('If enable this newly uploaded') }}
                                                        /{{ translate('Created files and data will be stored in 3rd party storage') }}
                                                        .
                                                    </p>
                                                </div>
                                            </div>
                                            @if(!$storageConnectionS3Credential)
                                                <div class="bg-warning bg-opacity-10 text-dark fs-12 rounded px-12 py-2 mt-2 d-flex align-items-start gap-2">
                                                    <i class="fi fi-sr-info text-warning mt-1"></i>
                                                    <span>{{ translate('Save your S3 credentials below first to enable 3rd party storage') }}.</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="method-icon rounded-circle border bg-white d-inline-flex align-items-center justify-content-center flex-shrink-0 text-primary">
                        <i class="fi fi-sr-cloud d-flex"></i>
                    </span>
                    <div>
                        <h2 class="mb-1">
                            {{translate('S3 Credential')}}
                        </h2>
                        <p class="fs-12 mb-0">{{ translate('Public identifier used to authenticate S3 requests.') }}
                            <a href="{{ 'https://drive.google.com/file/d/1vlzak2-pBD8zS-tVGRZkAUhlRT_QfRY9/view' }}"
                               target="_blank" class="text-decoration-underline fw-medium">{{ translate('Learn more') }}</a>
                        </p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.third-party.storage-connection-settings.s3-credential') }}" method="POST" autocomplete="off" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation"
                      id="get-storage-connection-route" novalidate>
                    @csrf
                    <div class="p-12 p-sm-20 bg-section rounded-8">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label text-capitalize" for="key-cred">
                                        {{ translate('Access key')}} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           placeholder="{{translate('Enter your access key')}}" id="key-cred"
                                           name="s3_key"
                                           data-required-msg="{{translate('S3 key field is required')}}"
                                           value="{{ $storageConnectionS3Credential['key'] ?? '' }}" required>
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('Your unique public key used to authenticate s3 requests.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label text-capitalize" for="key-secret-cred">
                                        {{ translate('Secret access key') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control pe-5" autocomplete="new-password"
                                               placeholder="{{translate('Enter your secret access key')}}"
                                               data-required-msg="{{translate('Secret key field is required')}}"
                                               id="key-secret-cred" name="s3_secret"
                                               value="{{ $storageConnectionS3Credential['secret'] ?? '' }}" required>
                                        <span class="changePassTarget position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-body-secondary" role="button" aria-label="{{ translate('Show hide') }}">
                                            <i class="fi fi-sr-eye"></i>
                                        </span>
                                    </div>
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('The private key paired with your access key for secure authentication.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="key-region">{{translate('Region')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           placeholder="{{translate('Enter your region')}}" id="key-region"
                                           name="s3_region"
                                           data-required-msg="{{translate('Region key field is required')}}"
                                           value="{{ $storageConnectionS3Credential['region'] ?? '' }}" required>
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('The aws region where your s3 bucket is hosted.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="key-bucket">{{ translate('Bucket') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           placeholder="{{translate('Enter your bucket')}}" id="key-bucket"
                                           name="s3_bucket"
                                           data-required-msg="{{translate('Bucket key field is required')}}"
                                           value="{{ $storageConnectionS3Credential['bucket'] ?? '' }}" required>
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('The name of the s3 bucket where your files will be stored.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="key-url">{{ translate('URL') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           placeholder="{{translate('Enter url')}}" id="key-url"
                                           name="s3_url"
                                           data-required-msg="{{translate('Url field is required')}}"
                                           value="{{ $storageConnectionS3Credential['url'] ?? '' }}" required>
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('The base url used to access your s3 bucket.') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="key-endpoint">
                                        {{ translate('Endpoint') }}
                                        <span class="badge badge-soft-secondary fs-10">{{ translate('Optional') }}</span>
                                    </label>
                                    <input type="text" class="form-control" autocomplete="off"
                                           placeholder="{{translate('Enter your endpoint')}}"
                                           id="key-endpoint" name="s3_endpoint"
                                           value="{{ $storageConnectionS3Credential['endpoint'] ?? '' }}">
                                    <small class="text-body-secondary d-block mt-1">
                                        {{ translate('The custom endpoint for your s3-compatible storage (optional for aws s3).') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                            {{ translate('Reset') }}
                        </button>
                        <button type="button"
                                data-message="{{ translate('Please ensure your s3 credentials are valid.') }}"
                                class="btn btn-primary px-3 px-sm-4 {{config('app.mode')!= 'demo'? 'form-submit' : 'call-demo-alert'}}"
                                data-form-id="get-storage-connection-route">
                            <i class="fi fi-sr-disk"></i>
                            {{ translate('Save Information') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include("layouts.admin.partials.offcanvas._3rd-party-storage-connection-setup")
@endsection
