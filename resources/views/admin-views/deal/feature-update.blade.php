@extends('layouts.admin.app')

@section('title', translate('Feature Deal Update'))

@section('content')
    <div class="content container-fluid">

        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/featured_deal.png') }}" alt="">
                {{ translate('Feature Deal Update') }}
            </h2>
        </div>

        <form action="{{ route('admin.retail.deal.featured-update', [$deal['id']]) }}" method="post" enctype="multipart/form-data"
              class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate>
            @csrf

            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-12">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">

                                <div class="form-group mb-0">
                                    <div class="table-responsive w-auto overflow-y-hidden mb-4">
                                        <div class="position-relative nav--tab-wrapper">
                                            <ul class="nav nav-pills nav--tab lang_tab" id="pills-tab" role="tablist">
                                                @foreach($language as $lang)
                                                    <li class="nav-item p-0">
                                                        <a data-bs-toggle="pill" data-bs-target="#{{ $lang }}-form" role="tab"
                                                           class="nav-link px-2 {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                                           id="{{ $lang }}-link">
                                                            {{ ucfirst(getLanguageName($lang)).'('.strtoupper($lang).')' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="nav--tab__prev">
                                                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                                    <i class="fi fi-sr-angle-left"></i>
                                                </button>
                                            </div>
                                            <div class="nav--tab__next">
                                                <button type="button" class="btn btn-circle border-0 bg-white text-primary">
                                                    <i class="fi fi-sr-angle-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="text" name="deal_type" value="feature_deal" hidden>

                                    <div class="tab-content" id="pills-tabContent">
                                        @foreach($language as $lang)
                                            
                                            <div class="tab-pane fade {{ $lang == $defaultLanguage ? 'show active' : '' }}"
                                                 id="{{ $lang }}-form" aria-labelledby="{{ $lang }}-link" role="tabpanel">
                                                <div class="form-group mb-0">
                                                    <label class="form-label" for="exampleFormControlInput1">
                                                        {{ translate('Title') }}
                                                        @if($lang == $defaultLanguage)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="title[]" class="form-control"
                                                           data-required-msg="{{ translate(':field is required', ['field' => translate('Deal title')]) }}"
                                                           placeholder="{{ translate('Ex') }}: {{ translate('Top Featured Deals') }}"
                                                           {{ $lang == $defaultLanguage? 'required':'' }}
                                                           value="{{ $lang == $defaultLanguage ? $deal['title'] : ($translate[$lang] ?? '') }}"
                                                           data-maxlength="100">
                                                    <div class="d-flex justify-content-end">
                                                        <span class="text-body-light">{{ '0/100' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        @endforeach
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-0">
                                            <label for="name" class="form-label">{{ translate('Start Date') }}<span class="text-danger">*</span></label>
                                            <input type="date" name="start_date" id="start-date-time"
                                                   data-required-msg="{{ translate('Start date field is required') }}"
                                                   value="{{ date('Y-m-d',strtotime($deal['start_date'])) }}"
                                                   class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group mb-0">
                                            <label for="name" class="form-label">{{ translate('End Date') }}<span class="text-danger">*</span></label>
                                            <input type="date" name="end_date" id="end-date-time" class="form-control"
                                                   data-required-msg="{{ translate('End date field is required') }}"
                                                   value="{{ date('Y-m-d', strtotime($deal['end_date'])) }}"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="row align-items-center gy-3 mb-3 mb-sm-20">
                        <div class="col-md-9">
                            <div>
                                <h2 class="text-capitalize">
                                    {{ translate('Meta Data Setup') }}
                                </h2>
                                <p class="fs-12 mb-0">
                                    {{ translate('Include meta titles, descriptions, and images for your feature deal.') }}
                                    {{ translate('Improves visibility so more people find your content in search.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row gy-3 mb-4">
                        <div class="col-lg-8">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">
                                <div class="form-group mb-0">
                                    <label class="form-label" for="meta_title">
                                        {{ translate('Meta Title') }}
                                        <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="{{ translate('Add the clearance sale taglines etc here.').' '.translate('Meta title shown in search results and when the sale is shared.') .' [ '. translate('Character Limit') }} : 50 ]">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <input type="text" name="meta_title" placeholder="{{ translate('Meta Title') }}"
                                           class="form-control" id="meta_title" data-maxlength="50"
                                            value="{{ $deal?->seo?->title ?? '' }}">
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/50</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label text-capitalize" for="meta_description">
                                        {{ translate('Meta Description') }}
                                        <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="{{ translate('Write a short description of the clearance sale.').' '.translate('Description shown in search results and when the clearance sale is shared.') .' [ '. translate('Character Limit') }} : 160 ]">
                                            <i class="fi fi-sr-info"></i>
                                        </span>
                                    </label>
                                    <textarea rows="2" type="text" name="meta_description" id="meta_description"
                                              class="form-control" data-maxlength="160"
                                              placeholder="{{ translate('Enter your meta description') }}">{{ $deal?->seo?->description ?? '' }}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/160</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="p-12 p-sm-20 bg-section rounded h-100">
                                <div class="d-flex flex-column gap-20">
                                    <div class="d-flex flex-column align-items-center">
                                        <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                            {{ translate('Meta Image') }}
                                            <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                                  aria-label="{{ translate('Add Meta Image in') }} {{ getFileUploadFormats(skip:'.svg,.gif') }} {{ translate('Format within') }} {{ getFileUploadMaxSize().'MB' }}, {{ translate('Which will be shown in search engine results') }}."
                                                  data-bs-title="{{ translate('Add Meta Image in') }} {{ getFileUploadFormats(skip:'.svg,.gif') }} {{ translate('Format within') }} {{ getFileUploadMaxSize().'MB' }}, {{ translate('Which will be shown in search engine results') }}."
                                            >
                                                <i class="fi fi-sr-info"></i>
                                            </span>
                                        </label>
                                        <p class="fs-12 mb-0">{{ translate('Upload meta image') }}</p>
                                    </div>
                                    <div class="upload-file">
                                        <input type="file" name="meta_image" class="upload-file__input single_file_input"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                               data-required-msg="{{ translate(':field is required', ['field' => translate('Meta Image')]) }}"
                                               accept="{{ getFileUploadFormats(skip:'.svg,.gif') }}">

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
                                            <img class="upload-file-img" loading="lazy"
                                                 src="{{ $deal?->seo?->image_full_url['path'] ? getStorageImages(path: $deal?->seo?->image_full_url['path'] ? $deal?->seo?->image_full_url : '', type: 'backend-banner') : '' }}"
                                                 data-default-src="{{ $deal?->seo?->image_full_url['path'] ? getStorageImages(path: $deal?->seo?->image_full_url['path'] ? $deal?->seo?->image_full_url : '', type: 'backend-banner') : '' }}"
                                                 alt="">
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
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-3 my-4">
                <button type="reset" class="btn btn-secondary px-4 w-120">{{translate('Reset')}}</button>
                <button type="submit" class="btn btn-primary px-4 w-120">{{translate('Save')}}</button>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/deal.js') }}"></script>
@endpush
