<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('faculty', '../../login.php');

// Get the faculty record linked to this login
$stmt = $pdo->prepare('SELECT * FROM faculty WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$faculty = $stmt->fetch();
if (!$faculty) { die('Faculty profile not found for this login.'); }

// Courses assigned to this faculty
$courses = $pdo->prepare(
    'SELECT DISTINCT c.course_id, c.course_code, c.course_name, ca.academic_year, ca.semester
     FROM course_assignments ca
     JOIN courses c ON c.course_id = ca.course_id
     WHERE ca.faculty_id = ?
     ORDER BY ca.semester, c.course_name'
);
$courses->execute([$faculty['faculty_id']]);
$courses = $courses->fetchAll();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark'])) {
    $courseId = $_POST['course_id'] ?? null;
    $date     = $_POST['attendance_date'] ?? null;
    $statuses = $_POST['status'] ?? [];  // keyed by student_id

    if (!$courseId || !$date || empty($statuses)) {
        $error = 'Please select a course, date, and mark attendance for at least one student.';
    } else {
        $upsert = $pdo->prepare(
            'INSERT INTO attendance (student_id, course_id, attendance_date, status, marked_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)'
        );
        foreach ($statuses as $studentId => $status) {
            if (in_array($status, ['Present', 'Absent'])) {
                $upsert->execute([$studentId, $courseId, $date, $status, $faculty['faculty_id']]);
            }
        }
        $message = 'Attendance saved for ' . date('d M Y', strtotime($date)) . '.';
    }
}

// Selected course and date for the marking form
$selectedCourse = $_POST['course_id'] ?? $_GET['course_id'] ?? null;
$selectedDate   = $_POST['attendance_date'] ?? $_GET['date'] ?? date('Y-m-d');

$students = [];
$existingAttendance = [];

if ($selectedCourse) {
    // Students enrolled in this course
    $stmt = $pdo->prepare(
        'SELECT s.student_id, s.roll_number, s.full_name
         FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         WHERE e.course_id = ?
         ORDER BY s.roll_number'
    );
    $stmt->execute([$selectedCourse]);
    $students = $stmt->fetchAll();

    // Previously saved attendance for this course+date
    if ($selectedDate) {
        $stmt = $pdo->prepare(
            'SELECT student_id, status FROM attendance WHERE course_id = ? AND attendance_date = ?'
        );
        $stmt->execute([$selectedCourse, $selectedDate]);
        foreach ($stmt->fetchAll() as $row) {
            $existingAttendance[$row['student_id']] = $row['status'];
        }
    }
}

$base = '../../';
$pageTitle = 'Mark Attendance';
include __DIR__ . '/../../includes/header.php';
?>
<div class="accent-bar"></div>
<h2>Mark Attendance</h2>

<?php if ($message): ?>
  <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
  <div class="row g-3 mb-4">
    <div class="col-md-5">
      <label class="form-label">Course</label>
      <select name="course_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Select Course --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['course_id'] ?>" <?= (string)$selectedCourse === (string)$c['course_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name'] . ' (Sem ' . $c['semester'] . ', ' . $c['academic_year'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date</label>
      <input type="date" name="attendance_date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" max="<?= date('Y-m-d') ?>">
    </div>
  </div>

  <?php if ($selectedCourse && !empty($students)): ?>
    <div class="card mb-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Name</th>
              <th>
                Status
                <span class="ms-3 text-muted small fw-normal">
                  <button type="button" class="btn btn-sm btn-link p-0 text-success" onclick="markAll('Present')">All Present</button>
                  /
                  <button type="button" class="btn btn-sm btn-link p-0 text-danger" onclick="markAll('Absent')">All Absent</button>
                </span>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <?php $existing = $existingAttendance[$s['student_id']] ?? 'Present'; ?>
            <tr>
              <td><?= htmlspecialchars($s['roll_number']) ?></td>
              <td><?= htmlspecialchars($s['full_name']) ?></td>
              <td>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input attendance-radio" type="radio"
                           name="status[<?= $s['student_id'] ?>]"
                           value="Present" <?= $existing === 'Present' ? 'checked' : '' ?>
                           id="p_<?= $s['student_id'] ?>">
                    <label class="form-check-label text-success" for="p_<?= $s['student_id'] ?>">Present</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input attendance-radio" type="radio"
                           name="status[<?= $s['student_id'] ?>]"
                           value="Absent" <?= $existing === 'Absent' ? 'checked' : '' ?>
                           id="a_<?= $s['student_id'] ?>">
                    <label class="form-check-label text-danger" for="a_<?= $s['student_id'] ?>">Absent</label>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <input type="hidden" name="mark" value="1">
    <button type="submit" class="btn btn-primary">Save Attendance</button>

  <?php elseif ($selectedCourse && empty($students)): ?>
    <div class="alert alert-warning">No students enrolled in this course yet. Enroll students from the Admin panel first.</div>
  <?php endif; ?>
</form>

<script>
function markAll(status) {
    document.querySelectorAll('.attendance-radio').forEach(function(radio) {
        if (radio.value === status) radio.checked = true;
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
