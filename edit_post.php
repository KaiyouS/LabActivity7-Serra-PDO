<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth(); // Authenticated users only

require __DIR__ . '/db.php';
$currentUser = current_user();

$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$postId) {
    set_flash('error', 'Invalid post ID.');
    header('Location: index.php');
    exit;
}

// Fetch post from database
$stmt = $pdo->prepare('SELECT id, user_id, title, content, created_at, updated_at FROM posts WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $postId]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('error', 'Post not found.');
    header('Location: index.php');
    exit;
}

// Authorization check: Users can only write changes to their own blog posts
if ((int) $post['user_id'] !== $currentUser['id']) {
    set_flash('error', 'Unauthorized. You can only edit your own blog posts.');
    header('Location: index.php');
    exit;
}

$errors = [];
$title   = $post['title'];
$content = $post['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Server-side validation
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Title must not exceed 255 characters.';
    }

    if ($content === '') {
        $errors['content'] = 'Content is required.';
    }

    if (empty($errors)) {
        // Update post and record updated_at timestamp (Lecture 7 UPDATE)
        $updateStmt = $pdo->prepare('
            UPDATE posts 
            SET title = :title, content = :content, updated_at = NOW() 
            WHERE id = :id AND user_id = :user_id
        ');
        $updateStmt->execute([
            ':title'   => $title,
            ':content' => $content,
            ':id'      => $postId,
            ':user_id' => $currentUser['id'],
        ]);

        set_flash('success', 'Your blog post has been successfully updated!');
        header("Location: index.php#post-{$postId}");
        exit;
    }
}

$pageTitle = 'Edit Blog Post — BlogSite';
require __DIR__ . '/header.php';
?>

<div class="page-nav-bar">
    <a href="index.php#post-<?= (int)$postId ?>" class="btn-back">
        <span class="material-symbols-outlined icon-inline">arrow_back</span>
        <span>Back to News Feed</span>
    </a>
</div>

<div class="edit-page-header">
    <h2><span class="material-symbols-outlined header-icon">edit_document</span> Edit Blog Post</h2>
    <p class="edit-subtitle">Make changes to your blog post. An <strong class="badge-edited-text">(edited)</strong> marker will be displayed once saved.</p>
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

<form method="POST" action="edit_post.php?id=<?= (int)$postId ?>" class="edit-form">
    <fieldset class="form-card">
        <legend><span class="material-symbols-outlined legend-icon">edit</span> Edit Post #<?= (int)$postId ?></legend>

        <div class="form-group">
            <label for="title">
                <span class="material-symbols-outlined label-icon">title</span>
                <span>Post Title:</span>
            </label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                required 
                maxlength="255"
                value="<?= e($title) ?>" 
                placeholder="Enter post title..."
            >
            <?php if (isset($errors['title'])): ?>
                <div class="field-error">
                    <span class="material-symbols-outlined error-icon">error</span>
                    <span><?= e($errors['title']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="content">
                <span class="material-symbols-outlined label-icon">notes</span>
                <span>Post Content (Text-Only):</span>
            </label>
            <textarea 
                id="content" 
                name="content" 
                rows="7" 
                required 
                placeholder="Write your post content..."
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
                <span>Save Changes</span>
            </button>
            <a href="index.php#post-<?= (int)$postId ?>" class="btn btn-cancel">
                <span class="material-symbols-outlined icon-inline">close</span>
                <span>Cancel</span>
            </a>
        </div>
    </fieldset>
</form>

<?php require __DIR__ . '/footer.php'; ?>
