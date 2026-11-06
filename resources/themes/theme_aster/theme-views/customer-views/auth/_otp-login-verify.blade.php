@extends('theme-views.layouts.profile')

@section('title', translate('Customer Verify') . ' | ' . $web_config['company_name'] . ' ' . translate('eCommerce'))
@php($recaptcha = getWebConfig(name: 'recaptcha'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-30">
        <div class="container" id="otp_form_section">
            @if ($userVerify == 0)
                <div class="card mb-5">
                    <div class="card-body py-5 px-lg-5">
                        <div class="row align-items-center pb-5">
                            <div class="col-lg-6 mb-5 mb-lg-0">
                                <h2 class="text-center mb-2 text-capitalize">{{ translate('OTP verification') }}</h2>
                                <p class="text-center mb-5 text-muted">{{ translate('Please Verify that its you') . '.' }}
                                </p>
                                <div class="d-flex justify-content-center">
                                    <img width="283" class="dark-support"
                                        src="{{ theme_asset('assets/img/media/otp.png') }}" alt="{{ translate('Image') }}">
                                </div>
                            </div>
                            <div class="col-lg-6 text-center">
                                <div class="d-flex justify-content-center mb-3">
                                    <img width="50" class="dark-support"
                                        src="{{ theme_asset('assets/img/media/otp-lock.png') }}"
                                        alt="{{ translate('Image') }}">
                                </div>
                                <p class="text-muted mx-w mx-auto width--27-5rem">
                                    {{ translate('We have sent a verification code to') }}
                                    {{ '******' . substr(base64_decode($identity), -4) }}
                                </p>
                                <div class="resend-otp-custom">
                                    <p class="text-primary mb-2">{{ translate('Resend code within') }}</p>
                                    <h6 class="text-primary mb-5 verifyTimer">
                                        <span class="verifyCounter"
                                            data-second="{{ $getTimeInSecond }}"></span>{{ translate('S') }}
                                    </h6>
                                </div>
                                <form class="otp-form" method="POST" id="customer-otp-reset-login-form"
                                    action="{{ $otpVerifyUrl }}"
                                    data-verify="{{ $otpVerifyUrl }}"
                                    data-resend="{{ $otpResendUrl }}">
                                    @csrf
                                    <div class="d-flex gap-2 gap-sm-3 align-items-end justify-content-center">
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
                                    <input type="hidden" name="identity" value="{{ $identity }}">
                                    <input type="hidden" name="type" value="{{ request('type') }}">

                                    @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
                                        <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                                    @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                                        <div class="dynamic-default-and-recaptcha-section">
                                            <input type="hidden" name="g-recaptcha-response"
                                                class="render-grecaptcha-response" data-action="customer_auth"
                                                data-input="#login-default-captcha-section"
                                                data-default-captcha="#login-default-captcha-section">

                                            <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                                data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                                data-base-url="{{ route('g-recaptcha-session-store') }}"
                                                data-session="{{ 'default_recaptcha_id_customer_auth' }}">
                                            </div>
                                        </div>
                                    @else
                                        <div class="default-captcha-container"
                                            data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                            data-base-url="{{ route('g-recaptcha-session-store') }}"
                                            data-session="{{ 'default_recaptcha_id_customer_auth' }}">
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-center gap-3 mt-2">
                                        <button class="btn btn-outline-primary otp-resend-btn"
                                            data-url="{{ $otpResendUrl }}">
                                            {{ translate('Resend OTP') }}
                                        </button>
                                        <button class="btn btn-primary px-sm-5 button-type-submit submitResetOTpVerifyForm"
                                            id="submitResetOTpVerifyForm" type="submit" disabled>
                                            {{ translate('Verify') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="container" id="success_message">
                    <div class="card">
                        <div class="card-body p-md-5">
                            <div class="row justify-content-center">
                                <div class="col-xl-6 col-md-10">
                                    <div class="text-center d-flex flex-column align-items-center gap-3">
                                        <img width="46" src="{{ theme_asset('assets/img/icons/check.png') }}"
                                            class="dark-support" alt="{{ translate('Image') }}">
                                        <h3 class="text-capitalize">{{ translate('Verification completed successfully') }}
                                        </h3>
                                        <p class="text-muted">
                                            {{ translate('Thank you for your verification') . '!' . translate('Now you can log in, your account is ready to use') }}
                                        </p>
                                        <div class="d-flex flex-wrap justify-content-center gap-3">
                                            <button class="btn btn-outline-primary bg-primary-light border-transparent"
                                                data-bs-toggle="modal"
                                                data-bs-target="#loginModal">{{ translate('Login') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/auth.js') }}" defer></script>
@endpush
