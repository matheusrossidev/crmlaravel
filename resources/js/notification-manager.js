/**
 * Syncro CRM — Notification Manager
 *
 * Handles: browser notifications, push subscriptions, sound playback.
 * Depends on window.vapidPublicKey, window.pushSubscriptionUrl, window.notificationPrefs
 * being injected via Blade before this script runs.
 */
(function () {
    'use strict';

    var sounds = {};
    var swRegistration = null;
    var audioUnlocked = false;

    // ── Sound preloading & playback ─────────────────────────

    var soundFiles = {
        'notification-chime': '/sounds/notification-chime.wav',
        'message-received': '/sounds/message-received.wav',
        'alert': '/sounds/alert.wav',
    };

    function preloadSounds() {
        for (var name in soundFiles) {
            if (soundFiles.hasOwnProperty(name)) {
                var audio = new Audio(soundFiles[name]);
                audio.preload = 'auto';
                audio.volume = 0.5;
                sounds[name] = audio;
            }
        }
    }

    function unlockAudio() {
        if (audioUnlocked) return;
        // Create a silent audio context to unlock autoplay
        for (var name in sounds) {
            if (sounds.hasOwnProperty(name)) {
                sounds[name].load();
            }
        }
        audioUnlocked = true;
        document.removeEventListener('click', unlockAudio);
        document.removeEventListener('keydown', unlockAudio);
    }

    function playSound(name) {
        var prefs = window.notificationPrefs || {};
        if (prefs.sound && prefs.sound.enabled === false) return;

        var audio = sounds[name] || sounds['notification-chime'];
        if (!audio) return;

        audio.currentTime = 0;
        audio.play().catch(function () {
            // Autoplay blocked — ignored
        });
    }

    // ── Service Worker registration ─────────────────────────

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return Promise.resolve(null);

        return navigator.serviceWorker.register('/sw.js')
            .then(function (reg) {
                swRegistration = reg;
                return reg;
            })
            .catch(function (err) {
                console.warn('[NotifManager] SW registration failed:', err);
                return null;
            });
    }

    // ── Browser notification permission ─────────────────────

    function requestPermission() {
        if (!('Notification' in window)) return Promise.resolve('denied');
        if (Notification.permission === 'granted') return Promise.resolve('granted');

        return Notification.requestPermission();
    }

    function getPermissionStatus() {
        if (!('Notification' in window)) return 'unsupported';
        return Notification.permission; // 'default', 'granted', 'denied'
    }

    // ── Push subscription ───────────────────────────────────

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; i++) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function subscribePush() {
        if (!swRegistration) {
            return Promise.reject(new Error('Service Worker not registered'));
        }

        var vapidKey = window.vapidPublicKey;
        if (!vapidKey) {
            return Promise.reject(new Error('VAPID public key not available'));
        }

        return requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                return Promise.reject(new Error('Notification permission denied'));
            }

            return swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });
        }).then(function (subscription) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            return fetch(window.pushSubscriptionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(subscription),
            });
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed to save push subscription');
            return response.json();
        });
    }

    function unsubscribePush() {
        if (!swRegistration) return Promise.resolve();

        return swRegistration.pushManager.getSubscription().then(function (sub) {
            if (!sub) return;

            return sub.unsubscribe().then(function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]');
                return fetch(window.pushSubscriptionUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ endpoint: sub.endpoint }),
                });
            });
        });
    }

    function isPushSubscribed() {
        if (!swRegistration) return Promise.resolve(false);

        return swRegistration.pushManager.getSubscription().then(function (sub) {
            return !!sub;
        });
    }

    // ── Browser notification display ────────────────────────

    function showBrowserNotification(title, body, url, type) {
        var prefs = window.notificationPrefs || {};

        // Check if user wants browser notifications for this type
        if (prefs.browser && prefs.browser[type] === false) return;

        if (Notification.permission !== 'granted') return;

        // Don't show if page is focused and visible
        if (document.hasFocus && document.hasFocus()) {
            // Page is focused — skip browser notification (toastr will show instead)
            return;
        }

        try {
            var notif = new Notification(title, {
                body: body || '',
                icon: '/images/favicon.svg',
                tag: type || 'syncro',
            });

            notif.onclick = function () {
                window.focus();
                if (url) window.location.href = url;
                notif.close();
            };

            // Auto-close after 8 seconds
            setTimeout(function () {
                notif.close();
            }, 8000);
        } catch (e) {
            // Notification constructor not available (e.g., insecure context)
        }
    }

    // ── Notify helper (sound + browser notif in one call) ───

    function notify(title, body, url, type, soundName) {
        showBrowserNotification(title, body, url, type);

        var prefs = window.notificationPrefs || {};
        var resolvedSound = soundName
            || (prefs.sound && prefs.sound[type])
            || (prefs.sound && prefs.sound['default'])
            || 'notification-chime';

        playSound(resolvedSound);
    }

    // ── Init ────────────────────────────────────────────────

    function init() {
        preloadSounds();

        // Unlock audio on first user interaction
        document.addEventListener('click', unlockAudio, { once: true });
        document.addEventListener('keydown', unlockAudio, { once: true });

        registerServiceWorker();
    }

    // Auto-init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ──────────────────────────────────────────

    window.NotifManager = {
        playSound: playSound,
        requestPermission: requestPermission,
        getPermissionStatus: getPermissionStatus,
        subscribePush: subscribePush,
        unsubscribePush: unsubscribePush,
        isPushSubscribed: isPushSubscribed,
        showBrowserNotification: showBrowserNotification,
        notify: notify,
    };
})();
