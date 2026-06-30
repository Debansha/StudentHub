<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$courses = $pdo->query('SELECT * FROM courses ORDER BY semester, course_name')->fetchAll();
$message = '';
$error   = '';

$selectedCourse = $_GET['course_id'] ?? ($courses[0]['course_id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $studentId    = $_POST['student_id'] ?? null;
    $courseId     = $_POST['course_id'] ?? null;
    $academicYear = trim($_POST['academic_year'] ?? '');
    $semester     = $_POST['semester'] ?? null;

    if (!$studentId || !$courseId || !$academicYear || !$semester) {
        $error = 'All fields are required.';
    } else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id=? AND course_id=? AND academic_year=? AND semester=?');
        $check->execute([$studentId, $courseId, $academicYear, $semester]);
        if ($check->fetchColumn() > 0) {
            $error = 'This student is already enrolled in this course for that term.';
        } else {
            $pdo->prepare('INSERT INTO enrollments (student_id, course_id, academic_year, semester) VALUES (?,?,?,?)')
                ->execute([$studentId, $courseId, $academicYear, $semester]);
            $message = 'Student enrolled successfully.';
            $selectedCourse = $courseId;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unenroll'])) {
    $pdo->prepare('DELETE FROM enrollments WHERE enrollment_id = ?')->execute([$_POST['enrollment_id']]);
    $message = 'Enrollment removed.';
    $selectedCourse = $_POST['course_id'];
}

$students = $pdo->query('SELECT student_id, roll_number, full_name FROM students ORDER BY full_name')->fetchAll();

$enrolled = [];
if ($selectedCourse) {
    $stmt = $pdo->prepare(
        'SELECT e.*, s.full_name, s.roll_number FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         WHERE e.course_id = ? ORDER BY e.academic_year DESC, s.roll_number'
    );
    $stmt->execute([$selectedCourse]);
    $enrolled = $stmt->fetchAll();
}

// Get semester for selected course
$courseInfo = null;
foreach ($courses as $c) {
    if ((string)$c['course_id'] === (string)$selectedCourse) { $courseInfo = $c; break; }
}

$base = '../../';
$pageTitle = 'Manage Enrollments';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Manage Enrollments</h2>
<p class="text-muted">Enroll students into courses so faculty can mark their attendance and enter marks.</p>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card p-4">
      <h5 class="mb-3">Enroll a Student</h5>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Course *</label>
          <select name="course_id" class="form-select" required onchange="this.form.submit()">
            <option value="">-- Select Course --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['course_id'] ?>" <?= (string)$selectedCourse === (string)$c['course_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Student *</label>
          <select name="student_id" class="form-select" required>
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['student_id'] ?>"><?= htmlspecialchars($s['full_name'] . ' (' . $s['roll_number'] . ')') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Academic Year *</label>
          <input type="text" name="academic_year" class="form-control" placeholder="2025-2026" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Semester *</label>
          <input type="number" name="semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)($courseInfo['semester'] ?? 1)) ?>" required>
        </div>
        <button type="submit" name="enroll" value="1" class="btn btn-primary w-100">Enroll</button>
      </form>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr><th>Roll No</th><th>Name</th><th>Academic Year</th><th>Sem</th><th class="text-end">Action</th></tr>
          </thead>
          <tbody>
            <?php if (empty($enrolled)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No students enrolled in this course yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($enrolled as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['roll_number']) ?></td>
              <td><?= htmlspecialchars($e['full_name']) ?></td>
              <td><?= htmlspecialchars($e['academic_year']) ?></td>
              <td><?= htmlspecialchars((string)$e['semester']) ?></td>
              <td class="text-end">
                <form method="POST" onsubmit="return confirm('Remove enrollment?');">
                  <input type="hidden" name="enrollment_id" value="<?= $e['enrollment_id'] ?>">
                  <input type="hidden" name="course_id" value="<?= $selectedCourse ?>">
                  <button type="submit" name="unenroll" value="1" class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
