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

$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
if (!$commentId) {
    set_flash('error', 'Invalid comment ID.');
    header('Location: index.php');
    exit;
}

// Check ownership before deletion
$stmt = $pdo->prepare('SELECT id, post_id, user_id FROM comments WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    set_flash('error', 'Comment not found.');
    header('Location: index.php');
    exit;
}

if ((int) $comment['user_id'] !== $currentUser['id']) {
    set_flash('error', 'Unauthorized. You can only delete your own comments.');
    header('Location: index.php');
    exit;
}

// Delete comment
$deleteStmt = $pdo->prepare('DELETE FROM comments WHERE id = :id AND user_id = :user_id');
$deleteStmt->execute([
    ':id'      => $commentId,
    ':user_id' => $currentUser['id'],
]);

set_flash('success', 'Your comment was successfully removed.');
header("Location: index.php#post-{$comment['post_id']}");
exit;
