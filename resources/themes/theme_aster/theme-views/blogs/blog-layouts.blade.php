<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->get('direction') }}">
<head>

    <meta name="base-url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ $web_config['company_name'] }}" />

    <meta name="google-site-verification" content="{{getWebConfig('google_search_console_code')}}">
    <meta name="msvalidate.01" content="{{getWebConfig('bing_webmaster_code')}}">
    <meta name="baidu-site-verification" content="{{getWebConfig('baidu_webmaster_code')}}">
    <meta name="yandex-verification" content="{{getWebConfig('yandex_webmaster_code')}}">

    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="index, follow">
    <meta name="_token" content="{{csrf_token()}}">
    <link rel="shortcut icon" href="{{$web_config['fav_icon']['path']}}"/>

    <link rel="stylesheet" href="{{ theme_asset('assets/css/fonts-init.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap-icons.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/magnific-popup-1.1.0/magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/swiper/swiper-bundle.min.css') }}"/>
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/sweet_alert/sweetalert2.css') }}">

    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/style.css') }}"/>

    {!! ToastMagic::styles() !!}

    @stack('css_or_js')
    @include(VIEW_FILE_NAMES['robots_meta_content_partials'])
    <title>@yield('title')</title>

    <link rel="stylesheet" href="{{ theme_asset('assets/css/custom.css') }}"/>
    <style>
        :root {
            --bs-primary: {{ $web_config['primary_color'] }};
            --bs-primary-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --primary-dark: {{ $web_config['primary_color'] }};
            --primary-light: {{ $web_config['primary_color_light'] ?? '#FFFFFF' }};
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
@include('theme-views.layouts.partials._theme-mode-script')

@include('theme-views.layouts.partials._page-loader')

@include('theme-views.layouts.partials._alert-message')

@yield('content')

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
<span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>

@include('theme-views.layouts.partials._translate-text-for-js')
@include('theme-views.layouts.partials._route-for-js')
@include('theme-views.layouts.main-script')
@include('theme-views.layouts._firebase-script')

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
