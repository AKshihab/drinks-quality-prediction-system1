<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function render_header(string $title, string $active = ''): void
{
    $loggedIn = isset($_SESSION['user_id'], $_SESSION['user_email']);
    $name = $_SESSION['user_name'] ?? 'User';
    $token = csrf_token();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Machine learning based drinks quality prediction web application">
    <title><?php echo e($title); ?> | <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
<header class="app-header">
    <div class="container header-content">
        <a href="<?php echo $loggedIn ? 'dashboard.php' : 'login.php'; ?>" class="brand">
            <span class="brand-icon">DQ</span>
            <span>Drinks Quality Prediction</span>
        </a>

        <?php if ($loggedIn): ?>
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation menu" aria-controls="appNav" aria-expanded="false">☰</button>
            <nav class="app-nav" id="appNav">
                <a class="<?php echo $active === 'home' ? 'active-link' : ''; ?>" href="dashboard.php">Home</a>
                <a href="dashboard.php#prediction">Prediction</a>
                <a class="<?php echo $active === 'history' ? 'active-link' : ''; ?>" href="history.php">History</a>
                <a class="<?php echo $active === 'profile' ? 'active-link' : ''; ?>" href="profile.php">Profile</a>
                <a href="dashboard.php#about">About</a>
                <span class="nav-user" title="<?php echo e($_SESSION['user_email']); ?>"><?php echo e($name); ?></span>
                <form class="logout-form" action="logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                    <button class="logout-link" type="submit">Logout</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>
<?php
}

function render_footer(): void
{
    ?>
<footer class="app-footer">
    <div class="container">
        <p>&copy; 2026 Group 7 - Drinks Quality Prediction System</p>
        <p>Software Development Project III | Week 6 Database Security Extension</p>
    </div>
</footer>
</body>
</html>
<?php
}
