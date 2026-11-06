importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

function resolveFirebaseConfig() {
    const queryConfig = Object.fromEntries(new URL(self.location.href).searchParams.entries());
    if (queryConfig.apiKey) {
        return queryConfig;
    }

    try {
        importScripts('firebase-messaging-sw-config.js');
    } catch (e) {
        return null;
    }

    return self.firebaseMessagingConfig || null;
}

const firebaseConfig = resolveFirebaseConfig();

if (firebaseConfig && firebaseConfig.apiKey && firebaseConfig.messagingSenderId) {
    firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();
    messaging.onBackgroundMessage(function (payload) {
        console.log('[FCM SW] Background message received:', payload.data);
        if (payload.notification || !payload.data) {
            return;
        }

        return self.registration.showNotification(payload.data.title, {
            body: payload.data.body || '',
            icon: payload.data.icon || ''
        });
    });
}
