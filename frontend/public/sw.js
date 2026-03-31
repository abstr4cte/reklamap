const CACHE_VERSION = 'v2'; // Zwiększ przy każdym deploy
const CACHE_NAME = `reklamap-${CACHE_VERSION}`;
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/manifest.webmanifest',
  '/pwa-icon-512.png'
];

// Install - cache podstawowe zasoby
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS_TO_CACHE))
      .then(() => self.skipWaiting()) // Aktywuj nowy SW natychmiast
  );
});

// Activate - usuń stare cache
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name.startsWith('reklamap-') && name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim()) // Przejmij kontrolę natychmiast
  );
});

// Fetch - strategia Network First dla JS/CSS, Cache First dla reszty
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Omijaj Service Worker dla zewnętrznych zasobów
  if (url.origin !== self.location.origin) {
    return;
  }

  // Omijaj Service Worker dla API
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/storage/')) {
    return;
  }

  // Network First dla JS/CSS (zawsze pobieraj najnowsze)
  if (url.pathname.match(/\.(js|css)$/)) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Cache nową wersję
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Fallback do cache tylko jeśli network fail
          return caches.match(event.request);
        })
    );
    return;
  }

  // Cache First dla pozostałych zasobów (obrazy, fonty, itp.)
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) {
          return response;
        }
        return fetch(event.request).then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        });
      })
  );
});
