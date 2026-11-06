@extends('layouts.vendor.app')

@section('title', translate('Edit Shop'))

@section('content')
    <div class="content container-fluid">

        <h1 class="mb-3">{{ translate('Shop Setup') }}</h1>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap flex-grow-1">
                    <div class="flex-grow-1">
                        <h3 class="text-capitalize">{{ translate('Edit Shop') }}</h3>
                        <p class="fs-12 mb-0">{{ translate('Here you set up all your business information') }}</p>
                    </div>
                    <a href="{{ route('vendor.shop.index') }}" class="d-flex gap-2 align-items-center">
                        <i class="fi fi-rr-arrow-small-left mt-1"></i>
                        {{ translate('Back to Shop Settings') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="d-flex gap-2 alert alert-soft-warning" role="alert">
                    <i class="fi fi-sr-info"></i>
                    <p class="fs-12 mb-0 text-dark">
                        {{ translate('After changing all information, make sure you click the save button.') }}
                        {{ translate('This setup is not related to and does not overwrite your business information.') }}
                    </p>
                </div>

                <form action="{{ route('vendor.shop.update', [$shop->id]) }}" method="post" class="text-start form-advance-validation non-ajax-form-validate" novalidate="novalidate"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="card-sm-body mb-4">
                        <div class="mb-3">
                            <h3 class="text-capitalize">{{ translate('Shop Name') }}</h3>
                            <p class="fs-12 mb-0">{{ translate('Here you can set your brand logo for website and app.') }}
                            </p>
                        </div>

                        <div class="bg-light p-3 rounded">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="name" class="text-capitalize">{{ translate('Shop Name') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" value="{{ $shop->name }}"  data-required-msg="{{ translate(':field is required', ['field' => translate('Shop Name')]) }}"
                                            class="form-control" id="name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="name">{{ translate('Contact') }} <span
                                                class="text-danger">*</span></label>
                                        <div class="">
                                            <input class="form-control" type="tel" name="company_phone" data-required-msg="{{ translate(':field is required', ['field' => translate('Contact number')]) }}"
                                                value="{{ $shop->contact ?? old('phone') }}"
                                                placeholder="{{ translate('Enter phone number') }}" required>
                                        </div>

                                        <div class="form-group">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="address">{{ translate('Address') }} <span
                                                class="text-danger">*</span></label>
                                        <textarea type="text" rows="1" name="address" class="form-control" id="address" required data-required-msg="{{ translate(':field is required', ['field' => translate('Address')]) }}">{{ $shop->address }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-sm-body">
                        <div class="mb-3">
                            <h3 class="text-capitalize">{{ translate('Logo and Cover') }}</h3>
                            <p class="fs-12 mb-0">
                                {{ translate('Brand logo and cover for the website and app.') }}</p>
                        </div>

                        <div class="row gy-2">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex flex-column gap-20">
                                        <div>
                                            <label for=""
                                                class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                {{ translate('Shop Logo') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <p class="fs-12 mb-0">
                                                {{ translate('Upload your Shop logo') }}
                                            </p>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="image"
                                                class="upload-file__input single_file_input"   data-max-size="{{ getFileUploadMaxSize() }}"
                                                accept="{{ getFileUploadFormats(skip: '.svg') }}" {{ empty(getStorageImages(path: $shop->image_full_url, type: 'backend-basic')) ? 'required' : '' }}>
                                            <label class="upload-file__wrapper mb-0">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg img-fluid"
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                        alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                        <span class="text-info text-capitalize">
                                                            {{ translate('Click to upload') }}
                                                        </span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy"
                                                    src="{{ getStorageImages(path: $shop->image_full_url, type: 'backend-basic') }}"
                                                    alt="">
                                            </label>
                                            <div class="overlay">
                                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                        <i class="fi fi-rr-camera"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-10 mb-0 text-center">
                                            {{ getFileUploadFormats(skip: '.svg'). translate('Image size') }}: {{ translate('Max').getFileUploadMaxSize().'MB' }}
                                            <span class="fw-medium">
                                                ({{ THEME_RATIO[theme_root_path()]['Store cover Image'] }})
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex flex-column gap-20">
                                        <div>
                                            <label for=""
                                                class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                {{ translate('Shop cover image') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <p class="fs-12 mb-0">{{ translate('Upload your Shop cover image') }}</p>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="banner"  data-max-size="{{ getFileUploadMaxSize() }}"
                                                class="upload-file__input single_file_input"
                                                accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                            <label class="upload-file__wrapper w-325 mb-0">
                                                <div class="upload-file-textbox text-center ">
                                                    <img width="34" height="34" class="svg img-fluid"
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                        alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                        <span
                                                            class="text-info text-capitalize">{{ translate('Click to upload') }}</span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy"
                                                    src="{{ getStorageImages(path: $shop->banner_full_url, type: 'backend-banner') }}"
                                                    data-default-src="" alt="">
                                            </label>
                                            <div class="overlay">
                                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                        <i class="fi fi-rr-camera"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-10 mb-0 text-center ">
                                            {{ getFileUploadFormats(skip: '.svg'). translate('Image size') }}: {{ translate('Max').getFileUploadMaxSize().'MB' }}
                                            <span class="fw-medium">
                                                ({{ THEME_RATIO[theme_root_path()]['Store cover Image'] }})
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if (theme_root_path() == 'theme_aster')
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex flex-column gap-20">
                                            <div>
                                                <label for=""
                                                    class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                    {{ translate('Secondary banner') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                    {{ translate('Upload your Shop secondary banner') }}
                                                </p>
                                            </div>
                                            <div class="upload-file">
                                                <input type="file" name="bottom_banner"  data-max-size="{{ getFileUploadMaxSize() }}"
                                                    class="upload-file__input single_file_input"
                                                    accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->bottom_banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                                <label class="upload-file__wrapper w-325 mb-0">
                                                    <div class="upload-file-textbox text-center ">
                                                        <img width="34" height="34" class="svg img-fluid"
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                            alt="image upload">
                                                        <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                            <span class="text-info text-capitalize">
                                                                {{ translate('Click to upload') }}
                                                            </span>
                                                            <br>
                                                            {{ translate('Or drag and drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy"
                                                        src="{{ getStorageImages(path: $shop->bottom_banner_full_url, type: 'backend-banner') }}"
                                                        data-default-src="" alt="">
                                                </label>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                        <button type="button"
                                                            class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="fi fi-rr-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="fs-10 mb-0 text-center ">
                                                {{ getFileUploadFormats(skip: '.svg'). translate('Image size') }}: {{ translate('Max').getFileUploadMaxSize().'MB' }}
                                                <span class="fw-medium">
                                                    ({{ translate('Ratio') . ' ' . '( 6:1 )' }})
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (theme_root_path() == 'theme_fashion')
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex flex-column gap-20">
                                            <div>
                                                <label for=""
                                                    class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                    {{ translate('Offer banner') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                    {{ translate('Upload your Shop offer banner') }}
                                                </p>
                                            </div>
                                            <div class="upload-file">
                                                <input type="file" name="offer_banner"
                                                    class="upload-file__input single_file_input"
                                                    accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->offer_banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                                <label class="upload-file__wrapper w-325 mb-0">
                                                    <div class="upload-file-textbox text-center ">
                                                        <img width="34" height="34" class="svg img-fluid"
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                            alt="image upload">
                                                        <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                            <span class="text-info text-capitalize">
                                                                {{ translate('Click to upload') }}
                                                            </span>
                                                            <br>
                                                            {{ translate('Or drag and drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy"
                                                        src="{{ getStorageImages(path: $shop->offer_banner_full_url, type: 'backend-banner') }}"
                                                        data-default-src="" alt="">
                                                </label>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                        <button type="button"
                                                            class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="fi fi-rr-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="fs-10 mb-0 text-center">
                                                {{ getFileUploadFormats(skip: '.svg'). translate('Image size') }}: {{ translate('Max').getFileUploadMaxSize().'MB' }}
                                                <span class="fw-medium">
                                                    ({{ translate('Ratio') . ' ' . '( 7:1 )' }})
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="reset" class="btn btn-secondary">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn--primary"><i class="fi fi-sr-disk"></i>
                            {{ translate('Save Information') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
