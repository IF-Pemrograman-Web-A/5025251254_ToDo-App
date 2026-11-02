<?php
// index.php — halaman utama, menyusun semua modul
require 'config.php';
require 'functions.php';

$view   = ($_GET['view'] ?? '') === 'shared' ? 'shared' : 'personal';
$filter = in_array($_GET['filter'] ?? '', ['ongoing', 'done'], true) ? $_GET['filter'] : 'all';

$tasks = getTasks($pdo, $view, $filter, $currentUserId);
$total = $filter === 'all' ? count($tasks) : count(getTasks($pdo, $view, 'all', $currentUserId));

$selId    = (int) ($_GET['task'] ?? 0);
$selected = $selId ? getTask($pdo, $selId, $currentUserId) : null;
$creating = !$selected && isset($_GET['new']);

$flash     = pullFlash();
$reminders = getReminders($pdo, $currentUserId);

include 'header.php';
?>
<main class="container">
    <?php include 'menu-bar.php'; ?>
    <?php include 'content.php'; ?>
    <?php include 'editor.php'; ?>
</main>
<?php include 'footer.php'; ?>
