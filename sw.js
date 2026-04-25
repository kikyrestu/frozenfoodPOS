// Fun Frozen Food POS - Service Worker
const CACHE_NAME = 'fff-pos-v13';
const ASSETS = [
  '/',
  '/pos.php',
  '/login.php',
  '/assets/js/pos.js',
  '/assets/images/logo.png',
  '/manifest.json',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return Promise.allSettled(ASSETS.map(url => cache.add(url).catch(() => {})));
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  // Don't cache API calls
  if (url.pathname.startsWith('/api/')) return;

  e.respondWith(
    (() => {
      const url = new URL(e.request.url);
      // CSS & JS: network-first so updates show immediately
      if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js')) {
        return fetch(e.request).then(response => {
          if (response.ok && response.type === 'basic') {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
          }
          return response;
        }).catch(() => caches.match(e.request));
      }
      // Everything else: stale-while-revalidate
      return caches.match(e.request).then(cached => {
        const fetchPromise = fetch(e.request).then(response => {
          if (response.ok && response.type === 'basic') {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
          }
          return response;
        });
        return cached || fetchPromise;
      }).catch(() => {
        if (e.request.destination === 'document') {
          return caches.match('/pos.php');
        }
      });
    })()
  );
});
