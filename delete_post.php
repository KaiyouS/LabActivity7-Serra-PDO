<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth(); // Authenticated users only

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';
$currentUser = current_user();

$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
if (!$postId) {
    set_flash('error', 'Invalid post ID.');
    header('Location: index.php');
    exit;
}

// Check ownership before deletion
$stmt = $pdo->prepare('SELECT id, user_id FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('error', 'Post not found.');
    header('Location: index.php');
    exit;
}

if ((int) $post['user_id'] !== $currentUser['id']) {
    set_flash('error', 'Unauthorized. You can only delete your own blog posts.');
    header('Location: index.php');
    exit;
}

// Execute deletion. All associated comments are cascaded automatically via MySQL FK constraint.
$deleteStmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
$deleteStmt->execute([
    ':id'      => $postId,
    ':user_id' => $currentUser['id'],
]);

set_flash('success', 'Blog post and its associated comments were successfully deleted.');
header('Location: index.php');
exit;
