<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/avatar.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?>WK Poule 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Archivo:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="stadium-bg"></div>

    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">
                <span class="logo-mark">⚽</span>
                <span class="logo-text">WK<span class="logo-accent">POULE</span>26</span>
            </a>

            <nav class="main-nav">
                <?php if ($user): ?>
                    <a href="index.php"      class="nav-link <?= $currentPage === 'index.php'       ? 'is-active' : '' ?>">Dashboard</a>
                    <a href="pools.php"      class="nav-link <?= $currentPage === 'pools.php'       ? 'is-active' : '' ?>">Poules</a>
                    <a href="predictions.php" class="nav-link <?= $currentPage === 'predictions.php' ? 'is-active' : '' ?>">Voorspellingen</a>
                    <?php if ($user['id'] === 1): ?>
                        <a href="admin_scores.php" class="nav-link <?= $currentPage === 'admin_scores.php' ? 'is-active' : '' ?>">⚙️ Uitslagen</a>
                    <?php endif; ?>
                    <div class="user-menu">
                        <a href="profile.php" class="nav-link <?= $currentPage === 'profile.php' ? 'is-active' : '' ?>">Profiel</a>
                        <span class="user-chip">
                            <span class="user-avatar" style="<?= getAvatarStyle($user['name']) ?>"><?= strtoupper(substr(htmlspecialchars($user['name']), 0, 1)) ?></span>
                            <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                        </span>
                        <a href="logout.php" class="btn btn-ghost btn-sm">Uitloggen</a>
                    </div>
                <?php else: ?>
                    <a href="login.php"    class="nav-link <?= $currentPage === 'login.php'    ? 'is-active' : '' ?>">Inloggen</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Registreren</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-main">
