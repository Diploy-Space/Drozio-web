<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="nofollow, noindex ">
    <title>{{ translate('Forgot password') }}</title>
    <link rel="shortcut icon"
        href="{{ getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: 'backend-logo') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/webfonts/uicons-regular-rounded.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/webfonts/uicons-solid-rounded.css') }}">

    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/admin/css/style-extended.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/admin/css/custom.css') }}">

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
                style="background: url('{{ dynamicAsset('public/assets/back-end/img/login-bg.webp') }}') no-repeat center center / cover">
                <div class="auth-left-cont user-select-none">
                    @php($eCommerceLogo = getWebConfig(name: 'company_web_logo'))
                    <a class="d-inline-flex mb-5" href="{{ route('home') }}">
                        <img width="310" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                            alt="Logo">
                    </a>
                    <h2 class="title h1">
                        {{ translate('Make Your Business') }}
                        <span class="fw-bold text-primary d-block text-capitalize">
                            {{ translate('Profitable...') }}
                        </span>
                    </h2>
                </div>
            </div>
            <div class="auth-wrapper-right">
                <div class="auth-wrapper-form">
                    <form action="{{ route('admin.auth.forgot-password.index') }}" method="post">
                        @csrf
                        <div class="mb-5 user-select-none">
                            <h1 class="display-4">{{ translate('Forgot password') }}</h1>
                            <h1 class="h3 text-body mb-4">
                                {{ translate('Enter the email on your account and we will send you a reset link') }}
                            </h1>
                        </div>

                        <div class="js-form-message form-group">
                            <label class="form-label user-select-none" for="forgotPasswordEmail">
                                {{ translate('Email') }}
                            </label>

                            <input type="email" class="form-control form-control-lg" name="email"
                                id="forgotPasswordEmail" tabindex="1" placeholder="email@address.com"
                                value="{{ old('email') }}" aria-label="email@address.com" required>
                        </div>

                        <button type="submit" class="btn btn-lg btn-block btn-primary mt-2">
                            {{ translate('Send reset link') }}
                        </button>

                        <div class="text-center mt-3">
                            <a href="{{ url($loginUrl) }}">
                                {{ translate('Back to login') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ dynamicAsset(path: 'public/assets/new/back-end/libs/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/bootstrap/bootstrap.bundle.min.js') }}"></script>

    {!! ToastMagic::scripts() !!}

    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastMagic.error('{{ $error }}');
            @endforeach
        </script>
    @endif
</body>

</html>
