<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: list.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM faculty WHERE faculty_id = ?');
$stmt->execute([$id]);
$faculty = $stmt->fetch();

if (!$faculty) { header('Location: list.php'); exit; }

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name     = trim($_POST['full_name'] ?? '');
    $department_id = $_POST['department_id'] ?: null;
    $designation   = trim($_POST['designation'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');

    if ($full_name === '') $errors[] = 'Full name is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE faculty SET full_name=?, department_id=?, designation=?, phone=? WHERE faculty_id=?'
        );
        $stmt->execute([$full_name, $department_id ?: null, $designation, $phone, $id]);
        header('Location: list.php?updated=1');
        exit;
    }
    $faculty = array_merge($faculty, compact('full_name', 'department_id', 'designation', 'phone'));
}

$base = '../../';
$pageTitle = 'Edit Faculty';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Edit Faculty</h2>
<p class="text-muted">Employee code <strong><?= htmlspecialchars($faculty['employee_code']) ?></strong> (login username — not editable here)</p>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4" style="max-width: 720px;">
  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($faculty['full_name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($faculty['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>" <?= (string)($faculty['department_id'] ?? '') === (string)$d['department_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($faculty['designation'] ?? '') ?>">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Update Faculty</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
