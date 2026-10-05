<?php
declare(strict_types=1);

/**
 * Database Configuration & PDO Instance
 * Lab Activity 7 - Relational Databases (RDBMS) & PDO Core
 */

// Ensure PHP uses UTC internally
date_default_timezone_set('UTC');

$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'blog_site';
$dbUser = 'root';
$dbPass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    // Explicitly guarantee UTC timezone on the MySQL session
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    // In production/lab, never leak raw credentials; display a friendly error
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
