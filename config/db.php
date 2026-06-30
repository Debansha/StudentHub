<?php
// ============================================================
// Database connection (PDO)
// Edit these if your XAMPP MySQL root has a password set.
// Default XAMPP install has no password.
// ============================================================
$host    = 'localhost';
$dbname  = 'smart_student_system';
$dbuser  = 'root';
$dbpass  = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
