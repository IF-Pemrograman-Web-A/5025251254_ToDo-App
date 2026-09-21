
const STORAGE_KEY = 'do-it-tasks';

let tasks = [];
let selectedTaskId = null; 
let isCreating = false; 


function loadTasks() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
        try {
            tasks = JSON.parse(raw);
            return;
        } catch (e) {
            console.error('Failed to parse stored tasks, resetting.', e);
        }
    }
    tasks = [];
    saveTasks();
}

function saveTasks() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(tasks));
}

function nextId() {
    return tasks.reduce((max, t) => Math.max(max, t.id), 0) + 1;
}

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

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// task list

function renderTaskList() {
    const container = document.getElementById('todo-container');
    const countBadge = document.getElementById('task-count');

    countBadge.textContent = tasks.length;

    if (tasks.length === 0) {
        container.innerHTML = '<p class="empty-list">No tasks yet. Add one below!</p>';
        return;
    }

    container.innerHTML = tasks.map(task => `
        <div class="todo-list ${task.done ? 'done' : ''} ${task.id === selectedTaskId ? 'active' : ''}" data-id="${task.id}">
            <div class="list-task">
                <input type="checkbox" data-id="${task.id}" class="task-checkbox" ${task.done ? 'checked' : ''}>
                <span class="task-title">${escapeHtml(task.title)}</span>
                <span class="arrow">&rsaquo;</span>
            </div>
            <div class="todo-meta">
                <span class="meta-due">📅 ${formatDate(task.due)}</span>
                <span class="meta-urgency ${task.urgency}">⚡ ${urgencyLabel[task.urgency] || task.urgency}</span>
            </div>
        </div>
    `).join('');

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
}


function fillEditorFields(task) {
    document.getElementById('editor-name').value = task ? task.title : '';
    document.getElementById('editor-description').value = task ? task.description : '';
    document.getElementById('due-date').value = task ? task.due : '';
    document.getElementById('urgency').value = task ? task.urgency : 'normal';
}

function showEditorPanel() {
    document.getElementById('empty-state').style.display = 'none';
    document.getElementById('editor-content').style.display = 'block';
}


function openDetail(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;

    selectedTaskId = id;
    isCreating = false;

    document.getElementById('editor-header-title').textContent = 'Task Details';
    document.getElementById('btn-delete').style.display = 'inline-block';
    fillEditorFields(task);
    showEditorPanel();
    renderTaskList(); 
}

function openNewTaskForm() {
    selectedTaskId = null;
    isCreating = true;

    document.getElementById('editor-header-title').textContent = 'Add New Task';
    document.getElementById('btn-delete').style.display = 'none';
    fillEditorFields(null);
    showEditorPanel();
    renderTaskList();
}

function closeDetail() {
    selectedTaskId = null;
    isCreating = false;
    document.getElementById('editor-content').style.display = 'none';
    document.getElementById('empty-state').style.display = 'block';
    renderTaskList();
}


function toggleDone(id) {
    const task = tasks.find(t => t.id === id);
    if (!task) return;
    task.done = !task.done;
    saveTasks();
    renderTaskList();
}

function deleteSelectedTask() {
    tasks = tasks.filter(t => t.id !== selectedTaskId);
    saveTasks();
    closeDetail();
}

function handleEditorSubmit() {
    const title = document.getElementById('editor-name').value.trim();
    const description = document.getElementById('editor-description').value.trim();
    const due = document.getElementById('due-date').value;
    const urgency = document.getElementById('urgency').value;

    if (!title || !due) return;

    if (isCreating) {
        const newTask = {
            id: nextId(),
            title,
            description,
            due,
            urgency,
            done: false
        };
        tasks.push(newTask);
        saveTasks();
        openDetail(newTask.id);
    } else if (selectedTaskId !== null) {
        const task = tasks.find(t => t.id === selectedTaskId);
        if (!task) return;
        task.title = title;
        task.description = description;
        task.due = due;
        task.urgency = urgency;
        saveTasks();
        renderTaskList();
    }
}

function initEventListeners() {
    document.getElementById('btn-add-task').addEventListener('click', () => {
        openNewTaskForm();
    });

    document.getElementById('editor-form').addEventListener('submit', (e) => {
        e.preventDefault();
        handleEditorSubmit();
    });

    document.getElementById('btn-delete').addEventListener('click', () => {
        if (selectedTaskId !== null) deleteSelectedTask();
    });

    document.getElementById('btn-close').addEventListener('click', (e) => {
        e.preventDefault();
        closeDetail();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
    renderTaskList();
    initEventListeners();
});