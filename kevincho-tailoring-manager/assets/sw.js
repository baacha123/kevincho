/**
 * Kevin Cho Tailoring — Service Worker
 * Cache-first for static assets, network-first for pages/API.
 */

const CACHE_NAME = 'kctm-cache-v1';

const PRE_CACHE_URLS = [
    '/',
    '/shop/',
];

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Offline — Kevin Cho Tailoring</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#402417;color:#fef9e7;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:2rem}
.container{max-width:420px}
h1{font-size:1.8rem;margin-bottom:.75rem;color:#c9a96e}
p{font-size:1.05rem;line-height:1.6;margin-bottom:1.5rem;opacity:.9}
button{background:#c9a96e;color:#402417;border:none;padding:.75rem 2rem;font-size:1rem;font-weight:700;border-radius:6px;cursor:pointer}
button:hover{background:#b8944f}
</style>
</head>
<body>
<div class="container">
<h1>You're Offline</h1>
<p>It looks like you've lost your internet connection. Please check your connection and try again.</p>
<button onclick="location.reload()">Try Again</button>
</div>
</body>
</html>`;

/* ── Install: pre-cache key pages ─────────────────────────── */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRE_CACHE_URLS).catch(() => {
                // Pre-caching is best-effort; don't block install.
            });
        })
    );
    self.skipWaiting();
});

/* ── Activate: clean old caches ───────────────────────────── */
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter((key) => key.startsWith('kctm-cache-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

/* ── Fetch: strategy depends on request type ──────────────── */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin GET requests.
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Skip wp-admin, AJAX, and WooCommerce dynamic endpoints.
    if (
        url.pathname.startsWith('/wp-admin') ||
        url.pathname.startsWith('/wp-login') ||
        url.pathname.includes('admin-ajax.php') ||
        url.pathname.includes('wc-ajax') ||
        url.search.includes('wc-ajax') ||
        url.pathname.startsWith('/wp-json')
    ) {
        return;
    }

    const isStaticAsset = /\.(css|js|png|jpg|jpeg|gif|svg|webp|woff2?|ttf|eot|ico)(\?.*)?$/i.test(url.pathname);

    if (isStaticAsset) {
        // Cache-first for static assets.
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }
                return fetch(request).then((response) => {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                }).catch(() => {
                    return new Response('', { status: 408, statusText: 'Offline' });
                });
            })
        );
    } else {
        // Network-first for HTML pages.
        event.respondWith(
            fetch(request).then((response) => {
                if (response && response.status === 200 && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, clone);
                    });
                }
                return response;
            }).catch(() => {
                return caches.match(request).then((cached) => {
                    if (cached) {
                        return cached;
                    }
                    // Return offline fallback for navigation requests.
                    if (request.mode === 'navigate') {
                        return new Response(OFFLINE_HTML, {
                            status: 503,
                            headers: { 'Content-Type': 'text/html; charset=utf-8' },
                        });
                    }
                    return new Response('', { status: 408, statusText: 'Offline' });
                });
            })
        );
    }
});
