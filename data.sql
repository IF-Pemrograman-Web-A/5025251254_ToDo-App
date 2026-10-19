
CREATE DATABASE IF NOT EXISTS do_it_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE do_it_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS todos;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(60)  NOT NULL,
    email      VARCHAR(100) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE todos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id    INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT         NULL,
    due_date    DATE         NOT NULL,
    urgency     ENUM('urgent','important','normal') NOT NULL DEFAULT 'normal',
    visibility  ENUM('personal','shared')           NOT NULL DEFAULT 'personal',
    is_done     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_todos_owner_visibility (owner_id, visibility),
    KEY idx_todos_visibility_due   (visibility, due_date),
    CONSTRAINT fk_todos_owner FOREIGN KEY (owner_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, name, email) VALUES
    (1, 'Alya',  'alya@example.com'),
    (2, 'Budi',  'budi@example.com'),
    (3, 'Citra', 'citra@example.com'),
    (4, 'Dimas', 'dimas@example.com');

INSERT INTO todos (owner_id, title, description, due_date, urgency, visibility, is_done) VALUES
    -- Personal: Alya
    (1, 'Kerjakan tugas Pemrograman Web',   'Selesaikan fitur CRUD dengan PHP dan MySQL.',          '2026-10-26', 'urgent',    'personal', 0),
    (1, 'Baca materi Basis Data',           'Bab normalisasi dan relasi antar tabel.',              '2026-10-30', 'important', 'personal', 0),
    (1, 'Beli buku catatan',                NULL,                                                   '2026-10-22', 'normal',    'personal', 1),
    -- Personal: Budi
    (2, 'Latihan soal Algoritma',           'Kerjakan 10 soal rekursi.',                            '2026-10-24', 'important', 'personal', 0),
    (2, 'Servis laptop',                    'Ganti thermal paste dan bersihkan kipas.',             '2026-11-02', 'normal',    'personal', 0),
    -- Personal: Citra
    (3, 'Revisi laporan praktikum',         'Perbaiki bagian analisis dan kesimpulan.',             '2026-10-23', 'urgent',    'personal', 0),
    (3, 'Daftar kegiatan volunteer',        'Isi formulir pendaftaran sebelum kuota penuh.',        '2026-10-28', 'normal',    'personal', 1),
    -- Personal: Dimas
    (4, 'Belajar Git dan GitHub',           'Coba branching, merge, dan pull request.',             '2026-10-29', 'important', 'personal', 0),

    -- Shared: terlihat oleh semua anggota
    (1, 'Rapat evaluasi mingguan',          'Bahas progres tugas kelompok. Siapkan laporan singkat.', '2026-10-24', 'urgent',    'shared', 0),
    (2, 'Siapkan slide presentasi kelompok','Gabungkan materi semua anggota ke satu template.',      '2026-10-27', 'important', 'shared', 0),
    (3, 'Booking ruang diskusi',            'Pesan ruang untuk sesi belajar bersama hari Jumat.',    '2026-10-23', 'normal',    'shared', 1),
    (4, 'Rapikan repository kelompok',      'Hapus file tidak terpakai dan perbarui README.',        '2026-10-31', 'normal',    'shared', 0),
    (1, 'Kumpulkan dokumentasi kegiatan',   'Kumpulkan foto dan catatan dari semua anggota.',        '2026-11-05', 'important', 'shared', 0);
