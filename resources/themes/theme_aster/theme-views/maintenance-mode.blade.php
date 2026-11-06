<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction') }}">

<head>
    <meta charset="utf-8">
    <title>{{ translate('Maintenance Mode On') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $web_config['fav_icon']['path'] }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $web_config['fav_icon']['path'] }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/fonts-init.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/maintenance-mode.css') }}">
    <style>
        :root {
            --bs-primary: {{ $web_config['primary_color'] }};
            --bs-primary-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
        }
    </style>
</head>

<body>

<main class="maintenance-shell">
    <div class="container">
        <div class="maintenance-panel mx-auto">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-5">
                    <div class="maintenance-visual h-100">
                        <img src="{{ dynamicAsset(path: 'public/assets/front-end/img/maintenance-mode-icon.png') }}"
                             alt="{{ translate('Maintenance') }}">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="maintenance-detail h-100 d-flex flex-column justify-content-center">
                        <span class="maintenance-status mb-3 align-self-start">{{ translate('Under maintenance') }}</span>

                        <h1 class="mb-3">{{ $maintenanceTitle }}</h1>

                        <p class="text-muted mb-0">{{ $maintenanceBody }}</p>

                        @if ($maintenanceReturnTime)
                            <div class="maintenance-window mt-4">
                                <div class="small text-muted">{{ translate('We expect to be back by') }}</div>
                                <div class="fw-semibold">{{ $maintenanceReturnTime }}</div>
                            </div>
                        @endif

                        @if ($maintenanceContacts)
                            <hr class="my-4">

                            @if ($maintenanceContactPrompt)
                                <p class="text-muted mb-3">{{ $maintenanceContactPrompt }}</p>
                            @endif

                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($maintenanceContacts as $maintenanceContact)
                                    <a class="maintenance-link" data-contact="{{ $maintenanceContact['type'] }}"
                                       href="{{ $maintenanceContact['href'] }}">
                                        {{ $maintenanceContact['value'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="{{ theme_asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ theme_asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
