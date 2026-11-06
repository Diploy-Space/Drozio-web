@extends('layouts.front-end.app')

@section('title', translate('Contact Us'))

@push('css_or_js')
    <link rel="stylesheet"
        href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
@endpush

@section('content')
    <div class="__inline-58">
        <div class="container rtl">
            <div class="row">
                <div class="col-md-12 contact-us-page sidebar_heading text-center mb-2">
                    <h1 class="h3 mb-0 headerTitle">{{ translate('Contact Us') }}</h1>
                </div>
            </div>
        </div>

        <div class="container rtl text-align-direction">
            <div class="row no-gutters py-5">
                <div class="col-lg-6 iframe-full-height-wrap ">
                    <img class="for-contact-image" src="{{ theme_asset(path: 'public/assets/front-end/png/contact.png') }}"
                        alt="">
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body for-send-message">
                            <h2 class="h4 mb-4 text-center font-semibold text-black">{{ translate('Send us a message') }}
                            </h2>
                            <form action="{{ route('contact.store') }}" method="POST" id="contact-form">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>{{ translate('Name') }}</label>
                                            <input class="form-control name" name="name" type="text"
                                                value="{{ old('name') }}" placeholder="John Doe"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="cf-email">{{ translate('Email Address') }}</label>
                                            <input class="form-control email" name="email" type="email"
                                                value="{{ old('email') }}"
                                                placeholder="{{ translate('Enter email address') }}" required>

                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="cf-phone">{{ translate('Phone') }}</label>
                                            <input class="form-control mobile_number phone-input-with-country-picker"
                                                type="tel" value="{{ old('mobile_number') }}" name="mobile_number"
                                                placeholder="{{ translate('Contact number') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="cf-subject">{{ translate('Subject') }}:</label>
                                            <input class="form-control subject" type="text" name="subject"
                                                value="{{ old('subject') }}" placeholder="{{ translate('Short title') }}"
                                                required>

                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="cf-message">{{ translate('Message') }}</label>
                                            <textarea class="form-control message" name="message" rows="6" required>{{ old('subject') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                @if(isset($recaptcha) && $recaptcha['status'] == 1)
                                    <div class="dynamic-default-and-recaptcha-section">
                                        <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response"
                                            data-action="contact" data-action="contact"
                                            data-input="#login-default-captcha-section"
                                            data-default-captcha="#login-default-captcha-section">

                                        <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                            data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                            data-base-url="{{ route('g-recaptcha-session-store') }}"
                                            data-session="{{ 'default_captcha_value_contact' }}">
                                        </div>
                                    </div>
                                @else
                                    <div class="default-captcha-container"
                                        data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                        data-base-url="{{ route('g-recaptcha-session-store') }}"
                                        data-session="{{ 'default_captcha_value_contact' }}">
                                    </div>
                                @endif
                                <div class=" ">
                                    <button class="btn btn--primary" type="submit"
                                        id="contact-form-btn">{{ translate('Send') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
