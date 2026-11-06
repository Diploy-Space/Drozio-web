<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction') }}">

<head>
    <meta charset="utf-8">
    <title>{{ translate('Maintenance Mode On') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $web_config['fav_icon']['path'] }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $web_config['fav_icon']['path'] }}">
    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/css/theme.css') }}">
    <link rel="stylesheet" media="screen" href="{{ theme_asset(path: 'public/assets/front-end/css/maintenance-mode.css') }}">
    <style>
        :root {
            --web-primary: {{ $web_config['primary_color'] }};
            --web-primary-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
        }
    </style>
</head>

<body>

<div class="maintenance-page">
    <div class="container">
        <div class="maintenance-card">
            <img class="maintenance-illustration mb-4"
                 src="{{ theme_asset(path: 'public/assets/front-end/img/maintenance-mode-icon.png') }}"
                 alt="{{ translate('Maintenance') }}">

            <div class="maintenance-badge mb-3">{{ translate('Under maintenance') }}</div>

            <h2 class="title mb-3">{{ $maintenanceTitle }}</h2>

            <p class="info">{{ $maintenanceBody }}</p>

            @if ($maintenanceReturnTime)
                <div class="maintenance-return mt-4">
                    <span>{{ translate('We expect to be back by') }}</span>
                    <strong>{{ $maintenanceReturnTime }}</strong>
                </div>
            @endif

            @if ($maintenanceContacts)
                <div class="maintenance-divider my-4"></div>

                @if ($maintenanceContactPrompt)
                    <p class="info mb-3">{{ $maintenanceContactPrompt }}</p>
                @endif

                <div class="maintenance-contact-list">
                    @foreach ($maintenanceContacts as $maintenanceContact)
                        <a class="maintenance-contact" href="{{ $maintenanceContact['href'] }}">
                            {{ $maintenanceContact['value'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ theme_asset(path: 'public/assets/front-end/vendor/jquery/dist/jquery-2.2.4.min.js') }}"></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/theme.js') }}" defer></script>
</body>

</html>
