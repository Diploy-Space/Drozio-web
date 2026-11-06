<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ translate('Vendor') }} | {{ translate('Reset Password') }}</title>
    <link rel="shortcut icon" href="{{getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type:'backend-logo')}}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/fonts/inter/inter.css') }}">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/google-fonts.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/style.css') }}">

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
                    <img width="310" src="{{ getStorageImages(path: $eCommerceLogo, type:'backend-logo') }}" alt="Logo">
                </a>
                <h2 class="title">{{translate('Make Your Business')}} <span
                        class="font-weight-bold c1 d-block text-capitalize">{{translate('Profitable...')}}</span></h2>
            </div>
        </div>
        <div class="auth-wrapper-right">
            <div class="auth-wrapper-form">
                <div>
                    <div class="d-block d-lg-none">
                        <a class="d-inline-flex mb-3" href="{{ route('home') }}">
                            <img width="100" src="{{ getStorageImages(path: $eCommerceLogo, type:'backend-logo') }}"
                                 alt="Logo">
                        </a>
                    </div>

                    <div>
                        <h1 class="auth-title">{{ translate('Reset your password').'?' }}</h1>
                        <p class="auth-subtitle">
                            {{ translate('Give a new password to reset the vendor password') }}
                        </p>
                    </div>

                    <ol class="list-unstyled font-size-md text-start">
                        <li>
                            <span class="text--primary mr-2">1.</span>
                            {{ translate('Enter your new password') . '.' }}
                        </li>
                        <li>
                            <span class="text--primary mr-2">2.</span>
                            {{ translate('Enter confirm password') . '.' }}
                        </li>
                    </ol>

                    <form id="vendor-reset-password-form" class="needs-validation" novalidate method="POST" action="{{ route('vendor.auth.forgot-password.reset-password') }}">
                            @csrf

                        <div class="py-2 mt-4">
                            <div class="form-group d-none">
                                <input type="text" name="reset_token" value="{{$token}}" required>
                            </div>
                            <div class="form-group">
                                <label for="si-password" class="d-flex align-items-center">
                                    {{translate('New password')}}
                                    <small class="text-danger mx-1 password-error"></small>
                                </label>

                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control password-check"
                                           name="password" required id="user_password"
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

                            </div>
                            <div class="form-group">
                                <label for="si-password">{{translate('Confirm password')}}</label>

                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control"
                                           name="confirm_password" required id="confirm_password"
                                           placeholder="{{ translate('Confirm password') }}"
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

                        <button type="submit" class="btn btn-lg btn-block btn--primary forget-password-form">
                            {{ translate('Reset password')}}
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</main>

<span id="password-error-message" data-max-character="{{translate('At least 8 characters').'.'}}" data-uppercase-character="{{translate('At least one uppercase letter').'(A...Z)'.'.'}}" data-lowercase-character="{{translate('At least one lowercase letter').'(a...z)'.'.'}}"
      data-number="{{translate('At least one number').'(0...9)'.'.'}}" data-symbol="{{translate('At least one symbol').'(!...%)'.'.'}}"></span>


<span id="message-please-check-recaptcha" data-text="{{ translate('Please check the ReCAPTCHA') }}"></span>
<span id="message-auth-failed" data-text="{{ translate('Something went wrong') }}"></span>
<span id="message-copied_success" data-text="{{ translate('Copied successfully') }}"></span>
<span id="route-get-session-recaptcha-code"
      data-route="{{ route('get-session-recaptcha-code') }}"
      data-mode="{{ config('app.mode') }}"
></span>

<span id="message-copied_success" data-text="{{ translate('Copied successfully') }}"></span>
<script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor.min.js')}}"></script>
<script src="{{dynamicAsset(path: 'public/assets/back-end/js/theme.min.js')}}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/auth-form/auth-form.js') }}"></script>
<script src="{{dynamicAsset(path: 'public/assets/back-end/js/vendor/forgot-password.js')}}"></script>

{!! ToastMagic::scripts() !!}

@if ($errors->any())
    <script>
        'use strict';
        @foreach($errors->all() as $error)
        toastMagic.error('{{ $error }}');
        @endforeach
    </script>
@endif

</body>
</html>




