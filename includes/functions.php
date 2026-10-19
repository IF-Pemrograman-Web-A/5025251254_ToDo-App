<?php

declare(strict_types=1);

const VIEWS   = ['personal', 'shared'];
const FILTERS = ['all' => 'All', 'ongoing' => 'Ongoing', 'done' => 'Done'];

const MONTHS_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const URGENCY_LABELS = [
    'urgent'    => 'Urgent',
    'important' => 'Important',
    'normal'    => 'Normal',
];

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(array $params = []): string
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== false);
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function valid_view(?string $view): string
{
    return in_array($view, VIEWS, true) ? $view : 'personal';
}

function valid_filter(?string $filter): string
{
    return array_key_exists((string) $filter, FILTERS) ? (string) $filter : 'all';
}

function str_length(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }
    $count = preg_match_all('/./su', $text);
    return $count === false ? strlen($text) : $count;
}


function format_date_id(?string $isoDate): string
{
    if (!$isoDate) {
        return '-';
    }
    [$y, $m, $d] = array_map('intval', explode('-', $isoDate));
    if (!$y || !$m || !$d) {
        return '-';
    }
    return $d . ' ' . MONTHS_ID[$m - 1] . ' ' . $y;
}

function urgency_label(string $urgency): string
{
    return URGENCY_LABELS[$urgency] ?? $urgency;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function old_set(array $input, array $errors): void
{
    $_SESSION['old']    = $input;
    $_SESSION['errors'] = $errors;
}

function old_get(): array
{
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $old;
}

function errors_get(): array
{
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
    return $errors;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- User aktif ---------- */

function all_users(): array
{
    return db()->query('SELECT id, name FROM users ORDER BY id')->fetchAll();
}

/**
 * User yang sedang dipakai. Aplikasi ini belum memakai login,
 * jadi user dipilih lewat menu "Signed in as" dan disimpan di session.
 */
function current_user(): array
{
    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id > 0) {
        $stmt = db()->prepare('SELECT id, name FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch() ?: null;
    }

    if ($user === null) {
        $user = db()->query('SELECT id, name FROM users ORDER BY id LIMIT 1')->fetch() ?: null;
        if ($user === null) {
            throw new RuntimeException('Tabel users kosong. Import data.sql terlebih dahulu.');
        }
        $_SESSION['user_id'] = (int) $user['id'];
    }

    return $user;
}
