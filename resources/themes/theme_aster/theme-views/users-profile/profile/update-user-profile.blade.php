@extends('theme-views.layouts.profile')

@section('title', translate('Personal Details').' | '.$web_config['company_name'].' '.translate('eCommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <h5>{{translate('Edit personal details')}}</h5>
                                <a href="{{ route('user-profile') }}" class="btn-link text-secondary d-flex align-items-baseline">
                                    <i class="bi bi-chevron-left fs-12"></i> {{translate('Go Back')}}
                                </a>
                            </div>
                            <div class="mt-4">
                                <form  action="{{route('user-update')}}" method="post" enctype="multipart/form-data" class="form-advance-validation form-advance-inputs-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                                    @csrf
                                    <div class="row gy-4">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="text-capitalize" for="f_name2">{{translate('First Name')}}</label>
                                                <input type="text" id="f_name" class="form-control" value="{{$customerDetail['f_name']}}" name="f_name" placeholder="{{ translate('Ex') }}: John" data-required-msg="{{ translate(':field is required', ['field' => translate('First Name')]) }}" maxlength="{{ $nameMaxLength }}" data-length-counter="#f-name-counter" data-length-limit-message="#f-name-limit-message" required>
                                                <div class="d-flex justify-content-between align-items-start gap-2 mt-1">
                                                    <small id="f-name-limit-message" class="text-danger d-none">{{ $nameLimitMessage }}</small>
                                                    <small id="f-name-counter" class="text-muted ms-auto text-nowrap"></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="text-capitalize" for="l_name2">{{translate('Last Name')}}</label>
                                                <input type="text" id="l_name" class="form-control" value="{{$customerDetail['l_name']}}" name="l_name" data-required-msg="{{ translate(':field is required', ['field' => translate('Last Name')]) }}" placeholder="{{ translate('Ex') }}: Doe" maxlength="{{ $nameMaxLength }}" data-length-counter="#l-name-counter" data-length-limit-message="#l-name-limit-message" required>
                                                <div class="d-flex justify-content-between align-items-start gap-2 mt-1">
                                                    <small id="l-name-limit-message" class="text-danger d-none">{{ $nameLimitMessage }}</small>
                                                    <small id="l-name-counter" class="text-muted ms-auto text-nowrap"></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="phone2">{{translate('Phone')}}</label>
                                                <div class="position-relative d-flex align-items-center">
                                                    <input type="tel" id="phone" class="form-control" value="{{$customerDetail['phone']}}" placeholder="{{translate('Ex').':'.'01xxxxxxxxx'}}" {{ $customerDetail['is_phone_verified'] ? 'disabled' : '' }} name="phone">

                                                    @if($customerDetail['phone'] && getLoginConfig(key: 'phone_verification'))
                                                        @if($customerDetail['is_phone_verified'])
                                                            <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ translate('Your phone is verified') }}">
                                                                <img width="16"
                                                                     src="{{theme_asset('assets/img/icons/verified.svg')}}"
                                                                     class="dark-support" alt="">
                                                            </span>
                                                        @else
                                                            <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                  title="{{ translate('Phone not verified.') }} {{ translate('Please verify through the user app') }}">
                                                                <img width="16"
                                                                     src="{{ theme_asset('assets/img/icons/verified-error.svg') }}"
                                                                     class="dark-support" alt="">
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="email2">{{translate('Email')}}</label>
                                                <div class="position-relative d-flex align-items-center">
                                                    <input type="email" id="email2" class="form-control" value="{{$customerDetail['email']}}" name="email">

                                                    @if($customerDetail['email'] && getLoginConfig(key: 'email_verification'))
                                                        @if($customerDetail['is_email_verified'])
                                                            <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ translate('Your email is verified') }}">
                                                            <img width="16"
                                                                 src="{{theme_asset('assets/img/icons/verified.svg')}}"
                                                                 class="dark-support" alt="">
                                                            </span>
                                                        @else
                                                            <span class="position-absolute inset-inline-end-10px cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                                                                  title="{{ translate('Email not verified.') }} {{ translate('Please verify through the user app.') }}">
                                                            <img width="16"
                                                                 src="{{theme_asset('assets/img/icons/verified-error.svg')}}"
                                                                 class="dark-support" alt="">
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="password2">{{translate('Password')}}</label>
                                                <div class="input-inner-end-ele">
                                                    <input type="password" minlength="6" id="password" class="form-control" name="password" placeholder="{{translate('Ex').':'.'7+'.translate('Character')}}">
                                                    <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                </div>
                                                <span class="text-danger mx-1 password-error"></span>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="text-capitalize" for="confirm_password2">{{translate('Confirm password')}}</label>
                                                <div class="input-inner-end-ele">
                                                    <input type="password" minlength="6" id="confirm-password" name="confirm_password" class="form-control" placeholder="{{translate('Ex').':'.'7+'.translate('Character')}}">
                                                    <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                </div>
                                            </div>
                                            <div id='message'></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>{{translate('Attachment')}}</label>
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input --size-8-75rem"  name="image" multiple aria-required="true" accept="{{getFileUploadFormats(skip: '.svg,.gif')}}"  data-max-size="{{ getFileUploadMaxSize() }}">
                                                        <div class="upload-file__img">
                                                            <div class="temp-img-box">
                                                                <div class="d-flex align-items-center flex-column gap-2">
                                                                    <i class="bi bi-upload fs-30"></i>
                                                                    <div class="fs-12 text-muted">{{translate('Change your profile')}}</div>
                                                                </div>
                                                            </div>
                                                            <img src="#" class="dark-support img-fit-contain border" alt="" hidden="">
                                                        </div>
                                                    </div>

                                                    <div class="text-muted">{{ translate('Image ratio should be') }} 1:1</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end gap-3">
                                                <button type="reset" class="btn btn-secondary" id="profile-reset-button">{{translate('Reset')}}</button>
                                                <button type="submit" class="btn btn-primary text-capitalize">{{translate('Update profile')}}</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
@endsection

@push('script')
    <script src="{{theme_asset('assets/js/password-strength.js')}}" defer></script>
    <script src="{{ theme_asset('assets/js/text-length-counter.js') }}" defer></script>
@endpush

