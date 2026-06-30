<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();

$errors = [];
$old = [
    'course_code' => '', 'course_name' => '', 'credits' => 3,
    'semester' => 1, 'department_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) {
        $old[$key] = trim($_POST[$key] ?? $default);
    }

    if ($old['course_code'] === '') $errors[] = 'Course code is required.';
    if ($old['course_name'] === '') $errors[] = 'Course name is required.';
    if ($old['credits'] === '' || (int)$old['credits'] <= 0) $errors[] = 'Credits must be a positive number.';
    if ($old['semester'] === '' || (int)$old['semester'] <= 0) $errors[] = 'Semester must be a positive number.';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM courses WHERE course_code = ?');
        $check->execute([$old['course_code']]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'A course with this code already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO courses (course_code, course_name, credits, semester, department_id) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['course_code'],
            $old['course_name'],
            (int)$old['credits'],
            (int)$old['semester'],
            $old['department_id'] ?: null,
        ]);
        header('Location: list.php?added=1');
        exit;
    }
}

$base = '../../';
$pageTitle = 'Add Course';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Add Course</h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4" style="max-width: 600px;">
  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Course Code *</label>
        <input type="text" name="course_code" class="form-control" placeholder="e.g. CSE301" value="<?= htmlspecialchars($old['course_code']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Course Name *</label>
        <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($old['course_name']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Credits *</label>
        <input type="number" name="credits" min="1" max="10" class="form-control" value="<?= htmlspecialchars((string)$old['credits']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Semester *</label>
        <input type="number" name="semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)$old['semester']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>" <?= (string)$old['department_id'] === (string)$d['department_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save Course</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
