<?php
// config.php — konfigurasi, koneksi database, session
session_start();
date_default_timezone_set('Asia/Jakarta');

const DB_HOST = 'localhost';
const DB_NAME = 'todo_app';
const DB_USER = 'root';
const DB_PASS = '';

const UPLOAD_DIR = __DIR__ . '/uploads/';
const UPLOAD_URL = 'uploads/';
const MAX_UPLOAD = 2 * 1024 * 1024; // 2 MB

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL menyala dan data.sql sudah di-import.');
}

// "Login" sederhana: pilih user lewat sidebar (disimpan di session)
$users = $pdo->query('SELECT id, name FROM users ORDER BY id')->fetchAll();
$userIds = array_map('intval', array_column($users, 'id'));
$currentUserId = (int) ($_SESSION['uid'] ?? 0);
if (!in_array($currentUserId, $userIds, true)) {
    $currentUserId = $userIds[0] ?? 0;
}
