@extends('layouts.admin.app')

@section('title', translate('Edit') . ' - ' . translate('In-house Shop'))

@section('content')
    <div class="content container-fluid">
        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-12">
                <h1 class="mb-3 sm-sm-20">
                    {{ translate('In-house Shop') }}
                </h1>
                @include('admin-views.inhouse-shop._inhouse-shop-menu')
            </div>
        </div>

        <form action="{{ route('admin.settings.business-settings.inhouse-shop') }}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
            @csrf
            <div class="card mb-4">
                <div class="card-header border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2>{{ translate('Edit Shop') }}</h2>
                        <p class="fs-12 mb-0">
                            {{ translate('On this page you can set up your shop information.') }}
                            {{ translate('This information is not related to your business information.') }}
                        </p>
                    </div>
                    <a href="{{ route('admin.settings.business-settings.inhouse-shop') }}"
                        class="d-flex gap-2 align-items-center fw-medium">
                        <i class="fi fi-rr-arrow-left"></i>
                        {{ translate('Back to Shop Settings') }}
                    </a>
                </div>
                <div class="card-body d-flex flex-column gap-3 gap-sm-20">
                    <div
                        class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center">
                        <i class="fi fi-sr-info text-warning"></i>
                        <span>
                            {{ translate('After changing all information, make sure you click') }}
                            <b>{{ translate('Save Button') }}</b>
                            {{ translate('This setup is not related to and does not overwrite your business information.') }}
                        </span>
                    </div>
                    <div class="card card-sm-shadow-2">
                        <div class="card-body">
                            <div class="mb-3 mb-sm-20">
                                <h3>{{ translate('Shop Name') }}</h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('Here you can set your brand logo for website and app.') }}
                                </p>
                            </div>

                            <div class="bg-section p-12 p-sm-20 rounded">
                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="" class="form-label mb-2">
                                                {{ translate('Shop name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" value="{{getInHouseShopConfig('name')}}" class="form-control" name="name" placeholder="{{ translate('Enter shop name') }}" required data-required-msg="{{ translate('Shop name field is required')}}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="" class="form-label mb-2">
                                                {{ translate('Contact') }} <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" value="{{getInHouseShopConfig('contact')}}" class="form-control" name="contact" required data-required-msg="{{ translate('Contact field is required')}}" placeholder="{{ translate('Enter contact number') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div>
                                            <label for="" class="form-label mb-2">
                                                {{ translate('Address') }} <span class="text-danger">*</span>
                                            </label>
                                            <textarea name="address" id="" rows="1" class="form-control" placeholder="{{ translate('Enter address') }}" required data-required-msg="{{ translate('Address field is required')}}">{{getInHouseShopConfig('address')}}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card card-sm-shadow-2">
                        <div class="card-body pb-0">
                            <div class="mb-3 mb-sm-20">
                                <h3>{{ translate('Logo and Cover') }}</h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('Set your brand logo and cover for website and app') }}
                                </p>
                            </div>

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    @include('admin-views.inhouse-shop.partials._shop_logo')
                                </div>
                                <div class="col-lg-6">
                                    @include('admin-views.inhouse-shop.partials._shop_cover')
                                </div>
                                @if (theme_root_path() == 'theme_aster')
                                <div class="col-12">
                                    @include('admin-views.inhouse-shop.partials._bottom_banner')
                                </div>
                                @endif
                                <div class="col-12">
                                    @if (theme_root_path() == 'theme_fashion')
                                        @include('admin-views.inhouse-shop.partials._offer_banner')
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <button type="reset" class="btn btn-secondary px-3 px-sm-4 w-120" id="custom-reset-btn">
                        {{ translate('Reset') }}
                    </button>

                    <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-3 px-sm-4 {{ getDemoModeFormButton(type: 'class') }}">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>

    </div>
@endsection
@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/business-settings/inhouse-shop.js') }}"></script>
@endpush
