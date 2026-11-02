<?php
// functions.php — helper & query database

const URGENCY = ['urgent' => 'Urgent', 'important' => 'Important', 'normal' => 'Normal'];
const MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function h($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $iso): string {
    if (!$iso) return '-';
    [$y, $m, $d] = array_map('intval', explode('-', substr($iso, 0, 10)));
    return $y ? "$d " . MONTHS_ID[$m - 1] . " $y" : '-';
}

function url(string $view = 'personal', string $filter = 'all', ?int $task = null, bool $new = false): string {
    $q = ['view' => $view];
    if ($filter !== 'all') $q['filter'] = $filter;
    if ($task) $q['task'] = $task;
    if ($new) $q['new'] = 1;
    return 'index.php?' . http_build_query($q);
}

function returnFields(string $view, string $filter, ?int $task = null): void {
    echo '<input type="hidden" name="view" value="' . h($view) . '">';
    echo '<input type="hidden" name="filter" value="' . h($filter) . '">';
    if ($task) echo '<input type="hidden" name="task" value="' . (int) $task . '">';
}

function flash(string $msg): void { $_SESSION['flash'] = $msg; }

function pullFlash(): ?string {
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// READ
function getTasks(PDO $pdo, string $view, string $filter, int $uid): array {
    $sql = 'SELECT t.*, u.name AS owner FROM tasks t JOIN users u ON u.id = t.user_id WHERE ';
    $params = [];
    if ($view === 'shared') {
        $sql .= "t.visibility = 'shared'";
    } else {
        $sql .= "t.visibility = 'personal' AND t.user_id = :uid";
        $params['uid'] = $uid;
    }
    if ($filter === 'ongoing') $sql .= ' AND t.done = 0';
    if ($filter === 'done')    $sql .= ' AND t.done = 1';
    $stmt = $pdo->prepare($sql . ' ORDER BY t.id');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Task bisa diakses jika shared, atau milik user sendiri
function getTask(PDO $pdo, int $id, int $uid): ?array {
    $stmt = $pdo->prepare("SELECT t.*, u.name AS owner FROM tasks t JOIN users u ON u.id = t.user_id
                           WHERE t.id = :id AND (t.visibility = 'shared' OR t.user_id = :uid)");
    $stmt->execute(['id' => $id, 'uid' => $uid]);
    return $stmt->fetch() ?: null;
}

function getReminders(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare('SELECT id, title, description, visibility, notify_at FROM tasks
                           WHERE user_id = :uid AND done = 0 AND notified = 0 AND notify_at IS NOT NULL');
    $stmt->execute(['uid' => $uid]);
    return array_map(fn($t) => [
        'id'          => (int) $t['id'],
        'title'       => $t['title'],
        'description' => $t['description'],
        'notify_at'   => date('Y-m-d\TH:i:s', strtotime($t['notify_at'])),
        'href'        => url($t['visibility'], 'all', (int) $t['id']),
    ], $stmt->fetchAll());
}

// File handling
function handleUpload(?array $file): ?string {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal, coba lagi.');
    if ($file['size'] > MAX_UPLOAD) throw new RuntimeException('Ukuran gambar maksimal 2 MB.');

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('File harus berupa gambar (JPG, PNG, WEBP, GIF).');

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('Gagal menyimpan file.');
    }
    return $name;
}

function deleteImage(?string $name): void {
    if ($name && is_file(UPLOAD_DIR . basename($name))) unlink(UPLOAD_DIR . basename($name));
}
