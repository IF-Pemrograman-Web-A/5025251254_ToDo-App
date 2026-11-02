<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <!-- pasang tema sebelum render supaya tidak berkedip -->
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('do-it-theme') || 'light'); } catch (e) {}</script>
</head>

<body>
    <header>
        <div class="main-header">
            <h1>DO . IT!</h1>
            <div class="dark-mode-toggle">
                <label class="switch">
                    <input type="checkbox" id="dark-mode-switch" aria-label="Dark mode">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </header>
