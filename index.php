<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard($_SESSION['role']);
} else {
    header('Location: login.php');
    exit;
}
