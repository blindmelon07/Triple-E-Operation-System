// Tri-E service worker.
//
// Pages and API calls always go to the network: they carry live sales, stock and
// customer data, and caching them could show stale numbers or another user's
// screen. Only static, versioned assets are cached, and an offline page is shown
// when a page can't load at all.

const VERSION = 'v1';
const STATIC_CACHE = `tri-e-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/images/logo.png',
    '/images/icon-192.png',
];

const STATIC_PATHS = ['/build/', '/css/', '/js/', '/fonts/', '/images/'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('tri-e-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (STATIC_PATHS.some((path) => url.pathname.startsWith(path))) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            }))
        );
    }
});
