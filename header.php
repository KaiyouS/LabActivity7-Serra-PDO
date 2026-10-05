<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Blog Site';
$currentUser = current_user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Google Fonts for Neobrutalist Aesthetic (Space Grotesk + Plus Jakarta Sans + Space Mono) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Google Material Symbols Outlined -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!-- Neobrutalist Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-inner">
            <a href="index.php" class="brand-link">
                <span class="brand-icon"><span class="material-symbols-outlined">bolt</span></span>
                <span class="brand-name">Blog Site</span>
                <span class="brand-badge">PDO CORE</span>
            </a>
            <nav class="site-nav">
                <?php if ($currentUser): ?>
                    <div class="user-pill">
                        <span class="material-symbols-outlined icon-inline">account_circle</span>
                        <span class="user-name"><?= e($currentUser['username']) ?></span>
                        <span class="user-email">(<?= e($currentUser['email']) ?>)</span>
                    </div>
                    <a href="index.php" class="nav-btn">
                        <span class="material-symbols-outlined icon-inline">feed</span>
                        <span>Feed</span>
                    </a>
                    <a href="logout.php" class="nav-btn nav-btn-danger">
                        <span class="material-symbols-outlined icon-inline">logout</span>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-btn">
                        <span class="material-symbols-outlined icon-inline">login</span>
                        <span>Sign In</span>
                    </a>
                    <a href="register.php" class="nav-btn nav-btn-accent">
                        <span class="material-symbols-outlined icon-inline">person_add</span>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash-container">
            <div class="flash-notice flash-<?= e($flash['type'] ?? 'info') ?>">
                <span class="material-symbols-outlined flash-icon">
                    <?= ($flash['type'] ?? '') === 'error' ? 'error' : 'check_circle' ?>
                </span>
                <div class="flash-text">
                    <strong>[<?= strtoupper(e($flash['type'] ?? 'notice')) ?>]</strong>
                    <span><?= e($flash['message']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <main class="site-main">
