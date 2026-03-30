// Service Worker — Menú QR 3D
// Manejado por vite-plugin-pwa (injectManifest strategy)
// Requerido por vite-plugin-pwa (injectManifest): workbox reemplaza self.__WB_MANIFEST
// con la lista de assets en build. Asignamos a self para que Vite no lo elimine.
self.__precache = self.__WB_MANIFEST

// ── Push notifications ────────────────────────────────────────────────────────

self.addEventListener('push', event => {
  let data = {
    title: '🛎️ Nuevo pedido',
    body: 'Tienes un pedido nuevo en el panel.',
    url: '/menu/admin/dashboard',
  }

  if (event.data) {
    try {
      Object.assign(data, event.data.json())
    } catch {
      data.body = event.data.text()
    }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon || '/menu/pwa-icon.svg',
      badge: data.badge || data.icon || '/menu/pwa-icon.svg',
      tag: 'nuevo-pedido',   // reemplaza notif anterior del mismo tipo
      renotify: true,         // vibra/suena aunque el tag ya exista
      data: { url: data.url },
    })
  )
})

// ── Clic en la notificación ───────────────────────────────────────────────────

self.addEventListener('notificationclick', event => {
  event.notification.close()
  const url = event.notification.data?.url || '/menu/admin/dashboard'

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      // Foco en ventana del admin si ya está abierta
      for (const client of clientList) {
        if (client.url.includes('/menu/admin') && 'focus' in client) {
          return client.focus()
        }
      }
      // Abrir nueva ventana
      if (clients.openWindow) return clients.openWindow(url)
    })
  )
})

// ── Activación inmediata (sin esperar reload) ─────────────────────────────────

self.addEventListener('install', event => {
  self.skipWaiting()
})

self.addEventListener('activate', event => {
  event.waitUntil(clients.claim())
})
