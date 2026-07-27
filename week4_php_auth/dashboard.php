<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$timeout_duration = 300;

if (
    isset($_SESSION['last_activity'])
    && (time() - (int) $_SESSION['last_activity']) > $timeout_duration
) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php?msg=' . urlencode('Session timed out due to inactivity.'));
    exit();
}

$_SESSION['last_activity'] = time();

$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'] ?? 'User';
$login_time = (int) ($_SESSION['login_time'] ?? time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="page-center">
        <section class="card dashboard-card">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Secure dashboard</p>
                    <h1>Drinks Quality Prediction System</h1>
                </div>
                <a href="logout.php" class="button logout-button">Log out</a>
            </div>

            <div class="message success" role="status">
                You are securely authenticated.
            </div>

            <div class="account-details">
                <div class="detail-row">
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Role</span>
                    <strong><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Login time</span>
                    <strong><?php echo htmlspecialchars(date('F j, Y, g:i a', $login_time), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <p class="timeout-note">For security, this session expires after 5 minutes of inactivity.</p>
        </section>
    </main>
</body>
</html>

