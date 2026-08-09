<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_email']);
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

function require_login(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!is_logged_in()) {
        destroy_login_session();
        flash('error', 'Please log in to open the main website.');
        redirect('login.php');
    }

    $lastActivity = (int) ($_SESSION['last_activity'] ?? time());
    if ((time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
        destroy_login_session();
        flash('error', 'Your session expired after 5 minutes of inactivity. Please log in again.');
        redirect('login.php');
    }

    $_SESSION['last_activity'] = time();
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['user_name'] = (string) $user['full_name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) ($user['role'] ?? 'User');
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function destroy_login_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Start a clean session so a logout/timeout message can be displayed.
    session_start();
}
