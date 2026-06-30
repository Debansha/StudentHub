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

$message = '';
$error   = '';
$selectedCourse = $_POST['course_id'] ?? $_GET['course_id'] ?? ($courses[0]['course_id'] ?? null);

// Get academic_year/semester for selected course assignment
$courseInfo = null;
foreach ($courses as $c) {
    if ((string)$c['course_id'] === (string)$selectedCourse) {
        $courseInfo = $c;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    if (!$selectedCourse || !$courseInfo) {
        $error = 'Please select a valid course.';
    } else {
        $marksData = $_POST['marks'] ?? [];  // keyed by student_id => [cat1, cat2, assignment, final_exam]

        $upsert = $pdo->prepare(
            'INSERT INTO marks (student_id, course_id, academic_year, semester, cat1, cat2, assignment, final_exam)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               cat1 = VALUES(cat1), cat2 = VALUES(cat2),
               assignment = VALUES(assignment), final_exam = VALUES(final_exam)'
        );

        foreach ($marksData as $studentId => $m) {
            $cat1      = min(max((float)($m['cat1'] ?? 0), 0), 30);
            $cat2      = min(max((float)($m['cat2'] ?? 0), 0), 30);
            $assign    = min(max((float)($m['assignment'] ?? 0), 0), 10);
            $final     = min(max((float)($m['final_exam'] ?? 0), 0), 75);
            $upsert->execute([
                $studentId, $selectedCourse,
                $courseInfo['academic_year'], $courseInfo['semester'],
                $cat1, $cat2, $assign, $final
            ]);
        }
        $message = 'Marks saved successfully.';
    }
}

// Fetch students + existing marks for selected course
$students = [];
if ($selectedCourse) {
    $stmt = $pdo->prepare(
        'SELECT s.student_id, s.roll_number, s.full_name,
                m.cat1, m.cat2, m.assignment, m.final_exam, m.total
         FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         LEFT JOIN marks m ON m.student_id = s.student_id AND m.course_id = e.course_id
         WHERE e.course_id = ?
         ORDER BY s.roll_number'
    );
    $stmt->execute([$selectedCourse]);
    $students = $stmt->fetchAll();
}

$base = '../../';
$pageTitle = 'Upload Marks';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Upload Marks</h2>
<p class="text-muted">Max marks: CAT1 = 30, CAT2 = 30, Assignment = 10, Final Exam = 75</p>

<?php if ($message): ?>
  <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
  <div class="mb-4" style="max-width:400px;">
    <label class="form-label">Course</label>
    <select name="course_id" class="form-select" onchange="this.form.submit()">
      <?php foreach ($courses as $c): ?>
        <option value="<?= $c['course_id'] ?>" <?= (string)$selectedCourse === (string)$c['course_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (!empty($students)): ?>
    <div class="card mb-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Name</th>
              <th>CAT 1 <small class="text-muted">/30</small></th>
              <th>CAT 2 <small class="text-muted">/30</small></th>
              <th>Assignment <small class="text-muted">/10</small></th>
              <th>Final Exam <small class="text-muted">/75</small></th>
              <th>Total <small class="text-muted">/145</small></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['roll_number']) ?></td>
              <td><?= htmlspecialchars($s['full_name']) ?></td>
              <td>
                <input type="number" name="marks[<?= $s['student_id'] ?>][cat1]"
                       class="form-control form-control-sm mark-input" style="width:75px;"
                       min="0" max="30" step="0.5" value="<?= htmlspecialchars((string)($s['cat1'] ?? '')) ?>">
              </td>
              <td>
                <input type="number" name="marks[<?= $s['student_id'] ?>][cat2]"
                       class="form-control form-control-sm mark-input" style="width:75px;"
                       min="0" max="30" step="0.5" value="<?= htmlspecialchars((string)($s['cat2'] ?? '')) ?>">
              </td>
              <td>
                <input type="number" name="marks[<?= $s['student_id'] ?>][assignment]"
                       class="form-control form-control-sm mark-input" style="width:75px;"
                       min="0" max="10" step="0.5" value="<?= htmlspecialchars((string)($s['assignment'] ?? '')) ?>">
              </td>
              <td>
                <input type="number" name="marks[<?= $s['student_id'] ?>][final_exam]"
                       class="form-control form-control-sm mark-input" style="width:75px;"
                       min="0" max="75" step="0.5" value="<?= htmlspecialchars((string)($s['final_exam'] ?? '')) ?>">
              </td>
              <td class="total-cell fw-bold">
                <?= $s['total'] !== null ? htmlspecialchars((string)$s['total']) : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <button type="submit" name="save_marks" value="1" class="btn btn-primary">Save Marks</button>
  <?php elseif ($selectedCourse): ?>
    <div class="alert alert-warning">No students enrolled in this course yet.</div>
  <?php endif; ?>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
