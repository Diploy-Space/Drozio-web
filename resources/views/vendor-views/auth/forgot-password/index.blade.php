<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ translate('Forgot password') }}</title>
    <link rel="shortcut icon"
        href="{{ getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: 'backend-logo') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/fonts/inter/inter.css') }}">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/style.css') }}">
    <link rel="stylesheet"
        href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
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

@php($recaptcha = getWebConfig(name: 'recaptcha'))

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
                    <div>
                        <div class="d-block d-lg-none">
                            <a class="d-inline-flex mb-3" href="{{ route('home') }}">
                                <img width="100"
                                    src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                                    alt="Logo">
                            </a>
                        </div>

                        <div>
                            <h1 class="auth-title">{{ translate('Forgot password') . '?' }}</h1>
                            <p class="auth-subtitle">
                                {{ translate('Follow steps to reset vendor password') }}
                            </p>
                        </div>

                        @php($verificationBy = getWebConfig('vendor_forgot_password_method'))
                        @if ($verificationBy == 'email')
                            <ol class="list-unstyled font-size-md text-start">
                                <li>
                                    <span class="text--primary mr-2">1.</span>
                                    {{ translate('Enter your email address below') . '.' }}
                                </li>
                                <li>
                                    <span class="text--primary mr-2">2.</span>
                                    {{ translate('We will send you a temporary link to your email') . '.' }}
                                </li>
                                <li>
                                    <span class="text--primary mr-2">3.</span>
                                    {{ translate('By clicking the link to change your password on our secure website') . '.' }}
                                </li>
                            </ol>

                            <form id="vendor-forgot-password-form"
                                action="{{ route('vendor.auth.forgot-password.index') }}" method="post">
                                @csrf
                                <div class="js-form-message form-group mt-5 mb-1">
                                    <label class="input-label" for="signingVendorPassword" tabindex="0">
                                        <span class="d-flex justify-content-between align-items-center">
                                            {{ translate('Email Address') }}
                                            <a href="{{ route('vendor.auth.login') }}" class="text--primary">
                                                {{ translate('Back to login') }}
                                            </a>
                                        </span>
                                    </label>

                                    <input type="email" class="form-control form-control-lg" name="identity"
                                        value="{{ old('identity') }}" tabindex="1"
                                        placeholder="{{ translate('Enter email address') }}"
                                        aria-label="{{ translate('Enter email address') }}" required>
                                </div>
                                @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
                                    <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                                @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                                    <div class="dynamic-default-and-recaptcha-section">
                                        <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response"
                                               data-input="#login-default-captcha-section" data-action="vendor_forgot_password"
                                               data-default-captcha="#login-default-captcha-section"
                                        >

                                        <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                             data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                             data-base-url="{{ route('g-recaptcha-session-store') }}"
                                             data-session="{{ 'default_recaptcha_id_vendor_forgot_password' }}"
                                        >
                                        </div>
                                    </div>
                                @else
                                    <div class="default-captcha-container"
                                         data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                         data-base-url="{{ route('g-recaptcha-session-store') }}"
                                         data-session="{{ 'default_recaptcha_id_vendor_forgot_password' }}"
                                    >
                                    </div>
                                @endif

                                <button type="submit" id="vendor-forgot-password-btn"
                                    class="btn btn-lg btn-block btn--primary">
                                    {{ translate('Send') }}
                                </button>
                            </form>
                        @else
                            <ol class="list-unstyled font-size-md text-start">
                                <li>
                                    <span class="text--primary mr-2">1.</span>
                                    {{ translate('Fill in your phone number below') . '.' }}
                                </li>
                                <li>
                                    <span class="text--primary mr-2">2.</span>
                                    {{ translate('We will send you a temporary OTP to your phone') . '.' }}
                                </li>
                                <li>
                                    <span class="text--primary mr-2">3.</span>
                                    {{ translate('Use the OTP to change your password on our secure website') . '.' }}
                                </li>
                            </ol>

                            <form id="vendor-forgot-password-form"
                                action="{{ route('vendor.auth.forgot-password.index') }}" method="post">
                                @csrf
                                <div class="js-form-message form-group mt-5 mb-1">
                                    <label class="input-label" for="forgotVendorPassword" tabindex="0">
                                        <span class="d-flex justify-content-between align-items-center">
                                            {{ translate('Phone') }}
                                            <a href="{{ route('vendor.auth.login') }}" class="text--primary">
                                                {{ translate('Back to login') }}
                                            </a>
                                        </span>
                                    </label>

                                    <div class="form-group mb-3">
                                        <input type="tel" id="forgotVendorPassword"
                                            value="{{ old('identity') }}" name="identity"
                                            class="form-control phone-input-with-country-picker-forgot-password"
                                            placeholder="{{ translate('Enter phone number') }}" />
                                    </div>
                                </div>

                                @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
                                    <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                                @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
                                    <div class="dynamic-default-and-recaptcha-section">
                                        <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response" data-action="vendor_forgot_password"
                                               data-input="#login-default-captcha-section"
                                               data-default-captcha="#login-default-captcha-section"
                                        >

                                        <div class="default-captcha-container d-none" id="login-default-captcha-section"
                                             data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                             data-base-url="{{ route('g-recaptcha-session-store') }}"
                                             data-session="{{ 'default_recaptcha_id_vendor_forgot_password' }}"
                                        >
                                        </div>
                                    </div>
                                @else
                                    <div class="default-captcha-container"
                                         data-placeholder="{{ translate('Enter ReCAPTCHA value') }}"
                                         data-base-url="{{ route('g-recaptcha-session-store') }}"
                                         data-session="{{ 'default_recaptcha_id_vendor_forgot_password' }}"
                                    >
                                    </div>
                                @endif

                                <button type="submit" id="vendor-forgot-password-btn"
                                    class="btn btn-lg btn-block btn--primary">
                                    {{ translate('Get OTP') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @if (config('app.mode') == 'demo')
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-10">
                                    <span id="admin-email"
                                        data-email="{{ \App\Enums\DemoConstant::VENDOR['email'] }}">{{ translate('Email') }}
                                        : {{ \App\Enums\DemoConstant::VENDOR['email'] }}</span><br>
                                    <span id="admin-password"
                                        data-password="{{ \App\Enums\DemoConstant::VENDOR['password'] }}">{{ translate('Password') }}
                                        : {{ \App\Enums\DemoConstant::VENDOR['password'] }}</span>
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

    <div class="modal fade password-reset-successfully-modal" tabindex="-1" aria-labelledby="toggle-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 d-flex justify-content-end">
                    <button type="button" class="btn-close border-0" data-dismiss="modal" aria-label="Close"><i
                            class="tio-clear"></i></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0">
                    <div class="d-flex flex-column align-items-center text-center gap-2 mb-2">
                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/password-reset.png') }}"
                            width="70" class="mb-3 mb-20" alt="">
                        <h5 class="modal-title">{{ translate(':item reset successfully', ['item' => translate('Password')]) }}</h5>
                        <div class="text-center">
                            {{ translate('A password reset mail has been sent to your email') . '. ' . translate('Please check your email') . '.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
    <span id="message-please-check-recaptcha" data-text="{{ translate('Please check the ReCAPTCHA') }}"></span>
    <span id="message-auth-failed" data-text="{{ translate('Something went wrong') }}"></span>
    <span id="message-copied_success" data-text="{{ translate('Copied successfully') }}"></span>
    <span id="route-get-session-recaptcha-code" data-route="{{ route('get-session-recaptcha-code') }}"
        data-mode="{{ config('app.mode') }}"></span>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/theme.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/auth-form/auth-form.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/vendor/js/auth.js') }}"></script>

    {!! ToastMagic::scripts() !!}

    @if ($errors->any())
        <script>
            'use strict';
            @foreach ($errors->all() as $error)
                toastMagic.error('{{ $error }}');
            @endforeach
        </script>
    @endif

    <span id="get-google-recaptcha-key" data-value="{{ isset($recaptcha) && $recaptcha['status'] == 1 ? $recaptcha['site_key'] : '' }}"
          data-default-captcha-required="{{ session(\App\Services\RecaptchaService::FALLBACK_SESSION_KEY) ? 1 : 0 }}"></span>
    @if ($web_config['firebase_otp_verification'] && $web_config['firebase_otp_verification']['status'])
    @elseif(isset($recaptcha) && $recaptcha['status'] == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
    @endif
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.js') }}"></script>

    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/intlTelInput.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/utils.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/intlTelInout-validation.js') }}"></script>

    @php($fcmCredentials = getWebConfig('fcm_credentials'))
    <span id="Firebase_Configuration_Config" data-api-key="{{ $fcmCredentials['apiKey'] ?? '' }}"
          data-auth-domain="{{ $fcmCredentials['authDomain'] ?? '' }}"
          data-project-id="{{ $fcmCredentials['projectId'] ?? '' }}"
          data-storage-bucket="{{ $fcmCredentials['storageBucket'] ?? '' }}"
          data-messaging-sender-id="{{ $fcmCredentials['messagingSenderId'] ?? '' }}"
          data-app-id="{{ $fcmCredentials['appId'] ?? '' }}"
          data-measurement-id="{{ $fcmCredentials['measurementId'] ?? '' }}"
          data-csrf-token="{{ csrf_token() }}"
          data-route="{{ route('system.subscribe-to-topic') }}"
          data-recaptcha-store="{{ route('g-recaptcha-response-store') }}"
          data-favicon="{{ $web_config['fav_icon']['path'] }}"
          data-firebase-service-worker-file="{{ dynamicAsset(path: 'firebase-messaging-sw.js') }}"
          data-firebase-service-worker-scope="{{ dynamicAsset(path: 'firebase-cloud-messaging-push-scope') }}"
    >
    </span>

    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js' }}"></script>
    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js' }}"></script>
    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js' }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-init.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-auth.js') }}"></script>

    <script>
        try {
            const topics = {!! json_encode(getFCMTopicListToSubscribe()) !!};
            subscribeToNotificationTopics(topics);
        } catch (e) {
            console.warn(e);
        }
    </script>


</body>

</html>
