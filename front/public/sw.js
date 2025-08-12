const CACHE_NAME = 'mixlink-v1.0.0';
const STATIC_CACHE_NAME = `${CACHE_NAME}-static`;
const DYNAMIC_CACHE_NAME = `${CACHE_NAME}-dynamic`;

const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/manifest.json',
    '/logo.png',
    '/logo.svg'
];

const CACHE_STRATEGIES = {
    images: /\.(png|jpg|jpeg|svg|gif|webp)$/,
    styles: /\.(css|scss)$/,
    scripts: /\.(js|ts)$/,
    fonts: /\.(woff|woff2|ttf|eot)$/
};

self.addEventListener('install', (event) => {

    event.waitUntil(
        caches.open(STATIC_CACHE_NAME).then((cache) => {
        return cache.addAll(STATIC_ASSETS);
        })
    );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                .filter((cacheName) => {
                    return cacheName.startsWith('mixlink-') && cacheName !== STATIC_CACHE_NAME && cacheName !== DYNAMIC_CACHE_NAME;
                })
                .map((cacheName) => {
                    console.log('[SW] Deleting old cache:', cacheName);
                    return caches.delete(cacheName);
                })
            );
        })
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (!request.url.startsWith('http')) {
        return;
    }

    if (shouldCacheAsset(url.pathname)) {
        event.respondWith(
        caches.match(request).then((response) => {
            if (response) {
                return response;
            }

            return fetch(request).then((fetchResponse) => {
                if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                    return fetchResponse;
                }

                const responseToCache = fetchResponse.clone();
                caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                    cache.put(request, responseToCache);
                });

                return fetchResponse;
            });
        })
        );
    }

    else if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
        fetch(request)
            .then((response) => {
                const responseToCache = response.clone();
                caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                    cache.put(request, responseToCache);
                });

                return response;
            })
            .catch(async () => {
                const response = await caches.match(request);
                return response || caches.match('/');
            })
        );
    }
});

function shouldCacheAsset(pathname) {
    return Object.values(CACHE_STRATEGIES).some((regex) => regex.test(pathname));
}

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const options = {
        body: event.data.text(),
        icon: '/logo-192x192.png',
        badge: '/badge-72x72.png',
        tag: 'mixlink-notification',
        requireInteraction: true
    };

    event.waitUntil(
        self.registration.showNotification('mixlink', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        self.clients.openWindow('/')
    );
});
