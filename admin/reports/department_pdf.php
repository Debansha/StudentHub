<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', '../../login.php');
require_once __DIR__ . '/../../includes/gpa.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

$deptId = (int)($_GET['dept_id'] ?? 0);
if (!$deptId) { die('No department specified.'); }

$dept = $pdo->prepare('SELECT * FROM departments WHERE department_id = ?');
$dept->execute([$deptId]);
$dept = $dept->fetch();
if (!$dept) { die('Department not found.'); }

$students = $pdo->prepare(
    'SELECT s.roll_number, s.full_name, s.current_semester,
            sg.cgpa,
            (SELECT ROUND(SUM(a.status="Present")/NULLIF(COUNT(a.attendance_id),0)*100,1)
             FROM attendance a WHERE a.student_id = s.student_id) AS att_pct,
            (SELECT COUNT(*) FROM backlogs b WHERE b.student_id = s.student_id AND b.status="Pending") AS backlogs
     FROM students s
     LEFT JOIN (SELECT student_id, MAX(cgpa) cgpa FROM semester_gpa GROUP BY student_id) sg ON sg.student_id = s.student_id
     WHERE s.department_id = ?
     ORDER BY s.current_semester, s.roll_number'
);
$students->execute([$deptId]);
$students = $students->fetchAll();

class DeptPDF extends FPDF {
    public string $deptName = '';
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(27, 42, 74);
        $this->Cell(0, 10, 'Department Academic Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 6, $this->deptName . ' | Generated: ' . date('d M Y'), 0, 1, 'C');
        $this->SetDrawColor(200, 155, 60);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY() + 2, 200, $this->GetY() + 2);
        $this->Ln(6);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130, 130, 130);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new DeptPDF('L', 'mm', 'A4');
$pdf->deptName = $dept['department_name'] . ' (' . $dept['department_code'] . ')';
$pdf->SetMargins(12, 15, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(27, 42, 74);
$pdf->SetTextColor(255, 255, 255);
$headers = ['#', 'Roll No', 'Name', 'Sem', 'CGPA', 'Attendance', 'Backlogs', 'Placement', 'Scholarship'];
$widths  = [8, 28, 70, 12, 18, 24, 20, 28, 28];
foreach ($headers as $i => $h) {
    $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 8.5);
$fill = false;
$i = 1;
foreach ($students as $s) {
    $cgpa   = (float)($s['cgpa'] ?? 0);
    $att    = (float)($s['att_pct'] ?? 0);
    $blogs  = (int)$s['backlogs'];
    $place  = ($cgpa >= 7 && $blogs === 0) ? 'Eligible' : 'Not Eligible';
    $schol  = ($cgpa >= 8 && $att >= 80) ? 'Eligible' : 'Not Eligible';

    $pdf->SetFillColor($fill ? 240 : 255, $fill ? 243 : 255, $fill ? 248 : 255);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->Cell(8,  6, (string)$i++, 1, 0, 'C', true);
    $pdf->Cell(28, 6, $s['roll_number'], 1, 0, 'C', true);
    $pdf->Cell(70, 6, $s['full_name'], 1, 0, 'L', true);
    $pdf->Cell(12, 6, (string)$s['current_semester'], 1, 0, 'C', true);
    $pdf->Cell(18, 6, $cgpa > 0 ? number_format($cgpa, 2) : '—', 1, 0, 'C', true);
    $pdf->Cell(24, 6, $s['att_pct'] !== null ? $att . '%' : '—', 1, 0, 'C', true);
    $pdf->Cell(20, 6, (string)$blogs, 1, 0, 'C', true);
    $pdf->Cell(28, 6, $place, 1, 0, 'C', true);
    $pdf->Cell(28, 6, $schol, 1, 0, 'C', true);
    $pdf->Ln();
    $fill = !$fill;
}

$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(90, 90, 90);
$eligible   = count(array_filter($students, fn($s) => (float)($s['cgpa'] ?? 0) >= 7 && (int)$s['backlogs'] === 0));
$scholarship = count(array_filter($students, fn($s) => (float)($s['cgpa'] ?? 0) >= 8 && (float)($s['att_pct'] ?? 0) >= 80));
$pdf->Cell(0, 6, 'Total: ' . count($students) . ' students | Placement Eligible: ' . $eligible . ' | Scholarship Eligible: ' . $scholarship, 0, 1);

$filename = 'DeptReport_' . $dept['department_code'] . '_' . date('Ymd') . '.pdf';
$pdf->Output('D', $filename);
exit;
