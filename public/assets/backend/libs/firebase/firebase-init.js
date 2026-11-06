"use strict";

class FirebaseAppManager {
    constructor(config) {
        if (!config || typeof config !== 'object') {
            throw new Error('Firebase config object is required');
        }

        this.firebaseConfig = config;
        this.app = null;
        this.messaging = null;
    }

    initialize() {
        try {
            if (firebase.apps.length === 0) {
                this.app = firebase.initializeApp(this.firebaseConfig);
            } else {
                this.app = firebase.app();
            }

            if (firebase.messaging.isSupported && firebase.messaging.isSupported()) {
                this.messaging = firebase.messaging();
            }
        } catch (e) {
            console.warn('🔥 Firebase initialization failed:', e);
        }
    }

    getApp() {
        return this.app;
    }

    getMessaging() {
        return this.messaging;
    }
}

let firebaseConfigurationConfig = $('#Firebase_Configuration_Config');
const firebaseWebConfig = {
    apiKey: firebaseConfigurationConfig.data('api-key'),
    authDomain: firebaseConfigurationConfig.data('auth-domain'),
    projectId: firebaseConfigurationConfig.data('project-id'),
    storageBucket: firebaseConfigurationConfig.data('storage-bucket'),
    messagingSenderId: firebaseConfigurationConfig.data('messaging-sender-id'),
    appId: firebaseConfigurationConfig.data('app-id'),
    measurementId: firebaseConfigurationConfig.data('measurement-id')
};
const firebaseManager = new FirebaseAppManager(firebaseWebConfig);

firebaseManager.initialize();

const app = firebaseManager.getApp();
const messaging = firebaseManager.getMessaging();

const PERMISSION_GESTURE_EVENTS = ['click', 'keydown', 'touchend'];

function askNotificationPermission() {
    return new Promise(resolve => {
        let settled = false;
        const settle = permission => {
            if (!settled) {
                settled = true;
                resolve(permission === 'granted');
            }
        };
        const request = Notification.requestPermission(settle);
        if (request && typeof request.then === 'function') {
            request.then(settle, () => settle('denied'));
        }
    });
}

function requestNotificationPermission() {
    if (!('Notification' in window)) {
        return Promise.resolve(false);
    }

    if (Notification.permission === 'granted') {
        return Promise.resolve(true);
    }

    if (Notification.permission === 'denied') {
        console.warn('[FCM] Notification permission is blocked for this site.');
        return Promise.resolve(false);
    }

    console.log('[FCM] Waiting for a click or key press to ask for notification permission.');

    return new Promise(resolve => {
        const onGesture = () => {
            PERMISSION_GESTURE_EVENTS.forEach(eventName => document.removeEventListener(eventName, onGesture, true));
            askNotificationPermission().then(resolve);
        };
        PERMISSION_GESTURE_EVENTS.forEach(eventName => document.addEventListener(eventName, onGesture, true));
    });
}

const FCM_SUBSCRIPTION_CACHE_KEY = 'fcm_topic_subscriptions_v2';

function readSubscribedTopics(token) {
    try {
        const cache = JSON.parse(window.localStorage.getItem(FCM_SUBSCRIPTION_CACHE_KEY));
        return (cache && cache.token === token && Array.isArray(cache.topics)) ? cache.topics : [];
    } catch (e) {
        return [];
    }
}

function rememberSubscribedTopics(token, topics) {
    try {
        window.localStorage.setItem(FCM_SUBSCRIPTION_CACHE_KEY, JSON.stringify({token: token, topics: topics}));
    } catch (e) {
        console.warn('Unable to cache topic subscriptions:', e);
    }
}

function forgetSubscribedTopics() {
    try {
        window.localStorage.removeItem(FCM_SUBSCRIPTION_CACHE_KEY);
    } catch (e) {
        console.warn('Unable to clear cached topic subscriptions:', e);
    }
}

function subscribeToNotificationTopics(topics) {
    const requestedTopics = (topics || []).filter(topic => typeof topic === 'string' && topic.length > 0);
    if (!messaging) {
        console.warn('[FCM] Not subscribed: Firebase messaging is not available in this browser.');
        return Promise.resolve();
    }
    if (requestedTopics.length === 0) {
        console.warn('[FCM] Not subscribed: no topics to subscribe.');
        return Promise.resolve();
    }

    console.log('[FCM] Requested topics:', requestedTopics);

    return requestNotificationPermission().then(permissionGranted => {
        if (!permissionGranted) {
            console.warn('[FCM] Not subscribed: notification permission was not granted.');
            return;
        }

        return getActiveServiceWorkerRegistration().then(registration => {
            return messaging.getToken(registration ? {serviceWorkerRegistration: registration} : undefined);
        }).then(token => {
            if (!token) {
                console.warn('[FCM] Not subscribed: no FCM token was issued.');
                return;
            }

            const subscribedTopics = readSubscribedTopics(token);
            const pendingTopics = requestedTopics.filter(topic => subscribedTopics.indexOf(topic) === -1);
            if (pendingTopics.length === 0) {
                console.log('[FCM] Already subscribed:', subscribedTopics);
                return;
            }

            return subscribeTokenToBackend(token, pendingTopics).then(() => {
                rememberSubscribedTopics(token, subscribedTopics.concat(pendingTopics));
                console.log('[FCM] Subscribed successfully:', pendingTopics);
            }).catch(error => {
                console.error('[FCM] Subscription failed:', pendingTopics, error.message || error);
            });
        }).catch(error => {
            console.error('[FCM] Not subscribed: could not get FCM token:', error.message || error);
        });
    });
}

function waitForServiceWorkerActivation(registration) {
    if (registration.active) {
        return Promise.resolve(registration);
    }

    const pendingWorker = registration.installing || registration.waiting;
    if (!pendingWorker) {
        return Promise.resolve(registration);
    }

    return new Promise(resolve => {
        pendingWorker.addEventListener('statechange', function onStateChange() {
            if (pendingWorker.state === 'activated' || pendingWorker.state === 'redundant') {
                pendingWorker.removeEventListener('statechange', onStateChange);
                resolve(registration);
            }
        });
    });
}

let fcmServiceWorkerRegistration = null;

function getActiveServiceWorkerRegistration() {
    const serviceWorkerFile = firebaseConfigurationConfig.data('firebase-service-worker-file');
    if (!('serviceWorker' in navigator) || !serviceWorkerFile) {
        return Promise.resolve(null);
    }

    if (fcmServiceWorkerRegistration) {
        return Promise.resolve(fcmServiceWorkerRegistration);
    }

    const serviceWorkerConfig = new URLSearchParams(
        Object.entries(firebaseWebConfig).filter(entry => entry[1] !== undefined && entry[1] !== null && entry[1] !== '')
    ).toString();

    return navigator.serviceWorker.register(serviceWorkerFile + (serviceWorkerFile.indexOf('?') === -1 ? '?' : '&') + serviceWorkerConfig, {
        scope: firebaseConfigurationConfig.data('firebase-service-worker-scope'),
    }).then(waitForServiceWorkerActivation).then(registration => {
        fcmServiceWorkerRegistration = registration;
        return registration;
    }).catch(error => {
        console.warn('[FCM] Service worker registration failed:', error);
        return null;
    });
}

function subscribeTokenToBackend(token, topics) {
    return fetch(firebaseConfigurationConfig.data('route'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': firebaseConfigurationConfig.data('csrf-token')
        },
        body: JSON.stringify({
            token: token,
            topics: topics
        })
    }).then(response => {
        if (response.status < 200 || response.status >= 400) {
            return response.text().then(text => {
                throw new Error(`Error subscribing to topic: ${response.status} - ${text}`);
            });
        }
    });
}

function displayNotification(notification) {
    const options = {
        body: notification.body,
        icon: $('#Firebase_Configuration_Config').data('favicon'),
        tag: notification.tag,
        renotify: true,
    };

    console.log('[FCM] Showing notification:', notification.title, 'permission:', Notification.permission);

    if (Notification.permission !== 'granted') {
        console.warn('[FCM] Not shown: notification permission is', Notification.permission);
        return Promise.resolve();
    }

    return getActiveServiceWorkerRegistration().then(registration => {
        if (!registration) {
            throw new Error('No service worker registration');
        }

        return registration.showNotification(notification.title, options);
    }).then(() => {
        console.log('[FCM] Notification shown by the browser.');
    }).catch(error => {
        console.warn('[FCM] showNotification failed, using the page Notification API:', error);
        try {
            new Notification(notification.title, options);
            console.log('[FCM] Notification shown with the page Notification API.');
        } catch (fallbackError) {
            console.error('[FCM] Notification could not be shown:', fallbackError);
        }
    });
}

const FCM_POPUP_TYPE = 'notification';
const FCM_POPUP_LIFETIME_MS = 10000;

function isInPagePopupEnabled() {
    return String(firebaseConfigurationConfig.data('in-page-popup')) === '1';
}

function getFcmPopupStack() {
    let stack = document.getElementById('fcm-popup-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'fcm-popup-stack';
        stack.className = 'fcm-popup-stack';
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }

    return stack;
}

function showFcmPopup(data) {
    const popup = document.createElement('div');
    popup.className = 'fcm-popup';
    popup.setAttribute('role', 'status');

    if (data.image) {
        const image = document.createElement('img');
        image.className = 'fcm-popup__image';
        image.src = data.image;
        image.alt = '';
        image.addEventListener('error', () => image.remove());
        popup.appendChild(image);
    }

    const content = document.createElement('div');
    content.className = 'fcm-popup__content';

    const title = document.createElement('div');
    title.className = 'fcm-popup__title';
    title.textContent = data.title || '';
    content.appendChild(title);

    if (data.body) {
        const body = document.createElement('div');
        body.className = 'fcm-popup__body';
        body.textContent = data.body;
        content.appendChild(body);
    }

    popup.appendChild(content);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'fcm-popup__close';
    closeButton.setAttribute('aria-label', firebaseConfigurationConfig.data('close-label') || 'Close');
    closeButton.textContent = '×';
    popup.appendChild(closeButton);

    const dismiss = () => {
        popup.classList.add('fcm-popup--leaving');
        setTimeout(() => popup.remove(), 200);
    };
    closeButton.addEventListener('click', dismiss);
    setTimeout(dismiss, FCM_POPUP_LIFETIME_MS);

    getFcmPopupStack().prepend(popup);
    console.log('[FCM] In-page popup shown:', data.title);
}

const fcmMessageHandlers = [];
let fcmAuctionMessageOwnerRegistered = false;

function onFcmMessage(handler, options) {
    fcmMessageHandlers.push(handler);
    if (options && options.ownsAuctionMessages) {
        fcmAuctionMessageOwnerRegistered = true;
    }
}

onFcmMessage(function (payload) {
    const data = payload.data;
    if (!data) {
        return;
    }

    if (data.type?.includes('product_restock')) {
        productRestockStockLimitStatus(data);
    }

    if (data.type?.startsWith('auction') && fcmAuctionMessageOwnerRegistered) {
        return;
    }

    if (data.type === FCM_POPUP_TYPE && isInPagePopupEnabled()) {
        showFcmPopup(data);
        return;
    }

    displayNotification({
        title: data.title,
        body: data.body,
        tag: payload.fcmMessageId || payload.messageId || (data.title + '|' + data.body),
    });
});

if (messaging) {
    messaging.onMessage(function (payload) {
        console.log('[FCM] Message received:', payload?.data);
        fcmMessageHandlers.forEach(handler => {
            try {
                handler(payload);
            } catch (e) {
                console.error('[FCM] Message handler failed:', e);
            }
        });
    });
}

function clearFcmToken() {
    forgetSubscribedTopics();
    if (!messaging) {
        return Promise.resolve();
    }
    return messaging.deleteToken().catch(function (e) {
        console.warn('[FCM] deleteToken failed:', e);
    });
}
