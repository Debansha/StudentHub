<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!isLoggedIn()) { header('Location: ../../login.php'); exit; }
if (!in_array($_SESSION['role'], ['admin', 'faculty'])) { die('Access denied.'); }

require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) { die('No course specified.'); }

$course = $pdo->prepare('SELECT c.*, d.department_name FROM courses c LEFT JOIN departments d ON d.department_id = c.department_id WHERE c.course_id = ?');
$course->execute([$courseId]);
$course = $course->fetch();
if (!$course) { die('Course not found.'); }

$stmt = $pdo->prepare(
    'SELECT s.roll_number, s.full_name,
            COUNT(a.attendance_id) AS total,
            SUM(a.status = "Present") AS present,
            ROUND(SUM(a.status = "Present") / NULLIF(COUNT(a.attendance_id),0) * 100, 1) AS pct
     FROM enrollments e
     JOIN students s ON s.student_id = e.student_id
     LEFT JOIN attendance a ON a.student_id = s.student_id AND a.course_id = e.course_id
     WHERE e.course_id = ?
     GROUP BY s.student_id ORDER BY s.roll_number'
);
$stmt->execute([$courseId]);
$rows = $stmt->fetchAll();

class AttendancePDF extends FPDF {
    public string $courseTitle = '';
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(27, 42, 74);
        $this->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 6, $this->courseTitle, 0, 1, 'C');
        $this->SetDrawColor(200, 155, 60);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY() + 2, 200, $this->GetY() + 2);
        $this->Ln(6);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130, 130, 130);
        $this->Cell(0, 10, 'Generated on ' . date('d M Y H:i') . ' | Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new AttendancePDF('P', 'mm', 'A4');
$pdf->courseTitle = $course['course_code'] . ' — ' . $course['course_name'] . ' | Semester ' . $course['semester'];
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(27, 42, 74);
$pdf->SetTextColor(255, 255, 255);
foreach (['#', 'Roll No', 'Student Name', 'Classes Held', 'Present', 'Absent', 'Attendance %', 'Status'] as $h) {
    $w = match($h) {
        '#' => 8, 'Roll No' => 24, 'Student Name' => 60,
        'Classes Held' => 22, 'Present' => 18, 'Absent' => 18,
        'Attendance %' => 22, 'Status' => 20
    };
    $pdf->Cell($w, 7, $h, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
$fill = false;
$i = 1;
foreach ($rows as $r) {
    $total   = (int)$r['total'];
    $present = (int)($r['present'] ?? 0);
    $absent  = $total - $present;
    $pct     = (float)($r['pct'] ?? 0);
    $status  = $total === 0 ? 'No Data' : ($pct >= 75 ? 'OK' : 'LOW');

    $pdf->SetFillColor($fill ? 240 : 255, $fill ? 243 : 255, $fill ? 248 : 255);
    if ($total > 0 && $pct < 75) {
        $pdf->SetFillColor(255, 235, 235);
    }
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(8,  6, (string)$i++, 1, 0, 'C', true);
    $pdf->Cell(24, 6, $r['roll_number'], 1, 0, 'C', true);
    $pdf->Cell(60, 6, $r['full_name'], 1, 0, 'L', true);
    $pdf->Cell(22, 6, (string)$total, 1, 0, 'C', true);
    $pdf->Cell(18, 6, (string)$present, 1, 0, 'C', true);
    $pdf->Cell(18, 6, (string)$absent, 1, 0, 'C', true);
    $pdf->Cell(22, 6, $total > 0 ? $pct . '%' : '—', 1, 0, 'C', true);
    $pdf->Cell(20, 6, $status, 1, 0, 'C', true);
    $pdf->Ln();
    $fill = !$fill;
}

// Summary
$lowCount = count(array_filter($rows, fn($r) => (float)($r['pct'] ?? 100) < 75 && (int)$r['total'] > 0));
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(90, 90, 90);
$pdf->Cell(0, 6, 'Total Students: ' . count($rows) . '  |  Students with Low Attendance (<75%): ' . $lowCount, 0, 1);

$filename = 'Attendance_' . preg_replace('/[^a-zA-Z0-9]/', '_', $course['course_code']) . '_' . date('Ymd') . '.pdf';
$pdf->Output('D', $filename);
exit;
