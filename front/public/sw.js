const CACHE_NAME = 'mixlink-v1.1.0';
const STATIC_CACHE_NAME = `${CACHE_NAME}-static`;
const DYNAMIC_CACHE_NAME = `${CACHE_NAME}-dynamic`;
const IMAGE_CACHE_NAME = `${CACHE_NAME}-images`;
const API_CACHE_NAME = `${CACHE_NAME}-api`;

const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/manifest.json',
    '/logo.png',
    '/logo.svg'
];

const CACHE_STRATEGIES = {
    images: /\.(png|jpg|jpeg|svg|gif|webp|avif)$/i,
    styles: /\.(css|scss)$/i,
    scripts: /\.(js|ts|mjs)$/i,
    fonts: /\.(woff|woff2|ttf|eot|otf)$/i,
    api: /\/api\//i,
    static: /\.(html|json|xml|txt)$/i
};

const CACHE_TTL = {
    static: 24 * 60 * 60 * 1000, // 24 hours
    dynamic: 60 * 60 * 1000, // 1 hour
    images: 7 * 24 * 60 * 60 * 1000, // 7 days
    api: 5 * 60 * 1000 // 5 minutes
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE_NAME).then((cache) => {
                return cache.addAll(STATIC_ASSETS);
            }),
            self.registration.navigationPreload && self.registration.navigationPreload.enable()
        ])
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        Promise.all([
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            return cacheName.startsWith('mixlink-') &&
                                   ![STATIC_CACHE_NAME, DYNAMIC_CACHE_NAME, IMAGE_CACHE_NAME, API_CACHE_NAME].includes(cacheName);
                        })
                        .map((cacheName) => {
                            return caches.delete(cacheName);
                        })
                );
            }),
            cleanExpiredCache()
        ])
    );
    self.clients.claim();
});

async function cleanExpiredCache() {
    const cacheNames = await caches.keys();
    const promises = cacheNames.map(async (cacheName) => {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();

        const deletePromises = requests.map(async (request) => {
            const response = await cache.match(request);
            if (response) {
                const dateHeader = response.headers.get('date');
                if (dateHeader) {
                    const responseDate = new Date(dateHeader);
                    const now = new Date();
                    const age = now - responseDate;

                    let ttl = CACHE_TTL.dynamic; // Default TTL

                    if (CACHE_STRATEGIES.images.test(request.url)) ttl = CACHE_TTL.images;
                    else if (CACHE_STRATEGIES.api.test(request.url)) ttl = CACHE_TTL.api;
                    else if (CACHE_STRATEGIES.static.test(request.url)) ttl = CACHE_TTL.static;

                    if (age > ttl) {
                        return cache.delete(request);
                    }
                }
            }
        });

        return Promise.all(deletePromises);
    });

    return Promise.all(promises);
}

function getCacheStrategy(request) {
    const url = new URL(request.url);

    if (CACHE_STRATEGIES.images.test(url.pathname)) {
        return { cacheName: IMAGE_CACHE_NAME, strategy: 'cache-first' };
    }
    if (CACHE_STRATEGIES.fonts.test(url.pathname)) {
        return { cacheName: STATIC_CACHE_NAME, strategy: 'cache-first' };
    }
    if (CACHE_STRATEGIES.api.test(url.pathname)) {
        return { cacheName: API_CACHE_NAME, strategy: 'network-first' };
    }
    if (CACHE_STRATEGIES.styles.test(url.pathname) || CACHE_STRATEGIES.scripts.test(url.pathname)) {
        return { cacheName: DYNAMIC_CACHE_NAME, strategy: 'stale-while-revalidate' };
    }

    return { cacheName: DYNAMIC_CACHE_NAME, strategy: 'network-first' };
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (!request.url.startsWith('http')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseToCache = response.clone();
                    caches.open(STATIC_CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                    return response;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    return cachedResponse || await caches.match('/');
                })
        );
        return;
    }

    if (shouldCacheAsset(url.pathname)) {
        const { cacheName, strategy } = getCacheStrategy(request);

        switch (strategy) {
            case 'cache-first':
                event.respondWith(cacheFirst(request, cacheName));
                break;
            case 'network-first':
                event.respondWith(networkFirst(request, cacheName));
                break;
            case 'stale-while-revalidate':
                event.respondWith(staleWhileRevalidate(request, cacheName));
                break;
            default:
                event.respondWith(networkFirst(request, cacheName));
        }
    }
});
async function cacheFirst(request, cacheName) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const fetchResponse = await fetch(request);
        if (fetchResponse && fetchResponse.status === 200 && fetchResponse.type === 'basic') {
            const cache = await caches.open(cacheName);
            cache.put(request, fetchResponse.clone());
        }
        return fetchResponse;
    } catch (error) {
        console.log('[SW] Fetch failed for:', request.url, error);
        throw error;
    }
}

async function networkFirst(request, cacheName) {
    try {
        const fetchResponse = await fetch(request);
        if (fetchResponse && fetchResponse.status === 200 && fetchResponse.type === 'basic') {
            const cache = await caches.open(cacheName);
            cache.put(request, fetchResponse.clone());
        }
        return fetchResponse;
    } catch (error) {
        console.log('[SW] Network failed, trying cache for:', request.url);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

async function staleWhileRevalidate(request, cacheName) {
    const cachedResponse = await caches.match(request);

    const fetchPromise = fetch(request).then((fetchResponse) => {
        if (fetchResponse && fetchResponse.status === 200 && fetchResponse.type === 'basic') {
            const cache = caches.open(cacheName);
            cache.then(c => c.put(request, fetchResponse.clone()));
        }
        return fetchResponse;
    }).catch(() => {
        return cachedResponse;
    });

    return cachedResponse || fetchPromise;
}

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
