<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_guest();

$error = '';
$email = '';
$notice = pull_flash();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter both your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!$pdo instanceof PDO) {
        $error = $database_connection_error;
    } else {
        try {
            $statement = $pdo->prepare(
                'SELECT user_id, full_name, email, password_hash, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                redirect('dashboard.php');
            }

            $error = 'Incorrect email or password. Register first or use the exact saved details.';
        } catch (PDOException $exception) {
            error_log('Login query failed: ' . $exception->getMessage());
            $error = 'Login failed because the database could not be queried.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<main class="auth-page">
    <section class="auth-card">
        <div class="auth-logo">DQ</div>
        <div class="auth-header">
            <p class="tagline">Welcome Back</p>
            <h1>Login</h1>
            <p>Use an account saved in the MySQL database.</p>
        </div>

        <?php if ($notice): ?>
            <div class="flash-message <?php echo e($notice['type']); ?>" role="status"><?php echo e($notice['message']); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="flash-message error" role="alert"><?php echo e($error); ?></div>
        <?php elseif ($database_connection_error !== ''): ?>
            <div class="flash-message error" role="alert"><?php echo e($database_connection_error); ?></div>
        <?php endif; ?>

        <form class="auth-form" action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo e($email); ?>" placeholder="example@gmail.com" autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="primary-btn full-width">Login to Main Website</button>
        </form>

        <p class="auth-link">No account yet? <a href="register.php">Create Account</a></p>
        <div class="demo-account">
            <strong>Ready-made test account</strong>
            <span>Email: demo@gmail.com</span>
            <span>Password: DemoUser123!</span>
        </div>
    </section>
</main>
</body>
</html>
