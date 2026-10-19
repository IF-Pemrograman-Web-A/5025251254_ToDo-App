<?php

declare(strict_types=1);

function todo_list(int $userId, string $view, string $filter): array
{
    $sql = 'SELECT t.*, u.name AS owner_name
            FROM todos t
            JOIN users u ON u.id = t.owner_id
            WHERE ';
    $params = [];

    if ($view === 'shared') {
        $sql .= "t.visibility = 'shared'";
    } else {
        $sql .= "t.visibility = 'personal' AND t.owner_id = :uid";
        $params['uid'] = $userId;
    }

    if ($filter === 'ongoing') {
        $sql .= ' AND t.is_done = 0';
    } elseif ($filter === 'done') {
        $sql .= ' AND t.is_done = 1';
    }

    $sql .= ' ORDER BY t.due_date ASC, t.id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Satu todo, hanya kalau boleh dilihat oleh user ini. */
function todo_find(int $id, int $userId): ?array
{
    $stmt = db()->prepare(
        "SELECT t.*, u.name AS owner_name
         FROM todos t
         JOIN users u ON u.id = t.owner_id
         WHERE t.id = :id
           AND (t.visibility = 'shared' OR t.owner_id = :uid)"
    );
    $stmt->execute(['id' => $id, 'uid' => $userId]);
    return $stmt->fetch() ?: null;
}

/** Jumlah todo yang belum selesai untuk badge di menu. */
function todo_counts(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT
            COALESCE(SUM(visibility = 'personal' AND owner_id = :uid1 AND is_done = 0), 0) AS personal,
            COALESCE(SUM(visibility = 'shared' AND is_done = 0), 0) AS shared
         FROM todos"
    );
    $stmt->execute(['uid1' => $userId]);
    $row = $stmt->fetch();
    return ['personal' => (int) $row['personal'], 'shared' => (int) $row['shared']];
}

/* ---------- CREATE ---------- */

function todo_create(int $ownerId, array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO todos (owner_id, title, description, due_date, urgency, visibility)
         VALUES (:owner, :title, :description, :due, :urgency, :visibility)'
    );
    $stmt->execute([
        'owner'       => $ownerId,
        'title'       => $data['title'],
        'description' => $data['description'],
        'due'         => $data['due_date'],
        'urgency'     => $data['urgency'],
        'visibility'  => $data['visibility'],
    ]);
    return (int) db()->lastInsertId();
}

/* ---------- UPDATE ---------- */

/** Ubah isi todo. Hanya pemilik yang berhasil (return false kalau bukan). */
function todo_update(int $id, int $ownerId, array $data): bool
{
    $stmt = db()->prepare(
        'UPDATE todos
         SET title = :title, description = :description, due_date = :due,
             urgency = :urgency, visibility = :visibility
         WHERE id = :id AND owner_id = :owner'
    );
    $stmt->execute([
        'title'       => $data['title'],
        'description' => $data['description'],
        'due'         => $data['due_date'],
        'urgency'     => $data['urgency'],
        'visibility'  => $data['visibility'],
        'id'          => $id,
        'owner'       => $ownerId,
    ]);
    return $stmt->rowCount() > 0;
}

/** Centang / batalkan centang selesai (shared: semua anggota boleh). */
function todo_toggle(int $id, int $userId): bool
{
    $stmt = db()->prepare(
        "UPDATE todos
         SET is_done = 1 - is_done
         WHERE id = :id AND (visibility = 'shared' OR owner_id = :uid)"
    );
    $stmt->execute(['id' => $id, 'uid' => $userId]);
    return $stmt->rowCount() > 0;
}

/* ---------- DELETE ---------- */

function todo_delete(int $id, int $ownerId): bool
{
    $stmt = db()->prepare('DELETE FROM todos WHERE id = :id AND owner_id = :owner');
    $stmt->execute(['id' => $id, 'owner' => $ownerId]);
    return $stmt->rowCount() > 0;
}

/* ---------- VALIDASI ---------- */

/**
 * Validasi input form todo.
 * @return array{0: array, 1: array} [data bersih, daftar error]
 */
function validate_todo(array $input): array
{
    $errors = [];

    $title       = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $due         = (string) ($input['due_date'] ?? '');
    $urgency     = (string) ($input['urgency'] ?? 'normal');
    $visibility  = (string) ($input['visibility'] ?? 'personal');

    if ($title === '') {
        $errors[] = 'Task name is required.';
    } elseif (str_length($title) > 150) {
        $errors[] = 'Task name must be 150 characters or fewer.';
    }

    $date = DateTime::createFromFormat('Y-m-d', $due);
    if (!$date || $date->format('Y-m-d') !== $due) {
        $errors[] = 'Please enter a valid due date.';
    }

    if (!array_key_exists($urgency, URGENCY_LABELS)) {
        $errors[] = 'Please choose a valid urgency.';
    }

    if (!in_array($visibility, VIEWS, true)) {
        $errors[] = 'Please choose a valid visibility.';
    }

    $data = [
        'title'       => $title,
        'description' => $description === '' ? null : $description,
        'due_date'    => $due,
        'urgency'     => $urgency,
        'visibility'  => $visibility,
    ];

    return [$data, $errors];
}
