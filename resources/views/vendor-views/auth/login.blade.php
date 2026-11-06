@php
    use App\Enums\DemoConstant;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="robots" content="nofollow, noindex ">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ translate('Vendor Login') }}</title>
    <link rel="shortcut icon" href="{{ getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: 'backend-logo') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/fonts/inter/inter.css') }}">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/style.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.css') }}">

    @if ($web_config['primary_color'])
        <style>
            :root {
                --bs-primary: {!! $web_config['primary_color'] !!};
            }
        </style>
    @endif

    {!! ToastMagic::styles() !!}

</head>

<body>
    <main id="content" role="main" class="main">
        <div class="auth-wrapper">
            <div class="auth-wrapper-left"
                style="background: url('{{ dynamicAsset(path: 'public/assets/back-end/img/login-bg.webp') }}') no-repeat center center / cover">
                <div class="auth-left-cont">
                    @php($eCommerceLogo = getWebConfig(name: 'company_web_logo'))
                    <a class="d-inline-flex mb-5" href="{{ route('home') }}">
                        <img width="310" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                            alt="Logo">
                    </a>
                    <h2 class="title">{{ translate('Make Your Business') }} <span
                            class="font-weight-bold c1 d-block text-capitalize">{{ translate('Profitable...') }}</span>
                    </h2>
                </div>
            </div>
            <div class="auth-wrapper-right">
                <div class="auth-wrapper-form">
                    <div class="d-block d-lg-none">
                        <a class="d-inline-flex mb-3" href="{{ route('home') }}">
                            <img width="100"
                                src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                                alt="Logo">
                        </a>
                    </div>

                    <form action="{{ route('vendor.auth.login') }}" method="post" id="vendor-login-form">
                        @csrf
                        <div>
                            <h1 class="auth-title">{{ translate('Sign In') }}</h1>
                            <p class="auth-subtitle">
                                {{ translate('Welcome back to') }} {{ translate('Vendor Login') }}
                            </p>
                        </div>

                        <div class="js-form-message form-group">
                            <label class="input-label" for="signingVendorEmail">{{ translate('Email') }}</label>

                            <input type="email" class="form-control form-control-lg" name="email"
                                id="signingVendorEmail" tabindex="1" placeholder="email@address.com"
                                aria-label="email@address.com" required data-msg="Please enter a valid email address.">
                        </div>
                        <div class="js-form-message form-group">
                            <label class="input-label" for="signingVendorPassword" tabindex="0">
                                <span class="d-flex justify-content-between align-items-center">
                                    {{ translate('Password') }}
                                    <a href="{{ route('vendor.auth.forgot-password.index') }}" class="text--primary">
                                        {{ translate('Forgot password') }}
                                    </a>
                                </span>
                            </label>

                            <div class="input-group input-group-merge">
                                <input type="password" class="js-toggle-password form-control form-control-lg"
                                    name="password" id="signingVendorPassword"
                                    placeholder="{{ translate('8+ characters required') }}"
                                    aria-label="8+ characters required" required
                                    data-msg="Your password is invalid. Please try again."
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
                        </div>
                        <div class="form-group mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="termsCheckbox" name="remember">
                                    <label class="custom-control-label text-muted" for="termsCheckbox">
                                        {{ translate('Remember me') }}
                                    </label>
                                </div>
                                @if ($isVendorRegistrationOpen)
                                    <a href="{{ route('vendor.auth.registration.index') }}" class="text--primary">
                                        {{ translate('Register New Account') }}
                                    </a>
                                @endif
                            </div>
                        </div>


                        @if(isset($recaptcha) && $recaptcha['status'] == 1)
                            <div class="dynamic-default-and-recaptcha-section">
                                <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="login"
                                       data-input="#login-default-captcha-section"
                                       data-default-captcha="#login-default-captcha-section"
                                >

                                <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                     data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                     data-base-url="{{ route('g-recaptcha-session-store') }}"
                                     data-session="{{ 'vendorRecaptchaSessionKey' }}"
                                >
                                </div>
                            </div>
                        @else
                            <div class="default-captcha-container"
                                 data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                 data-base-url="{{ route('g-recaptcha-session-store') }}"
                                 data-session="{{ 'vendorRecaptchaSessionKey' }}"
                            >
                            </div>
                        @endif

                        <button type="submit" id="vendorLoginBtn" class="btn btn-lg btn-block btn--primary">
                            {{ translate('Sign In') }}
                        </button>
                    </form>

                    @if (config('app.mode') == 'demo')
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-10">
                                    <span id="vendor-email"
                                        data-email="{{ \App\Enums\DemoConstant::VENDOR['email'] }}">
                                        {{ translate('Email') }}
                                        : {{ \App\Enums\DemoConstant::VENDOR['email'] }}
                                    </span>
                                    <br>
                                    <span id="vendor-password"
                                        data-password="{{ \App\Enums\DemoConstant::VENDOR['password'] }}">
                                        {{ translate('Password') }}
                                        : {{ \App\Enums\DemoConstant::VENDOR['password'] }}
                                    </span>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn--primary" id="copyLoginInfo"><i class="tio-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <span id="message-please-check-recaptcha" data-text="{{ translate('Please check the ReCAPTCHA') }}"></span>
    <span id="message-auth-failed" data-text="{{ translate('Something went wrong') }}"></span>
    <span id="message-copied_success" data-text="{{ translate('Copied successfully') }}"></span>
    <span id="route-get-session-recaptcha-code" data-route="{{ route('get-session-recaptcha-code') }}"
        data-mode="{{ config('app.mode') }}"></span>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/theme.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/auth-form/auth-form.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor/login.js') }}"></script>

    {!! ToastMagic::scripts() !!}

    @if ($errors->any())
        <script>
            'use strict';
            @foreach ($errors->all() as $error)
                toastMagic.error('{{ $error }}');
            @endforeach
        </script>
    @endif

    @php($recaptcha = getWebConfig(name: 'recaptcha'))
    <span id="get-google-recaptcha-key"
          data-value="{{ isset($recaptcha) && $recaptcha['status'] == 1 ? $recaptcha['site_key'] : '' }}"
          data-default-captcha-required="{{ session(\App\Services\RecaptchaService::FALLBACK_SESSION_KEY) ? 1 : 0 }}"></span>
    @if (isset($recaptcha) && $recaptcha['status'] == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
    @endif
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.js') }}"></script>
</body>

</html>
