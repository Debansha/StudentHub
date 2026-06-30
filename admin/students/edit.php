<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: list.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) { header('Location: list.php'); exit; }

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $dob              = $_POST['dob'] ?: null;
    $gender           = $_POST['gender'] ?: null;
    $department_id    = $_POST['department_id'] ?: null;
    $current_semester = $_POST['current_semester'] ?: 1;
    $batch_year       = $_POST['batch_year'] ?: null;
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $admission_date   = $_POST['admission_date'] ?: null;

    if ($full_name === '') $errors[] = 'Full name is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE students SET full_name=?, dob=?, gender=?, department_id=?, current_semester=?, batch_year=?, phone=?, address=?, admission_date=?
             WHERE student_id=?'
        );
        $stmt->execute([$full_name, $dob, $gender, $department_id ?: null, $current_semester, $batch_year ?: null, $phone, $address, $admission_date, $id]);
        header('Location: list.php?updated=1');
        exit;
    }
    // keep edited values on screen if validation failed
    $student = array_merge($student, compact('full_name','dob','gender','department_id','current_semester','batch_year','phone','address','admission_date'));
}

$base = '../../';
$pageTitle = 'Edit Student';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Edit Student</h2>
<p class="text-muted">Roll number <strong><?= htmlspecialchars($student['roll_number']) ?></strong> (login username — not editable here)</p>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card p-4" style="max-width: 720px;">
  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($student['dob'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">-- Select --</option>
          <option <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
          <option <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
          <option <?= ($student['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Batch Year</label>
        <input type="number" name="batch_year" class="form-control" value="<?= htmlspecialchars((string)($student['batch_year'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>" <?= (string)($student['department_id'] ?? '') === (string)$d['department_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Current Semester</label>
        <input type="number" name="current_semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)$student['current_semester']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Admission Date</label>
        <input type="date" name="admission_date" class="form-control" value="<?= htmlspecialchars($student['admission_date'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Update Student</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
