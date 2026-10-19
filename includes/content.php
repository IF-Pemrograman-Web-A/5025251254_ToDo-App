<?php

$uid       = (int) $user['id'];
$isShared  = $view === 'shared';
$heading   = $isShared ? 'Shared Tasks' : 'My Tasks';
$showPanel = $isNew || $selected !== null;
$canEdit   = $isNew || ($selected !== null && (int) $selected['owner_id'] === $uid);
$selectedId = $selected['id'] ?? null;

$form = [
    'title'       => $old['title']       ?? ($selected['title'] ?? ''),
    'description' => $old['description'] ?? ($selected['description'] ?? ''),
    'due_date'    => $old['due_date']    ?? ($selected['due_date'] ?? ''),
    'urgency'     => $old['urgency']     ?? ($selected['urgency'] ?? 'normal'),
    'visibility'  => $old['visibility']  ?? ($selected['visibility'] ?? $view),
];
?>
        <main class="container" id="main" tabindex="-1">

            <?php if (!empty($flash)): ?>
                <div class="flash flash-<?= e($flash['type']) ?>" role="status">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <section class="left-panel" aria-labelledby="list-heading">
                <div class="panel-header">
                    <h2 id="list-heading"><?= e($heading) ?></h2>
                    <span class="badge-count" aria-hidden="true"><?= count($todos) ?></span>
                    <span class="sr-only"><?= count($todos) ?> tasks shown</span>

                    <nav class="task-filter" aria-label="Filter tasks">
                        <?php foreach (FILTERS as $key => $label): ?>
                            <a class="filter-btn"
                               href="<?= e(url(['view' => $view, 'filter' => $key === 'all' ? null : $key])) ?>"
                               <?= $filter === $key ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <?php if (!$todos): ?>
                    <p class="empty-list">
                        <?php if ($filter === 'ongoing'): ?>No ongoing tasks.
                        <?php elseif ($filter === 'done'): ?>No completed tasks yet.
                        <?php else: ?>No tasks yet. Add one below!
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <ul class="todo-ul">
                        <?php foreach ($todos as $todo): ?>
                            <?php
                            $isDone   = (bool) $todo['is_done'];
                            $isActive = $selectedId !== null && (int) $selectedId === (int) $todo['id'];
                            ?>
                            <li class="todo-list <?= $isDone ? 'done' : '' ?> <?= $isActive ? 'active' : '' ?>">
                                <div class="list-task">
                                    <!-- Centang selesai -->
                                    <form class="toggle-form" method="post" action="actions.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int) $todo['id'] ?>">
                                        <input type="hidden" name="view" value="<?= e($view) ?>">
                                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                        <input type="hidden" name="selected" value="<?= e((string) $selectedId) ?>">
                                        <input type="checkbox" class="task-checkbox"
                                               aria-label="Mark <?= e($todo['title']) ?> as done"
                                               <?= $isDone ? 'checked' : '' ?>>
                                        <noscript><button type="submit" class="btn-small">Toggle</button></noscript>
                                    </form>

                                    <!-- Buka detail -->
                                    <a class="task-open"
                                       href="<?= e(url(['view' => $view, 'filter' => $filter === 'all' ? null : $filter, 'id' => $todo['id']])) ?>"
                                       <?= $isActive ? 'aria-current="true"' : '' ?>>
                                        <span class="task-title"><?= e($todo['title']) ?></span>
                                        <span class="arrow" aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </div>

                                <div class="todo-meta">
                                    <span class="meta-due"><span aria-hidden="true">📅</span> Due <?= e(format_date_id($todo['due_date'])) ?></span>
                                    <?php if ($isShared): ?>
                                        <span class="meta-due"><span aria-hidden="true">👤</span> by <?= e($todo['owner_name']) ?></span>
                                    <?php endif; ?>
                                    <span class="meta-urgency <?= e($todo['urgency']) ?>">
                                        <span aria-hidden="true">⚡</span> <?= e(urgency_label($todo['urgency'])) ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <a class="add-task" href="<?= e(url(['view' => $view, 'filter' => $filter === 'all' ? null : $filter, 'new' => 1])) ?>">
                    <span class="plus" aria-hidden="true">+</span>
                    <span>Add New Task</span>
                </a>
            </section>

            <!-- ===== Panel kanan: detail / form ===== -->
            <aside class="right-panel" aria-label="Task details">
                <?php if (!$showPanel): ?>
                    <div class="empty-state">
                        <h2>Task Details</h2>
                        <p>Select a task, or choose "Add New Task", to see it here.</p>
                    </div>

                <?php else: ?>
                    <div class="editor-header">
                        <h2><?= $isNew ? 'Add New Task' : 'Task Details' ?></h2>
                        <a class="btn-close"
                           href="<?= e(url(['view' => $view, 'filter' => $filter === 'all' ? null : $filter])) ?>"
                           aria-label="Close task details"><span aria-hidden="true">&times;</span></a>
                    </div>

                    <?php if ($errors): ?>
                        <div class="form-errors" role="alert">
                            <p>Please fix the following:</p>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($canEdit): ?>
                        <!-- Form Create / Update -->
                        <form class="editor-body" method="post" action="actions.php" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= $isNew ? 'create' : 'update' ?>">
                            <input type="hidden" name="id" value="<?= (int) $selectedId ?>">
                            <input type="hidden" name="view" value="<?= e($view) ?>">
                            <input type="hidden" name="filter" value="<?= e($filter) ?>">

                            <div class="editor-field">
                                <label for="title">Task Name</label>
                                <input type="text" id="title" name="title" maxlength="150" required autofocus
                                       placeholder="e.g. Tugas Pemrograman Web"
                                       value="<?= e($form['title']) ?>">
                            </div>

                            <div class="editor-field">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="3"
                                          placeholder="Add a description..."><?= e($form['description']) ?></textarea>
                            </div>

                            <div class="editor-field">
                                <label for="due_date">Due Date</label>
                                <input type="date" id="due_date" name="due_date" required
                                       value="<?= e($form['due_date']) ?>">
                            </div>

                            <div class="editor-field">
                                <label for="urgency">Urgency</label>
                                <select id="urgency" name="urgency">
                                    <?php foreach (URGENCY_LABELS as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= $form['urgency'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="editor-field">
                                <label for="visibility">Visibility</label>
                                <select id="visibility" name="visibility" aria-describedby="visibility-hint">
                                    <option value="personal" <?= $form['visibility'] === 'personal' ? 'selected' : '' ?>>Personal (only me)</option>
                                    <option value="shared" <?= $form['visibility'] === 'shared' ? 'selected' : '' ?>>Shared (everyone)</option>
                                </select>
                                <p class="hint" id="visibility-hint">Shared tasks can be seen and checked off by every member.</p>
                            </div>

                            <div class="editor-actions">
                                <?php if (!$isNew): ?>
                                    <button type="submit" form="delete-form" class="btn-delete">Delete Task</button>
                                <?php endif; ?>
                                <button type="submit" class="btn-save"><?= $isNew ? 'Add Task' : 'Save Changes' ?></button>
                            </div>
                        </form>

                        <?php if (!$isNew): ?>
                            <!-- Form Delete (dipisah supaya tidak bersarang di form lain) -->
                            <form id="delete-form" method="post" action="actions.php"
                                  data-confirm="Delete this task? This cannot be undone.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $selectedId ?>">
                                <input type="hidden" name="view" value="<?= e($view) ?>">
                                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                            </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Task milik anggota lain: hanya boleh dilihat -->
                        <p class="hint readonly-note">
                            This task belongs to <strong><?= e($selected['owner_name']) ?></strong>.
                            Only the owner can edit or delete it, but you can check it off from the list.
                        </p>
                        <dl class="detail-list">
                            <dt>Task Name</dt>
                            <dd><?= e($selected['title']) ?></dd>
                            <dt>Description</dt>
                            <dd><?= $selected['description'] !== null && $selected['description'] !== '' ? nl2br(e($selected['description'])) : '-' ?></dd>
                            <dt>Due Date</dt>
                            <dd><?= e(format_date_id($selected['due_date'])) ?></dd>
                            <dt>Urgency</dt>
                            <dd><?= e(urgency_label($selected['urgency'])) ?></dd>
                            <dt>Status</dt>
                            <dd><?= $selected['is_done'] ? 'Done' : 'Ongoing' ?></dd>
                            <dt>Owner</dt>
                            <dd><?= e($selected['owner_name']) ?></dd>
                        </dl>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </main>
