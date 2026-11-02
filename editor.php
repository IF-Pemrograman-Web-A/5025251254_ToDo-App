<aside class="right-panel" aria-label="Task details">
<?php if (!$selected && !$creating): ?>
    <div class="empty-state">
        <h2>Task Details</h2>
        <p>Select a task, or hit "Add New Task", to see it here.</p>
    </div>
<?php else:
    $t = $selected ?? ['id' => 0, 'title' => '', 'description' => '', 'due' => '', 'urgency' => 'normal',
                       'visibility' => $view, 'image' => null, 'notify_at' => null, 'user_id' => $currentUserId, 'owner' => ''];
    $isOwner = (int) $t['user_id'] === $currentUserId; ?>
    <div class="editor-header">
        <h2><?= $creating ? 'Add New Task' : 'Task Details' ?></h2>
        <a class="btn-close" href="<?= h(url($view, $filter)) ?>" aria-label="Close task details">&times;</a>
    </div>

    <form class="editor-body" method="post" action="actions.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
        <?php returnFields($view, $filter); ?>

        <div class="editor-field">
            <label for="editor-name">Task Name</label>
            <input type="text" id="editor-name" name="title" value="<?= h($t['title']) ?>" placeholder="e.g. Tugas Pemrograman Web" required>
        </div>

        <div class="editor-field">
            <label for="editor-description">Description</label>
            <textarea id="editor-description" name="description" rows="3" placeholder="Add a description..."><?= h($t['description']) ?></textarea>
        </div>

        <div class="editor-meta">
            <label for="due-date">Due Date</label>
            <input type="date" id="due-date" name="due" value="<?= h($t['due']) ?>" required>

            <label for="urgency">Urgency</label>
            <select id="urgency" name="urgency">
                <?php foreach (URGENCY as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $t['urgency'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($isOwner): ?>
                <label for="visibility">Visibility</label>
                <select id="visibility" name="visibility">
                    <option value="personal" <?= $t['visibility'] === 'personal' ? 'selected' : '' ?>>Personal (hanya saya)</option>
                    <option value="shared" <?= $t['visibility'] === 'shared' ? 'selected' : '' ?>>Shared (semua orang)</option>
                </select>
            <?php else: ?>
                <p class="hint">Dibuat oleh <?= h($t['owner']) ?> · Shared</p>
            <?php endif; ?>

            <label for="notify-at">Notification Time (optional)</label>
            <input type="datetime-local" id="notify-at" name="notify_at"
                   value="<?= $t['notify_at'] ? h(date('Y-m-d\TH:i', strtotime($t['notify_at']))) : '' ?>">
        </div>

        <div class="editor-field">
            <label for="image-file">Image (optional)</label>
            <img id="image-preview" src="<?= $t['image'] ? h(UPLOAD_URL . $t['image']) : '' ?>"
                 alt="<?= $t['image'] ? 'Photo attached to this task' : '' ?>" <?= $t['image'] ? '' : 'hidden' ?>>
            <input type="file" id="image-file" name="image" accept="image/*">
            <p class="hint">JPG, PNG, WEBP, atau GIF. Maks 2 MB.</p>
            <?php if ($t['image']): ?>
                <div class="checkbox-row">
                    <input type="checkbox" id="remove-image" name="remove_image" value="1">
                    <label for="remove-image">Remove current image</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="editor-actions">
            <?php if ($selected && $isOwner): ?>
                <button type="submit" form="delete-form" class="btn-delete"
                        onclick="return confirm('Hapus task ini?')">Delete Task</button>
            <?php endif; ?>
            <button type="submit" class="btn-save"><?= $creating ? 'Add Task' : 'Save Changes' ?></button>
        </div>
    </form>

    <!-- form terpisah supaya tombol Enter tidak memicu Delete -->
    <form id="delete-form" method="post" action="actions.php" hidden>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
        <?php returnFields($view, $filter); ?>
    </form>
<?php endif; ?>
</aside>
