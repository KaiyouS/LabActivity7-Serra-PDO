<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    // 1. Wipe in-memory session superglobal (Lecture 5 slide 15)
    $_SESSION = [];

    // 2. Clear session cookie in user browser
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // 3. Destroy server-side session data
    session_destroy();
}

// Start fresh guest session for flash feedback
session_start();
set_flash('info', 'You have been successfully logged out.');

header('Location: login.php');
exit;
