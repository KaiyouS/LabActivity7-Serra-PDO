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

<h2>Register Account</h2>
<p>Create a new account to publish blog posts and join discussions.</p>

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

<form method="POST" action="register.php">
    <fieldset>
        <legend>User Registration</legend>

        <p>
            <label for="username">Username:</label><br>
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
                <br><small><strong>Error:</strong> <?= e($errors['username']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <label for="email">Email Address:</label><br>
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
                <br><small><strong>Error:</strong> <?= e($errors['email']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <label for="password">Password (minimum 8 characters):</label><br>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                minlength="8"
            >
            <?php if (isset($errors['password'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['password']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <label for="confirm_password">Confirm Password:</label><br>
            <input 
                type="password" 
                id="confirm_password" 
                name="confirm_password" 
                required 
                minlength="8"
            >
            <?php if (isset($errors['confirm_password'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['confirm_password']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <button type="submit">Register</button>
        </p>
    </fieldset>
</form>

<p>
    Already have an account? <a href="login.php">Log in here</a>.
</p>

<?php require __DIR__ . '/footer.php'; ?>
