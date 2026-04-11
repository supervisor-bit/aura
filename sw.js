// AURA PWA Service Worker
const CACHE_NAME = 'aura-v2';

// Assets to pre-cache (app shell) — NOT / because it's dynamic (login/setup/app)
const SHELL_ASSETS = [
    '/public/css/style.css',
    '/public/js/app.js',
    '/public/icons/icon-192.png',
    '/public/icons/icon-512.png',
];

// Install — cache app shell
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch — network-first for API, cache-first for assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // API calls — network only (always fresh data)
    if (url.pathname.startsWith('/clients/') ||
        url.pathname.startsWith('/visits/') ||
        url.pathname.startsWith('/notes/') ||
        url.pathname.startsWith('/sales/') ||
        url.pathname.startsWith('/products/') ||
        url.pathname.startsWith('/tags/') ||
        url.pathname.startsWith('/codelists/') ||
        url.pathname.startsWith('/dashboard/') ||
        url.pathname.startsWith('/accounting/') ||
        url.pathname.startsWith('/settings/') ||
        url.pathname.startsWith('/auth/')) {
        return;
    }

    // Root page — network-first (dynamic: login/setup/app)
    if (url.pathname === '/' || url.pathname === '') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request))
        );
        return;
    }

    // Static assets — cache-first, fallback to network
    event.respondWith(
        caches.match(event.request).then(cached => {
            const fetchPromise = fetch(event.request).then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                }
                return response;
            }).catch(() => cached);
            return cached || fetchPromise;
        })
    );
});
