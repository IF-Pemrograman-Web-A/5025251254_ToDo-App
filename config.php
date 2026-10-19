<?php

declare(strict_types=1);

// ----- Database -----
define('DB_HOST', 'localhost');
define('DB_NAME', 'do_it_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ----- Aplikasi -----
define('APP_NAME', 'DO . IT!');
define('APP_FOOTER', '&copy; 2026 DO . IT! - 5025251254');

// ----- Session -----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
