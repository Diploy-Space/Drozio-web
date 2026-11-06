<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction') ?? "ltr" }}">

<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="nofollow, noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">

    <title>@yield('title')</title>
    <link rel="shortcut icon"
          href="{{ getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: 'backend-logo') }}">

    @include("layouts.admin.partials._style-partials")

    {!! ToastMagic::styles() !!}

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin-v2.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/v2-menu-motion.css') }}">

    @stack('css_or_js')
</head>

<body data-bs-theme="light" class="{{ config('app.mode') == 'demo' ? 'demo' : '' }} v2-active">
<div id="page-progress-bar" aria-hidden="true"></div>
<script type="text/javascript">
    localStorage.getItem('aside-mini') === 'true' ? document.body.classList.add('aside-mini') : document.body.classList.remove('aside-mini');
</script>

<div class="row">
    <div class="col-12 position-fixed loader-container mt-10rem">
        <div id="loading" class="d--none">
            <div id="loader"></div>
        </div>
    </div>
</div>

@include('layouts.admin.partials.v2._body')

<audio id="myAudio">
    <source src="{{ dynamicAsset(path: 'public/assets/backend/sound/notification.mp3') }}" type="audio/mpeg">
</audio>

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

@include('layouts.admin.partials._translator-for-js')
@include("layouts.admin.partials._translated-message-container")
@include("layouts.admin.partials._routes-list-container")
@include("layouts.admin.partials._script-partials")

<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin-v2.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/v2-menu-motion.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/v2-pjax.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/page-transition.js') }}"></script>
@stack('script_2')
@stack('script')

</body>

</html>
