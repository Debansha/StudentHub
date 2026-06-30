<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/gpa.php';
requireRole('student', '../login.php');

// Get student record
$stmt = $pdo->prepare('SELECT s.*, d.department_name FROM students s LEFT JOIN departments d ON d.department_id = s.department_id WHERE s.user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
if (!$student) { die('Student profile not found.'); }

$sid = $student['student_id'];

// CGPA
$cgpa = getLatestCGPA($pdo, $sid);

// Overall attendance %
$attStmt = $pdo->prepare(
    'SELECT COUNT(*) AS total, SUM(status="Present") AS present FROM attendance WHERE student_id = ?'
);
$attStmt->execute([$sid]);
$att = $attStmt->fetch();
$attendancePct = ($att['total'] > 0) ? round($att['present'] / $att['total'] * 100, 1) : 0;

// Latest marks
$marksStmt = $pdo->prepare(
    'SELECT m.*, c.course_name, c.course_code FROM marks m
     JOIN courses c ON c.course_id = m.course_id
     WHERE m.student_id = ? ORDER BY m.semester DESC, c.course_name'
);
$marksStmt->execute([$sid]);
$marksList = $marksStmt->fetchAll();

// Avg marks % for prediction
$avgPct = 0;
if (!empty($marksList)) {
    $totals = array_filter(array_column($marksList, 'total'), fn($v) => $v !== null);
    $avgPct = count($totals) > 0 ? (array_sum($totals) / count($totals) / 145 * 100) : 0;
}
$prediction = predictPerformance($avgPct, $attendancePct);

// SGPA history
$gpaHistory = $pdo->prepare('SELECT * FROM semester_gpa WHERE student_id = ? ORDER BY semester');
$gpaHistory->execute([$sid]);
$gpaHistory = $gpaHistory->fetchAll();

// Latest CGPA for eligibility checks
$hasBacklog = (bool)$pdo->prepare('SELECT COUNT(*) FROM backlogs WHERE student_id = ? AND status = "Pending"')->execute([$sid]);
$backlogCount = $pdo->prepare('SELECT COUNT(*) FROM backlogs WHERE student_id = ? AND status = "Pending"');
$backlogCount->execute([$sid]);
$backlogCount = (int)$backlogCount->fetchColumn();

$placementEligible  = $cgpa >= 7 && $backlogCount === 0;
$scholarshipEligible = $cgpa >= 8 && $attendancePct >= 80;

$predBadge = match($prediction) {
    'Excellent'        => 'bg-success',
    'Good'             => 'bg-primary',
    'Average'          => 'bg-warning text-dark',
    'Needs Improvement'=> 'bg-orange text-dark',
    default            => 'bg-danger',
};

$base = '../';
$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="accent-bar"></div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<h2>Welcome, <?= htmlspecialchars($student['full_name']) ?></h2>
<a href="../admin/reports/report_card_pdf.php?student_id=<?= (int)$sid ?>" class="btn btn-outline-danger" target="_blank">⬇ Download My Report Card</a>
</div>
<p class="text-muted"><?= htmlspecialchars($student['department_name'] ?? '') ?> — Semester <?= htmlspecialchars((string)$student['current_semester']) ?> | Roll No: <?= htmlspecialchars($student['roll_number']) ?></p>

<!-- Summary Cards -->
<div class="row g-4 mt-1">
  <div class="col-md-3">
    <div class="card p-3 stat-card">
      <h6>CGPA</h6>
      <h3><?= $cgpa > 0 ? number_format($cgpa, 2) : '—' ?></h3>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3 stat-card">
      <h6>Overall Attendance</h6>
      <h3 class="<?= $attendancePct < 75 ? 'text-danger' : 'text-success' ?>"><?= $att['total'] > 0 ? $attendancePct . '%' : '—' ?></h3>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3 stat-card">
      <h6>Performance</h6>
      <h5 class="mt-1"><span class="badge <?= $predBadge ?>"><?= $prediction ?></span></h5>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3 stat-card">
      <h6>Backlogs</h6>
      <h3 class="<?= $backlogCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $backlogCount ?></h3>
    </div>
  </div>
</div>

<!-- Eligibility -->
<div class="row g-3 mt-2">
  <div class="col-md-6">
    <div class="card p-3 <?= $placementEligible ? 'border-success' : 'border-danger' ?>">
      <h6>Placement Eligibility</h6>
      <p class="mb-0">
        <?php if ($placementEligible): ?>
          <span class="badge bg-success fs-6">✓ Eligible</span>
          <span class="text-muted small ms-2">CGPA ≥ 7 &amp; No backlogs</span>
        <?php else: ?>
          <span class="badge bg-danger fs-6">✗ Not Eligible</span>
          <span class="text-muted small ms-2">Need CGPA ≥ 7 &amp; zero backlogs (Current: <?= number_format($cgpa,2) ?> CGPA, <?= $backlogCount ?> backlog(s))</span>
        <?php endif; ?>
      </p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3 <?= $scholarshipEligible ? 'border-success' : 'border-danger' ?>">
      <h6>Scholarship Eligibility</h6>
      <p class="mb-0">
        <?php if ($scholarshipEligible): ?>
          <span class="badge bg-success fs-6">✓ Eligible</span>
          <span class="text-muted small ms-2">CGPA ≥ 8 &amp; Attendance ≥ 80%</span>
        <?php else: ?>
          <span class="badge bg-danger fs-6">✗ Not Eligible</span>
          <span class="text-muted small ms-2">Need CGPA ≥ 8 &amp; Attendance ≥ 80% (Current: <?= number_format($cgpa,2) ?>, <?= $attendancePct ?>%)</span>
        <?php endif; ?>
      </p>
    </div>
  </div>
</div>

<!-- SGPA History -->
<?php if (!empty($gpaHistory)): ?>
<div class="card mt-4">
  <div class="card-body">
    <h5>SGPA / CGPA History</h5>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Semester</th><th>Academic Year</th><th>SGPA</th><th>CGPA</th></tr></thead>
        <tbody>
          <?php foreach ($gpaHistory as $g): ?>
          <tr>
            <td><?= htmlspecialchars((string)$g['semester']) ?></td>
            <td><?= htmlspecialchars($g['academic_year']) ?></td>
            <td><?= number_format((float)$g['sgpa'], 2) ?></td>
            <td><?= number_format((float)$g['cgpa'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Marks -->
<?php if (!empty($marksList)): ?>
<div class="card mt-4">
  <div class="card-body">
    <h5>Marks</h5>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Course</th>
            <th>Semester</th>
            <th>CAT 1</th>
            <th>CAT 2</th>
            <th>Assignment</th>
            <th>Final</th>
            <th>Total</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($marksList as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['course_code'] . ' — ' . $m['course_name']) ?></td>
            <td><?= htmlspecialchars((string)$m['semester']) ?></td>
            <td><?= htmlspecialchars((string)($m['cat1'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['cat2'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['assignment'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['final_exam'] ?? '—')) ?></td>
            <td><strong><?= htmlspecialchars((string)($m['total'] ?? '—')) ?></strong></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['grade'] ?? '—') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
