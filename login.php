<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest(); // Only guests can view login

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/db.php';

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    // --- Server-side Validations ---
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        // Find user by email
        $stmt = $pdo->prepare('SELECT id, username, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Verify credentials with native password_verify
        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['email']    = (string) $user['email'];

            set_flash('success', "Welcome back, {$user['username']}!");
            header('Location: index.php');
            exit;
        } else {
            $errors['auth'] = 'Invalid email or password combination.';
        }
    }
}

$pageTitle = 'Login — BlogSite';
require __DIR__ . '/header.php';
?>

<div class="auth-container">
    <div class="auth-header">
        <h2><span class="material-symbols-outlined header-icon">login</span> Sign In</h2>
        <p class="auth-subtitle">Enter your credentials to access your community news feed.</p>
    </div>

    <?php if (isset($errors['auth'])): ?>
        <div class="flash-notice flash-error">
            <span class="material-symbols-outlined flash-icon">error</span>
            <div class="flash-text">
                <strong>[ERROR]</strong>
                <span><?= e($errors['auth']) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="auth-form">
        <fieldset class="form-card">
            <legend><span class="material-symbols-outlined legend-icon">lock</span> User Authentication</legend>

            <div class="form-group">
                <label for="email">
                    <span class="material-symbols-outlined label-icon">mail</span>
                    <span>Email Address:</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    value="<?= e($oldEmail) ?>"
                    placeholder="you@domain.com"
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="field-error">
                        <span class="material-symbols-outlined error-icon">error</span>
                        <span><?= e($errors['email']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">
                    <span class="material-symbols-outlined label-icon">key</span>
                    <span>Password:</span>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Enter your password..."
                >
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error">
                        <span class="material-symbols-outlined error-icon">error</span>
                        <span><?= e($errors['password']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined icon-inline">arrow_forward</span>
                    <span>Sign In</span>
                </button>
            </div>
        </fieldset>
    </form>

    <div class="auth-switch">
        <p>Don't have an account yet? <a href="register.php" class="switch-link"><span class="material-symbols-outlined icon-inline">person_add</span> Register here</a></p>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
