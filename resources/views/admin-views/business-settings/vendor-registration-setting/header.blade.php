@extends('layouts.admin.app')

@section('title', translate('Header'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.business-settings.vendor-registration-setting.partial.inline-menu')
        <form action="{{route('admin.settings.pages-and-media.vendor-registration-settings.index')}}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center gy-3 mb-3 mb-sm-20">
                        <div class="col-md-9">
                            <div>
                                <h2 class="text-capitalize">
                                   {{ translate('Header section') }}
                                </h2>
                                <p class="fs-12 mb-0">
                                   {{ translate('Shows this section on the vendor registration page.') }}.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row gy-3 mb-4">
                        <div class="col-lg-8">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">
                                <div class="form-group">
                                    <label class="form-label">
                                        {{translate('Title')}}
                                        <span class="text-danger">*</span>
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('A short, catchy title for this section of the registration page.') }}" data-bs-title="{{ translate('A short, catchy title for this section of the registration page.') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <textarea class="form-control" name="title" rows="1" placeholder="{{translate('Enter Title')}}" data-required-msg="{{ translate('Title field is required') }}" data-maxlength="50" required>{{$vendorRegistrationHeader?->title}}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/50</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-capitalize">
                                        {{translate('Subtitle')}}
                                        <span class="text-danger">*</span>
                                        <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="{{ translate('A short subtitle giving extra context under the main title.') }}" data-bs-title="{{ translate('A short subtitle giving extra context under the main title.') }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <textarea class="form-control text-capitalize" name="sub_title" rows="1" placeholder="{{translate('Enter Subtitle')}}" data-required-msg="{{ translate('Sub title field is required') }}" data-maxlength="50" required>{{$vendorRegistrationHeader?->sub_title}}</textarea>
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
                                        <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                           {{ translate('Header Image') }}
                                        </label>
                                        <p class="fs-12 mb-0">{{ translate('Upload your Header Image') }}</p>
                                    </div>
                                    <div class="upload-file">
                                        @php($imagePath = imagePathProcessing(imageData: $vendorRegistrationHeader?->image, path: 'vendor-registration-setting'))
                                        <input type="file" name="image" class="upload-file__input single_file_input"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                               data-required-msg="{{ translate(':field is required', ['field' => translate('Header Image')]) }}"
                                               accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"  value="{{ getStorageImages(path:$imagePath,type: 'backend-placeholder') ?? '' }}" {{ !empty($imagePath['path']) ? '' : 'required' }}>

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
                                            <img class="upload-file-img" loading="lazy" src="{{ !empty($imagePath['path']) ? getStorageImages(path: $imagePath, type: 'backend-placeholder') ?? '' :  '' }}" data-default-src="{{ getStorageImages(path:imagePathProcessing(imageData: $vendorRegistrationHeader?->image, path: 'vendor-registration-setting'),type: 'backend-banner') ?? '' }}" alt="">
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
                                        {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                        <span class="fw-medium text-dark">{{ "(310 x 240px)" }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3">
                        <button type="reset" class="btn btn-secondary px-4 w-120">{{translate('Reset')}}</button>
                        <button type="submit" class="btn btn-primary px-4 w-120"><i class="fi fi-sr-disk"></i> {{translate('Save')}}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @include("layouts.admin.partials.offcanvas._vendor-reg-header")
@endsection
@push('script')

@endpush
