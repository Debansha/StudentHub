<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('faculty', '../../login.php');

$stmt = $pdo->prepare('SELECT * FROM faculty WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$faculty = $stmt->fetch();
if (!$faculty) { die('Faculty profile not found.'); }

$courses = $pdo->prepare(
    'SELECT DISTINCT c.course_id, c.course_code, c.course_name, ca.semester, ca.academic_year
     FROM course_assignments ca
     JOIN courses c ON c.course_id = ca.course_id
     WHERE ca.faculty_id = ?
     ORDER BY ca.semester, c.course_name'
);
$courses->execute([$faculty['faculty_id']]);
$courses = $courses->fetchAll();

$selectedCourse = $_GET['course_id'] ?? ($courses[0]['course_id'] ?? null);
$summary = [];

if ($selectedCourse) {
    $summary = $pdo->prepare(
        'SELECT s.student_id, s.roll_number, s.full_name,
                COUNT(a.attendance_id) AS total_classes,
                SUM(a.status = "Present") AS present_count,
                ROUND(SUM(a.status = "Present") / COUNT(a.attendance_id) * 100, 1) AS attendance_pct
         FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         LEFT JOIN attendance a ON a.student_id = s.student_id AND a.course_id = e.course_id
         WHERE e.course_id = ?
         GROUP BY s.student_id
         ORDER BY attendance_pct ASC'
    );
    $summary->execute([$selectedCourse]);
    $summary = $summary->fetchAll();
}

$base = '../../';
$pageTitle = 'Attendance Summary';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Attendance Summary</h2>

<form method="GET" class="mb-4">
  <div class="row g-2">
    <div class="col-md-5">
      <select name="course_id" class="form-select" onchange="this.form.submit()">
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['course_id'] ?>" <?= (string)$selectedCourse === (string)$c['course_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</form>

<?php if (!empty($summary)): ?>
  <?php $lowCount = count(array_filter($summary, fn($r) => $r['attendance_pct'] < 75 && $r['total_classes'] > 0)); ?>
  <?php if ($lowCount > 0): ?>
    <div class="alert alert-danger">
      ⚠️ <strong><?= $lowCount ?> student(s)</strong> have attendance below 75% in this course.
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>Roll No</th>
            <th>Name</th>
            <th>Classes Held</th>
            <th>Present</th>
            <th>Attendance %</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $row): ?>
          <?php
            $pct = (float)($row['attendance_pct'] ?? 0);
            $total = (int)$row['total_classes'];
            $badgeClass = $total === 0 ? 'bg-secondary' : ($pct >= 75 ? 'bg-success' : 'bg-danger');
            $label = $total === 0 ? 'No classes yet' : ($pct >= 75 ? 'OK' : 'Low Attendance');
          ?>
          <tr class="<?= ($total > 0 && $pct < 75) ? 'table-danger' : '' ?>">
            <td><?= htmlspecialchars($row['roll_number']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= $total ?></td>
            <td><?= (int)$row['present_count'] ?></td>
            <td><strong><?= $total > 0 ? $pct . '%' : '—' ?></strong></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $label ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <p class="text-muted">No data available. Either no course is selected or no students are enrolled.</p>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
