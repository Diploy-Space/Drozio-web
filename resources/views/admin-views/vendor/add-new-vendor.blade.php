@extends('layouts.admin.app')

@section('title', translate('Add New Vendor'))

@section('content')
    <div class="content container-fluid main-card {{ Session::get('direction') }}">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                {{ translate('Add New Vendor') }}
            </h2>
        </div>

        <form action="{{ route('admin.people.vendors.add') }}" enctype="multipart/form-data" id="add-vendor-form" method="POST"
            data-message="{{ translate('Want to add this vendor') . '?' }}" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation"
            data-redirect-route="{{ route('admin.people.vendors.vendor-list') }}" novalidate="novalidate">
            @csrf

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="fs-18">{{ translate('Vendor Information') }}</h3>
                    <p class="mb-0 fs-12">
                        {{ translate('Provide all mandatory details to create a new vendor profile.') }}
                    </p>

                </div>
                <div class="card-body">
                    <input type="hidden" name="status" value="approved">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="bg-section rounded-8 p-20 h-100">
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-0">
                                            <label for="exampleFirstName"
                                                class="form-label mb-2">{{ translate('First Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-user"
                                                id="exampleFirstName" name="f_name" value="{{ old('f_name') }}"
                                                   data-required-msg="{{ translate('First name is required') }}"
                                                placeholder="{{ translate('Ex') }}: John" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="exampleLastName"
                                                class="form-label mb-2">{{ translate('Last Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-user"
                                                id="exampleLastName" name="l_name" value="{{ old('l_name') }}"
                                                   data-required-msg="{{ translate('Last name is required') }}"
                                                placeholder="{{ translate('Ex') }}: {{ 'Doe' }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group mb-0">
                                            <label for="phone_number" class="form-label mb-2">{{ translate('Phone') }}
                                                <span class="text-danger">*</span></label>
                                            <input class="form-control form-control-user" type="tel" value=""
                                                placeholder="{{ translate('Ex') . ': 017xxxxxxxx' }}" name="phone"
                                                   data-required-msg="{{ translate('Phone number is required') }}"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                <div class="d-flex flex-column gap-20 w-100">
                                    <div>
                                        <label for="" class="form-label fw-semibold mb-1">
                                            {{ translate('Vendor image')}}  <span class="text-danger">*</span>
                                        </label>
                                        <p class="fs-12 mb-0">
                                            {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                            <span class="text-dark fw-semibold">(1:1)</span>
                                        </p>
                                    </div>
                                    <div class="upload-file">
                                        <input type="file" name="image" id="custom-file-upload"
                                            class="upload-file__input single_file_input"
                                            data-required-msg="{{ translate('Vendor image is required') }}"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                            accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                                            value="">
                                        <label class="upload-file__wrapper">
                                            <div class="upload-file-textbox text-center">
                                                <img width="34" height="34" class="svg"
                                                    src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                    alt="image upload">
                                                <h6 class="mt-1 fw-medium lh-base text-center text-body">
                                                    <span class="text-info">{{ translate('Click to upload') }}</span>
                                                    <br>
                                                    {{ translate('Or drag and drop') }}
                                                </h6>
                                            </div>
                                            <img class="upload-file-img" loading="lazy" src="" data-default-src=""
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="fs-18 text-capitalize">{{ translate('Account Information') }}</h3>
                   <p class="mb-0 fs-12">
                        {{ translate('Provide the account details required for vendor operations.') }}
                    </p>

                </div>
                <div class="card-body">
                    <input type="hidden" name="status" value="approved">
                    <div class="row g-4">
                        <div class="col-lg-4 ">
                            <div class="form-group">
                                <label for="exampleInputEmail" class="form-label mb-2">{{ translate('Email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-user" id="exampleInputEmail"
                                    name="email" value="{{ old('email') }}"
                                       data-required-msg="{{ translate('Email field is required') }}"
                                    placeholder="{{ translate('Ex') }}: john@company.com" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="user_password" class="form-label mb-2">
                                    {{ translate('Password') }} <span class="text-danger">*</span>
                                    <span class="tooltip-icon cursor-pointer" data-bs-toggle="tooltip"
                                        data-bs-title="{{ translate('Use at least 8 characters, including one uppercase letter.') . ',' . translate('One lowercase letter') . ',' . translate('One digit') . ',' . translate('One special character') . ',' . translate('And no spaces') . '.' }}">
                                        <i class="fi fi-sr-info"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="js-toggle-password form-control password-check"
                                        name="password" required id="user_password" minlength="8"
                                           data-required-msg="{{ translate('Password is required') }}"
                                        placeholder="{{ translate('Password minimum 8 characters') }}">
                                    <div id="changePassTarget" class="input-group-append changePassTarget">
                                        <a class="text-body-light" href="javascript:">
                                            <i id="changePassIcon" class="fi fi-sr-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                <span class="text-danger mx-1 password-error"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label mb-2">{{ translate('Confirm password') }}
                                    <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="js-toggle-password form-control"
                                           data-required-msg="{{ translate('Confirm password is required') }}"
                                        name="confirm_password" required id="confirm_password"
                                        placeholder="{{ translate('Confirm password') }}">
                                    <div id="changeConfirmPassTarget" class="input-group-append changePassTarget">
                                        <a class="text-body-light" href="javascript:">
                                            <i id="changeConfirmPassIcon" class="fi fi-sr-eye"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="pass invalid-feedback">{{ translate('Repeat password not match') . '.' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="fs-18">{{ translate('Shop Information') }}</h3>
                    <p class="mb-0 fs-12">
                         {{ translate('Basic shop details provide below') }}.
                    </p>
                </div>
                <div class="card-body">
                    <div class="bg-section rounded-8 p-20 mb-4">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label for="shop_name" class="form-label mb-2">{{ translate('Shop Name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-user" id="shop_name"
                                           data-required-msg="{{ translate('Shop name is required') }}"
                                        name="shop_name" placeholder="{{ translate('Ex') }}: John"
                                        value="{{ old('shop_name') }}" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group mb-0">
                                    <label for="shop_address"
                                        class="form-label mb-2">{{ translate('Shop address') }}<span class="text-danger">*</span></label>
                                    <textarea name="shop_address" class="form-control text-area-max" id="shop_address" rows="1"
                                              data-required-msg="{{ translate('Shop address is required') }}"
                                        data-maxlength="100" placeholder="{{ translate('Ex') }}: Doe" required>{{ old('shop_address') }}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <span class="text-body-light">0/100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                <div class="d-flex flex-column gap-20 w-100">
                                    <div>
                                        <label for="" class="form-label fw-semibold mb-1">
                                            {{ translate('Shop Logo') }} <span class="text-danger">*</span>
                                        </label>
                                        <p class="fs-12 mb-0">
                                            {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                            <span class="text-dark fw-semibold">(1:1)</span>
                                        </p>
                                    </div>
                                    <div class="upload-file">
                                        <input type="file" name="logo" id="logo-upload"
                                            class="upload-file__input single_file_input"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                            data-required-msg="{{ translate('Shop logo is required') }}"
                                            accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"
                                            value="">
                                        <label class="upload-file__wrapper">
                                            <div class="upload-file-textbox text-center">
                                                <img width="34" height="34" class="svg"
                                                    src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                    alt="image upload">
                                                <h6 class="mt-1 fw-medium lh-base text-center text-body">
                                                    <span class="text-info">{{ translate('Click to upload') }}</span>
                                                    <br>
                                                    {{ translate('Or drag and drop') }}
                                                </h6>
                                            </div>
                                            <img class="upload-file-img" loading="lazy" src=""
                                                data-default-src="" alt="">
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
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                <div class="d-flex flex-column gap-20 w-100">
                                    <div>
                                        <label for="" class="form-label fw-semibold mb-1">
                                              {{ translate('Shop cover image') }}<span class="text-danger">*</span>
                                        </label>
                                        <p class="fs-12 mb-0">
                                            {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                            <span class="text-dark fw-semibold">(4:1)</span>
                                        </p>
                                    </div>
                                    <div class="upload-file">
                                        <input type="file" name="banner" id="banner-upload"
                                            class="upload-file__input single_file_input"
                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                            data-required-msg="{{ translate('Shop cover image is required') }}"
                                            accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"
                                            value="" >
                                        <label class="upload-file__wrapper ratio-4-1">
                                            <div class="upload-file-textbox text-center">
                                                <img width="34" height="34" class="svg"
                                                    src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                    alt="image upload">
                                                <h6 class="mt-1 fw-medium lh-base text-center text-body">
                                                    <span class="text-info">{{ translate('Click to upload') }}</span>
                                                    <br>
                                                    {{ translate('Or drag and drop') }}
                                                </h6>
                                            </div>
                                            <img class="upload-file-img" loading="lazy" src=""
                                                data-default-src="" alt="">
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
                                </div>
                            </div>
                        </div>
                        @if (theme_root_path() == 'theme_aster')
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                    <div class="d-flex flex-column gap-20 w-100">
                                        <div>
                                            <label for="" class="form-label fw-semibold mb-1">
                                               {{ translate('Secondary banner') }}
                                            </label>
                                            <p class="fs-12 mb-0">
                                                {{ getFileUploadFormats(skip: '.svg,.gif', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                <span class="text-dark fw-semibold">(1:1)</span>
                                            </p>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="bottom_banner" id="bottom-banner-upload"
                                                class="upload-file__input single_file_input"
                                                   data-max-size="{{ getFileUploadMaxSize() }}"
                                                data-required-msg="{{ translate('Secondary banner is required') }}"
                                                accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"
                                                value="" >
                                            <label class="upload-file__wrapper ratio-4-1">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg"
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                        alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center text-body">
                                                        <span class="text-info">{{ translate('Click to upload') }}</span>
                                                        <br>
                                                        {{ translate('Or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy" src=""
                                                    data-default-src="" alt="">
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
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="fs-18">{{ translate('TIN Document') }}</h3>
                    <p class="mb-0 fs-12">
                        {{ translate('Use this section to configure the necessary TIN certificate') }}.
                    </p>
                </div>
                <div class="card-body d-flex flex-column gap-4">
                    @foreach($tinBusinessModules as $moduleKey => $moduleLabel)
                        @include('admin-views.vendor.partials._tin-document', [
                            'moduleKey' => $moduleKey,
                            'moduleLabel' => $moduleLabel,
                        ])
                    @endforeach
                </div>
            </div>
            <div class="d-flex justify-content-end trans3 mt-4 action-btn-wrapper-container">
                <div
                    class="d-flex justify-content-sm-end justify-content-center gap-3 flex-grow-1 flex-grow-sm-0 bg-white action-btn-wrapper trans3">
                    <input type="hidden" name="from_submit" value="admin">
                    <button type="reset" id="reset_btn" class="btn btn-secondary px-3 px-sm-4 w-120">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn-primary px-3 px-sm-4">
                        <i class="fi fi-sr-disk"></i>
                        {{ translate('Save Information') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/js/admin/vendor.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/pdf-worker.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/file-upload/multiple-document-upload.js') }}"></script>
@endpush
