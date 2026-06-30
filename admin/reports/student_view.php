<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/gpa.php';
requireRole('admin', '../../login.php');

// All students for the dropdown
$allStudents = $pdo->query(
    'SELECT s.student_id, s.roll_number, s.full_name FROM students s ORDER BY s.full_name'
)->fetchAll();

$selectedId = $_GET['student_id'] ?? ($allStudents[0]['student_id'] ?? null);
$student    = null;
$marksList  = [];
$gpaHistory = [];
$attSummary = [];

if ($selectedId) {
    $stmt = $pdo->prepare(
        'SELECT s.*, d.department_name, u.email
         FROM students s
         LEFT JOIN departments d ON d.department_id = s.department_id
         LEFT JOIN users u ON u.user_id = s.user_id
         WHERE s.student_id = ?'
    );
    $stmt->execute([$selectedId]);
    $student = $stmt->fetch();

    if ($student) {
        // Marks
        $stmt = $pdo->prepare(
            'SELECT m.*, c.course_name, c.course_code, c.credits
             FROM marks m JOIN courses c ON c.course_id = m.course_id
             WHERE m.student_id = ? ORDER BY m.semester, c.course_name'
        );
        $stmt->execute([$selectedId]);
        $marksList = $stmt->fetchAll();

        // GPA history
        $stmt = $pdo->prepare(
            'SELECT * FROM semester_gpa WHERE student_id = ? ORDER BY semester'
        );
        $stmt->execute([$selectedId]);
        $gpaHistory = $stmt->fetchAll();

        // Attendance per course
        $stmt = $pdo->prepare(
            'SELECT c.course_code, c.course_name,
                    COUNT(a.attendance_id) AS total,
                    SUM(a.status = "Present") AS present,
                    ROUND(SUM(a.status = "Present") / NULLIF(COUNT(a.attendance_id),0) * 100, 1) AS pct
             FROM enrollments e
             JOIN courses c ON c.course_id = e.course_id
             LEFT JOIN attendance a ON a.student_id = e.student_id AND a.course_id = e.course_id
             WHERE e.student_id = ?
             GROUP BY c.course_id ORDER BY c.course_name'
        );
        $stmt->execute([$selectedId]);
        $attSummary = $stmt->fetchAll();

        // Recalculate CGPA on the fly
        $cgpa = getLatestCGPA($pdo, (int)$selectedId);

        // Backlog count
        $backlogCount = (int)$pdo->prepare('SELECT COUNT(*) FROM backlogs WHERE student_id = ? AND status = "Pending"')
            ->execute([$selectedId]) ? $pdo->query("SELECT COUNT(*) FROM backlogs WHERE student_id = $selectedId AND status = 'Pending'")->fetchColumn() : 0;

        $backlogStmt = $pdo->prepare('SELECT COUNT(*) FROM backlogs WHERE student_id = ? AND status = "Pending"');
        $backlogStmt->execute([$selectedId]);
        $backlogCount = (int)$backlogStmt->fetchColumn();

        // Overall attendance
        $attStmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(status="Present") AS present FROM attendance WHERE student_id = ?');
        $attStmt->execute([$selectedId]);
        $attOverall = $attStmt->fetch();
        $overallAttPct = $attOverall['total'] > 0 ? round($attOverall['present'] / $attOverall['total'] * 100, 1) : 0;

        $placementEligible  = $cgpa >= 7 && $backlogCount === 0;
        $scholarshipEligible = $cgpa >= 8 && $overallAttPct >= 80;
    }
}

$base = '../../';
$pageTitle = 'Student Academic Record';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0">Student Academic Record</h2>
  <?php if ($selectedId && $student): ?>
    <a href="report_card_pdf.php?student_id=<?= (int)$selectedId ?>" class="btn btn-outline-danger" target="_blank">⬇ Download Report Card PDF</a>
  <?php endif; ?>
</div>

<form method="GET" class="mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label">Select Student</label>
      <select name="student_id" class="form-select" onchange="this.form.submit()">
        <?php foreach ($allStudents as $s): ?>
          <option value="<?= $s['student_id'] ?>" <?= (string)$selectedId === (string)$s['student_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['full_name'] . ' (' . $s['roll_number'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if ($student): ?>

<!-- Profile + Summary -->
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card p-4 h-100">
      <h5><?= htmlspecialchars($student['full_name']) ?></h5>
      <table class="table table-sm table-borderless mb-0 mt-2">
        <tr><td class="text-muted">Roll No</td><td><?= htmlspecialchars($student['roll_number']) ?></td></tr>
        <tr><td class="text-muted">Department</td><td><?= htmlspecialchars($student['department_name'] ?? '—') ?></td></tr>
        <tr><td class="text-muted">Semester</td><td><?= htmlspecialchars((string)$student['current_semester']) ?></td></tr>
        <tr><td class="text-muted">Batch</td><td><?= htmlspecialchars((string)($student['batch_year'] ?? '—')) ?></td></tr>
        <tr><td class="text-muted">Email</td><td><?= htmlspecialchars($student['email'] ?? '—') ?></td></tr>
        <tr><td class="text-muted">Phone</td><td><?= htmlspecialchars($student['phone'] ?? '—') ?></td></tr>
        <tr><td class="text-muted">Admitted</td><td><?= htmlspecialchars($student['admission_date'] ?? '—') ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-md-8">
    <div class="row g-3">
      <div class="col-6">
        <div class="card p-3 stat-card">
          <h6>CGPA</h6>
          <h3><?= isset($cgpa) && $cgpa > 0 ? number_format($cgpa, 2) : '—' ?></h3>
        </div>
      </div>
      <div class="col-6">
        <div class="card p-3 stat-card">
          <h6>Overall Attendance</h6>
          <h3 class="<?= ($overallAttPct ?? 0) < 75 ? 'text-danger' : 'text-success' ?>">
            <?= isset($overallAttPct) && ($attOverall['total'] ?? 0) > 0 ? $overallAttPct . '%' : '—' ?>
          </h3>
        </div>
      </div>
      <div class="col-6">
        <div class="card p-3 <?= ($placementEligible ?? false) ? 'border-success' : 'border-danger' ?>">
          <h6>Placement</h6>
          <span class="badge <?= ($placementEligible ?? false) ? 'bg-success' : 'bg-danger' ?>">
            <?= ($placementEligible ?? false) ? '✓ Eligible' : '✗ Not Eligible' ?>
          </span>
        </div>
      </div>
      <div class="col-6">
        <div class="card p-3 <?= ($scholarshipEligible ?? false) ? 'border-success' : 'border-danger' ?>">
          <h6>Scholarship</h6>
          <span class="badge <?= ($scholarshipEligible ?? false) ? 'bg-success' : 'bg-danger' ?>">
            <?= ($scholarshipEligible ?? false) ? '✓ Eligible' : '✗ Not Eligible' ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Marks -->
<?php if (!empty($marksList)): ?>
<div class="card mb-4">
  <div class="card-body">
    <h5>Marks</h5>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr><th>Sem</th><th>Course</th><th>Credits</th><th>CAT1</th><th>CAT2</th><th>Assign</th><th>Final</th><th>Total</th><th>Grade</th><th>Points</th></tr>
        </thead>
        <tbody>
          <?php foreach ($marksList as $m): ?>
          <tr>
            <td><?= htmlspecialchars((string)$m['semester']) ?></td>
            <td><?= htmlspecialchars($m['course_code'] . ' ' . $m['course_name']) ?></td>
            <td><?= htmlspecialchars((string)$m['credits']) ?></td>
            <td><?= htmlspecialchars((string)($m['cat1'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['cat2'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['assignment'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($m['final_exam'] ?? '—')) ?></td>
            <td><strong><?= htmlspecialchars((string)($m['total'] ?? '—')) ?></strong></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['grade'] ?? '—') ?></span></td>
            <td><?= htmlspecialchars((string)($m['grade_point'] ?? '—')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- GPA History -->
<?php if (!empty($gpaHistory)): ?>
<div class="card mb-4">
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

<!-- Attendance per course -->
<?php if (!empty($attSummary)): ?>
<div class="card mb-4">
  <div class="card-body">
    <h5>Attendance per Course</h5>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><th>Course</th><th>Classes Held</th><th>Present</th><th>Attendance %</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($attSummary as $a): ?>
          <?php $pct = (float)($a['pct'] ?? 0); $total = (int)$a['total']; ?>
          <tr class="<?= ($total > 0 && $pct < 75) ? 'table-danger' : '' ?>">
            <td><?= htmlspecialchars($a['course_code'] . ' — ' . $a['course_name']) ?></td>
            <td><?= $total ?></td>
            <td><?= (int)($a['present'] ?? 0) ?></td>
            <td><strong><?= $total > 0 ? $pct . '%' : '—' ?></strong></td>
            <td>
              <?php if ($total === 0): ?>
                <span class="badge bg-secondary">No data</span>
              <?php elseif ($pct >= 75): ?>
                <span class="badge bg-success">OK</span>
              <?php else: ?>
                <span class="badge bg-danger">Low</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php else: ?>
  <p class="text-muted">Select a student above to view their record.</p>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
