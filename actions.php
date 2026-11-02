<?php
// actions.php — memproses Create, Update, Delete, toggle (hanya POST)
require 'config.php';
require 'functions.php';

function go(string $url) { header('Location: ' . $url); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') go('index.php');

$action = $_POST['action'] ?? '';
$view   = ($_POST['view'] ?? '') === 'shared' ? 'shared' : 'personal';
$filter = in_array($_POST['filter'] ?? '', ['ongoing', 'done'], true) ? $_POST['filter'] : 'all';
$id     = (int) ($_POST['id'] ?? 0);
$back   = (int) ($_POST['task'] ?? 0) ?: null;

switch ($action) {

    case 'switch_user':
        $uid = (int) ($_POST['user_id'] ?? 0);
        if (in_array($uid, $userIds, true)) $_SESSION['uid'] = $uid;
        go(url($view, $filter));

    case 'toggle':
        if (getTask($pdo, $id, $currentUserId)) {
            $pdo->prepare('UPDATE tasks SET done = 1 - done WHERE id = :id')->execute(['id' => $id]);
        }
        go(url($view, $filter, $back));

    case 'notified': // dipanggil JS setelah pengingat muncul
        $pdo->prepare('UPDATE tasks SET notified = 1 WHERE id = :id AND user_id = :uid')
            ->execute(['id' => $id, 'uid' => $currentUserId]);
        http_response_code(204);
        exit;

    case 'delete':
        $t = getTask($pdo, $id, $currentUserId);
        if ($t && (int) $t['user_id'] === $currentUserId) {
            $pdo->prepare('DELETE FROM tasks WHERE id = :id')->execute(['id' => $id]);
            deleteImage($t['image']);
            flash('Task dihapus.');
        } else {
            flash('Hanya pembuat task yang bisa menghapusnya.');
        }
        go(url($view, $filter));

    case 'save':
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $due   = $_POST['due'] ?? '';
        $urg   = array_key_exists($_POST['urgency'] ?? '', URGENCY) ? $_POST['urgency'] : 'normal';
        $vis   = ($_POST['visibility'] ?? '') === 'shared' ? 'shared' : 'personal';
        $raw   = $_POST['notify_at'] ?? '';
        $notify = ($raw !== '' && ($ts = strtotime($raw))) ? date('Y-m-d H:i:s', $ts) : null;

        $existing = $id ? getTask($pdo, $id, $currentUserId) : null;
        if ($id && !$existing) { flash('Task tidak ditemukan.'); go(url($view, $filter)); }

        $dateOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) && strtotime($due) !== false;
        if ($title === '' || !$dateOk) {
            flash('Nama task dan tanggal wajib diisi.');
            go(url($view, $filter, $id ?: null, !$id));
        }

        // hanya pembuat yang boleh mengubah visibility
        if ($existing && (int) $existing['user_id'] !== $currentUserId) $vis = $existing['visibility'];

        $image = $existing['image'] ?? null;
        try {
            $uploaded = handleUpload($_FILES['image'] ?? null);
        } catch (RuntimeException $e) {
            flash($e->getMessage());
            go(url($view, $filter, $id ?: null, !$id));
        }
        if (!empty($_POST['remove_image'])) { deleteImage($image); $image = null; }
        if ($uploaded) { deleteImage($image); $image = $uploaded; }

        if ($existing) {
            $notified = ($existing['notify_at'] === $notify) ? (int) $existing['notified'] : 0;
            $pdo->prepare('UPDATE tasks SET title = :t, description = :d, due = :due, urgency = :u,
                           visibility = :v, image = :i, notify_at = :n, notified = :nf WHERE id = :id')
                ->execute(['t' => $title, 'd' => $desc, 'due' => $due, 'u' => $urg, 'v' => $vis,
                           'i' => $image, 'n' => $notify, 'nf' => $notified, 'id' => $id]);
            $saved = $id;
        } else {
            $pdo->prepare('INSERT INTO tasks (user_id, title, description, due, urgency, visibility, image, notify_at)
                           VALUES (:uid, :t, :d, :due, :u, :v, :i, :n)')
                ->execute(['uid' => $currentUserId, 't' => $title, 'd' => $desc, 'due' => $due,
                           'u' => $urg, 'v' => $vis, 'i' => $image, 'n' => $notify]);
            $saved = (int) $pdo->lastInsertId();
        }
        flash('Task disimpan.');
        go(url($vis, $filter, $saved));
}

go(url($view, $filter));
