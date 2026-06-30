<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$search = trim($_GET['q'] ?? '');

$sql = "SELECT f.*, d.department_name,
               GROUP_CONCAT(DISTINCT c.course_name SEPARATOR ', ') AS assigned_courses
        FROM faculty f
        LEFT JOIN departments d ON f.department_id = d.department_id
        LEFT JOIN course_assignments ca ON ca.faculty_id = f.faculty_id
        LEFT JOIN courses c ON c.course_id = ca.course_id";

if ($search !== '') {
    $sql .= " WHERE f.full_name LIKE ? OR f.employee_code LIKE ?";
}
$sql .= " GROUP BY f.faculty_id ORDER BY f.full_name";

$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt->execute();
}
$facultyList = $stmt->fetchAll();

$base = '../../';
$pageTitle = 'Manage Faculty';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0">Manage Faculty</h2>
  <a href="add.php" class="btn btn-primary">+ Add Faculty</a>
</div>

<?php if (isset($_GET['added'])): ?>
  <div class="alert alert-success">Faculty added successfully. Default login password is their employee code.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Faculty details updated.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success">Faculty removed.</div>
<?php endif; ?>

<form method="GET" class="mb-3">
  <div class="input-group" style="max-width: 420px;">
    <input type="text" name="q" class="form-control" placeholder="Search by name or employee code" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
    <?php if ($search !== ''): ?><a href="list.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Employee Code</th>
          <th>Name</th>
          <th>Department</th>
          <th>Designation</th>
          <th>Assigned Courses</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($facultyList)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No faculty found.</td></tr>
        <?php endif; ?>
        <?php foreach ($facultyList as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['employee_code']) ?></td>
          <td><?= htmlspecialchars($f['full_name']) ?></td>
          <td><?= htmlspecialchars($f['department_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($f['designation'] ?? '—') ?></td>
          <td class="text-muted small"><?= htmlspecialchars($f['assigned_courses'] ?? '—') ?></td>
          <td class="text-end">
            <a href="edit.php?id=<?= (int)$f['faculty_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="POST" action="delete.php" class="d-inline"
                  onsubmit="return confirm('Remove this faculty member? This also removes their login and course assignments.');">
              <input type="hidden" name="faculty_id" value="<?= (int)$f['faculty_id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<p class="text-muted small mt-3">To assign a faculty member to a course, go to <a href="../courses/list.php">Manage Courses</a> and use "Assign" on the relevant course.</p>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
