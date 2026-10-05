const DB_NAME = 'do-it-db';
const DB_VERSION = 1;
const STORE = 'tasks';
const OLD_STORAGE_KEY = 'do-it-tasks'; // localStorage lama (E02)
const THEME_KEY = 'do-it-theme';

const $ = (id) => document.getElementById(id);

// IndexedDB

function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
            }
        };

        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

function run(mode, fn) {
    return openDB().then(db => new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const request = fn(tx.objectStore(STORE));
        tx.oncomplete = () => { db.close(); resolve(request.result); };
        tx.onerror = tx.onabort = () => { db.close(); reject(tx.error); };
    }));
}

const TaskDB = {
    getAll: ()     => run('readonly',  store => store.getAll()),
    put:    (task) => run('readwrite', store => store.put(task)),
    remove: (id)   => run('readwrite', store => store.delete(id))
};


// State

let tasks = [];
let selectedTaskId = null;
let isCreating = false;
let draftImage = null;
let cameraStream = null;

async function loadTasks() {
    try {
        const old = localStorage.getItem(OLD_STORAGE_KEY);
        if (old) {
            const oldTasks = JSON.parse(old);
            for (const t of oldTasks) {
                await TaskDB.put(t); // id lama
            }
            localStorage.removeItem(OLD_STORAGE_KEY);
        }
    } catch (e) {
        console.error('Gagal memindahkan data lama.', e);
    }

    try {
        tasks = await TaskDB.getAll();
        tasks.sort((a, b) => a.id - b.id);
    } catch (e) {
        console.error('Gagal membaca IndexedDB.', e);
        tasks = [];
    }
}

// Helper

const urgencyLabel = {
    urgent: 'Urgent',
    important: 'Important',
    normal: 'Normal'
};

const MONTHS_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function formatDate(isoDate) {
    if (!isoDate) return '-';
    const [y, m, d] = isoDate.split('-').map(Number);
    if (!y || !m || !d) return '-';
    return `${d} ${MONTHS_ID[m - 1]} ${y}`;
}

function formatDateTime(local) {
    if (!local) return '';
    const [date, time] = local.split('T');
    return `${formatDate(date)}, ${time || ''}`;
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function announce(message) {
    const el = $('announcer');
    if (!el) return;
    el.textContent = '';
    setTimeout(() => { el.textContent = message; }, 50);
}

// Task list & editor

let currentFilter = 'all';

function visibleTasks() {
    if (currentFilter === 'ongoing') return tasks.filter(t => !t.done);
    if (currentFilter === 'done') return tasks.filter(t => t.done);
    return tasks;
}

function emptyMessage() {
    if (tasks.length === 0) return 'No tasks yet. Add one below!';
    if (currentFilter === 'ongoing') return 'No ongoing tasks.';
    if (currentFilter === 'done') return 'No completed tasks yet.';
    return 'No tasks yet. Add one below!';
}

function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('#task-filter .filter-btn').forEach(btn => {
        btn.setAttribute('aria-pressed', String(btn.dataset.filter === filter));
    });
    renderTaskList();
    const label = { all: 'all tasks', ongoing: 'ongoing tasks', done: 'done tasks' }[filter];
    announce(`Showing ${label}: ${visibleTasks().length}`);
}

function renderTaskList(focusSelector) {
    const container = $('todo-container');
    const countBadge = $('task-count');
    const visible = visibleTasks();

    countBadge.textContent = tasks.length;

    if (visible.length === 0) {
        container.innerHTML = `<p class="empty-list">${emptyMessage()}</p>`;
        return;
    }

    container.innerHTML = visible.map(task => {
        const title = escapeHtml(task.title);
        const selected = task.id === selectedTaskId;
        const extras = [
            task.notifyAt ? `<span class="meta-due"><span aria-hidden="true">⏰</span>${escapeHtml(formatDateTime(task.notifyAt))}</span>` : '',
            task.image ? '<span class="meta-due"><span aria-hidden="true">🖼</span> Has photo</span>' : ''
        ].join('');

        return `
        <div class="todo-list ${task.done ? 'done' : ''} ${selected ? 'active' : ''}" data-id="${task.id}">
            <div class="list-task">
                <input type="checkbox" data-id="${task.id}" id="task-cb-${task.id}" class="task-checkbox"
                       aria-label="Mark ${title} as done" ${task.done ? 'checked' : ''}>
                <button type="button" class="task-open" data-id="${task.id}"${selected ? 'aria-current="true"' : ''}>
                    <span class="task-title">${title}</span>
                    <span class="arrow" aria-hidden="true">&rsaquo;</span>
                </button>
            </div>
            <div class="todo-meta">
                <span class="meta-due"><span aria-hidden="true">📅</span> ${escapeHtml(formatDate(task.due))}</span>
                <span class="meta-urgency ${task.urgency}"><span aria-hidden="true">⚡</span> ${escapeHtml(urgencyLabel[task.urgency] || task.urgency)}</span>
                ${extras}
            </div>
        </div>`;
    }).join('');

    container.querySelectorAll('.todo-list').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.classList.contains('task-checkbox')) return;
            const id = Number(row.dataset.id);
            openDetail(id);
        });
    });

    container.querySelectorAll('.task-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const id = Number(e.target.dataset.id);
            toggleDone(id);
        });
    });

    if (focusSelector) {
        const el = document.querySelector(focusSelector);
        if (el) el.focus();
    }
}

// Editor

function setImageStatus(msg) {
    const el = $('image-status');
    if (el) el.textContent = msg || '';
}

function setDraftImage(dataUrl) {
    draftImage = dataUrl || null;
    const img = $('image-preview');
    if (draftImage) {
        img.src = draftImage;
        img.alt = 'Photo attached to this task';
    } else {
        img.removeAttribute('src');
        img.alt = '';
    }
    img.hidden = !draftImage;
    $('btn-remove-image').hidden = !draftImage;

    const placeholder = $('image-placeholder');
    if (placeholder) placeholder.hidden = !!draftImage || !!cameraStream;
}

function fillEditorFields(task) {
    $('editor-name').value = task ? task.title : '';
    $('editor-description').value = task ? task.description : '';
    $('due-date').value = task ? task.due : '';
    $('urgency').value = task ? task.urgency : 'normal';
    $('notify-at').value = task && task.notifyAt ? task.notifyAt : '';
    setDraftImage(task ? task.image : null);
    setImageStatus('');
}

function showEditorPanel() {
    $('empty-state').style.display = 'none';
    $('editor-content').style.display = 'block';
}

function openDetail(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;

    stopCamera();
    selectedTaskId = id;
    isCreating = false;

    $('editor-header-title').textContent = 'Task Details';
    $('btn-delete').style.display = 'inline-block';
    fillEditorFields(task);
    showEditorPanel();
    renderTaskList();
    $('editor-name').focus();
}

function openNewTaskForm() {
    stopCamera();
    selectedTaskId = null;
    isCreating = true;

    $('editor-header-title').textContent = 'Add New Task';
    $('btn-delete').style.display = 'none';
    fillEditorFields(null);
    showEditorPanel();
    renderTaskList();
    $('editor-name').focus();
}

function closeDetail() {
    stopCamera();
    const previousId = selectedTaskId;
    selectedTaskId = null;
    isCreating = false;
    $('editor-content').style.display = 'none';
    $('empty-state').style.display = 'block';
    renderTaskList(previousId !== null ? `.task-open[data-id="${previousId}"]` : '#btn-add-task');
}

async function toggleDone(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;
    task.done = !task.done;
    try {
        await TaskDB.put(task);
        announce(`${task.title} marked as ${task.done ? 'done' : 'not done'}`);
    } catch (e) {
        console.error('Gagal menyimpan task.', e);
        task.done = !task.done;
    }
    renderTaskList(`#task-cb-${id}`);
}

async function deleteSelectedTask() {
    const task = tasks.find(t => t.id === selectedTaskId);
    try {
        await TaskDB.remove(selectedTaskId);
        tasks = tasks.filter(t => t.id !== selectedTaskId);
        if (task) announce(`Deleted ${task.title}`);
    } catch (e) {
        console.error('Gagal menghapus task.', e);
    }
    closeDetail();
    $('btn-add-task').focus();
}

async function handleEditorSubmit() {
    const title = $('editor-name').value.trim();
    const description = $('editor-description').value.trim();
    const due = $('due-date').value;
    const urgency = $('urgency').value;
    const notifyAt = $('notify-at').value;

    if (!title || !due) return;

    stopCamera();

    // persetujuan notifikasi (jika belum diizinkan)
    if (notifyAt && 'Notification' in window && Notification.permission === 'default') {
        try { await Notification.requestPermission(); } catch (e) { console.error(e); }
    }

    try {
        if (isCreating) {
            const newTask = {
                title, description, due, urgency,
                done: false,
                image: draftImage,
                notifyAt,
                notified: false
            };
            newTask.id = await TaskDB.put(newTask);   // mengembalikan id baru
            tasks.push(newTask);
            announce('Task added');
            openDetail(newTask.id);
        } else if (selectedTaskId !== null) {
            const task = tasks.find(t => t.id === selectedTaskId);
            if (!task) return;
            if (task.notifyAt !== notifyAt) task.notified = false;
            task.title = title;
            task.description = description;
            task.due = due;
            task.urgency = urgency;
            task.notifyAt = notifyAt;
            task.image = draftImage;
            await TaskDB.put(task);
            renderTaskList();
            announce('Task saved');
        }
        checkReminders();
    } catch (e) {
        console.error('Gagal menyimpan task.', e);
        announce('Could not save the task');
    }
}

// API 

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
        const placeholder = $('image-placeholder');
        if (placeholder) placeholder.hidden = true;
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
    video.srcObject = null;
    video.hidden = true;
    $('btn-capture').hidden = true;
    const placeholder = $('image-placeholder');
    if (placeholder) placeholder.hidden = !!draftImage;
}

function capturePhoto() {
    const video = $('camera-video');
    if (!video.videoWidth) {
        setImageStatus('Camera is not ready yet. Try again.');
        return;
    }
    setDraftImage(toDataURL(video, video.videoWidth, video.videoHeight));
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
        setDraftImage(toDataURL(img, img.naturalWidth, img.naturalHeight));
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
    setDraftImage(null);
    setImageStatus('Image removed. Save the task to apply this.');
    $('btn-camera').focus();
}


//Service Worker + Notifikasi 

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    try {
        await navigator.serviceWorker.register('sw.js');
    } catch (e) {
        console.error('Service worker gagal didaftarkan.', e);
    }
}

// Notifikasi UI 

function showToast(title, { body = '', actionLabel = '', onAction = null, timeout = 0 } = {}) {
    const region = $('toast-region');
    if (!region) return;

    const toast = document.createElement('div');
    toast.className = 'toast';

    const text = document.createElement('div');
    text.className = 'toast-text';
    const strong = document.createElement('strong');
    strong.textContent = title;
    const span = document.createElement('span');
    span.textContent = body;
    text.append(strong, span);

    const actions = document.createElement('div');
    actions.className = 'toast-actions';

    if (actionLabel && onAction) {
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'toast-btn';
        action.textContent = actionLabel;
        action.addEventListener('click', () => {
            onAction();
            toast.remove();
        });
        actions.appendChild(action);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-btn toast-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.textContent = '\u00d7';
    close.addEventListener('click', () => toast.remove());
    actions.appendChild(close);

    toast.append(text, actions);
    region.appendChild(toast);

    if (timeout) setTimeout(() => toast.remove(), timeout);
}

async function showReminder(task) {
    const title = 'Reminder: ' + task.title;
    const body = task.description || 'Task kamu menunggu.';

    // notifikasi tanpa permission
    showToast(title, {
        body,
        actionLabel: 'Open task',
        onAction: () => openDetail(task.id)
    });

    // notifikasi sistem (jika diizinkan)
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            const options = { body, tag: 'task-' + task.id };
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                await reg.showNotification(title, options);
            } else {
                new Notification(title, options);
            }
        } catch (e) {
            console.error('Notifikasi sistem gagal.', e);
        }
    }
}

async function checkReminders() {
    const now = Date.now();
    let changed = false;

    for (const t of tasks) {
        if (!t.done && t.notifyAt && !t.notified && new Date(t.notifyAt).getTime() <= now) {
            t.notified = true;
            try {
                await TaskDB.put(t);
                await showReminder(t);
                changed = true;
            } catch (e) {
                console.error('Gagal menampilkan notifikasi.', e);
            }
        }
    }
    if (changed) renderTaskList();
}

// Event listeners

function initEventListeners() {
    $('task-filter').addEventListener('click', (e) => {
        const btn = e.target.closest('.filter-btn');
        if (btn) setFilter(btn.dataset.filter);
    });

    $('btn-add-task').addEventListener('click', () => {
        openNewTaskForm();
    });

    $('editor-form').addEventListener('submit', (e) => {
        e.preventDefault();
        handleEditorSubmit();
    });

    $('btn-delete').addEventListener('click', () => {
        if (selectedTaskId !== null) deleteSelectedTask();
    });

    $('btn-close').addEventListener('click', (e) => {
        e.preventDefault();
        closeDetail();
    });

    // Gambar

    $('btn-camera').addEventListener('click', startCamera);
    $('btn-capture').addEventListener('click', capturePhoto);
    $('btn-upload').addEventListener('click', () => $('image-file').click());
    $('image-file').addEventListener('change', handleFile);
    $('btn-remove-image').addEventListener('click', removeImage);

    // Escape menutup kamera

    $('editor-form').addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cameraStream) {
            stopCamera();
            setImageStatus('Camera closed.');
            $('btn-camera').focus();
        }
    });
}


// localstorage theme

function initTheme() {
    const darkSwitch = $('dark-mode-switch');

    const saved = localStorage.getItem(THEME_KEY) || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    darkSwitch.checked = (saved === 'dark');

    darkSwitch.addEventListener('change', () => {
        const theme = darkSwitch.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        announce(theme === 'dark' ? 'Dark mode on' : 'Dark mode off');
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    initTheme();
    await loadTasks();
    renderTaskList();
    initEventListeners();
    registerServiceWorker();
    checkReminders();
    setInterval(checkReminders, 15000);
});