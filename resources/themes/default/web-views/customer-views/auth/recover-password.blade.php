@extends('layouts.front-end.profile')

@section('title', translate('Forgot password'))
@php($recaptcha = getWebConfig(name: 'recaptcha'))
@section('content')
    <div class="container py-4 py-lg-5 my-4 rtl">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 text-start">
                <h2 class="h3 mb-4">{{ translate('Forgot your password') }}?</h2>
                <p class="font-size-md">
                    {{ translate('Change your password in three easy steps.') }}
                    {{ translate('This helps to keep your new password secure.') }}
                </p>
                <ol class="list-unstyled font-size-md p-0">
                    <li>
                        <span class="text-primary mr-2">{{ translate('1') }}.</span>
                        {{ translate('Use your registered phone.') }}
                    </li>
                    <li>
                        <span class="text-primary mr-2">{{ translate('2') }}.</span>
                        {{ translate('We will send you a temporary OTP to your phone') }}.
                    </li>
                    <li>
                        <span class="text-primary mr-2">{{ translate('3') }}.</span>
                        {{ translate('Use the OTP code to change your password on our secure website.') }}
                    </li>
                </ol>

                <div class="card py-2 mt-4">
                    <form class="card-body needs-validation" action="{{ route('customer.auth.forgot-password') }}"
                        method="post" id="customer-forgot-password-form">
                        @csrf
                        <div class="form-group">
                            <label for="recover-email">
                                {{ translate('Phone') }}
                            </label>
                            <input class="form-control clean-phone-input-value" type="text" name="identity" required
                                placeholder="{{ translate('Enter your phone number') }}">
                            <span class="fs-12 text-muted">*
                                {{ translate('Must use country code before phone number') }}</span>
                            <div class="invalid-feedback">
                                {{ translate('Please provide valid phone number') }}
                            </div>
                        </div>

                        @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
                            <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                        @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                            <div class="dynamic-default-and-recaptcha-section">
                                <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response"
                                       data-input="#login-default-captcha-section"
                                       data-default-captcha="#login-default-captcha-section" data-action="customer_auth"
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

                        <button class="btn btn--primary" type="submit">
                            {{ translate('Send OTP') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
