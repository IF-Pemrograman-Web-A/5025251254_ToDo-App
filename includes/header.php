<?php

$pageTitle  = $pageTitle ?? 'Todo App';
$fatalError = $fatalError ?? false;
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>

    <script>
        (function () {
            try {
                var t = localStorage.getItem('do-it-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <a class="skip-link" href="#main">Skip to main content</a>



<header>
    <div class="main-header">
        <h1 aria-label="Do It"><?= e(APP_NAME) ?></h1>

        <div class="header-actions">
            <!-- Tombol dark mode -->
            <div class="dark-mode-toggle">
                <label class="switch">
                    <input
                        type="checkbox"
                        id="dark-mode-switch"
                        aria-label="Dark mode"
                    >
                    <span class="slider"></span>
                </label>
            </div>

            <!-- Tombol hamburger -->
            <button
                type="button"
                class="menu-toggle"
                id="menu-toggle"
                aria-label="Open navigation menu"
                aria-controls="menu-drawer"
                aria-expanded="false"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>


    <div class="app">
