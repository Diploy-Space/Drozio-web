@extends('layouts.vendor.app')

@section('title', translate('Add New Delivery Man'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/deliveryman.png')}}" alt="">
                {{translate('Add New Delivery Man')}}
            </h2>
        </div>
        <form action="{{route('vendor.delivery-man.index')}}" method="post" enctype="multipart/form-data" id="add-delivery-man-form" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation" novalidate="novalidate">
            @csrf
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="f_name">{{ translate('First Name') }} <span class="text-danger">*</span> </label>
                                <input type="text" name="f_name" value="{{ old('f_name') }}" class="form-control"  data-required-msg="{{ translate('First Name field is required') }}" placeholder="{{ translate('First Name') }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="exampleFormControlInput1">{{ translate('Last Name') }} <span class="text-danger">*</span> </label>
                                <input value="{{old('l_name') }}"  type="text" name="l_name" class="form-control"  data-required-msg="{{ translate('Last name field is required') }}" placeholder="{{ translate('Last Name') }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="exampleFormControlInput1">{{ translate('Phone') }} <span class="text-danger">*</span> </label>
                                <div class="input-group">
                                    <input type="tel" value="{{ old('phone') }}" name="phone" class="form-control"
                                           placeholder="{{ translate('Ex') }} : 017********"
                                           data-required-msg="{{ translate('Phone field is required') }}"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="exampleFormControlInput1">{{ translate('Identity Type') }}</label>
                                <div class="select-wrapper">
                                    <select name="identity_type" class="form-control">
                                        <option value="passport">{{ translate('Passport') }}</option>
                                        <option value="driving_license">{{ translate('Driving License') }}</option>
                                        <option value="nid">{{ translate('NID') }}</option>
                                        <option value="company_id">{{ translate('Company ID') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="exampleFormControlInput1">{{ translate('Identity Number') }}</label>
                                <input value="{{ old('identity_number') }}"  type="text" name="identity_number" class="form-control"
                                       placeholder="{{ translate('Ex').': '.'DH-23434-LS'}}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color mb-2" for="exampleFormControlInput1">{{ translate('Address') }}</label>
                                <div class="input-group mb-3">
                                    <textarea name="address" class="form-control" id="address" rows="1" placeholder="{{ translate('Address') }}">{{ old('address') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="p-12 p-sm-20 bg-section rounded">
                                <div class="row gy-4 gx-3">
                                    <div class="col-md-4">
                                        <div class="card shadow-none">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-20">
                                                    <div>
                                                        <label for="" class="form-label fw-semibold mb-1 text-capitalize">
                                                            {{ translate('Deliveryman Image') }}
                                                        </label>
                                                        <p class="fs-12 mb-0">
                                                            {{ translate('Displayed as the deliveryman avatar in the system.') }}
                                                        </p>
                                                    </div>
                                                    <div class="upload-file">
                                                        <input type="file" name="image" class="upload-file__input single_file_input action-upload-color-image"
                                                               accept="{{ getFileUploadFormats(skip: '.svg') }}"
                                                               data-max-size="{{ getFileUploadMaxSize() }}"
                                                               value="" data-imgpreview="pre_img_viewer" required>
                                                        <label class="upload-file__wrapper">
                                                            <div class="upload-file-textbox text-center">
                                                                <img width="34" height="34" class="svg"
                                                                     src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                                     alt="image upload">
                                                                <h6 class="mt-1 fw-medium lh-base text-center fs-10">
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
                                                                <button type="button" class="btn btn-outline-primary text-primary icon-btn view_btn">
                                                                    <i class="fi fi-sr-eye"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-primary text-primary icon-btn edit_btn">
                                                                    <i class="fi fi-rr-camera"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <p class="fs-10 mb-0 text-center">
                                                        {{ getFileUploadFormats(skip: '.svg', asBladeMessage: true).' '. translate('Image size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                                        <span class="fw-medium">
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
                                                    <div class="d-flex flex-column bg-section rounded-10">
                                                        <div class="position-relative">
                                                            <div class="multi_image_picker d-flex gap-4 p-3"
                                                                 data-ratio="1/1"
                                                                 data-max-filesize="{{ getFileUploadMaxSize() }}"
                                                                 data-max-count="5"
                                                                 data-field-name="identity_image[]"
                                                                 data-required="true"
                                                                 data-required-msg="{{ translate(':field is required', ['field' => translate('Additional image')]) }}"
                                                            >
                                                                <div class="dir-ltr">
                                                                    <div class="imageSlide_prev">
                                                                        <div
                                                                            class="d-flex justify-content-center align-items-center h-100">
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-circle border-0 bg-primary text-white">
                                                                                <i class="fi fi-sr-angle-left"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="imageSlide_next">
                                                                        <div
                                                                            class="d-flex justify-content-center align-items-center h-100">
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-circle border-0 bg-primary text-white">
                                                                                <i class="fi fi-sr-angle-right"></i>
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
                        <i class="fi fi-sr-user"></i>
                        {{ translate('Account Information') }}
                    </h3>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="title-color">{{translate('Email')}}</label>
                                <input type="email" value="{{old('email')}}" name="email" class="form-control" placeholder="{{translate('Ex')}} : ex@example.com" autocomplete="off"
                                       required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="title-color d-flex align-items-center">{{translate('Password')}}
                                    <span class="input-label-secondary cursor-pointer d-flex" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{translate('Use at least 8 characters, including one uppercase letter.').','.translate('One lowercase letter').','.translate('One digit').','.translate('One special character').','.translate('And no spaces').'.'}}">
                                        <img alt="" width="16" src="{{dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}">
                                    </span>
                                </label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control password-check"
                                           autocomplete="off"
                                           name="password" required id="user_password" minlength="8"
                                           placeholder="{{ translate('Password minimum 8 characters') }}"
                                           data-hs-toggle-password-options='{
                                                         "target": "#changePassTarget",
                                                        "defaultClass": "tio-hidden-outlined",
                                                        "showClass": "tio-visible-outlined",
                                                        "classChangeTarget": "#changePassIcon"
                                                }'>
                                    <div id="changePassTarget" class="input-group-append">
                                        <a class="input-group-text" href="javascript:">
                                            <i id="changePassIcon" class="tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                                <span class="text-danger mx-1 password-error"></span>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="title-color">{{translate('Confirm password')}}</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control"
                                           name="confirm_password" required id="confirm_password"
                                           placeholder="{{ translate('Confirm password') }}"
                                           autocomplete="off"
                                           data-hs-toggle-password-options='{
                                                         "target": "#changeConfirmPassTarget",
                                                        "defaultClass": "tio-hidden-outlined",
                                                        "showClass": "tio-visible-outlined",
                                                        "classChangeTarget": "#changeConfirmPassIcon"
                                                }'>
                                    <div id="changeConfirmPassTarget" class="input-group-append">
                                        <a class="input-group-text" href="javascript:">
                                            <i id="changeConfirmPassIcon" class="tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <span class="d-none" id="placeholderImg" data-img="{{dynamicAsset(path: 'public/assets/back-end/img/400x400/img3.png')}}"></span>

                    <div class="d-flex gap-3 justify-content-end">
                        <button type="reset" id="reset" class="btn btn-secondary">{{translate('Reset')}}</button>
                        <button type="button" class="btn btn--primary form-submit" data-form-id="add-delivery-man-form" data-redirect-route="{{route('vendor.delivery-man.list')}}"
                                data-message="{{translate('Want to add this delivery man').'?'}}">{{translate('Submit')}}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <span id="coba-image" data-url="{{dynamicAsset(path: "public/assets/back-end/img/400x400/img3.png")}}"></span>
    <span id="extension-error" data-text="{{ translate('Please only input a png or jpg type file') }}"></span>
    <span id="size-error" data-text="{{ translate('File size is too large') }}"></span>
@endsection

@push('script_2')
    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/spartan-multi-image-picker/spartan-multi-image-picker-min.js') }}"></script>
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/deliveryman.js')}}"></script>
@endpush
