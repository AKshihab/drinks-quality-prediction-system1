<?php
declare(strict_types=1);

$database_host = '127.0.0.1';
$database_port = '3306';
$database_name = 'drinks_quality_db';
$database_username = 'root';
$database_password = ''; // Standard XAMPP default. Change this if your MySQL root has a password.
$database_connection_error = '';
$pdo = null;

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $database_host,
    $database_port,
    $database_name
);

try {
    $pdo = new PDO(
        $dsn,
        $database_username,
        $database_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Drinks Quality database connection failed: ' . $exception->getMessage());
    $database_connection_error = 'Database connection failed. Start MySQL in XAMPP and import database/schema.sql.';
}
