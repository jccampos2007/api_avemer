const CACHE_NAME = 'avemer-pwa-v2';
const STATIC_URLS = [
  '/api/pwa/index.html',
  '/api/pwa/manifest.json',
  '/api/pwa/css/style.css',
  '/api/pwa/js/config.js',
  '/api/pwa/js/api.js',
  '/api/pwa/js/auth.js',
  '/api/pwa/js/router.js',
  '/api/pwa/js/app.js',
  '/api/pwa/js/pages/login.js',
  '/api/pwa/js/pages/dashboard.js',
  '/api/pwa/js/pages/profile.js',
  '/api/pwa/js/pages/enrollments.js',
  '/api/pwa/js/pages/payments.js',
  '/api/pwa/js/pages/offers.js',
  '/api/pwa/icons/icon.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_URLS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;

      return fetch(event.request)
        .then((response) => {
          if (response.ok && event.request.url.startsWith(self.location.origin)) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => caches.match('/api/pwa/index.html'));
    })
  );
});
