@extends('layouts.admin.app')

@section('title', translate('Download App'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.business-settings.vendor-registration-setting.partial.inline-menu')

        <div class="d-flex flex-column gap-3 gap-sm-20">
            <form action="{{route('admin.settings.pages-and-media.vendor-registration-settings.download-app') }}" method="post"
                  enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                @csrf
                <input type="hidden" name="section_type" value="title_section">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center gy-3 mb-3 mb-sm-20">
                            <div class="col-md-9">
                                <div>
                                    <h2>{{ translate('Download App Section') }}</h2>
                                    <p class="fs-12 mb-0">
                                        {{ translate('This section is about your vendor app') }}.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div
                                    class="d-flex justify-content-between align-items-center gap-3 rounded px-20 py-3 user-select-none bg-section">
                                    <span class="fw-semibold text-dark">{{ translate('Status') }}</span>
                                    <label class="switcher"
                                           for="download-app-status">
                                        <input
                                            class="switcher_input custom-modal-plugin"
                                            type="checkbox" value="1" name="status"
                                            id="download-app-status"
                                            {{ isset($downloadVendorApp->status) && $downloadVendorApp?->status == 1 ? 'checked' : '' }}
                                            data-modal-type="input-change"
                                            data-on-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-on.svg') }}"
                                            data-off-image="{{ dynamicAsset(path: 'public/assets/backend/media/toggle-modal-icons/turn-off.svg') }}"
                                            data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Status')]).'?'}}"
                                            data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Status')]).'?'}}"
                                            data-on-message="<p>{{ translate('Once you turn on the status and complete the setup')}}, {{ translate('This section will be displayed on the vendor registration page') }}</p>"
                                            data-off-message="<p>{{ translate('Once you turn off the status')}}, {{ translate('This section will not be displayed on the vendor registration page') }}</p>"
                                            data-on-button-text="{{ translate('Turn On') }}"
                                            data-off-button-text="{{ translate('Turn Off') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-3 mb-4">
                            <div class="col-lg-8">
                                <div class="p-12 p-sm-20 bg-section rounded h-100">
                                    <div>
                                        <label class="form-label">
                                            {{ translate('Section title') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Short, engaging title telling vendors this section is about the seller app.') }}" data-bs-title="{{ translate('Short, engaging title telling vendors this section is about the seller app.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" name="title" rows="1"
                                                  placeholder="{{ translate('Enter Title') }}"
                                                  data-maxlength="50">{{$downloadVendorApp?->title}}</textarea>
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/50</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">
                                            {{ translate('Subtitle') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Brief description of your vendor app benefits, such as easy setup.') }}" data-bs-title="{{ translate('Brief description of your vendor app benefits, such as easy setup.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" name="sub_title" rows="2"
                                                  placeholder="{{ translate('Enter Subtitle') }}"
                                                  data-maxlength="160">{{$downloadVendorApp?->sub_title}}</textarea>
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/160</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="p-12 p-sm-20 bg-section rounded h-100">
                                    <div class="d-flex flex-column gap-20">
                                        <div>
                                            <label for="" class="form-label fw-semibold mb-1">
                                                {{ translate('Header Image') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <p class="fs-12 mb-0">{{ translate('Upload your Header Image') }}</p>
                                        </div>
                                        <div class="upload-file">
                                            @php($imagePath = imagePathProcessing(imageData:$downloadVendorApp?->image, path: 'vendor-registration-setting'))
                                            <input type="file" name="image" class="upload-file__input single_file_input"
                                                   accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                   data-required-msg="{{ translate('Image field is required') }}"
                                                   value="{{ getStorageImages(path:imagePathProcessing(imageData: $downloadVendorApp?->image, path: 'vendor-registration-setting'),type: 'backend-basic') ?? '' }}" {{ !empty($imagePath['path']) ? '' : 'required' }}>
                                            <label
                                                class="upload-file__wrapper">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}" alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center">
                                                        <span class="text-info">
                                                            {{ translate('Click to upload') }}
                                                        </span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy" src="{{ getStorageImages(path:imagePathProcessing(imageData: $downloadVendorApp?->image, path: 'vendor-registration-setting'),type: 'backend-basic') ?? '' }}" alt="">
                                            </label>


                                            <div class="overlay">
                                                <div
                                                    class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                        <i class="fi fi-sr-eye"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-outline-info icon-btn edit_btn">
                                                        <i class="fi fi-rr-camera"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-10 mb-0 text-center">
                                            {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                            <span class="fw-medium text-dark">{{ "(1:1)" }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-3">
                            <button type="reset" class="btn btn-secondary px-4 w-120">{{ translate('Reset') }}</button>
                            <button type="submit" class="btn btn-primary px-4 w-120"><i class="fi fi-sr-disk"></i> {{ translate('Submit') }}</button>
                        </div>
                    </div>
                </div>
            </form>
            <div
                class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                <i class="fi fi-sr-info text-warning"></i>
                <span>
                   {{ translate('Check the links are correct, or buttons will redirect wrongly.') }}.
                </span>
            </div>
            <form action="{{route('admin.settings.pages-and-media.vendor-registration-settings.download-app') }}" method="post"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="section_type" value="link_section">
                <div class="card">
                    <div class="card-body">
                        <div class="row gy-3 mb-4">
                            <div class="col-lg-6">
                                <div class="d-flex gap-2 align-items-center justify-content-between mb-3 mb-sm-20">
                                    <div class="d-flex gap-2 align-items-center">
                                        <img width="24"
                                             src="{{dynamicAsset(path: 'public/assets/new/back-end/img/play_store.png') }}"
                                             alt="">
                                        <h3>{{ translate('For android') }}</h3>
                                    </div>
                                    <label class="switcher">
                                        <input type="checkbox" name="download_google_app_status" value="1"
                                               class="switcher_input" {{isset($downloadVendorApp?->download_google_app_status) && $downloadVendorApp?->download_google_app_status == 1  ? 'checked' : ''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>

                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="form-group">
                                        <label class="form-label">
                                            {{ translate('Download link') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Shows the Google Play button in the website footer.') }}"
                                                  data-bs-title="{{ translate('Shows the Google Play button in the website footer.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="url" name="download_google_app" class="form-control"
                                               value="{{ $downloadVendorApp?->download_google_app}}"
                                               placeholder="{{ translate('Ex') }}: https://play.google.com/store/apps">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex gap-2 align-items-center justify-content-between mb-3 mb-sm-20">
                                    <div class="d-flex gap-2 align-items-center">
                                        <img width="24"
                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/apple.png') }}"
                                             alt="">
                                        <h3>{{ translate('For iOS') }}</h3>
                                    </div>
                                    <label class="switcher">
                                        <input type="checkbox" name="download_apple_app_status" value="1"
                                               class="switcher_input" {{ isset($downloadVendorApp?->download_apple_app_status) && $downloadVendorApp?->download_apple_app_status == 1  ? 'checked' : ''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>

                                <div class="p-12 p-sm-20 bg-section rounded">
                                    <div class="form-group">
                                        <label class="form-label">
                                            {{ translate('Download link') }}
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Shows the App Store download button in the footer.') }}"
                                                  data-bs-title="{{ translate('Shows the App Store download button in the footer.') }}">
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <input type="url" name="download_apple_app" class="form-control"
                                               value="{{ $downloadVendorApp?->download_apple_app }}"
                                               placeholder="{{ translate('Ex').':'.'https://www.apple.com/app-store/'}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-3">
                            <button type="reset" class="btn btn-secondary px-4 w-120">
                                {{ translate('Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary px-4 w-120">
                                <i class="fi fi-sr-disk"></i>
                                {{ translate('Submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._vendor-reg-download-app")
@endsection
