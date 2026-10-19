<?php

$menuItems = [
    'personal' => [
        'label' => 'Personal',
        'icon'  => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ],
    'shared' => [
        'label' => 'Shared',
        'icon'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    ],
];
?>
        <nav class="sidebar" aria-label="Main menu">
            <p class="sidebar-title">Menu</p>

            <ul class="menu-list">
                <?php foreach ($menuItems as $key => $item): ?>
                    <li>
                        <a class="menu-link" href="<?= e(url(['view' => $key])) ?>"
                           <?= $view === $key ? 'aria-current="page"' : '' ?>>
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?= $item['icon'] ?></svg>
                            <span class="menu-label"><?= e($item['label']) ?></span>
                            <span class="menu-count" aria-hidden="true"><?= (int) $counts[$key] ?></span>
                            <span class="sr-only"><?= (int) $counts[$key] ?> ongoing</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Pilih user aktif (belum ada login) -->
            <form class="user-switcher" method="post" action="actions.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="switch_user">
                <input type="hidden" name="view" value="<?= e($view) ?>">

                <label for="switch-user">Signed in as</label>
                <select id="switch-user" name="user_id">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) $u['id'] === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-small">Switch</button>
            </form>
        </nav>
