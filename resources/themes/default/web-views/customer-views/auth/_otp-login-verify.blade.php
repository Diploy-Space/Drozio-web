@extends('layouts.front-end.profile')

@section('title', translate('Verify'))
@php($recaptcha = getWebConfig(name: 'recaptcha'))
@section('content')
    <div class="container py-4 py-lg-5 my-4 __inline-8">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="card py-2 mt-4">
                    @if ($userVerify == 0)
                        <form class="card-body  otp-form" method="post" id="customer-otp-login-form"
                            action="{{ $otpVerifyUrl }}"
                            data-verify="{{ $otpVerifyUrl }}"
                            data-resend="{{ $otpResendUrl }}">
                            @csrf
                            <div class="form-group">
                                <div class="text-center">
                                    <img src="{{ dynamicAsset('public/assets/front-end/img/icons/otp-login-icon.svg') }}"
                                        width="50" height="50" alt="" class="mb-4">
                                </div>
                                <div class="resend_otp_custom text-center {{ $getTimeInSecond <= 0 ? 'd--none' : '' }}">
                                    <p class="text-primary mb-2 ">{{ translate('Resend code within') }}</p>
                                    <h6 class="text-primary mb-5 verifyTimer">
                                        <span class="verifyCounter" data-second="{{ $getTimeInSecond }}"></span>
                                    </h6>
                                </div>

                                <div class="text-center mb-4 pb-2 fs-13 max-w-320 mx-auto text-body">
                                    {{ translate('We have sent a verification code to') }}
                                    {{ '******' . substr(base64_decode($identity), -4) }}
                                </div>

                                <div
                                    class="d-flex gap-2 gap-sm-3 align-items-end justify-content-center forget-password-otp mb-4">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                    <input class="otp-field" type="text" name="opt-field[]" maxlength="1"
                                        autocomplete="off">
                                </div>
                                <input class="otp-value" type="hidden" name="token">
                            </div>
                            <input type="hidden" name="identity" value="{{ $identity }}">
                            <input type="hidden" name="type" value="{{ request('type') }}">

                            @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
                                <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                            @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                                <div class="dynamic-default-and-recaptcha-section">
                                    <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="customer_auth"
                                           data-input="#login-default-captcha-section"
                                           data-default-captcha="#login-default-captcha-section"

>
                                    <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                         data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                         data-base-url="{{ route('g-recaptcha-session-store') }}"
                                         data-session="{{ 'default_recaptcha_id_customer_auth' }}"
                                    >
                                    </div>
                                </div>
                            @else
                                <div class="default-captcha-container"
                                     data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                     data-base-url="{{ route('g-recaptcha-session-store') }}"
                                     data-session="{{ 'default_recaptcha_id_customer_auth' }}"
                                >
                                </div>
                            @endif

                            <div class="d-flex gap-2 justify-content-between align-items-center mt-3">
                                <button class="btn btn--primary w-100 submitOTpVerifyForm" type="submit">
                                    {{ translate('Verify') }}
                                </button>

                                <button class="btn btn--primary w-100 resend-otp-button resendVerifyForm" type="button">
                                    {{ translate('Resend OTP') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class=" p-5">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="text-center">
                                        <i class="fa fa-check-circle __text-100px __color-0f9d58"></i>
                                    </div>

                                    <span class="font-weight-bold d-block mt-4 __text-17px text-center">
                                        {{ translate('Hello') }}, {{ $user->f_name }}
                                    </span>
                                    <h5 class="font-black __text-20px text-center my-2">
                                        {{ translate('Verification completed successfully') }}!
                                    </h5>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="{{ route('customer.auth.login') }}" class="btn btn-sm btn--primary">
                                    {{ translate('Sign In') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            $(".otp-form .otp-value").focus();
            let otp_fields = $(".otp-form .otp-field"),
                otp_value_field = $(".otp-form .otp-value");
            otp_fields
                .on("input", function(e) {
                    $(this).val($(this).val().replace(/[^0-9]/g, ""));
                    let opt_value = "";
                    otp_fields.each(function() {
                        let field_value = $(this).val();
                        if (field_value != "") opt_value += field_value;
                    });
                    otp_value_field.val(opt_value);
                })
                .on("keyup", function(e) {
                    let key = e.keyCode || e.charCode;
                    if (key == 8 || key == 46 || key == 37 || key == 40) {
                        $(this).prev().focus();
                    } else if (key == 38 || key == 39 || $(this).val() != "") {
                        $(this).next().focus();
                    }
                })
                .on("paste", function(e) {
                    let paste_data = e.originalEvent.clipboardData.getData("text");
                    let paste_data_splitted = paste_data.split("");
                    $.each(paste_data_splitted, function(index, value) {
                        otp_fields.eq(index).val(value);
                    });
                });
        });
    </script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/verify-otp.js') }}" defer></script>
@endpush
