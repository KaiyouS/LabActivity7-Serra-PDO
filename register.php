<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_guest(); // Only guests can view registration

$errors = [];
$old = [
    'username' => '',
    'email'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/db.php';

    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $old['username'] = $username;
    $old['email']    = $email;

    // --- Server-side Validations ---
    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, dots, hyphens, and underscores.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 255) {
        $errors['email'] = 'Email must not exceed 255 characters.';
    } else {
        // Check uniqueness of email in database
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $errors['email'] = 'This email is already registered. Please log in instead.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // --- If Valid, Create User ---
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashedPassword,
        ]);

        $newUserId = (int) $pdo->lastInsertId();

        // Regenerate session ID on privilege change for security
        session_regenerate_id(true);
        $_SESSION['user_id']  = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;

        set_flash('success', "Welcome to BlogSite, {$username}! Your account has been created.");
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Register — BlogSite';
require __DIR__ . '/header.php';
?>

<div class="auth-container">
    <div class="auth-header">
        <h2><span class="material-symbols-outlined header-icon">how_to_reg</span> Register Account</h2>
        <p class="auth-subtitle">Create a new account to publish blog posts and join discussions.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-summary">
            <div class="error-title">
                <span class="material-symbols-outlined">error</span>
                <strong>Please fix the following registration errors:</strong>
            </div>
            <ul>
                <?php foreach ($errors as $errorMsg): ?>
                    <li><?= e($errorMsg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="auth-form">
        <fieldset class="form-card">
            <legend><span class="material-symbols-outlined legend-icon">person_add</span> User Registration</legend>

            <div class="form-group">
                <label for="username">
                    <span class="material-symbols-outlined label-icon">badge</span>
                    <span>Username:</span>
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    minlength="3" 
                    maxlength="50" 
                    value="<?= e($old['username']) ?>"
                    placeholder="e.g. jdoe"
                >
                <?php if (isset($errors['username'])): ?>
                    <div class="field-error">
                        <span class="material-symbols-outlined error-icon">error</span>
                        <span><?= e($errors['username']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

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
                    maxlength="255"
                    value="<?= e($old['email']) ?>"
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
                    <span>Password (minimum 8 characters):</span>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    minlength="8"
                    placeholder="At least 8 characters..."
                >
                <?php if (isset($errors['password'])): ?>
                    <div class="field-error">
                        <span class="material-symbols-outlined error-icon">error</span>
                        <span><?= e($errors['password']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <span class="material-symbols-outlined label-icon">lock_reset</span>
                    <span>Confirm Password:</span>
                </label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required 
                    minlength="8"
                    placeholder="Re-type your password..."
                >
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="field-error">
                        <span class="material-symbols-outlined error-icon">error</span>
                        <span><?= e($errors['confirm_password']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined icon-inline">person_add</span>
                    <span>Create Account</span>
                </button>
            </div>
        </fieldset>
    </form>

    <div class="auth-switch">
        <p>Already have an account? <a href="login.php" class="switch-link"><span class="material-symbols-outlined icon-inline">login</span> Log in here</a></p>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
