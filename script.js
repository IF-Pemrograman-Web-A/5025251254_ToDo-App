// script.js — tema, toast, kamera/gambar, pengingat, aksesibilitas (CRUD sekarang di PHP)
const THEME_KEY = 'do-it-theme';
const $ = (id) => document.getElementById(id);

let reminders = window.REMINDERS || [];
let cameraStream = null;

function announce(message) {
    const el = $('announcer');
    if (!el) return;
    el.textContent = '';
    setTimeout(() => { el.textContent = message; }, 50);
}

// Tema

function initTheme() {
    const sw = $('dark-mode-switch');
    if (!sw) return;
    sw.checked = document.documentElement.getAttribute('data-theme') === 'dark';
    sw.addEventListener('change', () => {
        const theme = sw.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
        announce(theme === 'dark' ? 'Dark mode on' : 'Dark mode off');
    });
}

// Toast

function showToast(title, body, href) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = '<div class="toast-text"><strong></strong><span></span></div><div class="toast-actions"></div>';
    toast.querySelector('strong').textContent = title;
    toast.querySelector('span').textContent = body || '';
    const actions = toast.querySelector('.toast-actions');
    if (href) {
        const a = document.createElement('a');
        a.className = 'toast-btn';
        a.href = href;
        a.textContent = 'Open task';
        actions.appendChild(a);
    }
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-btn toast-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.textContent = '\u00d7';
    actions.appendChild(close);
    $('toast-region').appendChild(toast);
}

// Gambar (Media Capture)

function setImageStatus(msg) {
    const el = $('image-status');
    if (el) el.textContent = msg || '';
}

function setPreview(src) {
    const img = $('image-preview');
    if (src) {
        img.src = src;
        img.alt = 'Photo attached to this task';
    } else {
        img.removeAttribute('src');
        img.alt = '';
    }
    img.hidden = !src;
    $('btn-remove-image').hidden = !src;
}

// gambar baru dikirim ke PHP lewat field tersembunyi
function setNewImage(dataUrl) {
    $('image-data').value = dataUrl;
    $('remove-image').value = '';
    setPreview(dataUrl);
}

function toDataURL(source, w, h) {
    const scale = Math.min(1, 800 / Math.max(w, h));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(w * scale);
    canvas.height = Math.round(h * scale);
    canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/jpeg', 0.8);
}

async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setImageStatus('Camera not supported. Use "Add file" instead.');
        return;
    }
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        $('camera-video').srcObject = cameraStream;
        $('camera-video').hidden = false;
        $('btn-capture').hidden = false;
        setImageStatus('Camera is on. Press Capture.');
        $('btn-capture').focus();
    } catch (e) {
        console.error(e);
        setImageStatus('Could not open the camera. Use "Add file" instead.');
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
    }
    const video = $('camera-video');
    if (!video) return;
    video.srcObject = null;
    video.hidden = true;
    $('btn-capture').hidden = true;
}

function capturePhoto() {
    const video = $('camera-video');
    if (!video.videoWidth) {
        setImageStatus('Camera is not ready yet. Try again.');
        return;
    }
    setNewImage(toDataURL(video, video.videoWidth, video.videoHeight));
    stopCamera();
    setImageStatus('Photo captured. Save the task to keep it.');
    $('btn-camera').focus();
}

function handleFile(e) {
    const file = e.target.files[0];
    e.target.value = '';
    if (!file) return;
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
        setNewImage(toDataURL(img, img.naturalWidth, img.naturalHeight));
        setImageStatus('Image added. Save the task to keep it.');
        URL.revokeObjectURL(url);
    };
    img.onerror = () => {
        setImageStatus('That file is not a valid image.');
        URL.revokeObjectURL(url);
    };
    img.src = url;
}

function removeImage() {
    $('image-data').value = '';
    $('remove-image').value = '1';
    setPreview(null);
    setImageStatus('Image removed. Save the task to apply this.');
    $('btn-camera').focus();
}

function initImage() {
    const form = $('editor-form');
    if (!form) return;

    $('btn-camera').addEventListener('click', startCamera);
    $('btn-capture').addEventListener('click', capturePhoto);
    $('btn-upload').addEventListener('click', () => $('image-file').click());
    $('image-file').addEventListener('change', handleFile);
    $('btn-remove-image').addEventListener('click', removeImage);

    // matikan kamera saat form dikirim
    form.addEventListener('submit', stopCamera);

    // Escape menutup kamera
    form.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cameraStream) {
            stopCamera();
            setImageStatus('Camera closed.');
            $('btn-camera').focus();
        }
    });
}

// Pengingat / notifikasi

async function showReminder(t) {
    const title = 'Reminder: ' + t.title;
    const body = t.description || 'Task kamu menunggu.';

    // notifikasi UI (toast), tanpa permission
    showToast(title, body, t.href);

    // notifikasi sistem (jika diizinkan)
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            const reg = await navigator.serviceWorker.ready;
            await reg.showNotification(title, { body, tag: 'task-' + t.id, icon: 'icons/icon-192.png', badge: 'icons/icon-192.png' });
        } catch (e) {
            try { new Notification(title, { body }); } catch (_) {}
        }
    }
}

function checkReminders() {
    const now = Date.now();
    const due = reminders.filter(t => new Date(t.notify_at).getTime() <= now);
    if (!due.length) return;
    reminders = reminders.filter(t => !due.includes(t));
    due.forEach(t => {
        fetch('actions.php', { method: 'POST', body: new URLSearchParams({ action: 'notified', id: t.id }) }).catch(() => {});
        showReminder(t);
    });
}

// PWA: tombol install & status koneksi

let deferredPrompt = null;
const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const btn = $('btn-install');
    if (btn && !isStandalone()) btn.hidden = false;
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    const btn = $('btn-install');
    if (btn) btn.hidden = true;
    showToast('App installed', 'DO . IT! sekarang ada di perangkatmu.');
    announce('App installed');
});

function initInstall() {
    const btn = $('btn-install');
    if (!btn || isStandalone()) return;

    btn.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            announce(outcome === 'accepted' ? 'Installing app' : 'Install dismissed');
            deferredPrompt = null;
            btn.hidden = true;
        } else {
            // iOS Safari tidak punya prompt otomatis
            showToast('Install DO . IT!', 'Di Safari: tekan Share, lalu "Add to Home Screen".');
        }
    });

    if (/iphone|ipad|ipod/i.test(navigator.userAgent)) btn.hidden = false;
}

function initConnectionStatus() {
    const offline = () => {
        showToast('You are offline', 'Menampilkan halaman tersimpan. Menambah/mengubah task butuh koneksi.');
        announce('You are offline');
    };
    window.addEventListener('offline', offline);
    window.addEventListener('online', () => {
        showToast('Back online', 'Koneksi kembali normal.');
        announce('Back online');
    });
    if (!navigator.onLine) offline();
}

// Event global

document.addEventListener('click', (e) => {
    const close = e.target.closest('.toast-close');
    if (close) { close.closest('.toast').remove(); return; }

    // klik di mana saja pada kartu task membuka detailnya
    const row = e.target.closest('.todo-list[data-href]');
    if (row && !e.target.closest('a, form, input, button')) {
        location.href = row.dataset.href;
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initImage();
    initInstall();
    initConnectionStatus();

    // minta izin notifikasi saat jam pengingat diisi
    const notify = $('notify-at');
    if (notify && 'Notification' in window) {
        notify.addEventListener('change', () => {
            if (notify.value && Notification.permission === 'default') Notification.requestPermission();
        });
    }

    // pesan hasil aksi (flash) hilang sendiri dan diumumkan ke screen reader
    document.querySelectorAll('.toast[data-auto]').forEach(t => {
        announce(t.querySelector('strong').textContent);
        setTimeout(() => t.remove(), 4000);
    });

    if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(console.error);
    checkReminders();
    setInterval(checkReminders, 15000);
});
