<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verify_csrf()) {
    redirect(is_logged_in() ? 'dashboard.php' : 'login.php');
}

destroy_login_session();
flash('success', 'You have logged out successfully.');
redirect('login.php');
