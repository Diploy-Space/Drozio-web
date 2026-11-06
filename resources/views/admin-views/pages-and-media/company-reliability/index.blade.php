@extends('layouts.admin.app')

@section('title', translate('Our Commitments'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/Pages.png') }}" alt="">
                {{ translate('Pages') }}
            </h2>
        </div>

        @include('admin-views.pages-and-media._pages-and-media-inline-menu')

        <div class="bg-info bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
            <i class="fi fi-sr-lightbulb-on text-info"></i>
            <span>
            {{ translate('Builds customer trust and increases revenue.') }}
        </span>
        </div>

        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2>
                        {{ translate('Our Commitments') }}
                    </h2>
                    <p class="mb-0 fs-12">
                        {{ translate('On this page you can set up your website company reliability section.') }}
                    </p>
                </div>
                <div>
                    <button class="btn btn-outline-primary" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSectionPreview">
                        <i class="fi fi-sr-eye"></i>
                        {{ translate('Section Preview') }}
                    </button>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.settings.pages-and-media.company-reliability') }}" method="POST"
              enctype="multipart/form-data" class="form-advance-validation form-advance-file-validation non-ajax-form-validate">
            @csrf

            <div class="row g-3 mb-4">
                @foreach (json_decode($companyReliabilityData?->value ?? '') as  $key => $value)
                    <input type="hidden" name="item_{{ $key + 1 }}" value="{{ $value->item }}">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <div
                                    class="d-flex gap-3 flex-wrap justify-content-between align-items-center mb-3 mb-sm-20">
                                    <h3 class="mb-0">{{ translate($value->item) }}</h3>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="text-dark" for="">{{ translate('Show this card') }}</label>
                                        <input class="form-check-input checkbox--input checkbox--input_lg"
                                               type="checkbox" name="status_{{ $key + 1 }}" id="" value="1" {{ $value->status == 1 ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="bg-section rounded p-12 p-sm-20 d-flex flex-column gap-sm-20 gap-3">
                                    <div class="d-flex flex-column gap-20 bg-white p-3">
                                        <div>
                                            <label for="" class="form-label fw-semibold mb-1">
                                                {{ translate('Upload Icon') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="image_{{ $key + 1 }}"
                                                   class="upload-file__input single_file_input"
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                   data-required-msg="{{ translate($value->item . '_image_field_name_is_required') }}"
                                                   accept="{{ getFileUploadFormats(skip: '.svg') }}" value="">
                                            <div
                                                class="upload-file__wrapper bg-section">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg"
                                                         src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg')}}"
                                                         alt="{{ translate('Image') }}">
                                                    <h6 class="mt-1 fw-medium lh-base text-center">
                                                        <span class="text-info">
                                                            {{ translate('Click to upload') }}
                                                        </span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                @php
                                                    $imageArray = [
                                                        'image_name' => $value?->image->image_name ?? $value?->image,
                                                        'storage' => $value?->image?->storage ?? 'public',
                                                    ];
                                                    $imagePath = storageLink('company-reliability',$imageArray['image_name'],$imageArray['storage']);
                                                @endphp
                                                <img class="upload-file-img" loading="lazy" src="{{$imagePath['path']}}" data-default-src="{{$imagePath['path']}}"  alt="">
                                            </div>
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
                                            {{ translate( getFileUploadFormats(skip: '.svg')).' Less Than '. getFileUploadMaxSize().'MB' }}
                                            <span class="fw-medium">{{ '('.translate('Ratio').' 1:1)' }}</span>
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="">
                                            {{ translate('Title') }}
                                            <span class="text-danger">*</span>
                                            <span class="tooltip-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                  aria-label="{{ translate('Enter card :number title', ['number' => $key + 1]) }}" data-bs-title="{{ translate('Enter card :number title', ['number' => $key + 1]) }}">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                        </label>
                                        <input type="text" class="form-control" name="title_{{ $key + 1 }}" value="{{$value->title}}"
                                               placeholder="{{ translate('Type your title text') }}"
                                               data-required-msg="{{ translate(':field is required', ['field' => translate('Title field name')]) }}"
                                               data-maxlength="40">
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/100</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120">
                        {{ translate('Reset') }}
                    </button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSectionPreview"
             aria-labelledby="offcanvasSectionPreview">
            <div class="offcanvas-header bg-body">
                <h2 class="mb-0">{{ translate('Section Preview') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <img class="w-100 h-auto" src="{{dynamicAsset(path: 'public/assets/new/back-end/img/demo-ss.jpg')}}" alt="">
            </div>
        </div>
    </div>

    @include("layouts.admin.partials.offcanvas._company-reliability-setup")
@endsection
