<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!csrf_verify($_POST['csrf'] ?? null)) {
    http_response_code(419);
    exit('Invalid or expired form token. Please go back, refresh the page, and try again.');
}

$action = (string) ($_POST['action'] ?? '');
$view   = valid_view($_POST['view'] ?? null);
$filter = valid_filter($_POST['filter'] ?? null);
$id     = (int) ($_POST['id'] ?? 0);

try {
    $user = current_user();
    $uid  = (int) $user['id'];

    switch ($action) {

        /* ----- Ganti user aktif ----- */
        case 'switch_user':
            $newId = (int) ($_POST['user_id'] ?? 0);
            $stmt = db()->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute(['id' => $newId]);
            if ($stmt->fetch()) {
                $_SESSION['user_id'] = $newId;
                flash_set('success', 'User switched.');
            } else {
                flash_set('error', 'User not found.');
            }
            redirect(url(['view' => $view]));

        /* ----- CREATE ----- */
        case 'create':
            [$data, $errors] = validate_todo($_POST);
            if ($errors) {
                old_set($_POST, $errors);
                redirect(url(['view' => $view, 'filter' => $filter, 'new' => 1]));
            }
            $newId = todo_create($uid, $data);
            flash_set('success', 'Task added.');
            redirect(url(['view' => $data['visibility'], 'filter' => $filter, 'id' => $newId]));

        /* ----- UPDATE ----- */
        case 'update':
            [$data, $errors] = validate_todo($_POST);
            if ($errors) {
                old_set($_POST, $errors);
                redirect(url(['view' => $view, 'filter' => $filter, 'id' => $id]));
            }
            if (todo_update($id, $uid, $data)) {
                flash_set('success', 'Task saved.');
                redirect(url(['view' => $data['visibility'], 'filter' => $filter, 'id' => $id]));
            }
            flash_set('error', 'You can only edit your own tasks.');
            redirect(url(['view' => $view, 'filter' => $filter]));

        /* ----- DELETE ----- */
        case 'delete':
            if (todo_delete($id, $uid)) {
                flash_set('success', 'Task deleted.');
            } else {
                flash_set('error', 'You can only delete your own tasks.');
            }
            redirect(url(['view' => $view, 'filter' => $filter]));

        /* ----- TOGGLE selesai / belum ----- */
        case 'toggle':
            if (!todo_toggle($id, $uid)) {
                flash_set('error', 'Could not update that task.');
            }
            $selected = (int) ($_POST['selected'] ?? 0);
            redirect(url(['view' => $view, 'filter' => $filter, 'id' => $selected ?: null]));

        default:
            flash_set('error', 'Unknown action.');
            redirect(url(['view' => $view]));
    }
} catch (PDOException $ex) {
    error_log('DO.IT database error: ' . $ex->getMessage());
    flash_set('error', 'A database error occurred. Please try again.');
    redirect(url(['view' => $view, 'filter' => $filter]));
}
