@php($fcmCredentials = getWebConfig('fcm_credentials'))
<span id="Firebase_Configuration_Config"
      data-api-key="{{ $fcmCredentials['apiKey'] ?? '' }}"
      data-auth-domain="{{ $fcmCredentials['authDomain'] ?? '' }}"
      data-project-id="{{ $fcmCredentials['projectId'] ?? '' }}"
      data-storage-bucket="{{ $fcmCredentials['storageBucket'] ?? '' }}"
      data-messaging-sender-id="{{ $fcmCredentials['messagingSenderId'] ?? '' }}"
      data-app-id="{{ $fcmCredentials['appId'] ?? '' }}"
      data-measurement-id="{{ $fcmCredentials['measurementId'] ?? '' }}"
      data-csrf-token="{{ csrf_token() }}"
      data-save-token-route="{{ route('vendor.system.save-fcm-web-token') }}"
      data-firebase-service-worker-file="{{ dynamicAsset(path: 'firebase-messaging-sw.js') }}"
      data-firebase-service-worker-scope="{{ dynamicAsset(path: 'firebase-cloud-messaging-push-scope') }}"
></span>

@if(isset($fcmCredentials['apiKey']) && $fcmCredentials['apiKey'])
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js' }}"></script>
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js' }}"></script>
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js' }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-init.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-auth.js') }}"></script>

<script>
    'use strict';
    try {
        requestNotificationPermission().then(function (permissionGranted) {
            if (!permissionGranted || !messaging) return;
            return getActiveServiceWorkerRegistration().then(function (registration) {
                return messaging.getToken(registration ? {serviceWorkerRegistration: registration} : undefined);
            }).then(function (token) {
                if (!token) return;
                var config = document.getElementById('Firebase_Configuration_Config');
                return fetch(config.dataset.saveTokenRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.dataset.csrfToken
                    },
                    body: JSON.stringify({ token: token })
                });
            }).catch(function (e) {
                console.warn('[FCM] Seller token registration failed:', e);
            });
        });
    } catch (e) {
        console.warn(e);
    }
</script>
@endif

@if(function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction'))
    @include('auction::vendor-views.partials._auction-notification-setup')
@endif
