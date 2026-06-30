<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();

$errors = [];
$old = [
    'roll_number' => '', 'full_name' => '', 'email' => '', 'dob' => '',
    'gender' => '', 'department_id' => '', 'current_semester' => 1,
    'batch_year' => '', 'phone' => '', 'address' => '', 'admission_date' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) {
        $old[$key] = trim($_POST[$key] ?? $default);
    }

    if ($old['roll_number'] === '') $errors[] = 'Roll number is required.';
    if ($old['full_name'] === '')   $errors[] = 'Full name is required.';
    if ($old['email'] === '')      $errors[] = 'Email is required.';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM students WHERE roll_number = ?');
        $check->execute([$old['roll_number']]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'A student with this roll number already exists.';
        }

        $checkUser = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR username = ?');
        $checkUser->execute([$old['email'], $old['roll_number']]);
        if ($checkUser->fetchColumn() > 0) {
            $errors[] = 'A login already exists with this email or roll number.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Default login password = roll number. Student should change it after first login
            // (password-change feature can be added in a later phase).
            $hashedPassword = password_hash($old['roll_number'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, "student")');
            $stmt->execute([$old['roll_number'], $hashedPassword, $old['email']]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO students (user_id, roll_number, full_name, dob, gender, department_id, current_semester, batch_year, phone, address, admission_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $old['roll_number'],
                $old['full_name'],
                $old['dob'] ?: null,
                $old['gender'] ?: null,
                $old['department_id'] ?: null,
                $old['current_semester'] ?: 1,
                $old['batch_year'] ?: null,
                $old['phone'] ?: null,
                $old['address'] ?: null,
                $old['admission_date'] ?: null,
            ]);

            $pdo->commit();
            header('Location: list.php?added=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong: ' . $e->getMessage();
        }
    }
}

$base = '../../';
$pageTitle = 'Add Student';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Add Student</h2>
<p class="text-muted">Creates a student profile and a matching login (default password = roll number).</p>

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
        <label class="form-label">Roll Number *</label>
        <input type="text" name="roll_number" class="form-control" value="<?= htmlspecialchars($old['roll_number']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($old['full_name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($old['phone']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($old['dob']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">-- Select --</option>
          <option <?= $old['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
          <option <?= $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
          <option <?= $old['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Batch Year</label>
        <input type="number" name="batch_year" class="form-control" value="<?= htmlspecialchars($old['batch_year']) ?>" placeholder="2026">
      </div>
      <div class="col-md-6">
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
      <div class="col-md-6">
        <label class="form-label">Current Semester</label>
        <input type="number" name="current_semester" min="1" max="12" class="form-control" value="<?= htmlspecialchars((string)$old['current_semester']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Admission Date</label>
        <input type="date" name="admission_date" class="form-control" value="<?= htmlspecialchars($old['admission_date']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($old['address']) ?></textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save Student</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
