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

<h2>Community News Feed</h2>
<p>List of blog posts posted by all users, sorted by most recently posted.</p>

<!-- Form: Add a new blog post -->
<form method="POST" action="index.php">
    <fieldset>
        <legend>Add a New Blog Post (Text-Only)</legend>

        <?php if (!empty($postErrors)): ?>
            <div>
                <strong>Please fix the following post errors:</strong>
                <ul>
                    <?php foreach ($postErrors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <input type="hidden" name="action" value="create_post">

        <p>
            <label for="post_title">Title:</label><br>
            <input 
                type="text" 
                id="post_title" 
                name="title" 
                required 
                maxlength="255"
                size="60"
                value="<?= e($oldPost['title']) ?>"
                placeholder="Enter post title..."
            >
        </p>

        <p>
            <label for="post_content">Content (Text-Only):</label><br>
            <textarea 
                id="post_content" 
                name="content" 
                rows="5" 
                cols="60" 
                required 
                placeholder="Write your blog post content here..."
            ><?= e($oldPost['content']) ?></textarea>
        </p>

        <p>
            <button type="submit">Publish Blog Post</button>
        </p>
    </fieldset>
</form>

<hr>

<h3>Recent Posts</h3>

<?php if (empty($posts)): ?>
    <p><em>No blog posts have been published yet. Be the first to add one using the form above!</em></p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?php 
            $isPostOwner = ((int)$post['user_id'] === $currentUser['id']);
            $postComments = $commentsByPost[(int)$post['id']] ?? [];
            $isEdited = !empty($post['updated_at']);
        ?>
        <article id="post-<?= (int)$post['id'] ?>">
            <h4><?= e($post['title']) ?></h4>

            <p>
                <small>
                    By <strong><?= e($post['author_name']) ?></strong>
                    <?php if ($isPostOwner): ?>
                        <em>(You)</em>
                    <?php endif; ?>
                    &middot; Posted on <time datetime="<?= iso_date($post['created_at']) ?>"><?= format_date($post['created_at']) ?></time>
                    <?php if ($isEdited): ?>
                        <time datetime="<?= iso_date($post['updated_at']) ?>" data-edited-marker="true" title="Edited on <?= format_date($post['updated_at']) ?>"><strong>(edited)</strong></time>
                    <?php endif; ?>
                </small>
            </p>

            <p><?= nl2br(e($post['content'])) ?></p>

            <?php if ($isPostOwner): ?>
                <p>
                    <a href="edit_post.php?id=<?= (int)$post['id'] ?>">[Edit Post]</a>
                    <form method="POST" action="delete_post.php" onsubmit="return confirm('Are you sure you want to delete this post? All comments on it will also be deleted.');">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <button type="submit">[Delete Post]</button>
                    </form>
                </p>
            <?php endif; ?>

            <!-- Comments Section (Pivot / Junction Table) -->
            <section>
                <h5>Comments (<?= count($postComments) ?>)</h5>

                <?php if (!empty($postComments)): ?>
                    <ul>
                        <?php foreach ($postComments as $comment): ?>
                            <?php 
                                $isCommentOwner = ((int)$comment['user_id'] === $currentUser['id']);
                                $isCommentEdited = !empty($comment['updated_at']);
                            ?>
                            <li id="comment-<?= (int)$comment['id'] ?>">
                                <strong><?= e($comment['commenter_name']) ?></strong>
                                <?php if ($isCommentOwner): ?>
                                    <em>(You)</em>
                                <?php endif; ?>
                                <small>
                                    &middot; <time datetime="<?= iso_date($comment['created_at']) ?>"><?= format_date($comment['created_at']) ?></time>
                                    <?php if ($isCommentEdited): ?>
                                        <time datetime="<?= iso_date($comment['updated_at']) ?>" data-edited-marker="true" title="Edited on <?= format_date($comment['updated_at']) ?>"><strong>(edited)</strong></time>
                                    <?php endif; ?>
                                </small>
                                <p><?= nl2br(e($comment['content'])) ?></p>

                                <?php if ($isCommentOwner): ?>
                                    <p>
                                        <a href="edit_comment.php?id=<?= (int)$comment['id'] ?>">[Edit Comment]</a>
                                        <form method="POST" action="delete_comment.php" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                            <input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>">
                                            <button type="submit">[Delete Comment]</button>
                                        </form>
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><small><em>No comments on this post yet.</em></small></p>
                <?php endif; ?>

                <!-- Form: Add a comment -->
                <form method="POST" action="index.php#post-<?= (int)$post['id'] ?>">
                    <fieldset>
                        <legend>Add a Comment (Text-Only)</legend>
                        <input type="hidden" name="action" value="create_comment">
                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                        <p>
                            <label for="comment_content_<?= (int)$post['id'] ?>">Comment:</label><br>
                            <textarea 
                                id="comment_content_<?= (int)$post['id'] ?>" 
                                name="content" 
                                rows="2" 
                                cols="50" 
                                required 
                                placeholder="Write your comment here..."
                            ></textarea>
                        </p>
                        <p>
                            <button type="submit">Submit Comment</button>
                        </p>
                    </fieldset>
                </form>

            </section>
        </article>

        <hr>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
