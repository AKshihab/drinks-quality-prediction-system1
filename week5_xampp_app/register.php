<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_guest();

$error = '';
$fullName = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verify_csrf()) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (strlen($fullName) < 2 || strlen($fullName) > 100) {
        $error = 'Full name must contain 2 to 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $error = 'Password must include uppercase, lowercase, and a number.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirm password do not match.';
    } elseif (!$pdo instanceof PDO) {
        $error = $database_connection_error;
    } else {
        try {
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $check->execute(['email' => $email]);

            if ($check->fetch()) {
                $error = 'That email is already registered. Use it on the login page.';
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role)
                     VALUES (:full_name, :email, :password_hash, :role)'
                );
                $insert->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'User',
                ]);

                flash('success', 'Registration successful. Now log in with the same email and password.');
                redirect('login.php');
            }
        } catch (PDOException $exception) {
            error_log('Registration failed: ' . $exception->getMessage());
            $error = 'Registration failed. The email may already exist or MySQL may be unavailable.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
<main class="auth-page">
    <section class="auth-card">
        <div class="auth-logo">DQ</div>
        <div class="auth-header">
            <p class="tagline">New User</p>
            <h1>Create Account</h1>
            <p>Each user gets a separate database account.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="flash-message error" role="alert"><?php echo e($error); ?></div>
        <?php elseif ($database_connection_error !== ''): ?>
            <div class="flash-message error" role="alert"><?php echo e($database_connection_error); ?></div>
        <?php endif; ?>

        <form class="auth-form" id="registrationForm" action="register.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo e($fullName); ?>" placeholder="Enter your full name" autocomplete="name" required>
                <small id="fullNameError" class="error-message" aria-live="polite"></small>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo e($email); ?>" placeholder="example@gmail.com" autocomplete="email" required>
                <small id="emailError" class="error-message" aria-live="polite"></small>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" required>
                <small id="passwordError" class="error-message" aria-live="polite"></small>
                <div class="password-strength">
                    <div class="strength-track"><div id="passwordStrengthBar" class="password-strength-bar empty"></div></div>
                    <p id="passwordStrengthLabel">Strength: Empty</p>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Enter the same password again" autocomplete="new-password" required>
            </div>
            <button type="submit" class="primary-btn full-width">Register</button>
        </form>

        <p class="auth-link">Already registered? <a href="login.php">Login Here</a></p>
        <p class="security-note">The database stores a secure password hash, not your readable password.</p>
    </section>
</main>
</body>
</html>
