@extends('layouts.front-end.profile')

@section('title', translate('Register'))

@push('css_or_js')
    <link rel="stylesheet"
        href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
@endpush

@section('content')
    <div class="container py-4 __inline-7 text-align-direction">
        <div class="login-card">
            <div class="mx-auto __max-w-760">
                <h2 class="text-center h4 mb-4 font-bold text-capitalize fs-18-mobile">{{ translate('Sign up') }}</h2>
                <form class="needs-validation_" id="customer-register-form" action="{{ route('customer.auth.sign-up') }}"
                    method="post">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('First Name') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input class="form-control text-align-direction" value="{{ old('f_name') }}" type="text"
                                    name="f_name" placeholder="{{ translate('Ex') }}: John" required>
                                <div class="invalid-feedback">{{ translate('Please enter your first name') }}!</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('Last Name') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input class="form-control text-align-direction" type="text" value="{{ old('l_name') }}"
                                    name="l_name" placeholder="{{ translate('Ex') }}: Doe" required>
                                <div class="invalid-feedback">{{ translate('Please enter your last name') }}!</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('Email Address') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input class="form-control text-align-direction" type="email" value="{{ old('email') }}"
                                    name="email" placeholder="{{ translate('Enter email address') }}" autocomplete="off"
                                    required>
                                <div class="invalid-feedback">{{ translate('Please enter a valid email address') }}!</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('Phone number') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <input class="form-control phone-input-with-country-picker"
                                    type="tel" value="{{ old('phone') }}" name="phone"
                                    placeholder="{{ translate('Enter phone number') }}" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('Password') }}
                                    <span class="input-required-icon">*</span>
                                    <small class="text-danger mx-1 password-error"></small>
                                </label>
                                <div class="password-toggle rtl">
                                    <input class="form-control text-align-direction" name="password" type="password"
                                        id="si-password" placeholder="{{ translate('Minimum 8 characters long') }}"
                                        required>
                                    <label class="password-toggle-btn">
                                        <input class="custom-control-input" type="checkbox"><i
                                            class="tio-hidden password-toggle-indicator"></i><span
                                            class="sr-only">{{ translate('Show password') }} </span>
                                    </label>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">
                                    {{ translate('Confirm password') }}
                                    <span class="input-required-icon">*</span>
                                </label>
                                <div class="password-toggle rtl">
                                    <input class="form-control text-align-direction" name="con_password" type="password"
                                        placeholder="{{ translate('Minimum 8 characters long') }}" id="si-confirm-password"
                                        required>
                                    <label class="password-toggle-btn">
                                        <input class="custom-control-input text-align-direction" type="checkbox">
                                        <i class="tio-hidden password-toggle-indicator"></i>
                                        <span class="sr-only">{{ translate('Show password') }}</span>
                                    </label>
                                </div>
                            </div>

                        </div>

                        @if ($web_config['ref_earning_status'])
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label class="form-label font-semibold">{{ translate('Refer code') }} <small
                                            class="text-muted">({{ translate('Optional') }})</small></label>
                                    <input type="text" id="referral_code" class="form-control" name="referral_code"
                                        placeholder="{{ translate('Use referral code') }}">
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="rtl">
                                    <label class="custom-control custom-checkbox m-0 d-flex">
                                        <input type="checkbox" class="custom-control-input" name="remember"
                                            id="inputChecked">
                                        <span class="custom-control-label">
                                            <span>{{ translate('I agree to Your') }}</span>
                                            <a class="font-size-sm text-primary text-force-underline" target="_blank"
                                                href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}">{{ translate('Terms and condition') }}</a>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-6">
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
                            </div>
                        </div>
                    </div>

                    <div class="web-direction">
                        <div class="mx-auto mt-4 __max-w-356">
                            <button class="w-100 btn btn--primary" id="sign-up" type="submit" disabled>
                                {{ translate('Sign up') }}
                            </button>
                        </div>

                        @if ($web_config['social_login_text'])
                            <div class="text-center m-3 text-black-50">
                                <small>{{ translate('Or continue with') }}</small>
                            </div>
                        @endif

                        <div class="d-flex justify-content-center my-3 gap-2">
                            @if (
                                isset($web_config['customer_login_options']['social_login']) &&
                                $web_config['customer_login_options']['social_login']
                            )
                                @foreach ($web_config['customer_social_login_options'] as $socialLoginServiceKey => $socialLoginService)
                                    @if ($socialLoginService && $socialLoginServiceKey != 'apple')
                                        <a class="d-block"
                                            href="{{ route('customer.auth.service-login', $socialLoginServiceKey) }}">
                                            <img src="{{ theme_asset(path: 'public/assets/front-end/img/icons/' . $socialLoginServiceKey . '.png') }}"
                                                alt="{{ translate($socialLoginServiceKey) }}">
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        <div class="text-black-50 mt-3 text-center">
                            <small>
                                {{ translate('Already have account') }}?
                                <a class="text-primary text-underline" href="{{ route('customer.auth.login') }}">
                                    {{ translate('Sign In') }}
                                </a>
                            </small>
                        </div>

                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
