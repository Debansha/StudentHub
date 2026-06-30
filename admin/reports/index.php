<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$courses     = $pdo->query('SELECT course_id, course_code, course_name, semester FROM courses ORDER BY semester, course_name')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name, department_code FROM departments ORDER BY department_name')->fetchAll();

$base = '../../';
$pageTitle = 'Reports';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Reports</h2>

<div class="row g-4 mt-1">

  <!-- Student Record View -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5>Student Academic Record</h5>
      <p class="text-muted">View full academic profile for any student — marks, GPA/CGPA, attendance, eligibility.</p>
      <a href="student_view.php" class="btn btn-primary mt-auto">View Records</a>
    </div>
  </div>

  <!-- Report Card PDF -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5>Student Report Card PDF</h5>
      <p class="text-muted">Generate and download a printable PDF report card for any student.</p>
      <a href="student_view.php" class="btn btn-outline-danger mt-auto">Go to Student View → Download</a>
    </div>
  </div>

  <!-- Attendance Report PDF -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5>Attendance Report PDF</h5>
      <p class="text-muted">Download a course-wise attendance report with low-attendance highlights.</p>
      <?php if (!empty($courses)): ?>
        <form method="GET" action="attendance_pdf.php" target="_blank" class="mt-auto">
          <select name="course_id" class="form-select mb-2" required>
            <option value="">-- Select Course --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['course_id'] ?>">
                <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-outline-danger w-100">Download PDF</button>
        </form>
      <?php else: ?>
        <p class="text-muted small">No courses found. Add courses first.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Department Report PDF -->
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5>Department Report PDF</h5>
      <p class="text-muted">Full academic overview for an entire department — CGPA, attendance, eligibility for all students.</p>
      <?php if (!empty($departments)): ?>
        <form method="GET" action="department_pdf.php" target="_blank" class="mt-auto">
          <select name="dept_id" class="form-select mb-2" required>
            <option value="">-- Select Department --</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['department_id'] ?>">
                <?= htmlspecialchars($d['department_name'] . ' (' . $d['department_code'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-outline-danger w-100">Download PDF</button>
        </form>
      <?php else: ?>
        <p class="text-muted small">No departments found.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
