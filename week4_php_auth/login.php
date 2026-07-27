<?php
session_start();

if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$status_message = trim($_GET['msg'] ?? '');
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error_message = 'All form fields are mandatory.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please provide a valid email address.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must contain at least 8 characters.';
    } else {
        $mock_user_email = 'student@university.edu';
        $mock_password_hash = password_hash('secureStudent123', PASSWORD_DEFAULT);

        if ($email === $mock_user_email && password_verify($password, $mock_password_hash)) {
            session_regenerate_id(true);

            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'Administrator';
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();

            header('Location: dashboard.php');
            exit();
        }

        $error_message = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="page-center">
        <section class="card auth-card">
            <h1>Drinks Quality Prediction System</h1>
            <p class="subtitle">Week 4 secure login demonstration</p>

            <?php if ($status_message !== ''): ?>
                <div class="message success" role="status">
                    <?php echo htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message !== ''): ?>
                <div class="message error" role="alert">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="button primary-button">Sign in</button>
            </form>

            <div class="demo-details">
                <strong>Demonstration account</strong>
                <span>Email: student@university.edu</span>
                <span>Password: secureStudent123</span>
            </div>
        </section>
    </main>
</body>
</html>
