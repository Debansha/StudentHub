<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');

$search = trim($_GET['q'] ?? '');

$sql = "SELECT c.*, d.department_name,
               COUNT(DISTINCT ca.faculty_id) AS faculty_count
        FROM courses c
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN course_assignments ca ON ca.course_id = c.course_id";

if ($search !== '') {
    $sql .= " WHERE c.course_name LIKE ? OR c.course_code LIKE ?";
}
$sql .= " GROUP BY c.course_id ORDER BY c.semester, c.course_name";

$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt->execute();
}
$courses = $stmt->fetchAll();

$base = '../../';
$pageTitle = 'Manage Courses';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0">Manage Courses</h2>
  <a href="add.php" class="btn btn-primary">+ Add Course</a>
</div>

<?php if (isset($_GET['added'])): ?>
  <div class="alert alert-success">Course added successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Course updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success">Course deleted.</div>
<?php endif; ?>

<form method="GET" class="mb-3">
  <div class="input-group" style="max-width: 420px;">
    <input type="text" name="q" class="form-control" placeholder="Search by course name or code" value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
    <?php if ($search !== ''): ?><a href="list.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Code</th>
          <th>Course Name</th>
          <th>Department</th>
          <th>Semester</th>
          <th>Credits</th>
          <th>Faculty Assigned</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($courses)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No courses found.</td></tr>
        <?php endif; ?>
        <?php foreach ($courses as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['course_code']) ?></td>
          <td><?= htmlspecialchars($c['course_name']) ?></td>
          <td><?= htmlspecialchars($c['department_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars((string)$c['semester']) ?></td>
          <td><?= htmlspecialchars((string)$c['credits']) ?></td>
          <td><span class="badge bg-secondary"><?= (int)$c['faculty_count'] ?></span></td>
          <td class="text-end">
            <a href="assign.php?course_id=<?= (int)$c['course_id'] ?>" class="btn btn-sm btn-outline-dark">Assign Faculty</a>
            <a href="edit.php?id=<?= (int)$c['course_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="POST" action="delete.php" class="d-inline"
                  onsubmit="return confirm('Delete this course? This also removes its faculty assignments, enrollments, attendance, and marks records.');">
              <input type="hidden" name="course_id" value="<?= (int)$c['course_id'] ?>">
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
