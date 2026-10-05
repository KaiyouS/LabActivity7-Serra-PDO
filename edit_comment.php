<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth(); // Authenticated users only

require __DIR__ . '/db.php';
$currentUser = current_user();

$commentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$commentId) {
    set_flash('error', 'Invalid comment ID.');
    header('Location: index.php');
    exit;
}

// Fetch comment from database
$stmt = $pdo->prepare('SELECT id, post_id, user_id, content, created_at, updated_at FROM comments WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    set_flash('error', 'Comment not found.');
    header('Location: index.php');
    exit;
}

// Authorization check: Users can only edit their own comments
if ((int) $comment['user_id'] !== $currentUser['id']) {
    set_flash('error', 'Unauthorized. You can only edit your own comments.');
    header('Location: index.php');
    exit;
}

$errors = [];
$content = $comment['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    // Server-side validation
    if ($content === '') {
        $errors['content'] = 'Comment content cannot be empty.';
    } elseif (mb_strlen($content) > 3000) {
        $errors['content'] = 'Comment must not exceed 3000 characters.';
    }

    if (empty($errors)) {
        // Update comment content and set updated_at timestamp marker
        $updateStmt = $pdo->prepare('
            UPDATE comments 
            SET content = :content, updated_at = NOW() 
            WHERE id = :id AND user_id = :user_id
        ');
        $updateStmt->execute([
            ':content' => $content,
            ':id'      => $commentId,
            ':user_id' => $currentUser['id'],
        ]);

        set_flash('success', 'Your comment was successfully updated!');
        header("Location: index.php#post-{$comment['post_id']}");
        exit;
    }
}

$pageTitle = 'Edit Comment — BlogSite';
require __DIR__ . '/header.php';
?>

<div class="page-nav-bar">
    <a href="index.php#post-<?= (int)$comment['post_id'] ?>" class="btn-back">
        <span class="material-symbols-outlined icon-inline">arrow_back</span>
        <span>Back to Post</span>
    </a>
</div>

<div class="edit-page-header">
    <h2><span class="material-symbols-outlined header-icon">rate_review</span> Edit Comment</h2>
    <p class="edit-subtitle">Make changes to your comment. An <strong class="badge-edited-text">(edited)</strong> marker will be displayed once saved.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="error-summary">
        <div class="error-title">
            <span class="material-symbols-outlined">error</span>
            <strong>Please fix the following errors:</strong>
        </div>
        <ul>
            <?php foreach ($errors as $errorMsg): ?>
                <li><?= e($errorMsg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="edit_comment.php?id=<?= (int)$commentId ?>" class="edit-form">
    <fieldset class="form-card">
        <legend><span class="material-symbols-outlined legend-icon">edit_note</span> Edit Comment #<?= (int)$commentId ?></legend>

        <div class="form-group">
            <label for="content">
                <span class="material-symbols-outlined label-icon">notes</span>
                <span>Comment Content (Text-Only):</span>
            </label>
            <textarea 
                id="content" 
                name="content" 
                rows="5" 
                required 
                placeholder="Write your comment..."
            ><?= e($content) ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <div class="field-error">
                    <span class="material-symbols-outlined error-icon">error</span>
                    <span><?= e($errors['content']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined icon-inline">save</span>
                <span>Update Comment</span>
            </button>
            <a href="index.php#post-<?= (int)$comment['post_id'] ?>" class="btn btn-cancel">
                <span class="material-symbols-outlined icon-inline">close</span>
                <span>Cancel</span>
            </a>
        </div>
    </fieldset>
</form>

<?php require __DIR__ . '/footer.php'; ?>
