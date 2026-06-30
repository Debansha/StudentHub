<?php
// ============================================================
// Grade + GPA / CGPA calculation functions.
// Include this wherever you need grade logic — student view,
// admin reports, faculty marks page.
//
// Grading scale (out of 145 total marks):
//   O  (10) >= 90%   = 130.5+
//   A+ (9)  >= 80%   = 116+
//   A  (8)  >= 70%   = 101.5+
//   B+ (7)  >= 60%   = 87+
//   B  (6)  >= 50%   = 72.5+
//   C  (5)  >= 40%   = 58+
//   F  (0)  < 40%    = below 58
// ============================================================

function getGradeAndPoint(float $total, float $maxMarks = 145.0): array {
    $pct = ($total / $maxMarks) * 100;

    if ($pct >= 90) return ['O',  10.0];
    if ($pct >= 80) return ['A+',  9.0];
    if ($pct >= 70) return ['A',   8.0];
    if ($pct >= 60) return ['B+',  7.0];
    if ($pct >= 50) return ['B',   6.0];
    if ($pct >= 40) return ['C',   5.0];
    return ['F', 0.0];
}

// Calculate and persist SGPA + CGPA for a student for a given semester.
// Should be called after marks are saved for a semester.
function calculateAndSaveGPA(PDO $pdo, int $studentId, int $semester, string $academicYear): float {
    // Fetch all marks for this student in this semester
    $stmt = $pdo->prepare(
        'SELECT m.total, c.credits
         FROM marks m
         JOIN courses c ON c.course_id = m.course_id
         WHERE m.student_id = ? AND m.semester = ? AND m.academic_year = ?
           AND m.total IS NOT NULL'
    );
    $stmt->execute([$studentId, $semester, $academicYear]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) return 0.0;

    $totalCredits   = 0;
    $weightedPoints = 0.0;

    foreach ($rows as $row) {
        [, $gradePoint] = getGradeAndPoint((float)$row['total']);
        $credits         = (int)$row['credits'];
        $totalCredits   += $credits;
        $weightedPoints += $gradePoint * $credits;
        // Update the grade/grade_point on the marks row too
        [$grade, $gp] = getGradeAndPoint((float)$row['total']);
        $pdo->prepare('UPDATE marks SET grade=?, grade_point=? WHERE student_id=? AND course_id=(
            SELECT course_id FROM courses WHERE credits=? LIMIT 1
        )'); // will do a proper update below
    }

    // Properly update grade + grade_point on each marks row
    $markRows = $pdo->prepare(
        'SELECT m.marks_id, m.total FROM marks m
         WHERE m.student_id = ? AND m.semester = ? AND m.academic_year = ?'
    );
    $markRows->execute([$studentId, $semester, $academicYear]);
    foreach ($markRows->fetchAll() as $m) {
        [$grade, $gp] = getGradeAndPoint((float)($m['total'] ?? 0));
        $pdo->prepare('UPDATE marks SET grade=?, grade_point=? WHERE marks_id=?')
            ->execute([$grade, $gp, $m['marks_id']]);
    }

    $sgpa = $totalCredits > 0 ? round($weightedPoints / $totalCredits, 2) : 0.0;

    // Calculate CGPA = weighted average across ALL semesters up to this one
    $allSems = $pdo->prepare(
        'SELECT m.total, c.credits
         FROM marks m
         JOIN courses c ON c.course_id = m.course_id
         WHERE m.student_id = ? AND m.total IS NOT NULL'
    );
    $allSems->execute([$studentId]);
    $allRows = $allSems->fetchAll();

    $allCredits = 0;
    $allPoints  = 0.0;
    foreach ($allRows as $row) {
        [, $gp] = getGradeAndPoint((float)$row['total']);
        $allCredits += (int)$row['credits'];
        $allPoints  += $gp * (int)$row['credits'];
    }
    $cgpa = $allCredits > 0 ? round($allPoints / $allCredits, 2) : 0.0;

    // Upsert into semester_gpa table
    $pdo->prepare(
        'INSERT INTO semester_gpa (student_id, semester, academic_year, sgpa, cgpa)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE sgpa = VALUES(sgpa), cgpa = VALUES(cgpa), calculated_at = NOW()'
    )->execute([$studentId, $semester, $academicYear, $sgpa, $cgpa]);

    return $cgpa;
}

// Returns latest CGPA for a student
function getLatestCGPA(PDO $pdo, int $studentId): float {
    $stmt = $pdo->prepare(
        'SELECT cgpa FROM semester_gpa WHERE student_id = ? ORDER BY calculated_at DESC LIMIT 1'
    );
    $stmt->execute([$studentId]);
    return (float)($stmt->fetchColumn() ?? 0);
}

// Performance prediction based on marks average + attendance %
function predictPerformance(float $marksAvgPct, float $attendancePct): string {
    $score = ($marksAvgPct * 0.7) + ($attendancePct * 0.3);

    if ($score >= 80) return 'Excellent';
    if ($score >= 65) return 'Good';
    if ($score >= 50) return 'Average';
    if ($score >= 35) return 'Needs Improvement';
    return 'At Risk';
}
