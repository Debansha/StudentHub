<?php
// ============================================================
// Session + role-based access control helpers.
// Include this file at the top of any page that needs to know
// who's logged in, or that should be restricted by role.
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Redirects to login if nobody is signed in.
// Pass the correct relative path to login.php from the calling page.
function requireLogin(string $loginPath = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $loginPath");
        exit;
    }
}

// Redirects to login if not signed in, OR signed in as the wrong role.
function requireRole(string $role, string $loginPath = 'login.php'): void {
    requireLogin($loginPath);
    if ($_SESSION['role'] !== $role) {
        header("Location: $loginPath");
        exit;
    }
}

// Sends each role to its own dashboard. Used after login and from index.php.
function redirectToDashboard(string $role): void {
    switch ($role) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'faculty':
            header('Location: faculty/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: login.php');
    }
    exit;
}
