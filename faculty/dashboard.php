<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('faculty', '../login.php');

$stmt = $pdo->prepare('SELECT * FROM faculty WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$faculty = $stmt->fetch();

$courseCount = 0;
$studentCount = 0;
if ($faculty) {
    $courseCount = $pdo->prepare(
        'SELECT COUNT(DISTINCT course_id) FROM course_assignments WHERE faculty_id = ?'
    );
    $courseCount->execute([$faculty['faculty_id']]);
    $courseCount = $courseCount->fetchColumn();

    $studentCount = $pdo->prepare(
        'SELECT COUNT(DISTINCT e.student_id)
         FROM enrollments e
         JOIN course_assignments ca ON ca.course_id = e.course_id
         WHERE ca.faculty_id = ?'
    );
    $studentCount->execute([$faculty['faculty_id']]);
    $studentCount = $studentCount->fetchColumn();
}

$base = '../';
$pageTitle = 'Faculty Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Welcome, <?= htmlspecialchars($faculty['full_name'] ?? $_SESSION['username']) ?></h2>
<p class="text-muted">Faculty Dashboard</p>

<div class="row g-4 mt-2">
  <div class="col-md-4">
    <div class="card p-3 stat-card">
      <h6>Courses Assigned</h6>
      <h3><?= $courseCount ?></h3>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 stat-card">
      <h6>Students (across all courses)</h6>
      <h3><?= $studentCount ?></h3>
    </div>
  </div>
</div>

<div class="row g-3 mt-4">
  <div class="col-md-4">
    <a href="attendance/mark.php" class="card p-4 text-decoration-none d-block">
      <h5>Mark Attendance</h5>
      <p class="text-muted mb-0">Record daily attendance for your courses.</p>
    </a>
  </div>
  <div class="col-md-4">
    <a href="attendance/summary.php" class="card p-4 text-decoration-none d-block">
      <h5>Attendance Summary</h5>
      <p class="text-muted mb-0">View per-student attendance % and low attendance alerts.</p>
    </a>
  </div>
  <div class="col-md-4">
    <a href="marks/entry.php" class="card p-4 text-decoration-none d-block">
      <h5>Upload Marks</h5>
      <p class="text-muted mb-0">Enter CAT 1, CAT 2, Assignment, and Final Exam marks.</p>
    </a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
