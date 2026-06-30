<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Smart Student System') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg custom-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= $base ?>index.php">Smart Student System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto">
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/students/list.php">Students</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/faculty/list.php">Faculty</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/courses/list.php">Courses</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/enrollments/manage.php">Enrollments</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/reports/index.php">Reports</a></li>
        <?php elseif (($_SESSION['role'] ?? '') === 'faculty'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>faculty/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>faculty/attendance/mark.php">Mark Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>faculty/attendance/summary.php">Attendance Summary</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>faculty/marks/entry.php">Upload Marks</a></li>
        <?php elseif (($_SESSION['role'] ?? '') === 'student'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>student/dashboard.php">My Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="navbar-text me-3" style="color:rgba(255,255,255,0.85);">
          Signed in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
          (<?= htmlspecialchars($_SESSION['role']) ?>)
        </span>
        <a href="<?= $base ?>logout.php" class="btn btn-sm btn-outline-light">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container py-4">
