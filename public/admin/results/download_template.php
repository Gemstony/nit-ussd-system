<?php
// admin/results/download_template.php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="results_template.csv"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, ['reg_no', 'semester', 'course_code', 'course_name', 'grade', 'marks']);

// Write two example rows
fputcsv($output, ['NIT/2022/1234', '2024/2025 Sem I', 'BIT101', 'Introduction to Programming', 'A', '85.5']);
fputcsv($output, ['NIT/2022/5678', '2024/2025 Sem I', 'BIT102', 'Database Systems', 'B+', '72.0']);

fclose($output);
exit;