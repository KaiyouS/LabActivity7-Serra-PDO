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
 * Get the currently logged-in user data verified against the database.
 * If the session holds an ID of a deleted user, the stale session is cleanly cleared.
 */
function current_user(?PDO $pdo = null): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if ($pdo === null) {
        require __DIR__ . '/db.php';
    }

    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Stale session pointing to non-existent user in database
        $_SESSION = [];
        return null;
    }

    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['email']    = (string) $user['email'];

    return [
        'id'       => (int) $user['id'],
        'username' => (string) $user['username'],
        'email'    => (string) $user['email'],
    ];
}

/**
 * Check if a user is currently authenticated with a valid database record
 */
function is_logged_in(?PDO $pdo = null): bool
{
    return current_user($pdo) !== null;
}

/**
 * Enforce that the user MUST be authenticated.
 * If the session is missing or points to a non-existent user in the database,
 * redirects unauthenticated visitors to login.php.
 */
function require_auth(?PDO $pdo = null): void
{
    if (empty($_SESSION['user_id'])) {
        set_flash('error', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }

    $user = current_user($pdo);
    if (!$user) {
        set_flash('error', 'Your session has expired or your account was not found. Please log in or register again.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Enforce that the user MUST be a guest (unauthenticated).
 * Redirects already logged-in users to the home feed.
 */
function require_guest(?PDO $pdo = null): void
{
    if (is_logged_in($pdo)) {
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
 * converted to the client's detected browser timezone with grammatically correct wording
 */
function format_date(string $datetime, bool $withPostedPrefix = false): string
{
    try {
        $time = new DateTime($datetime, new DateTimeZone('UTC'));
        $clientTz = get_client_timezone();
        $time->setTimezone($clientTz);

        $now = new DateTime('now', $clientTz);
        $diffSec = $now->getTimestamp() - $time->getTimestamp();

        if ($withPostedPrefix) {
            if ($diffSec < 60) {
                return 'Posted just now';
            }
            if ($diffSec < 3600) {
                $mins = max(1, (int) floor($diffSec / 60));
                return $mins === 1 ? 'Posted 1 minute ago' : "Posted {$mins} minutes ago";
            }
            if ($diffSec < 86400) {
                $hours = (int) floor($diffSec / 3600);
                return $hours === 1 ? 'Posted 1 hour ago' : "Posted {$hours} hours ago";
            }
            if ($diffSec < 172800) {
                return 'Posted yesterday at ' . $time->format('g:i A');
            }
            if ($diffSec < 604800) {
                $days = (int) floor($diffSec / 86400);
                return $days === 1 ? 'Posted 1 day ago' : "Posted {$days} days ago";
            }
            return 'Posted on ' . $time->format('M j, Y \a\t g:i A');
        }

        // Without prefix (e.g. for comments)
        if ($diffSec < 60) {
            return 'Just now';
        }
        if ($diffSec < 3600) {
            $mins = max(1, (int) floor($diffSec / 60));
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
            return $days === 1 ? '1 day ago' : "{$days} days ago";
        }
        return $time->format('M j, Y \a\t g:i A');
    } catch (Exception) {
        return $datetime;
    }
}
