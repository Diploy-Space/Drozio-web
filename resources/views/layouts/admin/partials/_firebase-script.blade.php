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
>
    </span>

@if(isset($fcmCredentials['apiKey']) && $fcmCredentials['apiKey'])
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js' }}"></script>
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js' }}"></script>
<script src="{{ 'https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js' }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-init.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/backend/libs/firebase/firebase-auth.js') }}"></script>

<script>
    try {
        const topics = {!! json_encode(getFCMTopicListToSubscribe()) !!};
        subscribeToNotificationTopics(topics);
    } catch (e) {
        console.warn(e);
    }
</script>
@endif

@if(function_exists('getCheckAddonPublishedStatus') && getCheckAddonPublishedStatus(moduleName: 'Auction'))
    @include('auction::admin-views.partials._auction-notification-setup')
@endif
