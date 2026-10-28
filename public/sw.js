// Wasilah AI Omnichannel CRM - PWA Service Worker
const CACHE_NAME = 'wasilah-pwa-v1';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/favicon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-192-maskable.png',
    '/icons/icon-512-maskable.png',
    '/icons/apple-touch-icon.png',
    '/manifest.json'
];

// Install: Pre-cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA SW] Pre-caching non-fatal error:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate: Cleanup old caches & claim clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Network-first for pages, fallback to offline.html
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Don't intercept livewire polling or WebSocket/Composio endpoints
    if (url.pathname.includes('/livewire/') || url.pathname.includes('/api/')) {
        return;
    }

    // 1. Navigation requests (HTML pages) -> Network first, fallback to offline.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    // 2. Pre-cached static assets (icons, offline.html, manifest)
    if (url.origin === self.location.origin) {
        if (
            url.pathname.startsWith('/icons/') ||
            url.pathname === '/favicon.svg' ||
            url.pathname === '/manifest.json' ||
            url.pathname === OFFLINE_URL
        ) {
            event.respondWith(
                caches.match(request).then((cached) => {
                    return cached || fetch(request).then((response) => {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                        return response;
                    });
                })
            );
            return;
        }
    }

    // Default: Normal network request
});
