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
</head>
<body>

    <header>
        <h1>Blog Site</h1>
        <nav>
            <?php if ($currentUser): ?>
                <span>Logged in as: <strong><?= e($currentUser['username']) ?></strong> (<?= e($currentUser['email']) ?>)</span> |
                <a href="index.php">Home (News Feed)</a> |
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <hr>

    <?php if ($flash): ?>
        <div>
            <p><strong>[Notice]:</strong> <?= e($flash['message']) ?></p>
        </div>
        <hr>
    <?php endif; ?>
