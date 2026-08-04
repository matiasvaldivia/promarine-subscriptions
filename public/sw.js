const CACHE_NAME = 'promarine-app-v1';
const APP_SHELL = [
    '/offline.html',
    '/site.webmanifest',
    '/assets/promarine/promarine-app-icon.svg',
    '/assets/promarine/optimized/promarine-logo-300.webp',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || !['style', 'script', 'image', 'font'].includes(request.destination)) return;

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response.ok) caches.open(CACHE_NAME).then((cache) => cache.put(request, response.clone()));
            return response;
        }))
    );
});
