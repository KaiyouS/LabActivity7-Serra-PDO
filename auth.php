<?php
declare(strict_types=1);

/**
 * Authentication and Session Management Helper
 * Lab Activity 7 - Core Authentication & Session Gates
 */

// Default PHP timezone to UTC so internal calculations are consistent with MySQL
date_default_timezone_set('UTC');

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
 * Detect client timezone from cookie (set via browser JavaScript) or fallback to UTC
 */
function get_client_timezone(): DateTimeZone
{
    $cookieTz = $_COOKIE['client_tz'] ?? '';
    if ($cookieTz !== '' && in_array($cookieTz, DateTimeZone::listIdentifiers(), true)) {
        try {
            return new DateTimeZone($cookieTz);
        } catch (Exception) {
            // Fallback to UTC on invalid timezone
        }
    }
    return new DateTimeZone('UTC');
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
 * Generate standard ISO 8601 UTC timestamp for HTML <time datetime="..."> tags
 */
function iso_date(string $datetime): string
{
    try {
        $time = new DateTime($datetime, new DateTimeZone('UTC'));
        return $time->format('Y-m-d\TH:i:s\Z');
    } catch (Exception) {
        return $datetime;
    }
}

/**
 * Format UTC timestamp into human-readable relative time or formatted date
 * converted to the client's detected browser timezone
 */
function format_date(string $datetime): string
{
    try {
        $time = new DateTime($datetime, new DateTimeZone('UTC'));
        $clientTz = get_client_timezone();
        $time->setTimezone($clientTz);

        $now = new DateTime('now', $clientTz);
        $diffSec = $now->getTimestamp() - $time->getTimestamp();

        if ($diffSec < 60) {
            return 'Just now';
        }
        if ($diffSec < 3600) {
            $mins = (int) floor($diffSec / 60);
            return $mins === 1 ? '1 minute ago' : "{$mins} minutes ago";
        }
        if ($diffSec < 86400) {
            $hours = (int) floor($diffSec / 3600);
            return $hours === 1 ? '1 hour ago' : "{$hours} hours ago";
        }
        if ($diffSec < 172800) {
            return 'Yesterday at ' . $time->format('g:i A');
        }
        if ($diffSec < 604800) {
            $days = (int) floor($diffSec / 86400);
            return "{$days} days ago";
        }
        return $time->format('M j, Y \a\t g:i A');
    } catch (Exception) {
        return $datetime;
    }
}
