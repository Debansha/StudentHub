<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['faculty_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare('SELECT user_id FROM faculty WHERE faculty_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        // Deleting the faculty row cascades to course_assignments (ON DELETE CASCADE)
        // and sets attendance.marked_by to NULL (ON DELETE SET NULL) per the schema.
        $del = $pdo->prepare('DELETE FROM faculty WHERE faculty_id = ?');
        $del->execute([$id]);

        if ($row && $row['user_id']) {
            $delUser = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $delUser->execute([$row['user_id']]);
        }
    }
}

header('Location: list.php?deleted=1');
exit;
