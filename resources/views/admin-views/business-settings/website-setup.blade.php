@extends('layouts.admin.app')

@section('title', translate('Website Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3 mb-sm-20 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="h1 mb-0 text-capitalize d-flex flex-column flex-sm-row align-items-sm-center gap-sm-2">
                {{ translate('Business Setup') }}
            </h2>

            @include('admin-views.business-settings._business-module-switcher')
        </div>

        @include('admin-views.business-settings.business-setup-inline-menu')

        <form action="{{ route('admin.settings.business-settings.website-setup') }}" method="POST" enctype="multipart/form-data" id="website-setup-form-element" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf
            <div class="d-flex flex-column gap-sm-20 gap-3 mb-3 sm-sm-4">
                <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                    <i class="fi fi-sr-lightbulb-on text-info"></i>
                    <span>
                        {{ translate('You can upload your business logo, icons, and other important files here.') . ' ' . translate('Changes are saved when you click Save Information.') }}
                    </span>
                </div>
                <div class="card">
                    <div class="card-header py-3">
                        <h2>{{ translate('Websites & Panels') }}</h2>
                        <p class="mb-0 fs-12 text-capitalize">
                            {{ translate('Setup your business logo and icons') }}
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-sm-20 gap-3">
                            <div class="card card-sm shadow-1">
                                <div class="card-body">
                                    <div class="pb-12 pb-sm-20">
                                        <h3>{{ translate('Logo & Loader') }}</h3>
                                        <p class="mb-0 fs-12 text-capitalize">
                                            {{ translate('Set the header, footer, favicon and page loader images.') }}
                                        </p>
                                        <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mt-3">
                                            <i class="fi fi-sr-info text-warning"></i>
                                            <span>
                                            {{ translate('Use the suggested image and GIF ratio for the best presentation.') }}
                                        </span>
                                        </div>
                                    </div>
                                    <div class="p-12 p-sm-20 bg-section rounded">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Website header logo') }}
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <p class="fs-12 mb-0">{{ translate('It will show on the website header left side') }}</p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="company_web_logo" class="upload-file__input single_file_input" id="imageInput" value="{{ getStorageImages(path:$businessSetting['web_logo'],type: 'backend-placeholder') ?? '' }}"
                                                                       accept="{{ getFileUploadFormats(skip: '.svg') }}" {{ !empty($businessSetting['web_logo']) ? '' : 'required' }} data-required-msg="{{ translate('Website header logo field is required') }}" data-max-size="{{ getFileUploadMaxSize() }}">
                                                                <label
                                                                    class="upload-file__wrapper w-325">
                                                                    <div class="upload-file-textbox text-center {{ $businessSetting['web_logo']['path'] ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg" src="{{ getStorageImages(path: $businessSetting['web_logo'] , type: 'backend-placeholder') ?? '' }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center">
                                                                            <span class="text-info text-capitalize">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ getStorageImages(path: $businessSetting['web_logo'] , type: 'backend-basic') }}" alt="" src="{{ $businessSetting['web_logo']['path'] ? getStorageImages(path:$businessSetting['web_logo'],type: 'backend-placeholder') ?? '' :  '' }}" data-default-src="{{ !empty($businessSetting['web_logo']) ? getStorageImages(path:$businessSetting['web_logo'],type: 'backend-placeholder') ?? '' :  '' }}">
                                                                </label>
                                                                <div class="overlay">
                                                                    <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                                            <i class="fi fi-sr-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                                            <i class="fi fi-rr-camera"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="fs-10 mb-0 text-center text-capitalize">
                                                                {{ getFileUploadFormats(skip: '.svg', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                                <span class="text-dark fw-medium">
                                                                    {{ "(325 x 100 px)" }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Website header logo') }} ({{ translate('Mobile View') }})
                                                                </label>
                                                                <p class="fs-12 mb-0">
                                                                    {{ translate('It will show on the website header left side on mobile devices.') }}
                                                                </p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="company_mobile_logo" class="upload-file__input single_file_input" value="{{ getStorageImages(path: $businessSetting['mob_logo'],type: 'backend-placeholder') ?? '' }}" {{ $businessSetting['mob_logo']['path'] ? '' : 'required' }}
                                                                accept="{{ getFileUploadFormats(skip: '.svg') }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Website header logo mobile view field')]) }}" data-max-size="{{ getFileUploadMaxSize() }}">

                                                                <label
                                                                    class="upload-file__wrapper">
                                                                    <div class="upload-file-textbox text-center {{ $businessSetting['mob_logo']['path'] ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path: $businessSetting['mob_logo'] , type: 'backend-placeholder') }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center text-capitalize">
                                                                            <span class="text-info">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ !empty($businessSetting['mob_logo']['path']) ? getStorageImages(path:$businessSetting['mob_logo'],type: 'backend-placeholder') ?? '' :  '' }}" data-default-src="{{ !empty($businessSetting['mob_logo']['path']) ? getStorageImages(path:$businessSetting['mob_logo'],type: 'backend-placeholder') ?? '' :  '' }}" alt="">
                                                                </label>
                                                                <div class="overlay">
                                                                    <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                                            <i class="fi fi-sr-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                                            <i class="fi fi-rr-camera"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <p class="fs-10 mb-0 text-center">
                                                                {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                                <span class="fw-medium">{{ "(325 x 100 px)" }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Website footer logo') }}
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <p class="fs-12 mb-0">{{ translate('It will show on the website footer left side') }}</p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="company_footer_logo" class="upload-file__input single_file_input"
                                                                       accept="{{ getFileUploadFormats(skip: '.svg') }}"  value="{{ getStorageImages(path:$businessSetting['footer_logo'],type: 'backend-placeholder') ?? '' }}" {{ !empty($businessSetting['footer_logo']) ? '' : 'required' }} data-max-size="{{ getFileUploadMaxSize() }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Website footer logo')]) }}">
                                                                <label
                                                                    class="upload-file__wrapper w-325">
                                                                    <div class="upload-file-textbox text-center {{ $businessSetting['footer_logo']['path'] ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path:$businessSetting['footer_logo'],type: 'backend-placeholder') ?? '' }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center">
                                                                            <span class="text-info text-capitalize">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ !empty($businessSetting['footer_logo']['path']) ? getStorageImages(path:$businessSetting['footer_logo'],type: 'backend-placeholder') ?? '' :  '' }}" data-default-src="{{ !empty($businessSetting['footer_logo']) ? getStorageImages(path:$businessSetting['footer_logo'],type: 'backend-placeholder') ?? '' :  '' }}" alt="">
                                                                </label>
                                                                <div class="overlay">
                                                                    <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                                            <i class="fi fi-sr-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                                            <i class="fi fi-rr-camera"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="fs-10 mb-0 text-center">
                                                                {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                                <span class="fw-medium">{{ "(325 x 100 px)" }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Website Favicon') }}
                                                                    <span class="text-danger">*</span>
                                                                </label>
                                                                <p class="fs-12 mb-0">{{ translate('It will show as a website favicon') }}.</p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="company_fav_icon" class="upload-file__input single_file_input" value="{{ getStorageImages(path:$businessSetting['fav_icon'],type: 'backend-placeholder') ?? '' }}" {{ !empty($businessSetting['fav_icon']) ? '' : 'required' }}
                                                                       accept="{{ getFileUploadFormats(skip: '.svg') }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Website Favicon')]) }}" data-max-size="{{ getFileUploadMaxSize() }}">
                                                                <label
                                                                    class="upload-file__wrapper">
                                                                    <div class="upload-file-textbox text-center {{ $businessSetting['fav_icon']['path'] ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path: $businessSetting['fav_icon'] , type: 'backend-placeholder') }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center text-capitalize">
                                                                            <span class="text-info">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ !empty($businessSetting['fav_icon']['path']) ? getStorageImages(path:$businessSetting['fav_icon'],type: 'backend-placeholder') ?? '' :  '' }}" data-default-src="{{ !empty($businessSetting['fav_icon']) ? getStorageImages(path:$businessSetting['fav_icon'],type: 'backend-placeholder') ?? '' :  '' }}" alt="">
                                                                </label>
                                                                <div class="overlay">
                                                                    <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                                            <i class="fi fi-sr-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                                            <i class="fi fi-rr-camera"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="fs-10 mb-0 text-center">
                                                                {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                                <span class="fw-medium">{{ "(325 x 100 px)" }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                   {{ translate('Loading GIF') }}
                                                                </label>
                                                                <p class="fs-12 mb-0">{{ translate('It will show when the website loads any page') }}.</p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="loader_gif" class="upload-file__input single_file_input" value="{{ getStorageImages(path:$businessSetting['loader_gif'],type: 'backend-placeholder') ?? '' }}" {{ !empty($businessSetting['loader_gif']) ? '' : 'required' }}
                                                                       accept="{{ getFileUploadFormats(skip: '.svg') }}" data-required-msg="{{ translate(':field is required', ['field' => translate('Website loader gif')]) }}" data-max-size="{{ getFileUploadMaxSize() }}">
                                                                <label
                                                                    class="upload-file__wrapper">
                                                                    <div class="upload-file-textbox text-center {{ isset($businessSetting['loader_gif']['path']) && $businessSetting['loader_gif']['path'] ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path: $businessSetting['loader_gif'] , type: 'backend-placeholder') }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center text-capitalize">
                                                                            <span class="text-info">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ !empty($businessSetting['loader_gif']['path']) ? getStorageImages(path:$businessSetting['loader_gif'], type: 'backend-placeholder-8-1') ?? '' :  '' }}" data-default-src="{{ !empty($businessSetting['loader_gif']) ? getStorageImages(path:$businessSetting['loader_gif'],type: 'backend-placeholder') ?? '' :  '' }}" alt="">
                                                                </label>
                                                                <div class="overlay">
                                                                    <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                                            <i class="fi fi-sr-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                                            <i class="fi fi-rr-camera"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="fs-10 mb-0 text-center">
                                                                {{ getFileUploadFormats(skip: '.svg,', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                                <span class="fw-medium">{{ "(325 x 100 px)" }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-sm shadow-1">
                                <div class="card-body">
                                    <div class="pb-12 pb-sm-20">
                                        <h3>{{ translate('Color Settings') }}</h3>
                                        <p class="mb-0 fs-12 text-capitalize">
                                            {{ translate('Select the colors used on the customer website') }}
                                        </p>
                                        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mt-3">
                                            <i class="fi fi-sr-lightbulb-on text-info"></i>
                                            <span>
                                                {{ translate('Primary color is used for the website header, buttons and links. Secondary color is used for secondary buttons and accents.') }}
                                                @if(theme_root_path() == 'theme_aster')
                                                    {{ translate('Primary light color is used for the header top bar, section backgrounds and filter tags.') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-12 p-sm-20 bg-section rounded">
                                        <div class="row g-4">
                                            <div class="col-lg-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="">
                                                        {{ translate('Primary Color') }}
                                                    </label>
                                                    <div class="d-flex align-items-center gap-10 bg-white border rounded py-2 px-10">
                                                        <input type="color" name="primary" value="{{ $businessSetting['primary_color'] }}"
                                                        class="form-control form-control_color color-code-preview">
                                                        <span class="fs-14 fw-medium text-dark color-code color-code-selection">#1455AC</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="form-group">
                                                    <label class="form-label" for="">
                                                        {{ translate('Secondary Color') }}
                                                    </label>
                                                    <div class="d-flex align-items-center gap-10 bg-white border rounded py-2 px-10">
                                                        <input type="color" name="secondary" value="{{ $businessSetting['secondary_color'] }}"
                                                        class="form-control form-control_color color-code-preview">
                                                        <span class="fs-14 fw-medium text-dark color-code color-code-selection">#F58300</span>
                                                    </div>
                                                </div>
                                            </div>
                                            @if(theme_root_path() == 'theme_aster')
                                                <div class="col-lg-3">
                                                    <div class="form-group">
                                                        <label class="form-label text-capitalize" for="">
                                                            {{ translate('Primary light color') }}
                                                        </label>
                                                        <div class="d-flex align-items-center gap-10 bg-white border rounded py-2 px-10">
                                                            <input type="color" name="primary_light" value="{{ $businessSetting['primary_color_light'] }}" class="form-control form-control_color color-code-preview">
                                                            <span class="fs-14 fw-medium text-dark color-code color-code-selection">#073B74</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-sm shadow-1">
                                <div class="card-body text-capitalize">
                                    <div class="pb-12 pb-sm-20">
                                        <h3>{{ translate('Footer app download button') }}</h3>
                                        <p class="mb-0 fs-12">
                                            {{ translate('Configure the link for the app download button here') }}.
                                        </p>
                                    </div>
                                    <div class="p-12 p-sm-20 bg-section rounded">
                                        <div class="row g-4">
                                            @php($appStoreDownload = getWebConfig('download_app_apple_store'))
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                                        <label class="form-label mb-0" for="">
                                                            {{ translate('Apple store download link') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ translate('Enter your App Store download link.') }}">
                                                                <i class="fi fi-sr-info"></i>
                                                            </span>
                                                        </label>
                                                        <label class="switcher switcher-sm" for="app-store-download-status">
                                                            <input
                                                                class="switcher_input custom-modal-plugin"
                                                                type="checkbox" value="1" name="app_store_download_status"
                                                                id="app-store-download-status"
                                                                {{ isset($appStoreDownload['status']) && $appStoreDownload['status'] == 1 ? 'checked' : '' }}
                                                                data-modal-type="input-change"
                                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/app-store-download-on.png') }}"
                                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/app-store-download-off.png') }}"
                                                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('App Store Button')]) }}"
                                                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('App Store Button')]) }}"
                                                                data-on-message="<p>{{ translate('Hides the App Store button in the website footer.') }}</p>"
                                                                data-off-message="<p>{{ translate('Shows the App Store button in the website footer.') }}</p>">
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                    <input type="url" name="app_store_download_url" class="form-control" {{ isset($appStoreDownload['status']) && $appStoreDownload['status'] == 1 ? 'required' : '' }}
                                                    value="{{ $appStoreDownload['link'] ?? '' }}"
                                                    placeholder="{{ translate('Ex') . ' : ' . 'https://www.apple.com/app-store/' }}">
                                                </div>
                                            </div>
                                            @php($playStoreDownload = getWebConfig('download_app_google_store'))
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                                        <label class="form-label mb-0" for="">
                                                            {{ translate('Google play store download link') }}
                                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="{{ translate('Enter your Google Play download link.') }}">
                                                                <i class="fi fi-sr-info"></i>
                                                            </span>
                                                        </label>
                                                        <label class="switcher switcher-sm" for="play-store-download-status">
                                                            <input
                                                                class="switcher_input custom-modal-plugin"
                                                                type="checkbox" value="1" name="play_store_download_status"
                                                                id="play-store-download-status"
                                                                {{ isset($playStoreDownload['status']) && $playStoreDownload['status'] == 1 ? 'checked' : '' }}
                                                                data-modal-type="input-change"
                                                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/play-store-download-on.png') }}"
                                                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/play-store-download-off.png') }}"
                                                                data-on-title="{{ translate('Want to Turn ON :item', ['item' => translate('Google Play Store button')]) }}"
                                                                data-off-title="{{ translate('Want to Turn OFF :item', ['item' => translate('Google Play Store button')]) }}"
                                                                data-on-message="<p>{{ translate('Hides the Google Play button in the website footer.') }}</p>"
                                                                data-off-message="<p>{{ translate('Shows the Google Play button in the website footer.') }}</p>">
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                    <input type="url" name="play_store_download_url" class="form-control"
                                                        value="{{ $playStoreDownload['link'] ?? '' }}" {{ isset($playStoreDownload['status']) && $playStoreDownload['status'] == 1 ? 'required' : '' }}
                                                        placeholder="{{ translate('Ex') . ' : ' . 'https://play.google.com/store/apps' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @include("layouts.admin.partials.offcanvas._website-setup")
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/business-settings.js') }}"></script>
@endpush
