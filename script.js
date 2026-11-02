// script.js — tema, toast, preview gambar, pengingat (CRUD sekarang di PHP)
const THEME_KEY = 'do-it-theme';
const $ = (id) => document.getElementById(id);
let reminders = window.REMINDERS || [];

function initTheme() {
    const sw = $('dark-mode-switch');
    if (!sw) return;
    sw.checked = document.documentElement.getAttribute('data-theme') === 'dark';
    sw.addEventListener('change', () => {
        const theme = sw.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
    });
}

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

function initImagePreview() {
    const input = $('image-file'), img = $('image-preview');
    if (!input) return;
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        img.src = URL.createObjectURL(file);
        img.alt = 'Preview of the selected image';
        img.hidden = false;
    });
}

async function showReminder(t) {
    const title = 'Reminder: ' + t.title;
    const body = t.description || 'Task kamu menunggu.';
    showToast(title, body, t.href);

    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            const reg = await navigator.serviceWorker.ready;
            await reg.showNotification(title, { body, tag: 'task-' + t.id });
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

document.addEventListener('click', (e) => {
    const close = e.target.closest('.toast-close');
    if (close) close.closest('.toast').remove();
});

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initImagePreview();

    const notify = $('notify-at');
    if (notify && 'Notification' in window) {
        notify.addEventListener('change', () => {
            if (notify.value && Notification.permission === 'default') Notification.requestPermission();
        });
    }

    document.querySelectorAll('.toast[data-auto]').forEach(t => setTimeout(() => t.remove(), 4000));

    if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(console.error);
    checkReminders();
    setInterval(checkReminders, 15000);
});
