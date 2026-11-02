<section class="left-panel" aria-label="Daftar tugas">
    <div class="panel-header">
        <h2><?= $view === 'shared' ? 'Shared Tasks' : 'My Tasks' ?></h2>
        <span class="badge-count"><?= (int) $total ?></span>

        <div class="task-filter" role="group" aria-label="Filter tasks">
            <?php foreach (['all' => 'All', 'ongoing' => 'Ongoing', 'done' => 'Done'] as $key => $label): ?>
                <a class="filter-btn" href="<?= h(url($view, $key)) ?>" <?= $filter === $key ? 'aria-current="true"' : '' ?>><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="todo-container">
        <?php if (!$tasks): ?>
            <p class="empty-list"><?= $filter === 'ongoing' ? 'No ongoing tasks.' : ($filter === 'done' ? 'No completed tasks yet.' : 'No tasks yet. Add one below!') ?></p>
        <?php endif; ?>

        <?php foreach ($tasks as $t):
            $isSel = $selected && (int) $selected['id'] === (int) $t['id']; ?>
            <div class="todo-list <?= $t['done'] ? 'done' : '' ?> <?= $isSel ? 'active' : '' ?>">
                <div class="list-task">
                    <form method="post" action="actions.php" class="toggle-form">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                        <?php returnFields($view, $filter, $selected['id'] ?? null); ?>
                        <input type="checkbox" class="task-checkbox" onchange="this.form.submit()"
                               aria-label="Mark <?= h($t['title']) ?> as done" <?= $t['done'] ? 'checked' : '' ?>>
                    </form>
                    <a class="task-open" href="<?= h(url($view, $filter, (int) $t['id'])) ?>" <?= $isSel ? 'aria-current="true"' : '' ?>>
                        <span class="task-title"><?= h($t['title']) ?></span>
                        <span class="arrow" aria-hidden="true">&rsaquo;</span>
                    </a>
                </div>
                <div class="todo-meta">
                    <span class="meta-due"><span aria-hidden="true">📅</span> <?= h(formatDate($t['due'])) ?></span>
                    <span class="meta-urgency <?= h($t['urgency']) ?>"><span aria-hidden="true">⚡</span> <?= h(URGENCY[$t['urgency']] ?? $t['urgency']) ?></span>
                    <?php if ($view === 'shared'): ?><span class="meta-due"><span aria-hidden="true">👤</span> <?= h($t['owner']) ?></span><?php endif; ?>
                    <?php if ($t['notify_at']): ?><span class="meta-due"><span aria-hidden="true">⏰</span> <?= h(date('d/m H:i', strtotime($t['notify_at']))) ?></span><?php endif; ?>
                    <?php if ($t['image']): ?><span class="meta-due"><span aria-hidden="true">🖼</span> Has photo</span><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a class="add-task" href="<?= h(url($view, $filter, null, true)) ?>">
        <span class="plus" aria-hidden="true">+</span>
        <span>Add New Task</span>
    </a>
</section>
