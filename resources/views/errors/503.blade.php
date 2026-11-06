<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction') ?? 'ltr' }}">

<head>
    <meta charset="utf-8">
    <title>{{ translate('Database refreshing') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $web_config['fav_icon']['path'] ?? '' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $web_config['fav_icon']['path'] ?? '' }}">
    <style>
        :root {
            --refresh-primary: {{ ($web_config['primary_color'] ?? '') ?: '#1455AC' }};
            --refresh-title: #1f2a37;
            --refresh-text: #5b6777;
            --refresh-muted: #8a94a3;
            --refresh-surface: #ffffff;
            --refresh-track: #e8edf4;
            --refresh-backdrop: #f4f7fb;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--refresh-backdrop);
            color: var(--refresh-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .refresh-card {
            width: 100%;
            max-width: 560px;
            padding: 40px 32px 32px;
            background: var(--refresh-surface);
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(31, 42, 55, .08);
            text-align: center;
        }

        .refresh-illustration {
            display: block;
            width: 100%;
            max-width: 320px;
            height: auto;
            margin: 0 auto 28px;
        }

        .refresh-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            margin-bottom: 16px;
            border-radius: 999px;
            background: var(--refresh-track);
            color: var(--refresh-primary);
            font-size: 13px;
            font-weight: 600;
        }

        .refresh-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: refresh-pulse 1.4s ease-in-out infinite;
        }

        .refresh-title {
            margin: 0 0 12px;
            color: var(--refresh-title);
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
        }

        .refresh-description {
            margin: 0 auto 28px;
            max-width: 440px;
        }

        .refresh-progress {
            position: relative;
            height: 6px;
            margin-bottom: 14px;
            border-radius: 999px;
            background: var(--refresh-track);
            overflow: hidden;
        }

        .refresh-progress-bar {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 40%;
            border-radius: inherit;
            background: var(--refresh-primary);
            animation: refresh-slide 1.8s ease-in-out infinite;
        }

        .refresh-status {
            margin: 0 0 24px;
            color: var(--refresh-muted);
            font-size: 14px;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 24px;
            border: 0;
            border-radius: 10px;
            background: var(--refresh-primary);
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s ease;
        }

        .refresh-button:hover {
            opacity: .9;
        }

        .refresh-button:focus-visible {
            outline: 3px solid var(--refresh-track);
            outline-offset: 2px;
        }

        .refresh-button svg {
            width: 18px;
            height: 18px;
        }

        @keyframes refresh-slide {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(250%);
            }
        }

        @keyframes refresh-pulse {
            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .35;
            }
        }

        [dir="rtl"] .refresh-progress-bar {
            animation-direction: reverse;
        }

        @media (max-width: 575px) {
            .refresh-card {
                padding: 28px 20px 24px;
                border-radius: 16px;
            }

            .refresh-illustration {
                max-width: 240px;
                margin-bottom: 20px;
            }

            .refresh-title {
                font-size: 21px;
            }

            .refresh-button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .refresh-badge-dot {
                animation: none;
            }

            .refresh-progress-bar {
                width: 100%;
                opacity: .45;
                animation: none;
            }
        }
    </style>
</head>

<body>

<main class="refresh-card">
    <img class="refresh-illustration" width="320" height="203"
         src="{{ dynamicAsset(path: 'public/assets/front-end/img/maintenance-mode-icon.png') }}"
         alt="{{ translate('Maintenance-mode') }}">

    <span class="refresh-badge">
        <span class="refresh-badge-dot" aria-hidden="true"></span>
        {{ translate('Refresh in progress') }}
    </span>

    <h1 class="refresh-title">{{ translate('We are refreshing the system') }}</h1>

    <p class="refresh-description">
        {{ translate('The system database is being refreshed. Please keep patience, the system will be up in about :minutes minutes.', ['minutes' => 2]) }}
    </p>

    <div class="refresh-progress" role="progressbar" aria-label="{{ translate('Refresh in progress') }}">
        <span class="refresh-progress-bar"></span>
    </div>

    <p class="refresh-status" role="status">
        {{ translate('This page will reload automatically once the system is ready.') }}
    </p>

    <button type="button" class="refresh-button" id="refresh-reload-button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-2.64-6.36"/>
            <path d="M21 3v6h-6"/>
        </svg>
        {{ translate('Reload now') }}
    </button>
</main>

<script>
    (function () {
        const availabilityCheckInterval = 15000;
        const serviceUnavailableStatus = 503;

        document.getElementById('refresh-reload-button').addEventListener('click', function () {
            window.location.reload();
        });

        setInterval(function () {
            fetch(window.location.href, {method: 'HEAD', cache: 'no-store', credentials: 'same-origin'})
                .then(function (response) {
                    if (response.status !== serviceUnavailableStatus) {
                        window.location.reload();
                    }
                })
                .catch(function () {
                });
        }, availabilityCheckInterval);
    })();
</script>
</body>

</html>
