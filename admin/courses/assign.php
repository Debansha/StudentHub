<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$courseId = $_GET['course_id'] ?? null;
if (!$courseId) { header('Location: list.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM courses WHERE course_id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { header('Location: list.php'); exit; }

$errors = [];

// Handle new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $facultyId    = $_POST['faculty_id'] ?? '';
    $academicYear = trim($_POST['academic_year'] ?? '');
    $semester     = $_POST['semester'] ?? $course['semester'];

    if ($facultyId === '') $errors[] = 'Select a faculty member.';
    if ($academicYear === '') $errors[] = 'Academic year is required (e.g. 2025-2026).';

    if (empty($errors)) {
        $check = $pdo->prepare(
            'SELECT COUNT(*) FROM course_assignments WHERE course_id = ? AND faculty_id = ? AND academic_year = ? AND semester = ?'
        );
        $check->execute([$courseId, $facultyId, $academicYear, $semester]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'This faculty member is already assigned to this course for that term.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO course_assignments (course_id, faculty_id, academic_year, semester) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$courseId, $facultyId, $academicYear, $semester]);
            header("Location: assign.php?course_id=$courseId&assigned=1");
            exit;
        }
    }
}

// Handle unassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign'])) {
    $assignmentId = $_POST['assignment_id'] ?? null;
    if ($assignmentId) {
        $stmt = $pdo->prepare('DELETE FROM course_assignments WHERE assignment_id = ?');
        $stmt->execute([$assignmentId]);
    }
    header("Location: assign.php?course_id=$courseId&unassigned=1");
    exit;
}

$facultyOptions = $pdo->query('SELECT faculty_id, full_name, employee_code FROM faculty ORDER BY full_name')->fetchAll();

$assignments = $pdo->prepare(
    'SELECT ca.*, f.full_name, f.employee_code
     FROM course_assignments ca
     JOIN faculty f ON f.faculty_id = ca.faculty_id
     WHERE ca.course_id = ?
     ORDER BY ca.academic_year DESC, ca.semester'
);
$assignments->execute([$courseId]);
$assignments = $assignments->fetchAll();

$base = '../../';
$pageTitle = 'Assign Faculty - ' . $course['course_name'];
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Assign Faculty</h2>
<p class="text-muted">
  <?= htmlspecialchars($course['course_code']) ?> — <?= htmlspecialchars($course['course_name']) ?>
  (Semester <?= htmlspecialchars((string)$course['semester']) ?>, <?= htmlspecialchars((string)$course['credits']) ?> credits)
</p>

<?php if (isset($_GET['assigned'])): ?>
  <div class="alert alert-success">Faculty assigned to this course.</div>
<?php endif; ?>
<?php if (isset($_GET['unassigned'])): ?>
  <div class="alert alert-success">Assignment removed.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-5">
    <div class="card p-4">
      <h5 class="mb-3">Add Assignment</h5>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Faculty *</label>
          <select name="faculty_id" class="form-select" required>
            <option value="">-- Select Faculty --</option>
            <?php foreach ($facultyOptions as $f): ?>
              <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['full_name']) ?> (<?= htmlspecialchars($f['employee_code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Academic Year *</label>
          <input type="text" name="academic_year" class="form-control" placeholder="2025-2026" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Semester *</label>
          <input type="number" name="semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)$course['semester']) ?>" required>
        </div>
        <button type="submit" name="assign" value="1" class="btn btn-primary w-100">Assign</button>
      </form>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Faculty</th>
              <th>Academic Year</th>
              <th>Semester</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($assignments)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No faculty assigned yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($assignments as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['full_name']) ?> <span class="text-muted small">(<?= htmlspecialchars($a['employee_code']) ?>)</span></td>
              <td><?= htmlspecialchars($a['academic_year']) ?></td>
              <td><?= htmlspecialchars((string)$a['semester']) ?></td>
              <td class="text-end">
                <form method="POST" onsubmit="return confirm('Remove this assignment?');">
                  <input type="hidden" name="assignment_id" value="<?= (int)$a['assignment_id'] ?>">
                  <button type="submit" name="unassign" value="1" class="btn btn-sm btn-outline-danger">Remove</button>
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

<a href="list.php" class="btn btn-outline-secondary mt-4">&larr; Back to Courses</a>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
