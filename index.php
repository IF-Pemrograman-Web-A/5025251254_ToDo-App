<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$view     = valid_view($_GET['view'] ?? null);
$filter   = valid_filter($_GET['filter'] ?? null);
$isNew    = isset($_GET['new']);
$selectedId = (int) ($_GET['id'] ?? 0);

try {
    $user   = current_user();
    $uid    = (int) $user['id'];
    $selected = null;

    if (!$isNew && $selectedId > 0) {
        $selected = todo_find($selectedId, $uid);
        if ($selected === null) {
            flash_set('error', 'Task not found or you do not have access to it.');
            redirect(url(['view' => $view, 'filter' => $filter]));
        }
        // Buka di halaman yang sesuai dengan jenis task-nya
        if ($selected['visibility'] !== $view) {
            redirect(url(['view' => $selected['visibility'], 'filter' => $filter, 'id' => $selectedId]));
        }
    }

    $todos  = todo_list($uid, $view, $filter);
    $counts = todo_counts($uid);
    $users  = all_users();
    $flash  = flash_get();
    $old    = old_get();
    $errors = errors_get();
} catch (Throwable $ex) {
    error_log('DO.IT error: ' . $ex->getMessage());
    http_response_code(500);
    $pageTitle = 'Error';
    $fatalError = true;
    require __DIR__ . '/includes/header.php';
    echo '<main class="container" id="main"><section class="left-panel"><h2>Something went wrong</h2>'
       . '<p>Could not connect to the database. Check <code>config.php</code> '
       . 'and make sure <code>data.sql</code> has been imported.</p></section></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = ($view === 'shared' ? 'Shared' : 'Personal') . ' Tasks';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/menu.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/footer.php';
