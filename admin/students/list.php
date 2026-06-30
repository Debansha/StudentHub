<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = "%$search%";
    $stmt = $pdo->prepare(
        "SELECT s.*, d.department_name FROM students s
         LEFT JOIN departments d ON s.department_id = d.department_id
         WHERE s.full_name LIKE ? OR s.roll_number LIKE ?
         ORDER BY s.full_name"
    );
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query(
        "SELECT s.*, d.department_name FROM students s
         LEFT JOIN departments d ON s.department_id = d.department_id
         ORDER BY s.full_name"
    );
}
$students = $stmt->fetchAll();

$base = '../../';
$pageTitle = 'Manage Students';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0">Manage Students</h2>
  <a href="add.php" class="btn btn-primary">+ Add Student</a>
</div>

<?php if (isset($_GET['added'])): ?>
  <div class="alert alert-success">Student added successfully. Default login password is their roll number.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Student updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success">Student deleted.</div>
<?php endif; ?>

<form method="GET" class="mb-3">
  <div class="input-group" style="max-width: 420px;">
    <input type="text" name="q" class="form-control" placeholder="Search by name or roll number" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
    <?php if ($search !== ''): ?>
      <a href="list.php" class="btn btn-outline-secondary">Clear</a>
    <?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Roll No</th>
          <th>Name</th>
          <th>Department</th>
          <th>Semester</th>
          <th>Phone</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['roll_number']) ?></td>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars((string)$s['current_semester']) ?></td>
          <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
          <td class="text-end">
            <a href="edit.php?id=<?= (int)$s['student_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="POST" action="delete.php" class="d-inline"
                  onsubmit="return confirm('Delete this student? This also removes their login and cannot be undone.');">
              <input type="hidden" name="student_id" value="<?= (int)$s['student_id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
