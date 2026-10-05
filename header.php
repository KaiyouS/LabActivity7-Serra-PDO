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
    <!-- Neobrutalist Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>Blog Site</h1>
        <nav>
            <?php if ($currentUser): ?>
                <span>Logged in as: <strong><?= e($currentUser['username']) ?></strong> (<?= e($currentUser['email']) ?>)</span>
                <span>&middot;</span>
                <a href="index.php">Home (News Feed)</a>
                <span>&middot;</span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <span>&middot;</span>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($flash): ?>
        <div class="flash-notice flash-<?= e($flash['type'] ?? 'info') ?>">
            <p><strong>[<?= strtoupper(e($flash['type'] ?? 'notice')) ?>]:</strong> <?= e($flash['message']) ?></p>
        </div>
    <?php endif; ?>

    <main>
