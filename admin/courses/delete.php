<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['course_id'] ?? null;
    if ($id) {
        // Cascades to course_assignments, enrollments, attendance, marks, backlogs (schema ON DELETE CASCADE).
        $stmt = $pdo->prepare('DELETE FROM courses WHERE course_id = ?');
        $stmt->execute([$id]);
    }
}

header('Location: list.php?deleted=1');
exit;
