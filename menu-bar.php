<nav class="sidebar" aria-label="Menu utama">
    <div>
        <p class="sidebar-title">Menu</p>
        <ul class="menu">
            <li><a href="<?= h(url('personal')) ?>" <?= $view === 'personal' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">🔒</span> Personal</a></li>
            <li><a href="<?= h(url('shared')) ?>" <?= $view === 'shared' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">👥</span> Shared</a></li>
        </ul>
    </div>

    <form method="post" action="actions.php" class="user-switch">
        <input type="hidden" name="action" value="switch_user">
        <?php returnFields($view, $filter, $selected['id'] ?? null); ?>
        <label for="user-select">Masuk sebagai</label>
        <select id="user-select" name="user_id" onchange="this.form.submit()">
            <?php foreach ($users as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= (int) $u['id'] === $currentUserId ? 'selected' : '' ?>><?= h($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</nav>
