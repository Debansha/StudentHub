<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: list.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM courses WHERE course_id = ?');
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) { header('Location: list.php'); exit; }

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name   = trim($_POST['course_name'] ?? '');
    $credits       = trim($_POST['credits'] ?? '');
    $semester      = trim($_POST['semester'] ?? '');
    $department_id = $_POST['department_id'] ?: null;

    if ($course_name === '') $errors[] = 'Course name is required.';
    if ($credits === '' || (int)$credits <= 0) $errors[] = 'Credits must be a positive number.';
    if ($semester === '' || (int)$semester <= 0) $errors[] = 'Semester must be a positive number.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE courses SET course_name=?, credits=?, semester=?, department_id=? WHERE course_id=?'
        );
        $stmt->execute([$course_name, (int)$credits, (int)$semester, $department_id ?: null, $id]);
        header('Location: list.php?updated=1');
        exit;
    }
    $course = array_merge($course, compact('course_name', 'credits', 'semester', 'department_id'));
}

$base = '../../';
$pageTitle = 'Edit Course';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Edit Course</h2>
<p class="text-muted">Course code <strong><?= htmlspecialchars($course['course_code']) ?></strong> (not editable here)</p>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4" style="max-width: 600px;">
  <form method="POST">
    <div class="row g-3">
      <div class="col-md-12">
        <label class="form-label">Course Name *</label>
        <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($course['course_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Credits *</label>
        <input type="number" name="credits" min="1" max="10" class="form-control" value="<?= htmlspecialchars((string)$course['credits']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Semester *</label>
        <input type="number" name="semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)$course['semester']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>" <?= (string)($course['department_id'] ?? '') === (string)$d['department_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Update Course</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
