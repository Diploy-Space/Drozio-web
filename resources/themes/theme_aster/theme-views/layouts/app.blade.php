<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->get('direction') }}">
<head>

    <meta name="base-url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ $web_config['company_name'] }}" />

    <meta name="google-site-verification" content="{{ getWebConfig('google_search_console_code') }}">
    <meta name="msvalidate.01" content="{{ getWebConfig('bing_webmaster_code') }}">
    <meta name="baidu-site-verification" content="{{ getWebConfig('baidu_webmaster_code') }}">
    <meta name="yandex-verification" content="{{ getWebConfig('yandex_webmaster_code') }}">

    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="index, follow">
    <meta name="_token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ $web_config['fav_icon']['path'] }}"/>

    <link rel="stylesheet" href="{{ theme_asset('assets/css/fonts-init.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap-icons.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/magnific-popup-1.1.0/magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/swiper/swiper-bundle.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/sweet_alert/sweetalert2.css') }}">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/front-end/webfonts/storefront-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/storefront-header.css') }}">

    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/style.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.css') }}">

    <title>@yield('title')</title>

    @include(VIEW_FILE_NAMES['robots_meta_content_partials'])

    {!! ToastMagic::styles() !!}

    @stack('css_or_js')

    <link rel="stylesheet" href="{{ theme_asset('assets/css/custom.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/page-transition.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/popup-banner.css') }}"/>
    <style>
        :root {
            --bs-primary: {{ $web_config['primary_color'] }};
            --bs-primary-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --primary-dark: {{ $web_config['primary_color'] }};
            --primary-light: {{ !empty($web_config['primary_color_light']) ? $web_config['primary_color_light'] : '#FFFFFF' }};
            --bs-secondary: {{ $web_config['secondary_color'] }};
            --bs-secondary-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};
        }

        .announcement-color {
            background-color: {{ $web_config['announcement']['color'] }};
            color: {{$web_config['announcement']['text_color']}};
        }
        .btn-outline-success {
            --bs-btn-hover-bg: {{ $web_config['primary_color'] }} !important;
            --bs-btn-active-bg: {{ $web_config['primary_color'] }} !important;
            --bs-btn-border-color: {{ $web_config['primary_color'] }} !important;
        }
        .btn-outline-success:active {
            background-color: var(--bg-color) !important;
            color: {{ $web_config['primary_color'] }} !important;
            --bs-btn-border-color: {{ $web_config['primary_color'] }} !important;
        }
    </style>

    {!! getSystemDynamicPartials(type: 'analytics_script') !!}
</head>
<body class="toolbar-enabled">
<div id="page-progress-bar" aria-hidden="true"></div>
<script src="{{ theme_asset('assets/js/page-transition.js') }}"></script>
@include('theme-views.layouts.partials._theme-mode-script')

@include('theme-views.layouts.partials._page-loader')




@include('theme-views.layouts.partials._alert-message')
@yield('header')
@sectionMissing('header')
    @include('theme-views.layouts.partials._header')
@endif
@include('theme-views.layouts.partials._settings-sidebar')
@yield('content')
@include('theme-views.layouts.partials._feature')
@include('theme-views.layouts.partials._footer')

@include(storefrontShoppingAssistantView())

<a href="#" class="back-to-top">
    <i class="bi bi-arrow-up"></i>
</a>

<div class="app-bar header-mobile-block" id="mobile_app_bar">
    @yield('app-bar')
    @sectionMissing('app-bar')
        @include('theme-views.layouts.partials._app-bar')
    @endif
</div>

<div class="cart-overlay" id="cart_overlay"></div>

<span class="customize-text"
      data-textno="{{ translate('No') }}"
      data-textyes="{{ translate('Yes') }}"
      data-textnow="{{ translate('Now') }}"
      data-textsuccessfullycopied="{{ translate('Copied successfully') }}"
      data-text-no-discount="{{ translate('No discount') }}"
      data-stock-available="{{ translate('Stock available') }}"
      data-stock-not-available="{{ translate('Stock not available') }}"
      data-update-this-address="{{ translate('Update this Address') }}"
      data-password-characters-limit="{{ translate('Your password must be at least 8 characters') }}"
      data-password-not-match="{{ translate('Password does not Match') }}"
      data-textpleaseselectpaymentmethods="{{ translate('Please select a payment Methods') }}"
      data-reviewmessage="{{ translate('You can review after the product is delivered') }}"
      data-refundmessage="{{ translate('You can request a refund after the product is delivered') }}"
      data-textshoptemporaryclose="{{ translate('This shop is temporarily closed or on vacation').' '.translate('You cannot add products to the cart from this shop for now') }}"
></span>
<span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
<span class="cannot_use_zero" data-text="{{ translate('Cannot Use 0 only') }}"></span>
@php($cookie = $web_config['cookie_setting'] ? json_decode($web_config['cookie_setting']['value'], true):null)
@if($cookie && $cookie['status']==1)
    <section id="cookie-section"></section>
@endif

@if(!auth()->guard('customer')->check())
    @include('theme-views.layouts.partials.modal._register')
    @include('theme-views.layouts.partials.modal._login')
@endif

@include('theme-views.layouts.partials.modal._quick-view')
@include('theme-views.layouts.partials.modal._buy-now')
@include('theme-views.layouts.partials.modal._initial')

@include('theme-views.layouts.partials._social-chat')
@if(isset($isMobile) && $isMobile)
    <div id="app-banner" class="app-download-popup" style="--bottom: 80px;">
        <div class="d-flex gap-3 align-items-center">
            <button type="button" class="btn p-0 px-1 bg-transparent text-dark border-0 shadow-none app-banner-close">
                <i class="fi fi-sr-cross fs-14"></i>
            </button>
            <img width="44" class="img-fit aspect-1 w-40px flex-shrink-0"
                src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}"
                alt="6Valley"
            >
            <div class="flex-grow-1">
                <h5 class="fs-14 mb-1">{{ getWebConfig(name: 'company_name')  }} {{ translate('Mobile App') }}</h5>
                <div class="fs-11">{{ translate('For better experience download') }}</div>
                <button type="button" id="install-btn" class="btn btn-primary fs-12 fw-semibold px-2 py-1 mt-2">
                    {{ translate('Download') }}
                </button>
            </div>
        </div>
    </div>
@endif
@include('theme-views.layouts.partials._translate-text-for-js')
@include('theme-views.layouts.partials._route-for-js')
@include('theme-views.layouts.main-script')
@include('theme-views.layouts._firebase-script')
<span class="d-none" id="text-validate-translate"
      data-required="{{ translate('This field is required') }}"
      data-file-size-larger="{{ translate('File size is larger') }}"
      data-max-limit-crossed="{{ translate('Max limit crossed') }}"
      data-something-went-wrong="{{ translate('Something went wrong') }}"
      data-passwords-do-not-match="{{ translate('Passwords do not match') }}"
      data-valid-email="{{ translate('Please enter a valid email') }}"
      data-password-validation="{{ translate('Password must be 8+ chars with upper, lower, number & symbol') }}"
      data-file-type-not-allowed="{{ translate('Invalid file type selected') }}"
></span>
<span id="imageUploadMaxSize" data-max-size="{{ getFileUploadMaxSize() }}"></span>
@if(isset($isMobile) && $isMobile)
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const banner = document.getElementById("app-banner");
            if (!banner) return;

            const closeBtn = document.querySelector(".app-banner-close");
            const installBtn = document.getElementById("install-btn");

            const REOPEN_DELAY = 20 * 60 * 1000;
            const INITIAL_DELAY = 3000;
            const STORE_URL = '{{ $isAndroid
                ? (getWebConfig(name: "app_deep_link")["playstore_redirect_url"] ?? "")
                : (getWebConfig(name: "app_deep_link")["app_store_redirect_url"] ?? "")
            }}';

            let reopenTimer = null;

            function showBanner() {
                if (!document.hidden) {
                    banner.style.display = "block";
                    document.body.style.setProperty("--app-download-popup-height", banner.offsetHeight + "px");
                }
            }

            function scheduleReopen(delay) {
                if (reopenTimer) clearTimeout(reopenTimer);
                reopenTimer = setTimeout(showBanner, delay);
            }

            const closedAt = parseInt(localStorage.getItem("app_banner_closed_at") || "0");
            const elapsed = Date.now() - closedAt;

            if (closedAt && elapsed < REOPEN_DELAY) {
                banner.style.display = "none";
                scheduleReopen(REOPEN_DELAY - elapsed);
            } else {
                setTimeout(showBanner, INITIAL_DELAY);
            }

            closeBtn.addEventListener("click", function () {
                banner.style.display = "none";
                localStorage.setItem("app_banner_closed_at", Date.now().toString());
                scheduleReopen(REOPEN_DELAY);
            });

            installBtn.addEventListener("click", function () {
                window.location.href = STORE_URL;
            });
        });
    </script>
@endif

{!! ToastMagic::scripts() !!}

@if ($errors->any())
    <script>
        'use strict';
        @foreach($errors->all() as $index => $error)
        setTimeout(function() {
            toastMagic.error('{{ $error }}');
        }, {{ $index * 500 }});
        @endforeach
    </script>
@endif

<script>
    function route_alert(route, message) {
        Swal.fire({
            title: "{{translate('Are you sure')}}?",
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '{{$web_config['primary_color']}}',
            cancelButtonText: '{{translate('No')}}',
            confirmButtonText: '{{translate('Yes')}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }
</script>
@stack('script')

</body>
</html>
