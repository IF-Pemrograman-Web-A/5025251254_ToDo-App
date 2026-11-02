-- data.sql — struktur database + data dummy untuk Todo App (E04)
CREATE DATABASE IF NOT EXISTS todo_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE todo_app;

DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    due         DATE NOT NULL,
    urgency     ENUM('urgent','important','normal') NOT NULL DEFAULT 'normal',
    visibility  ENUM('personal','shared') NOT NULL DEFAULT 'personal',
    done        TINYINT(1) NOT NULL DEFAULT 0,
    image       VARCHAR(255) NULL,
    notify_at   DATETIME NULL,
    notified    TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name) VALUES ('Shafa'), ('Budi'), ('Citra');

INSERT INTO tasks (user_id, title, description, due, urgency, visibility, done) VALUES
(1, 'Kerjakan tugas Pemrograman Web E04', 'Migrasi ke PHP + database.',            '2026-11-09', 'urgent',    'personal', 0),
(1, 'Belajar untuk kuis Basis Data',      'Bab normalisasi dan ERD.',               '2026-11-12', 'important', 'personal', 0),
(1, 'Beli buku catatan',                  NULL,                                     '2026-11-05', 'normal',    'personal', 1),
(2, 'Revisi laporan praktikum',           'Tambahkan analisis hasil.',              '2026-11-10', 'important', 'personal', 0),
(2, 'Siapkan slide presentasi kelompok',  'Kerjakan bersama tim.',                  '2026-11-11', 'urgent',    'shared',   0),
(3, 'Rapat kelompok proyek akhir',        'Bahas pembagian tugas, Sabtu pukul 10.', '2026-11-07', 'important', 'shared',   0),
(3, 'Rekap nilai kuis kelas',             'Input ke spreadsheet bersama.',          '2026-11-14', 'normal',    'shared',   1),
(1, 'Review kode anggota tim',            'Cek pull request di GitHub.',            '2026-11-08', 'normal',    'shared',   0);
