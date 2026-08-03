<?php
session_start();

if (isset($_SESSION['user_id'], $_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$status_message = trim($_GET['msg'] ?? '');
$email = '';

require_once __DIR__ . '/config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error_message = 'All form fields are mandatory.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please provide a valid email address.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must contain at least 8 characters.';
    } elseif (!$pdo instanceof PDO) {
        $error_message = $database_connection_error !== ''
            ? $database_connection_error
            : 'The database is temporarily unavailable. Please try again later.';
    } else {
        try {
            $statement = $pdo->prepare(
                'SELECT
                    user_id,
                    full_name,
                    email,
                    password_hash,
                    role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                header('Location: dashboard.php');
                exit();
            }

            $error_message = 'Invalid email or password.';
        } catch (PDOException $exception) {
            error_log('Database login query failed: ' . $exception->getMessage());
            $error_message = 'Unable to sign in right now. Please check that MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <div class="auth-logo">DQ</div>

            <div class="auth-header">
                <p class="tagline">Welcome Back</p>
                <h1>Login</h1>
                <p>Access your drinks quality prediction dashboard.</p>
            </div>

            <?php if ($status_message !== ''): ?>
                <div class="flash-wrapper">
                    <div class="flash-message success" role="status">
                        <?php echo htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message !== ''): ?>
                <div class="flash-wrapper">
                    <div class="flash-message error" role="alert">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php elseif ($database_connection_error !== ''): ?>
                <div class="flash-wrapper">
                    <div class="flash-message error" role="alert">
                        <?php echo htmlspecialchars($database_connection_error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="login.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="example@gmail.com"
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="primary-btn full-width">Login</button>
            </form>

            <p class="auth-link">
                Do not have an account?
                <a href="register.php">Create Account</a>
            </p>

            <div class="demo-details">
                <strong>Demonstration account</strong>
                <br>
                Email: student@university.edu
                <br>
                Password: secureStudent123
            </div>
        </section>
    </main>
</body>
</html>
