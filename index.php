<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_auth(); // Authenticated users only! Unauthenticated are redirected to login.php

require __DIR__ . '/db.php';
$currentUser = current_user();

$postErrors = [];
$commentErrors = [];
$oldPost = ['title' => '', 'content' => ''];

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Create Blog Post
    if ($action === 'create_post') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        $oldPost['title']   = $title;
        $oldPost['content'] = $content;

        if ($title === '') {
            $postErrors['title'] = 'Post title is required.';
        } elseif (mb_strlen($title) > 255) {
            $postErrors['title'] = 'Post title cannot exceed 255 characters.';
        }

        if ($content === '') {
            $postErrors['content'] = 'Post content cannot be empty.';
        }

        if (empty($postErrors)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content) VALUES (:user_id, :title, :content)');
                $stmt->execute([
                    ':user_id' => $currentUser['id'],
                    ':title'   => $title,
                    ':content' => $content,
                ]);

                set_flash('success', 'Your blog post was successfully published!');
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                // If user foreign key constraint fails, session is stale
                if ($e->getCode() === '23000') {
                    $_SESSION = [];
                    set_flash('error', 'Your session has expired. Please log in or register a new account.');
                    header('Location: login.php');
                    exit;
                }
                throw $e;
            }
        }
    }

    // Action: Add Comment to a Post
    if ($action === 'create_comment') {
        $postId  = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $content = trim($_POST['content'] ?? '');

        if (!$postId) {
            $commentErrors['general'] = 'Invalid post target for comment.';
        } else {
            // Verify post existence
            $checkPost = $pdo->prepare('SELECT id FROM posts WHERE id = :id LIMIT 1');
            $checkPost->execute([':id' => $postId]);
            if (!$checkPost->fetch()) {
                $commentErrors['general'] = 'The post you are commenting on no longer exists.';
            }
        }

        if ($content === '') {
            $commentErrors['content'] = 'Comment cannot be empty.';
        } elseif (mb_strlen($content) > 3000) {
            $commentErrors['content'] = 'Comment cannot exceed 3000 characters.';
        }

        if (empty($commentErrors)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)');
                $stmt->execute([
                    ':post_id' => $postId,
                    ':user_id' => $currentUser['id'],
                    ':content' => $content,
                ]);

                set_flash('success', 'Your comment has been posted.');
                header("Location: index.php#post-{$postId}");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $_SESSION = [];
                    set_flash('error', 'Your session has expired. Please log in or register a new account.');
                    header('Location: login.php');
                    exit;
                }
                throw $e;
            }
        }
    }
}

// --- Fetch Blog Posts (News Feed: sorted by most recently posted) ---
$postsQuery = '
    SELECT 
        p.id, 
        p.user_id, 
        p.title, 
        p.content, 
        p.created_at, 
        p.updated_at,
        u.username AS author_name,
        u.email AS author_email
    FROM posts p
    INNER JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC, p.id DESC
';
$posts = $pdo->query($postsQuery)->fetchAll();

// --- Fetch Comments Efficiently (Preventing N+1 queries) ---
$commentsByPost = [];
if (!empty($posts)) {
    $postIds = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));

    $commentsSql = "
        SELECT 
            c.id, 
            c.post_id, 
            c.user_id, 
            c.content, 
            c.created_at, 
            c.updated_at,
            u.username AS commenter_name
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.post_id IN ({$placeholders})
        ORDER BY c.created_at ASC
    ";
    $commentsStmt = $pdo->prepare($commentsSql);
    $commentsStmt->execute($postIds);
    $allComments = $commentsStmt->fetchAll();

    foreach ($allComments as $comment) {
        $commentsByPost[(int)$comment['post_id']][] = $comment;
    }
}

$pageTitle = 'News Feed — BlogSite';
require __DIR__ . '/header.php';
?>

<div class="feed-header">
    <h2><span class="material-symbols-outlined header-icon">dynamic_feed</span> Community News Feed</h2>
    <p class="feed-subtitle">Live stream of blog posts posted by all users, sorted by most recently posted.</p>
</div>

<!-- Form: Add a new blog post -->
<form method="POST" action="index.php" class="post-create-form">
    <fieldset class="form-card">
        <legend><span class="material-symbols-outlined legend-icon">edit_note</span> Add a New Blog Post (Text-Only)</legend>

        <?php if (!empty($postErrors)): ?>
            <div class="error-summary">
                <div class="error-title">
                    <span class="material-symbols-outlined">error</span>
                    <strong>Please fix the following post errors:</strong>
                </div>
                <ul>
                    <?php foreach ($postErrors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <input type="hidden" name="action" value="create_post">

        <div class="form-group">
            <label for="post_title">
                <span class="material-symbols-outlined label-icon">title</span>
                <span>Title:</span>
            </label>
            <input 
                type="text" 
                id="post_title" 
                name="title" 
                required 
                maxlength="255"
                value="<?= e($oldPost['title']) ?>"
                placeholder="Enter an engaging post title..."
            >
        </div>

        <div class="form-group">
            <label for="post_content">
                <span class="material-symbols-outlined label-icon">notes</span>
                <span>Content (Text-Only):</span>
            </label>
            <textarea 
                id="post_content" 
                name="content" 
                rows="4" 
                required 
                placeholder="What's on your mind? Write your blog post here..."
            ><?= e($oldPost['content']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined icon-inline">send</span>
                <span>Publish Blog Post</span>
            </button>
        </div>
    </fieldset>
</form>

<div class="section-title-bar">
    <h3><span class="material-symbols-outlined section-icon">history_edu</span> Recent Posts</h3>
    <span class="badge badge-accent"><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?></span>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state-card">
        <span class="material-symbols-outlined empty-icon">forum</span>
        <h4>No blog posts have been published yet.</h4>
        <p>Be the first to share your thoughts using the form above!</p>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php 
            $isPostOwner = ((int)$post['user_id'] === $currentUser['id']);
            $postComments = $commentsByPost[(int)$post['id']] ?? [];
            $isEdited = !empty($post['updated_at']);
        ?>
        <article class="post-card" id="post-<?= (int)$post['id'] ?>">
            <div class="post-header">
                <h4 class="post-title"><?= e($post['title']) ?></h4>
                <?php if ($isPostOwner): ?>
                    <div class="post-actions">
                        <a href="edit_post.php?id=<?= (int)$post['id'] ?>" class="btn-action btn-edit">
                            <span class="material-symbols-outlined icon-inline">edit</span>
                            <span>Edit</span>
                        </a>
                        <form method="POST" action="delete_post.php" onsubmit="return confirm('Are you sure you want to delete this post? All comments on it will also be deleted.');">
                            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                            <button type="submit" class="btn-action btn-delete">
                                <span class="material-symbols-outlined icon-inline">delete</span>
                                <span>Delete</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="post-meta">
                <span class="meta-item meta-author">
                    <span class="material-symbols-outlined meta-icon">person</span>
                    <span>By <strong><?= e($post['author_name']) ?></strong></span>
                    <?php if ($isPostOwner): ?>
                        <span class="owner-pill">You</span>
                    <?php endif; ?>
                </span>
                <span class="meta-item meta-time">
                    <span class="material-symbols-outlined meta-icon">schedule</span>
                    <time datetime="<?= iso_date($post['created_at']) ?>" data-prefix="posted"><?= format_date($post['created_at'], true) ?></time>
                </span>
                <?php if ($isEdited): ?>
                    <time datetime="<?= iso_date($post['updated_at']) ?>" data-edited-marker="true" class="badge-edited" title="Edited on <?= format_date($post['updated_at']) ?>">
                        <span class="material-symbols-outlined badge-icon">edit_note</span>
                        <strong>(edited)</strong>
                    </time>
                <?php endif; ?>
            </div>

            <div class="post-body">
                <p><?= nl2br(e($post['content'])) ?></p>
            </div>

            <!-- Comments Section (Pivot / Junction Table) -->
            <section class="comments-section">
                <div class="comments-header">
                    <h5>
                        <span class="material-symbols-outlined header-icon">chat_bubble</span>
                        <span>Comments</span>
                        <span class="badge badge-count"><?= count($postComments) ?></span>
                    </h5>
                </div>

                <?php if (!empty($postComments)): ?>
                    <ul class="comments-list">
                        <?php foreach ($postComments as $comment): ?>
                            <?php 
                                $isCommentOwner = ((int)$comment['user_id'] === $currentUser['id']);
                                $isCommentEdited = !empty($comment['updated_at']);
                            ?>
                            <li class="comment-item" id="comment-<?= (int)$comment['id'] ?>">
                                <div class="comment-header">
                                    <div class="comment-author-info">
                                        <span class="material-symbols-outlined meta-icon">person</span>
                                        <strong><?= e($comment['commenter_name']) ?></strong>
                                        <?php if ($isCommentOwner): ?>
                                            <span class="owner-pill">You</span>
                                        <?php endif; ?>
                                        <span class="comment-time">
                                            &middot;
                                            <span class="material-symbols-outlined meta-icon">schedule</span>
                                            <time datetime="<?= iso_date($comment['created_at']) ?>"><?= format_date($comment['created_at']) ?></time>
                                        </span>
                                        <?php if ($isCommentEdited): ?>
                                            <time datetime="<?= iso_date($comment['updated_at']) ?>" data-edited-marker="true" class="badge-edited" title="Edited on <?= format_date($comment['updated_at']) ?>">
                                                <span class="material-symbols-outlined badge-icon">edit_note</span>
                                                <strong>(edited)</strong>
                                            </time>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isCommentOwner): ?>
                                        <div class="comment-actions">
                                            <a href="edit_comment.php?id=<?= (int)$comment['id'] ?>" class="btn-action-sm btn-edit">
                                                <span class="material-symbols-outlined icon-inline">edit</span>
                                                <span>Edit</span>
                                            </a>
                                            <form method="POST" action="delete_comment.php" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                <input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>">
                                                <button type="submit" class="btn-action-sm btn-delete">
                                                    <span class="material-symbols-outlined icon-inline">delete</span>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="comment-body">
                                    <p><?= nl2br(e($comment['content'])) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-comments">
                        <span class="material-symbols-outlined empty-comments-icon">chat</span>
                        <p><small><em>No comments on this post yet. Be the first to share your thoughts!</em></small></p>
                    </div>
                <?php endif; ?>

                <!-- Form: Add a comment -->
                <form method="POST" action="index.php#post-<?= (int)$post['id'] ?>" class="comment-form">
                    <fieldset class="comment-fieldset">
                        <legend><span class="material-symbols-outlined legend-icon">add_comment</span> Add a Comment (Text-Only)</legend>
                        
                        <?php if (!empty($commentErrors) && (int)($_POST['post_id'] ?? 0) === (int)$post['id']): ?>
                            <div class="error-summary">
                                <ul>
                                    <?php foreach ($commentErrors as $err): ?>
                                        <li><?= e($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="action" value="create_comment">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">

                        <div class="form-group">
                            <label for="comment_content_<?= (int)$post['id'] ?>">
                                <span class="material-symbols-outlined label-icon">comment</span>
                                <span>Comment:</span>
                            </label>
                            <textarea 
                                id="comment_content_<?= (int)$post['id'] ?>" 
                                name="content" 
                                rows="2" 
                                required 
                                placeholder="Write your comment here..."
                            ></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <span class="material-symbols-outlined icon-inline">reply</span>
                                <span>Submit Comment</span>
                            </button>
                        </div>
                    </fieldset>
                </form>

            </section>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
