@extends('layouts.admin.app')

@section('title', translate('Update Delivery Man'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/deliveryman.png') }}" width="20"
                    alt="">
                {{ translate('Update Deliveryman') }}
            </h2>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <form action="{{ route('admin.people.delivery-man.update', [$deliveryMan['id']]) }}" method="post"
                    id="update-delivery-man-form" enctype="multipart/form-data" class="form-advance-validation form-advance-file-validation" novalidate>
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <h3 class="mb-0 page-header-title d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
                                <i class="fi fi-sr-user"></i>
                                {{ translate('General Information') }}
                            </h3>
                            <div class="row g-3">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2">{{ translate('First Name') }}<span class="text-danger">*</span> </label>
                                        <input type="text" value="{{ $deliveryMan['f_name'] }}" name="f_name"  data-required-msg="{{translate('First name field is required')}}"
                                               class="form-control" placeholder="{{ translate('New delivery man') }}" required data-maxlength="50">
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/50</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2">{{ translate('Last Name') }}<span class="text-danger">*</span> </label>
                                        <input type="text" value="{{ $deliveryMan['l_name'] }}" name="l_name"  data-required-msg="{{translate('Last name field is required')}}"
                                               class="form-control" placeholder="{{ translate('Last Name') }}" required data-maxlength="50">
                                        <div class="d-flex justify-content-end">
                                            <span class="text-body-light">0/50</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2"
                                               for="exampleFormControlInput1">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <input type="tel" value="{{ '+'.$deliveryMan['country_code']. $deliveryMan['phone'] }}" name="phone" class="form-control"
                                                   placeholder="{{ translate('Ex') }} : 017********"
                                                   data-required-msg="{{translate('Phone field is required')}}"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2">{{ translate('Identity Type') }}</label>
                                        <div class="select-wrapper">
                                            <select name="identity_type" class="form-select">
                                                <option value="passport"
                                                    {{ $deliveryMan['identity_type'] == 'passport' ? 'selected' : '' }}>
                                                    {{ translate('Passport') }}
                                                </option>
                                                <option value="driving_license"
                                                    {{ $deliveryMan['identity_type'] == 'driving_license' ? 'selected' : '' }}>
                                                    {{ translate('Driving License') }}
                                                </option>
                                                <option value="nid"
                                                    {{ $deliveryMan['identity_type'] == 'nid' ? 'selected' : '' }}>
                                                    {{ translate('Nid') }}
                                                </option>
                                                <option value="company_id"
                                                    {{ $deliveryMan['identity_type'] == 'company_id' ? 'selected' : '' }}>
                                                    {{ translate('Company ID') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2">{{ translate('Identity Number') }}</label>
                                        <input type="text" name="identity_number"
                                               value="{{ $deliveryMan['identity_number'] }}" class="form-control"
                                               placeholder="{{ translate('Ex') }} : DH-23434-LS" >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="mb-2">{{ translate('Address') }}</label>
                                        <textarea name="address" class="form-control" id="address" rows="1" placeholder="Address">{{ $deliveryMan['address'] }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="p-12 p-sm-20 bg-section rounded">
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <div class="card shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Deliveryman Image') }} <span class="text-danger">*</span>
                                                                </label>
                                                                <p class="fs-12 mb-0">
                                                                    {{ translate('Displayed as the deliveryman avatar in the system.') }}
                                                                </p>
                                                            </div>
                                                            <div class="upload-file">
                                                                <input type="file" name="image" class="upload-file__input single_file_input" value="{{ getStorageImages(path: $deliveryMan->image_full_url, type: 'backend-placeholder') ?? '' }}" {{ $deliveryMan?->image_full_url ? '' : 'required' }}
                                                                data-max-size="{{ getFileUploadMaxSize() }}"
                                                                       accept="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                                                                       data-required-msg="{{ translate('Deliveryman image field is required') }}">
                                                                <label
                                                                    class="upload-file__wrapper">
                                                                    <div class="upload-file-textbox text-center {{ $deliveryMan?->image_full_url ? 'd-none' : '' }}">
                                                                        <img width="34" height="34" class="svg img-fluid" src="{{ getStorageImages(path: $deliveryMan?->image_full_url, type: 'backend-placeholder') }}" alt="image upload">
                                                                        <h6 class="mt-1 fw-medium lh-base text-center text-capitalize">
                                                                            <span class="text-info">{{ translate('Click to upload') }}</span>
                                                                            <br>
                                                                            {{ translate('Or drag and drop') }}
                                                                        </h6>
                                                                    </div>
                                                                    <img class="upload-file-img" loading="lazy" src="{{ !empty($deliveryMan?->image_full_url) ? getStorageImages(path: $deliveryMan?->image_full_url, type: 'backend-placeholder') ?? '' :  '' }}"
                                                                         data-default-src="{{ !empty($deliveryMan?->image_full_url) ? getStorageImages(path: $deliveryMan?->image_full_url,type: 'backend-placeholder') ?? '' :  '' }}" alt="">
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
                                                                <span class="text-dark fw-medium">
                                                                    ({{ translate('Ratio') }} {{ "1:1" }})
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-8">
                                                <div class="card shadow-none h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex flex-column gap-20">
                                                            <div>
                                                                <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                                    {{ translate('Deliveryman Identity Image') }}
                                                                </label>
                                                                <p class="fs-12 mb-0">
                                                                    {{ translate('Upload the documents that help verify and identify the deliveryman.') }}
                                                                </p>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <div class="position-relative">
                                                                    <div class="multi_image_picker d-flex gap-20 pt-20"
                                                                         data-ratio="1/1"
                                                                         data-max-count="5"
                                                                         data-max-filesize="{{getFileUploadMaxSize()}}"
                                                                         data-allowed-formats="{{ getFileUploadFormats(skip: '.svg,.gif') }}"
                                                                         data-validation-error-msg="{{ translate('File size is too large Maximum').' '.getFileUploadMaxSize().' '.'MB' }}"
                                                                         data-field-name="identity_image[]"
                                                                    >
                                                                        <div class="dir-ltr">
                                                                            <div class="imageSlide_prev">
                                                                                <div class="d-flex justify-content-center align-items-center h-100">
                                                                                    <button type="button"
                                                                                            class="btn btn-circle border-0 bg-primary text-white">
                                                                                        <i class="fi fi-sr-angle-left"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                            <div class="imageSlide_next">
                                                                                <div class="d-flex justify-content-center align-items-center h-100">
                                                                                    <button type="button"
                                                                                            class="btn btn-circle border-0 bg-primary text-white">
                                                                                        <i class="fi fi-sr-angle-right"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        @if ($deliveryMan['identity_image'])
                                                                            @foreach ($deliveryMan->identity_images_full_url as $img)
                                                                                @php($unique_id = rand(1111, 9999))
                                                                                <div class="upload-file m-0 position-relative">
                                                                                    <label class="upload-file__wrapper">
                                                                                        <img class="upload-file-img" loading="lazy"
                                                                                             id="additional_Image_{{ $unique_id }}"
                                                                                             src="{{ getStorageImages(path: $img, type:'backend-basic') }}"
                                                                                             data-default-src="{{ getStorageImages(path: $img, type:'backend-basic') }}"
                                                                                             alt="">
                                                                                    </label>
                                                                                    <div class="overlay">
                                                                                        <div
                                                                                            class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                                                            <button type="button"
                                                                                                    class="btn btn-outline-info icon-btn view_btn"
                                                                                                    data-img="#additional_Image_{{ $unique_id }}">
                                                                                                <i class="fi fi-sr-eye"></i>
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        @endif
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
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h3 class="mb-0 page-header-title d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
                                <i class="tio-user"></i>
                                {{ translate('Account Information') }}
                            </h3>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label class="mb-2 d-flex">{{ translate('Email') }} <span class="text-danger">*</span></label>
                                        <input type="email" value="{{ $deliveryMan['email'] }}" name="email"
                                            class="form-control"
                                            placeholder="{{ translate('Ex') . ':' . 'email@example.com' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="user_password" class="mb-2 d-flex gap-1 align-items-center">
                                        {{ translate('Password') }}
                                        <span class="input-label-secondary cursor-pointer d-flex" data-bs-toggle="tooltip"
                                            data-bs-title="{{ translate('Use at least 8 characters, including one uppercase letter.') . ',' . translate('One lowercase letter') . ',' . translate('One digit') . ',' . translate('One special character') . ',' . translate('And no spaces') . '.' }}">
                                            <i class="fi fi-rr-info"></i>
                                        </span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="js-toggle-password form-control password-check"
                                            name="password" required id="user_password" minlength="8"
                                               data-required-msg="{{translate('Password field is required')}}"
                                            placeholder="{{ translate('Password minimum 8 characters') }}">
                                        <div id="changePassTarget" class="input-group-append changePassTarget">
                                            <a class="text-body-light" href="javascript:">
                                                <i id="changePassIcon" class="fi fi-sr-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label for="confirm_password"
                                        class="mb-2 d-flex gap-1 align-items-center">{{ translate('Confirm password') }}</label>
                                    <div class="input-group">
                                        <input type="password" class="js-toggle-password form-control"
                                            name="confirm_password" required id="confirm_password"
                                            placeholder="{{ translate('Confirm password') }}">
                                        <div id="changeConfirmPassTarget" class="input-group-append changePassTarget">
                                            <a class="text-body-light" href="javascript:">
                                                <i id="changeConfirmPassIcon" class="fi fi-sr-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="pass invalid-feedback">{{ translate('Repeat password not match') . '.' }}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-3 justify-content-end">
                                <button type="reset" id="reset"
                                    class="btn btn-secondary">{{ translate('Reset') }}</button>
                                <button type="button" class="btn btn-primary form-submit"
                                    data-form-id="update-delivery-man-form"
                                    data-redirect-route="{{ route('admin.people.delivery-man.list') }}"
                                    data-message="{{ translate('Want to update this delivery man') . '?' }}">
                                    {{ translate('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <span id="coba-image" data-url="{{ dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}"></span>
    <span id="extension-error" data-text="{{ translate('Please only input a png or jpg type file') }}"></span>
    <span id="size-error" data-text="{{ translate('File size is too large') }}"></span>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/deliveryman.js') }}"></script>
@endpush
