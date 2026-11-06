@extends('layouts.admin.app')

@section('title', translate('Banner'))

@section('content')
    <div class="content container-fluid">

        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
            <div>
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/banner.png') }}" alt="">
                    {{ translate('Banner update form') }}
                </h2>
            </div>
            <div>
                <a class="btn btn-primary text-white" href="{{ route('admin.retail.banner.list') }}">
                    <i class="fi fi-sr-angle-left"></i> {{ translate('Back') }}
                </a>
            </div>
        </div>

        <div class="row text-start">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.retail.banner.update', [$banner['id']]) }}" method="post"
                              enctype="multipart/form-data"
                              class="banner-form form-advance-validation form-advance-file-validation non-ajax-form-validate"
                              data-msg-banner-type="{{ translate('Please select a banner type') }}"
                              data-msg-resource-type="{{ translate('Please select a resource type') }}"
                              data-msg-url="{{ translate('Please enter a valid banner url') }}"
                              data-msg-resource-product="{{ translate('Please select a product') }}"
                              data-msg-resource-category="{{ translate('Please select a category') }}"
                              data-msg-resource-shop="{{ translate('Please select a shop') }}"
                              data-msg-resource-brand="{{ translate('Please select a brand') }}"
                              novalidate="novalidate">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="bg-section rounded-8 p-20 w-100 h-100">
                                        <div class="h-100">
                                            @include('admin-views.banner.partials._banner-form-fields', ['banner' => $banner])
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <div class="d-flex justify-content-center align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                        @include('admin-views.banner.partials._banner-image-field', ['banner' => $banner])
                                    </div>
                                </div>

                                <div class="col-md-12 d-flex justify-content-end gap-3">
                                    <button type="submit" class="btn btn-primary px-4">
                                        {{ translate('Update') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span id="theme-ratio-map" data-theme="{{ theme_root_path() }}" data-map="{{ json_encode(THEME_RATIO) }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/promotion/banner.js') }}"></script>
@endpush
