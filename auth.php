<?php
declare(strict_types=1);

/**
 * Authentication and Session Management Helper
 * Lab Activity 7 - Core Authentication & Session Gates
 */

if (session_status() === PHP_SESSION_NONE) {
    // Enforce safe cookie flags (Lectures 5 & 6)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    ]);
    session_start();
}

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user data from session
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'       => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? ''),
        'email'    => (string) ($_SESSION['email'] ?? ''),
    ];
}

/**
 * Enforce that the user MUST be authenticated.
 * Redirects unauthenticated visitors to login.php.
 */
function require_auth(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Enforce that the user MUST be a guest (unauthenticated).
 * Redirects already logged-in users to the home feed.
 */
function require_guest(): void
{
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Set a session flash notification
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear the session flash notification
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Safe string escaping helper for XSS mitigation (Lecture 5)
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format timestamp into human-readable relative time or formatted date
 */
function format_date(string $datetime): string
{
    try {
        $time = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->diff($time);

        if ($diff->y > 0) {
            return $time->format('M j, Y \a\t g:i A');
        }
        if ($diff->m > 0 || $diff->d > 7) {
            return $time->format('M j \a\t g:i A');
        }
        if ($diff->d > 0) {
            return $diff->d === 1 ? 'Yesterday at ' . $time->format('g:i A') : $diff->d . ' days ago';
        }
        if ($diff->h > 0) {
            return $diff->h === 1 ? '1 hour ago' : $diff->h . ' hours ago';
        }
        if ($diff->i > 0) {
            return $diff->i === 1 ? '1 minute ago' : $diff->i . ' minutes ago';
        }
        return 'Just now';
    } catch (Exception) {
        return $datetime;
    }
}
