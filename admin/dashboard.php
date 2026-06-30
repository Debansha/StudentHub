<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', '../login.php');

$studentCount = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$facultyCount = $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
$courseCount  = $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();

$base = '../';
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
<p class="text-muted">Admin Dashboard</p>

<div class="row g-4 mt-2">
  <div class="col-md-4">
    <a href="students/list.php" class="text-decoration-none">
      <div class="card p-3 stat-card">
        <h6>Total Students</h6>
        <h3><?= $studentCount ?></h3>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="faculty/list.php" class="text-decoration-none">
      <div class="card p-3 stat-card">
        <h6>Total Faculty</h6>
        <h3><?= $facultyCount ?></h3>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="courses/list.php" class="text-decoration-none">
      <div class="card p-3 stat-card">
        <h6>Total Courses</h6>
        <h3><?= $courseCount ?></h3>
      </div>
    </a>
  </div>
</div>

<p class="mt-4 text-muted">Use the navigation above to manage students, faculty, and courses.</p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
