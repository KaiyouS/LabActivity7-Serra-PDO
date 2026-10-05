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

<p>
    <a href="index.php#post-<?= (int)$postId ?>">&larr; Back to News Feed</a>
</p>

<h2>Edit Blog Post</h2>
<p>Make changes to your blog post. An <strong>(edited)</strong> marker will be displayed once saved.</p>

<?php if (!empty($errors)): ?>
    <div>
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $errorMsg): ?>
                <li><?= e($errorMsg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="edit_post.php?id=<?= (int)$postId ?>">
    <fieldset>
        <legend>Edit Post #<?= (int)$postId ?></legend>

        <p>
            <label for="title">Post Title:</label><br>
            <input 
                type="text" 
                id="title" 
                name="title" 
                required 
                maxlength="255"
                size="60"
                value="<?= e($title) ?>" 
            >
            <?php if (isset($errors['title'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['title']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <label for="content">Post Content (Text-Only):</label><br>
            <textarea 
                id="content" 
                name="content" 
                rows="8" 
                cols="60" 
                required 
            ><?= e($content) ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['content']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <button type="submit">Save Changes</button>
            <a href="index.php#post-<?= (int)$postId ?>">Cancel</a>
        </p>
    </fieldset>
</form>

<?php require __DIR__ . '/footer.php'; ?>
