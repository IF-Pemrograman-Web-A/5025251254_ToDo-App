// sw.js — Service Worker (E05: PWA)
const VERSION  = 'v3';
const PRECACHE = 'do-it-precache-' + VERSION;  // app shell
const RUNTIME  = 'do-it-runtime-' + VERSION;   // halaman PHP, foto, font

// Wajib: kalau salah satu gagal diunduh, instalasi dibatalkan
const SHELL = ['offline.html', 'style.css', 'script.js', 'manifest.webmanifest'];
// Dicache sebisanya: tidak menggagalkan instalasi jika belum ada / server error
const OPTIONAL = ['index.php', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png'];

// ---------- Install: precache app shell + offline page ----------
self.addEventListener('install', (e) => {
    e.waitUntil((async () => {
        const cache = await caches.open(PRECACHE);
        await cache.addAll(SHELL.map(u => new Request(u, { cache: 'reload' })));
        await Promise.allSettled(OPTIONAL.map(u => cache.add(new Request(u, { cache: 'reload' }))));
        await self.skipWaiting();
    })());
});

// ---------- Activate: hapus cache versi lama ----------
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== PRECACHE && k !== RUNTIME).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// ---------- Strategi caching ----------

// Cache-first: untuk file yang tidak berubah (icon, foto upload, file font)
async function cacheFirst(req, name) {
    const cache = await caches.open(name);
    const hit = await cache.match(req);
    if (hit) return hit;
    try {
        const res = await fetch(req);
        if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
        return res;
    } catch (err) {
        return Response.error();
    }
}

// Stale-while-revalidate: tampil cepat dari cache, diperbarui di belakang layar
async function staleWhileRevalidate(event, name) {
    const req = event.request;
    const cache = await caches.open(name);
    const hit = await cache.match(req);
    const update = fetch(req)
        .then(res => {
            if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
            return res;
        })
        .catch(() => null);
    event.waitUntil(update);
    return hit || (await update) || Response.error();
}

// Network-first: halaman PHP selalu coba server dulu (data terbaru),
// kalau offline pakai salinan terakhir, kalau tidak ada tampilkan offline.html
async function handleNavigate(req) {
    try {
        const res = await fetch(req);
        if (req.method === 'GET' && res.ok && res.type === 'basic' && !res.redirected) {
            const cache = await caches.open(RUNTIME);
            cache.put(req, res.clone());
        }
        return res;
    } catch (err) {
        if (req.method === 'GET') {
            const runtime = await caches.open(RUNTIME);
            const shell = await caches.open(PRECACHE);
            const hit = (await runtime.match(req))
                || (await shell.match(req))
                || (new URL(req.url).pathname.endsWith('/') ? await shell.match('index.php') : null);
            if (hit) return hit;
        }
        return (await caches.open(PRECACHE)).match('offline.html');
    }
}

self.addEventListener('fetch', (e) => {
    const req = e.request;
    const url = new URL(req.url);

    // Halaman PHP + submit form (POST saat offline -> offline.html)
    if (req.mode === 'navigate') {
        e.respondWith(handleNavigate(req));
        return;
    }

    if (req.method !== 'GET') return;

    // Google Fonts: CSS = stale-while-revalidate, file font = cache-first
    if (url.origin === 'https://fonts.googleapis.com') {
        e.respondWith(staleWhileRevalidate(e, RUNTIME));
        return;
    }
    if (url.origin === 'https://fonts.gstatic.com') {
        e.respondWith(cacheFirst(req, RUNTIME));
        return;
    }

    if (url.origin !== self.location.origin) return;

    // Icon & foto upload: cache-first
    if (url.pathname.includes('/icons/') || url.pathname.includes('/uploads/')) {
        e.respondWith(cacheFirst(req, url.pathname.includes('/icons/') ? PRECACHE : RUNTIME));
        return;
    }

    // CSS / JS / manifest: stale-while-revalidate (di cache yang sama dengan precache)
    if (/\.(css|js|webmanifest)$/.test(url.pathname)) {
        e.respondWith(staleWhileRevalidate(e, PRECACHE));
    }
});

// ---------- Klik notifikasi ----------
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    e.waitUntil(
        self.clients.matchAll({ type: 'window' }).then(list =>
            list.length ? list[0].focus() : self.clients.openWindow('./index.php')
        )
    );
});
