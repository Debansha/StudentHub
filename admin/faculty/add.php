<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$departments = $pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();

$errors = [];
$old = [
    'employee_code' => '', 'full_name' => '', 'email' => '',
    'department_id' => '', 'designation' => '', 'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) {
        $old[$key] = trim($_POST[$key] ?? $default);
    }

    if ($old['employee_code'] === '') $errors[] = 'Employee code is required.';
    if ($old['full_name'] === '')     $errors[] = 'Full name is required.';
    if ($old['email'] === '')         $errors[] = 'Email is required.';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM faculty WHERE employee_code = ?');
        $check->execute([$old['employee_code']]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'A faculty member with this employee code already exists.';
        }

        $checkUser = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR username = ?');
        $checkUser->execute([$old['email'], $old['employee_code']]);
        if ($checkUser->fetchColumn() > 0) {
            $errors[] = 'A login already exists with this email or employee code.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Default login password = employee code (same convention as students).
            $hashedPassword = password_hash($old['employee_code'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, "faculty")');
            $stmt->execute([$old['employee_code'], $hashedPassword, $old['email']]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO faculty (user_id, employee_code, full_name, department_id, designation, phone)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $old['employee_code'],
                $old['full_name'],
                $old['department_id'] ?: null,
                $old['designation'] ?: null,
                $old['phone'] ?: null,
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
$pageTitle = 'Add Faculty';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Add Faculty</h2>
<p class="text-muted">Creates a faculty profile and a matching login (default password = employee code).</p>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4" style="max-width: 720px;">
  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Employee Code *</label>
        <input type="text" name="employee_code" class="form-control" value="<?= htmlspecialchars($old['employee_code']) ?>" required>
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
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control" placeholder="e.g. Assistant Professor" value="<?= htmlspecialchars($old['designation']) ?>">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save Faculty</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
