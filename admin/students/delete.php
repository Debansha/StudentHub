<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['student_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare('SELECT user_id FROM students WHERE student_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        // Deleting the student row cascades to attendance/marks/enrollments/backlogs
        // (all set ON DELETE CASCADE in the schema).
        $del = $pdo->prepare('DELETE FROM students WHERE student_id = ?');
        $del->execute([$id]);

        // Also remove their login account, since it's no longer attached to anything.
        if ($row && $row['user_id']) {
            $delUser = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $delUser->execute([$row['user_id']]);
        }
    }
}

header('Location: list.php?deleted=1');
exit;
