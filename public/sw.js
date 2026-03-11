/**
 * Service Worker — Syncro CRM Push Notifications
 */

self.addEventListener('push', function (event) {
    var data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'Syncro CRM', body: event.data ? event.data.text() : '' };
    }

    var title = data.title || 'Syncro CRM';
    var options = {
        body: data.body || '',
        icon: data.icon || '/images/favicon.svg',
        badge: '/images/favicon.png',
        data: { url: data.url || '/' },
        tag: data.tag || 'syncro-' + Date.now(),
        renotify: true,
        vibrate: [200, 100, 200],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url.indexOf(url) !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
