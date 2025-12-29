// Service Worker for Muhimbili Hospital Frontend
const STATIC_CACHE_NAME = 'muhimbili-static-v1'
const DYNAMIC_CACHE_NAME = 'muhimbili-dynamic-v1'

// Assets to cache immediately
const STATIC_ASSETS = [
  '/',
  '/assets/images/logo2.png',
  '/assets/images/ngao2.png',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
]

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...')
  event.waitUntil(
    caches
      .open(STATIC_CACHE_NAME)
      .then((cache) => {
        console.log('Caching static assets...')
        return cache.addAll(STATIC_ASSETS)
      })
      .catch((error) => {
        console.error('Failed to cache static assets:', error)
      })
  )
  self.skipWaiting()
})

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...')
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (
            cacheName !== STATIC_CACHE_NAME &&
            cacheName !== DYNAMIC_CACHE_NAME
          ) {
            console.log('Deleting old cache:', cacheName)
            return caches.delete(cacheName)
          }
        })
      )
    })
  )
  self.clients.claim()
})

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return
  }

  // Skip API requests (let them go to network)
  if (url.pathname.startsWith('/api/')) {
    return
  }

  // Handle different types of requests
  if (request.destination === 'document') {
    // HTML documents - network first, cache fallback
    event.respondWith(networkFirstStrategy(request))
  } else if (request.destination === 'image') {
    // Images - cache first, network fallback
    event.respondWith(cacheFirstStrategy(request))
  } else if (
    request.destination === 'script' ||
    request.destination === 'style'
  ) {
    // JS/CSS - stale while revalidate
    event.respondWith(staleWhileRevalidateStrategy(request))
  } else {
    // Other resources - cache first
    event.respondWith(cacheFirstStrategy(request))
  }
})

// Network first strategy (for HTML)
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request)
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE_NAME)
      cache.put(request, networkResponse.clone())
      return networkResponse
    }
  } catch (error) {
    console.log('Network failed, trying cache:', error)
  }

  const cachedResponse = await caches.match(request)
  return cachedResponse || new Response('Offline', { status: 503 })
}

// Cache first strategy (for images, fonts)
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request)
  if (cachedResponse) {
    return cachedResponse
  }

  try {
    const networkResponse = await fetch(request)
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE_NAME)
      cache.put(request, networkResponse.clone())
      return networkResponse
    }
  } catch (error) {
    console.log('Failed to fetch resource:', error)
  }

  return new Response('Resource not available', { status: 404 })
}

// Stale while revalidate strategy (for JS/CSS)
async function staleWhileRevalidateStrategy(request) {
  const cachedResponse = await caches.match(request)

  const fetchPromise = fetch(request)
    .then((networkResponse) => {
      if (networkResponse.ok) {
        const cache = caches.open(DYNAMIC_CACHE_NAME)
        cache.then((c) => c.put(request, networkResponse.clone()))
      }
      return networkResponse
    })
    .catch(() => cachedResponse)

  return cachedResponse || fetchPromise
}

// Background sync for failed API requests (if needed)
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    console.log('Background sync triggered')
    // Handle background sync logic here
  }
})

// Push notifications (if needed in future)
self.addEventListener('push', (event) => {
  if (event.data) {
    const data = event.data.json()
    const options = {
      body: data.body,
      icon: '/assets/images/logo2.png',
      badge: '/assets/images/ngao2.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey
      }
    }

    event.waitUntil(self.registration.showNotification(data.title, options))
  }
})
