<?php
session_start();

if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit();
}

header('Location: login.php');
exit();
