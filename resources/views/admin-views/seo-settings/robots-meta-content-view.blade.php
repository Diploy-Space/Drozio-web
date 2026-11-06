@extends('layouts.admin.app')

@section('title', translate('SEO Settings'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/seo-settings.svg') }}" alt="">
                {{ translate('SEO Settings') }}
            </h2>
        </div>
        @include('admin-views.seo-settings._inline-menu')

        <div class="card shadow-none">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="">
                    <h4 class="title m-0">{{translate('Robots Meta Content & OG Meta Content')}}</h4>
                    <p class="m-0">
                        {{ translate('Optimize your website\'s performance, indexing status and search visibility') }}
                        <a href="{{ 'https://6amtech.com/blog/robots-meta-content-and-og-content/' }}"
                           target="_blank"
                           class="text-primary text-decoration-underline fw-semibold">
                            {{ translate('Learn more') }}
                        </a>
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.settings.seo-settings.robots-meta-content.index') }}" class="text-primary text-decoration-underline fw-semibold">
                        {{ translate('Back to List') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-xl-30">
                <form action="{{ route('admin.settings.seo-settings.robots-meta-content.page-content-update') }}" method="POST" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                    @csrf
                    @if($pageName == 'default')
                        <input type="hidden" name="page_name" value="{{ 'default' }}">
                    @else
                        <input type="hidden" name="page_name" value="{{ $pageData['page_name'] ?? ''}}">
                    @endif
                    <div class="card shadow-none">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">
                                                {{ translate('Meta Title') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" placeholder="{{ translate('Maximum 120 characters') }} ({{ translate('Ideal 60 characters') }})"
                                                   class="form-control" value="{{ $pageData['meta_title'] ?? '' }}"
                                                   data-required-msg="{{ translate(':field is required', ['field' => translate('Meta Title')]) }}"
                                                   name="meta_title" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">{{ translate('Meta Description') }} <span class="text-danger">*</span></label>
                                            <textarea placeholder="{{ translate('Maximum 220 characters') }} ({{ translate('Ideal 160 characters') }})"
                                                  class="form-control" rows="5" name="meta_description"
                                            >{{ $pageData['meta_description'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex flex-column gap-20">
                                        <div class="text-center">
                                            <label for="" class="form-label fw-semibold mb-0">
                                                {{ translate('Meta Image') }}
                                            </label>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="meta_image" class="upload-file__input single_file_input"
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"  value="{{$pageData?->meta_image_full_url['path'] ?? ''}}">
                                            <label
                                                class="upload-file__wrapper ratio-2-1">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg" src="{{dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg')}}" alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center">
                                                        <span class="text-info">{{ translate('Click to upload') }}</span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy"
                                                     src="{{ getStorageImages(path: $pageData?->meta_image_full_url, type: 'backend-banner', source: 'public/assets/back-end/img/placeholder/placeholder-2-1.png') }}"
                                                     data-default-src="{{ getStorageImages(path: $pageData?->meta_image_full_url, type: 'backend-banner', source: 'public/assets/back-end/img/placeholder/placeholder-2-1.png') }}"
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
                                            <span class="fw-medium">{{ "(2:1)" }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 border rounded my-4">
                                <div class="row g-3">
                                    <div class="col-md-4 col-xl-2">
                                        <h5 class="m-0 mt-3">{{translate('Canonical URL')}}</h5>
                                    </div>
                                    <div class="col-md-8 col-xl-8">
                                        <input type="url" placeholder="{{ translate('Enter url') }}..."
                                               class="form-control" name="canonicals_url" value="{{ $pageData['canonicals_url'] ?? '' }}">
                                        <div class="mt-2 fs-12">
                                            <div>
                                                {{translate('Learn how to get it.')}}
                                                <a href="{{ 'https://6amtech.com/blog/canonical-urls/' }}"
                                                   target="_blank"
                                                   class="text-primary text-decoration-underline fw-semibold">
                                                    {{ translate('Learn more') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-lg-6 col-xl-5">
                                    <div
                                        class="robots-meta-checkbox-card d-flex flex-wrap gap-2 justify-content-between h-100 border rounded p-20">
                                        <div class="item flex-grow-1">
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="radio" class="form-check-input radio--input" name="meta_index" value="index"
                                                    {{ (isset($pageData['index']) && $pageData['index'] != 'noindex') || (isset($pageData['index']) && $pageData['index'] == null) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label">{{ translate('Index') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Lets search engines index this page and show it in results.') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="checkbox" name="meta_no_follow" value="1" {{ isset($pageData['no_follow']) && $pageData['no_follow'] ? 'checked' : '' }} class="form-check-input checkbox--input input-no-index-sub-element">
                                                <label class="form-check-label">{{ translate('No Follow') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Instruct search engines not to follow links from this web page') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="checkbox" name="meta_no_image_index" value="1" {{ isset($pageData['no_image_index']) && $pageData['no_image_index'] ? 'checked' : '' }} class="form-check-input checkbox--input input-no-index-sub-element">
                                                <label class="form-check-label">{{ translate('No Image Index') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Prevents images from being listed or indexed by search engines') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                        </div>
                                        <div class="item flex-grow-1">
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="radio" name="meta_index" value="noindex" class="form-check-input radio--input action-input-no-index-event"
                                                    {{ (isset($pageData['index']) && $pageData['index'] != 'noindex') || (isset($pageData['index']) && $pageData['index'] == null) ? '' : 'checked' }}
                                                >
                                                <label class="form-check-label">{{ translate('No Index') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Keeps this page out of search engine results.') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="checkbox" name="meta_no_archive" value="1" {{ isset($pageData['no_archive']) && $pageData['no_archive'] ? 'checked' : '' }} class="form-check-input checkbox--input input-no-index-sub-element">
                                               <label class="form-check-label">{{ translate('No Archive') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Instruct search engines not to display this web page\'s cached or saved version') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                            <label class="form-check d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="checkbox" name="meta_no_snippet" value="1" {{ isset($pageData['no_snippet']) && $pageData['no_snippet'] ? 'checked' : '' }} class="form-check-input checkbox--input input-no-index-sub-element">
                                                <label class="form-check-label">{{ translate('No Snippet') }}</label>
                                                <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Stops search engines showing a snippet of this page.') }}">
                                                    <img src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                         alt="">
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-xl-5">
                                    <div class="robots-meta-checkbox-card d-flex flex-column gap-2 h-100 border rounded p-20">
                                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                            <div class="item">
                                                <label class="form-check d-flex gap-2 user-select-none">
                                                    <input type="checkbox" class="form-check-input checkbox--input" name="meta_max_snippet" value="1" {{ isset($pageData['max_snippet']) && $pageData['max_snippet'] ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ translate('Max Snippet') }}</label>
                                                    <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum length of the snippet search engines display.') }}">
                                                        <img
                                                            src="{{ dynamicAsset('public/assets/new/back-end/img/query.png')}}"
                                                            alt="">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="item w-120px flex-grow-0">
                                                <input type="text" placeholder="-1" class="form-control h-30 py-0" name="meta_max_snippet_value"
                                                       value="{{ $pageData['max_snippet_value'] ?? '-1' }}">
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                            <div class="item">
                                                <label class="form-check d-flex gap-2 user-select-none">
                                                    <input type="checkbox" class="form-check-input checkbox--input" name="meta_max_video_preview" value="1" {{ isset($pageData['max_video_preview']) && $pageData['max_video_preview'] ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ translate('Max Video Preview') }}</label>
                                                    <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum length of the video preview search engines display.') }}">
                                                        <img
                                                            src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}"
                                                            alt="">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="item w-120px flex-grow-0">
                                                <input type="text" placeholder="-1" class="form-control h-30 py-0" name="meta_max_video_preview_value"
                                                       value="{{ $pageData['max_video_preview_value'] ?? '-1' }}">
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                            <div class="item">
                                                <label class="form-check d-flex gap-2 user-select-none">
                                                    <input type="checkbox" class="form-check-input checkbox--input" name="meta_max_image_preview" value="1" {{ isset($pageData['max_image_preview']) && $pageData['max_image_preview'] ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ translate('Max Image Preview') }}</label>
                                                    <span data-bs-toggle="tooltip" data-bs-title="{{ translate('Maximum size of the image preview search engines display.') }}">
                                                        <img alt=""
                                                            src="{{ dynamicAsset('public/assets/back-end/img/query.png')}}">
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="item w-120px flex-grow-0">
                                                <div class="select-wrapper">
                                                    <select class="form-select bg-white h-30 py-0" name="meta_max_image_preview_value">
                                                        <option value="large" {{ isset($pageData['max_image_preview_value']) && $pageData['max_image_preview_value'] == 'large' ? 'selected' : '' }}>{{ translate('Large') }}</option>
                                                        <option value="medium" {{ isset($pageData['max_image_preview_value']) && $pageData['max_image_preview_value'] == 'medium' ? 'selected' : '' }}>{{ translate('Medium') }}</option>
                                                        <option value="small" {{ isset($pageData['max_image_preview_value']) && $pageData['max_image_preview_value'] == 'small' ? 'selected' : '' }}>{{ translate('Small') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="reset" class="btn btn-secondary px-5">
                            {{ translate('Reset') }}
                        </button>
                        <button type="{{ config('app.mode') == 'demo' ? 'button' : 'submit' }}" class="btn btn-primary px-5 {{config('app.mode')!='demo'? '' : 'call-demo-alert'}}">
                            {{ translate('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection

@push('script')

@endpush
