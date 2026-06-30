<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/gpa.php';

// Both admin and student can access their own report card
if (!isLoggedIn()) { header('Location: ../../login.php'); exit; }

$requestedId = (int)($_GET['student_id'] ?? 0);

// Students can only download their own
if ($_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare('SELECT student_id FROM students WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $own = $stmt->fetchColumn();
    if ((int)$own !== $requestedId) { die('Access denied.'); }
} elseif ($_SESSION['role'] !== 'admin') {
    die('Access denied.');
}

// Load FPDF
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

// ── Fetch all data ─────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT s.*, d.department_name FROM students s
     LEFT JOIN departments d ON d.department_id = s.department_id
     WHERE s.student_id = ?'
);
$stmt->execute([$requestedId]);
$student = $stmt->fetch();
if (!$student) { die('Student not found.'); }

$marksList = $pdo->prepare(
    'SELECT m.*, c.course_name, c.course_code, c.credits
     FROM marks m JOIN courses c ON c.course_id = m.course_id
     WHERE m.student_id = ? ORDER BY m.semester, c.course_name'
);
$marksList->execute([$requestedId]);
$marksList = $marksList->fetchAll();

$gpaHistory = $pdo->prepare(
    'SELECT * FROM semester_gpa WHERE student_id = ? ORDER BY semester'
);
$gpaHistory->execute([$requestedId]);
$gpaHistory = $gpaHistory->fetchAll();

$attStmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(status="Present") AS present FROM attendance WHERE student_id = ?');
$attStmt->execute([$requestedId]);
$att = $attStmt->fetch();
$attPct = $att['total'] > 0 ? round($att['present'] / $att['total'] * 100, 1) : 0;

$cgpa = getLatestCGPA($pdo, $requestedId);

$backlogStmt = $pdo->prepare('SELECT COUNT(*) FROM backlogs WHERE student_id = ? AND status = "Pending"');
$backlogStmt->execute([$requestedId]);
$backlogCount = (int)$backlogStmt->fetchColumn();

$placementEligible  = $cgpa >= 7 && $backlogCount === 0;
$scholarshipEligible = $cgpa >= 8 && $attPct >= 80;

// ── Build PDF ──────────────────────────────────────────────
class ReportCard extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(27, 42, 74);
        $this->Cell(0, 10, 'Smart Student Record & Academic Analytics System', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 6, 'Official Student Report Card', 0, 1, 'C');
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
    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(27, 42, 74);
        $this->SetFillColor(240, 243, 248);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->Ln(2);
    }
    function KeyValue($key, $value, $w1 = 45) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(90, 90, 90);
        $this->Cell($w1, 6, $key . ':', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(30, 30, 30);
        $this->Cell(0, 6, $value, 0, 1);
    }
}

$pdf = new ReportCard('P', 'mm', 'A4');
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// ── Student Info ───────────────────────────────────────────
$pdf->SectionTitle('Student Information');
$pdf->KeyValue('Name',        $student['full_name']);
$pdf->KeyValue('Roll Number', $student['roll_number']);
$pdf->KeyValue('Department',  $student['department_name'] ?? 'N/A');
$pdf->KeyValue('Semester',    (string)$student['current_semester']);
$pdf->KeyValue('Batch Year',  (string)($student['batch_year'] ?? 'N/A'));
$pdf->KeyValue('Admission',   $student['admission_date'] ?? 'N/A');
$pdf->Ln(4);

// ── Academic Summary ───────────────────────────────────────
$pdf->SectionTitle('Academic Summary');
$pdf->KeyValue('CGPA',               $cgpa > 0 ? number_format($cgpa, 2) . ' / 10' : 'Not calculated yet');
$pdf->KeyValue('Overall Attendance', $att['total'] > 0 ? $attPct . '%' : 'No data');
$pdf->KeyValue('Active Backlogs',    (string)$backlogCount);
$pdf->KeyValue('Placement Eligible', $placementEligible ? 'Yes (CGPA >= 7, No backlogs)' : 'No');
$pdf->KeyValue('Scholarship Eligible', $scholarshipEligible ? 'Yes (CGPA >= 8, Attendance >= 80%)' : 'No');
$pdf->Ln(4);

// ── Marks Table ───────────────────────────────────────────
if (!empty($marksList)) {
    $pdf->SectionTitle('Marks Details');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(27, 42, 74);
    $pdf->SetTextColor(255, 255, 255);
    $headers = ['Sem', 'Code', 'Course Name', 'Cr', 'CAT1', 'CAT2', 'Assgn', 'Final', 'Total', 'Grade'];
    $widths  = [10,    18,     60,            10,   12,     12,     12,      12,      14,      14];
    foreach ($headers as $i => $h) {
        $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    $fill = false;
    foreach ($marksList as $m) {
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 243 : 255, $fill ? 248 : 255);
        $pdf->SetTextColor(30, 30, 30);
        $rowData = [
            (string)$m['semester'],
            $m['course_code'],
            $m['course_name'],
            (string)$m['credits'],
            (string)($m['cat1'] ?? '—'),
            (string)($m['cat2'] ?? '—'),
            (string)($m['assignment'] ?? '—'),
            (string)($m['final_exam'] ?? '—'),
            (string)($m['total'] ?? '—'),
            $m['grade'] ?? '—',
        ];
        foreach ($rowData as $i => $val) {
            $align = in_array($i, [2]) ? 'L' : 'C';
            $pdf->Cell($widths[$i], 6, $val, 1, 0, $align, true);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
    $pdf->Ln(4);
}

// ── SGPA/CGPA Table ───────────────────────────────────────
if (!empty($gpaHistory)) {
    $pdf->SectionTitle('SGPA / CGPA History');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(27, 42, 74);
    $pdf->SetTextColor(255, 255, 255);
    foreach (['Semester', 'Academic Year', 'SGPA', 'CGPA'] as $h) {
        $pdf->Cell(44, 7, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 9);
    $fill = false;
    foreach ($gpaHistory as $g) {
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 243 : 255, $fill ? 248 : 255);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(44, 6, 'Semester ' . $g['semester'], 1, 0, 'C', true);
        $pdf->Cell(44, 6, $g['academic_year'], 1, 0, 'C', true);
        $pdf->Cell(44, 6, number_format((float)$g['sgpa'], 2), 1, 0, 'C', true);
        $pdf->Cell(44, 6, number_format((float)$g['cgpa'], 2), 1, 0, 'C', true);
        $pdf->Ln();
        $fill = !$fill;
    }
}

// ── Output ────────────────────────────────────────────────
$filename = 'ReportCard_' . preg_replace('/[^a-zA-Z0-9]/', '_', $student['roll_number']) . '_' . date('Ymd') . '.pdf';
$pdf->Output('D', $filename);
exit;
