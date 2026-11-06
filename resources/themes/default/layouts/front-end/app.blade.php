<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->get('direction') ?? 'ltr' }}">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <meta name="_token" content="{{csrf_token()}}">
    <meta property="og:site_name" content="{{ $web_config['company_name'] }}" />

    <meta name="google-site-verification" content="{{getWebConfig('google_search_console_code')}}">
    <meta name="msvalidate.01" content="{{getWebConfig('bing_webmaster_code')}}">
    <meta name="baidu-site-verification" content="{{getWebConfig('baidu_webmaster_code')}}">
    <meta name="yandex-verification" content="{{getWebConfig('yandex_webmaster_code')}}">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $web_config['fav_icon']['path'] }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $web_config['fav_icon']['path'] }}">

    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/vendor/simplebar/dist/simplebar.min.css') }}">
    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/vendor/drift-zoom/dist/drift-basic.min.css') }}">
    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/vendor/lightgallery.js/dist/css/lightgallery.min.css') }}">
    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/css/theme.css') }}">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/front-end/webfonts/storefront-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/master.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/open-sans-font.css') }}">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/css/lightbox.css') }}">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/front-end/plugin/swiper/swiper-bundle.min.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.css') }}">

    @stack('css_or_js')

    {!! ToastMagic::styles() !!}

    @include(VIEW_FILE_NAMES['robots_meta_content_partials'])

    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/home.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/responsive1.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/style.css') }}">

    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/page-transition.css') }}">
    <style>
        :root {
            --base: {{ $web_config['primary_color'] }};
            --bs-base-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --base-2: {{ $web_config['secondary_color'] }};
            --web-primary: {{ $web_config['primary_color'] }};
            --web-primary-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --web-primary-10: {{ $web_config['primary_color'] }}10;
            --web-primary-20: {{ $web_config['primary_color'] }}20;
            --web-primary-40: {{ $web_config['primary_color'] }}40;
            --web-secondary: {{ $web_config['secondary_color'] }};
            --web-direction: {{ Session::get('direction') }};
            --text-align-direction: {{ Session::get('direction') === "rtl" ? 'right' : 'left' }};
            --text-align-direction-alt: {{ Session::get('direction') === "rtl" ? 'left' : 'right'}};
        }

        .dropdown-menu:not(.m-0) {
            margin-{{ Session::get('direction') === "rtl" ? 'right' : 'left' }}: -8px !important;
        }

        @media (max-width: 767px) {
            .navbar-expand-md .dropdown-menu > .dropdown > .dropdown-toggle {
                padding-{{ Session::get('direction') === "rtl" ? 'left' : 'right'}}: 1.95rem;
            }
        }
    </style>

    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/custom.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/product-ui.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/quick-view.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/popup-banner.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/header-menu.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/header-ui.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/storefront-header.css')}}">
    <link rel="stylesheet" href="{{theme_asset(path: 'public/assets/front-end/css/module-switcher.css')}}">

    @stack('page_css')

    {!! getSystemDynamicPartials(type: 'analytics_script') !!}

    @if(config('app.mode')=='demo')
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-TTLMKMHQ');</script>
    @endif
</head>

<body class="toolbar-enabled {{ config('app.mode')=='demo'?'demo':'' }}" id="demo">
<div id="page-progress-bar" aria-hidden="true"></div>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/page-transition.js') }}"></script>

@include('layouts.front-end.partials._modals')

@include('layouts.front-end.partials._quick-view-modal')
@include('layouts.front-end.partials.modal._buy-now')

@yield('header')
@sectionMissing('header')
    @include('layouts.front-end.partials._header')
@endif
@include('layouts.front-end.partials._alert-message')

@if(isset($isMobile) && $isMobile)
    <div id="app-banner" class="app-download-popup" style="--bottom: 30px;">
        <div class="d-flex gap-3 align-items-center">
            <button type="button" class="btn p-0 px-1 bg-transparent text-dark border-0 shadow-none app-banner-close">
                <i class="fi fi-sr-cross fs-14"></i>
            </button>
            <img width="44" class="img-fit aspect-1 w-40px flex-shrink-0"
                src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}"
                alt="6Valley"
            >
            <div class="flex-grow-1">
                <h5 class="fs-14 mb-1">{{ getWebConfig(name: 'company_name') }} {{ translate('Mobile App') }}</h5>
                <div class="fs-11 opacity-70">{{ translate('For better experience download') }}</div>
                <button type="button" id="install-btn" class="btn btn--primary fs-12 fw-semibold px-2 py-1 mt-2">
                    {{ translate('Download') }}
                </button>
            </div>
        </div>
    </div>
@endif

<span id="authentication-status" data-auth="{{ auth('customer')->check() ? 'true' : 'false' }}"></span>

@include('layouts.front-end.partials._page-loader')

<div id="global-loader" class="global-loader d-none">
    <span class="loader"></span>
</div>

@yield('content')

<span id="message-otp-sent-again" data-text="{{ translate('OTP has been sent again.') }}"></span>
<span id="message-wait-for-new-code" data-text="{{ translate('Please wait for new code.') }}"></span>
<span id="message-please-check-recaptcha" data-text="{{ translate('Please check the ReCAPTCHA') }}"></span>
<span id="message-please-retype-password" data-text="{{ translate('Please ReType Password') }}"></span>
<span id="message-password-not-match" data-text="{{ translate('Password do not match') }}"></span>
<span id="message-password-match" data-text="{{ translate('Password match') }}"></span>
<span id="message-password-need-longest" data-text="{{ translate('Password Must Be 6 Character') }}"></span>
<span id="message-send-successfully" data-text="{{ translate('Sent successfully') }}"></span>
<span id="message-update-successfully" data-text="{{ translate('Updated successfully') }}"></span>
<span id="message-successfully-copied" data-text="{{ translate('Copied successfully') }}"></span>
<span id="message-coupon-copied" data-text="{{ translate('Coupon copied') }}"></span>
<span id="message-copied-failed" data-text="{{ translate('Copy failed') }}"></span>
<span id="message-select-payment-method" data-text="{{ translate('Please select a payment Methods') }}"></span>
<span id="message-please-choose-all-options" data-text="{{ translate('Please choose all the options') }}"></span>
<span id="message-cannot-input-minus-value" data-text="{{ translate('Cannot input minus value') }}"></span>
<span id="message-all-input-field-required" data-text="{{ translate('All input field required') }}"></span>
<span id="message-no-data-found" data-text="{{ translate('No Data Found') }}"></span>
<span id="message-minimum-order-quantity-cannot-less-than" data-text="{{ translate('Minimum order quantity cannot be less than') }}"></span>
<span id="message-item-has-been-removed-from-cart" data-text="{{ translate('Item has been removed from cart') }}"></span>
<span id="message-sorry-stock-limit-exceeded" data-text="{{ translate('Sorry stock limit exceeded') }}"></span>
<span id="message-sorry-the-minimum-order-quantity-not-match" data-text="{{ translate('Sorry the minimum order quantity does not match') }}"></span>
<span id="message-cart" data-text="{{ translate('Cart') }}"></span>

<span id="route-messages-store" data-url="{{ route('messages') }}"></span>
<span id="route-address-update" data-url="{{ route('address-update') }}"></span>
<span id="route-coupon-apply" data-url="{{ route('coupon.apply') }}"></span>
<span id="route-cart-add" data-url="{{ route('cart.add') }}"></span>
<span id="route-cart-remove" data-url="{{ route('cart.remove') }}"></span>
<span id="route-cart-variant-price" data-url="{{ route('cart.variant-price') }}"></span>
<span id="route-cart-nav-cart" data-url="{{ route('cart.nav-cart', storefrontUsesModuleHeaderPanel() ? ['panel' => 'module'] : []) }}"></span>
<span id="route-cart-order-again" data-url="{{ route('cart.order-again') }}"></span>
<span id="route-cart-updateQuantity" data-url="{{route('cart.update-quantity')}}"></span>
<span id="route-cart-updateQuantity-guest" data-url="{{route('cart.update-quantity.guest')}}"></span>
<span id="route-pay-offline-method-list" data-url="{{ route('pay-offline-method-list') }}"></span>
<span id="route-customer-auth-sign-up" data-url="{{ route('customer.auth.sign-up') }}"></span>
<span id="route-searched-products" data-url="{{ route('product-search') }}"></span>
<span id="route-currency-change" data-url="{{ route('currency.update') }}"></span>
<span id="route-store-wishlist" data-url="{{ route('wishlists.store') }}"></span>
<span id="route-delete-wishlist" data-url="{{ route('wishlists.destroy', ['productId' => '__ID__']) }}"></span>
<span id="route-wishlists" data-url="{{ route('wishlists.index') }}"></span>
<span id="route-quick-view" data-url="{{ route('quick-view') }}"></span>
<span id="route-checkout-details" data-url="{{ route('checkout-details') }}"></span>
<span id="route-checkout-payment" data-url="{{ route('checkout-payment') }}"></span>
<span id="route-set-shipping-id" data-url="{{ route('customer.set-shipping-method') }}"></span>
<span id="route-order-note" data-url="{{ route('order-note') }}"></span>
<span id="route-product-restock-request" data-url="{{ route('cart.product-restock-request') }}"></span>
<div id="route-vendor-list" data-url="{{ route('vendors') }}"></div>

<span id="route-get-session-recaptcha-code"
      data-route="{{ route('get-session-recaptcha-code') }}"
      data-mode="{{ config('app.mode') }}"
></span>
<span id="password-error-message" data-max-character="{{translate('At least 8 characters').'.'}}" data-uppercase-character="{{translate('At least one uppercase letter').'(A...Z)'.'.'}}" data-lowercase-character="{{translate('At least one lowercase letter').'(a...z)'.'.'}}"
      data-number="{{translate('At least one number').'(0...9)'.'.'}}" data-symbol="{{translate('At least one symbol').'(!...%)'.'.'}}"></span>
<span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
<span id="system-session-direction" data-value="{{ session()->get('direction') ?? 'ltr' }}"></span>

<span id="is-request-customer-auth-sign-up" data-value="{{ Request::is('customer/auth/sign-up*') ? 1:0 }}"></span>
<span id="is-customer-auth-active" data-value="{{ auth('customer')->check() ? 1:0 }}"></span>

<span id="storage-flash-deals" data-value="{{ $web_config['flash_deals']['start_date'] ?? '' }}"></span>
<span id="exceeds10MBSizeLimit" data-text="{{ translate('File exceeds 10MB size limit') }}"></span>
<span id="imageUploadMaxSize" data-max-size="{{ getFileUploadMaxSize() }}"></span>
@include('layouts.front-end.partials._footer')
@include('layouts.front-end.partials.modal._dynamic-modals')

@include(storefrontShoppingAssistantView())

<div class="floating-btn-grp">
    <div class="__floating-btn">
        @php($whatsapp = getWebConfig(name: 'whatsapp'))
        @if(isset($whatsapp['status']) && $whatsapp['status'] == 1 )
            <div class="wa-widget-send-button">
                <a href="https://wa.me/{{ $whatsapp['phone'] }}?text=Hello%20there!" target="_blank">
                    <img src="{{theme_asset(path: 'public/assets/front-end/img/whatsapp.svg')}}" class="wa-messenger-svg-whatsapp wh-svg-icon" alt="{{ translate('Chat with us on WhatsApp') }}">
                </a>
            </div>
        @endif
    </div>
    <a class="btn-scroll-top btn--primary" href="#top" data-scroll>
        <i class="btn-scroll-top-icon czi-arrow-up"></i>
    </a>
</div>

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

<span id="see-more-less"
    data-see-more="{{ translate('See More') }}"
    data-see-less="{{ translate('See Less') }}"
    >
</span>

@php($recaptcha = getWebConfig(name: 'recaptcha'))
<span id="get-google-recaptcha-key"
      data-value="{{ isset($recaptcha) && $recaptcha['status'] == 1 ? $recaptcha['site_key'] : '' }}"
      data-default-captcha-required="{{ session(\App\Services\RecaptchaService::FALLBACK_SESSION_KEY) ? 1 : 0 }}"></span>

<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/jquery/dist/jquery-2.2.4.min.js') }}"></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/jquery-validate/jquery.validate.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/bs-custom-file-input/dist/bs-custom-file-input.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/simplebar/dist/simplebar.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/js/lightbox.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/drift-zoom/dist/Drift.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/lightgallery.js/dist/js/lightgallery.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/lg-video.js/dist/lg-video.min.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/owl.carousel.min.js')}}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/plugin/swiper/swiper-bundle.min.js')}}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/plugin/easyzoom/easyzoom.min.js')}}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/theme.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/sweet_alert.js') }}"></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/custom.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/slider-swipe.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/storefront-header.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/header-dropdown-autoclose.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/popup-banner.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/seller-filter.js') }}" defer></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/account-order-details.js') }}" defer></script>
<span id="intl-tel-input-assets"
      data-css="{{ dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/css/intlTelInput.css') }}"
      data-scripts="{{ json_encode([
          dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/intlTelInput.js'),
          dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/utils.js'),
          dynamicAsset(path: 'public/assets/backend/libs/intl-tel-input/js/intlTelInout-validation.js'),
      ]) }}"></span>
<script src="{{ dynamicAsset(path: 'public/assets/front-end/js/intl-tel-input-loader.js') }}" defer></script>

<script src="{{ dynamicAsset(path: 'public/assets/backend/file-validation/polyfills.js') }}" defer></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/file-validation/just-validate.min.js') }}" defer></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/file-validation/form-advance-validation.js') }}" defer></script>

<script src="{{ dynamicAsset(path: 'public/assets/front-end/js/auction-common.js') }}" defer></script>

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

@include('layouts.front-end.partials._firebase-script')

@if (isset($recaptcha) && $recaptcha['status'] == 1)
    <script async src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
@endif

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

<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/google-recaptcha/google-recaptcha-init.js') }}" defer></script>

<script>
    "use strict";

    $(document).mouseup(function (e) {
        let container = $(".search-card");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
        }
    });

    function route_alert(route, message) {
        Swal.fire({
            title: '{{ translate('Are you sure')}}?',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '{{$web_config['primary_color']}}',
            cancelButtonText: '{{ translate('No')}}',
            confirmButtonText: '{{ translate('Yes')}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }

    @php($cookie = $web_config['cookie_setting'] ? json_decode($web_config['cookie_setting']['value'], true):null)
    let cookie_content = `
        <div class="cookie-section">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between column-gap-4 row-gap-3">
                    <div class="text-wrapper">
                        <h5 class="title">{{ translate('Your Privacy Matter')}}</h5>
                        <div>{{ $cookie ? $cookie['cookie_text'] : '' }}</div>
                    </div>
                    <div class="btn-wrapper">
                        <button class="btn bg-dark text-white cursor-pointer" id="cookie-reject">{{ translate('No thanks')}}</button>
                        <button class="btn btn-success cookie-accept" id="cookie-accept">{{ translate('I Accept')}}</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $(document).on('click','#cookie-accept',function() {
        document.cookie = '6valley_cookie_consent=accepted; max-age=' + 60 * 60 * 24 * 30;
        $('#cookie-section').hide();
    });
    $(document).on('click','#cookie-reject',function() {
        document.cookie = '6valley_cookie_consent=reject; max-age=' + 60 * 60 * 24;
        $('#cookie-section').hide();
    });

    $(document).ready(function() {
        if (document.cookie.indexOf("6valley_cookie_consent=accepted") !== -1) {
            $('#cookie-section').hide();
        } else if (document.cookie.indexOf("6valley_cookie_consent=reject") !== -1) {
            $('#cookie-section').hide();
        } else{
            $('#cookie-section').html(cookie_content).show();
        }
    });
</script>

@stack('script')
</body>
</html>
