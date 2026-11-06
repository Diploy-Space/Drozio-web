@php($fcmCredentials = getWebConfig('fcm_credentials'))
<span id="Firebase_Configuration_Config" data-api-key="{{ $fcmCredentials['apiKey'] ?? '' }}"
      data-auth-domain="{{ $fcmCredentials['authDomain'] ?? '' }}"
      data-project-id="{{ $fcmCredentials['projectId'] ?? '' }}"
      data-storage-bucket="{{ $fcmCredentials['storageBucket'] ?? '' }}"
      data-messaging-sender-id="{{ $fcmCredentials['messagingSenderId'] ?? '' }}"
      data-app-id="{{ $fcmCredentials['appId'] ?? '' }}"
      data-measurement-id="{{ $fcmCredentials['measurementId'] ?? '' }}"
      data-csrf-token="{{ csrf_token() }}"
      data-route="{{ route('system.subscribe-to-topic') }}"
      data-recaptcha-store="{{ route('g-recaptcha-response-store') }}"
      data-favicon="{{ $web_config['fav_icon']['path'] }}"
      data-firebase-service-worker-file="{{ dynamicAsset(path: 'firebase-messaging-sw.js') }}"
      data-firebase-service-worker-scope="{{ dynamicAsset(path: 'firebase-cloud-messaging-push-scope') }}"
      data-in-page-popup="1"
      data-close-label="{{ translate('Close') }}"
>
</span>
<link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/fcm-popup.css') }}">


@if(isset($fcmCredentials['apiKey']) && !empty($fcmCredentials['apiKey']))
    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js' }}" defer></script>
    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js' }}" defer></script>
    <script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js' }}" defer></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-init.js') }}" defer></script>
    <script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-auth.js') }}" defer></script>

    <script>
        try {
            const topics = {!! json_encode(getFCMTopicListToSubscribe()) !!};
            const startFcmSubscription = function () {
                try { subscribeToNotificationTopics(topics); } catch (e) { console.warn(e); }
            };
            if (document.readyState === 'complete') {
                setTimeout(startFcmSubscription, 0);
            } else {
                window.addEventListener('load', function () { setTimeout(startFcmSubscription, 0); });
            }
        } catch (e) {
            console.warn(e);
        }
    </script>
    <script>
    (function () {
        var logoutHref = '{{ route('customer.auth.logout') }}';
        document.addEventListener('click', function (e) {
            var anchor = e.target.closest('a[href]');
            if (!anchor) return;
            if (anchor.href !== logoutHref && anchor.getAttribute('href') !== logoutHref) return;
            if (typeof clearFcmToken !== 'function') return;
            e.preventDefault();
            clearFcmToken().finally(function () {
                window.location.href = logoutHref;
            });
        });
    })();
    </script>
    <script src="{{ dynamicAsset(path: 'public/assets/modules/auction/js/auction-notification-listener.js') }}" defer></script>
@endif
