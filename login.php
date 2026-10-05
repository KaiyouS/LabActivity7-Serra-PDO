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

<h2>Sign In</h2>
<p>Enter your credentials to access your news feed.</p>

<?php if (isset($errors['auth'])): ?>
    <div class="flash-notice flash-error">
        <p><strong>[ERROR]:</strong> <?= e($errors['auth']) ?></p>
    </div>
<?php endif; ?>

<form method="POST" action="login.php">
    <fieldset>
        <legend>User Authentication</legend>

        <p>
            <label for="email">Email Address:</label><br>
            <input 
                type="email" 
                id="email" 
                name="email" 
                required 
                value="<?= e($oldEmail) ?>"
                placeholder="you@domain.com"
            >
            <?php if (isset($errors['email'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['email']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <label for="password">Password:</label><br>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required
            >
            <?php if (isset($errors['password'])): ?>
                <br><small><strong>Error:</strong> <?= e($errors['password']) ?></small>
            <?php endif; ?>
        </p>

        <p>
            <button type="submit">Sign In</button>
        </p>
    </fieldset>
</form>

<p>
    Don't have an account yet? <a href="register.php">Register here</a>.
</p>

<?php require __DIR__ . '/footer.php'; ?>
