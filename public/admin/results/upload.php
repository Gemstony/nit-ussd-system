<?php
// admin/results/upload.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

$error = '';
$success = '';
$import_log = [];

// If the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // 1. Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error. Code: ' . $file['error'];
    }
    // 2. Check file type (extension + MIME)
    elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $error = 'Only CSV files are allowed.';
    }
    else {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            $error = 'Cannot read the uploaded file.';
        } else {
            // Read header row
            $header = fgetcsv($handle);
            if ($header === false) {
                $error = 'Empty file or invalid CSV format.';
            } else {
                // Expected columns
                $expected = ['reg_no', 'semester', 'course_code', 'course_name', 'grade', 'marks'];
                if (count($header) < 6) {
                    $error = 'CSV must have at least 6 columns: reg_no, semester, course_code, course_name, grade, marks';
                } else {
                    $row_num = 1;
                    $inserted = 0;
                    $updated = 0;
                    $failed = 0;
                    $errors = [];
                    
                    while (($data = fgetcsv($handle)) !== false) {
                        $row_num++;
                        // Normalise: if there are extra columns, ignore them; if fewer, error
                        if (count($data) < 6) {
                            $errors[] = "Row $row_num: insufficient columns (need 6).";
                            $failed++;
                            continue;
                        }
                        
                        list($reg_no, $semester, $course_code, $course_name, $grade, $marks) = array_map('trim', $data);
                        $marks = floatval($marks);
                        
                        $row_error = false;
                        
                        // Validate required fields
                        if (empty($reg_no)) {
                            $errors[] = "Row $row_num: registration number is empty.";
                            $row_error = true;
                        }
                        if (empty($semester)) {
                            $errors[] = "Row $row_num: semester is empty.";
                            $row_error = true;
                        }
                        if (empty($course_code)) {
                            $errors[] = "Row $row_num: course code is empty.";
                            $row_error = true;
                        }
                        if (empty($grade)) {
                            $errors[] = "Row $row_num: grade is empty.";
                            $row_error = true;
                        }
                        if ($marks < 0 || $marks > 100) {
                            $errors[] = "Row $row_num: marks must be between 0 and 100 (given: $marks).";
                            $row_error = true;
                        }
                        
                        // Check if student exists
                        if (!$row_error && !empty($reg_no)) {
                            $checkStmt = $db->prepare("SELECT reg_no FROM students WHERE reg_no = :reg_no");
                            $checkStmt->execute(['reg_no' => $reg_no]);
                            if (!$checkStmt->fetch()) {
                                $errors[] = "Row $row_num: student reg_no '$reg_no' does not exist.";
                                $row_error = true;
                            }
                        }
                        
                        if ($row_error) {
                            $failed++;
                            continue;
                        }
                        
                        // Now check if result already exists (duplicate)
                        $dupStmt = $db->prepare("SELECT result_id FROM results WHERE reg_no = :reg_no AND semester = :semester AND course_code = :course_code");
                        $dupStmt->execute(['reg_no' => $reg_no, 'semester' => $semester, 'course_code' => $course_code]);
                        $existing = $dupStmt->fetch();
                        
                        if ($existing) {
                            // Update
                            $upd = $db->prepare("UPDATE results SET course_name = :course_name, grade = :grade, marks = :marks WHERE reg_no = :reg_no AND semester = :semester AND course_code = :course_code");
                            $result = $upd->execute([
                                'course_name' => $course_name,
                                'grade' => $grade,
                                'marks' => $marks,
                                'reg_no' => $reg_no,
                                'semester' => $semester,
                                'course_code' => $course_code
                            ]);
                            if ($result) {
                                $updated++;
                            } else {
                                $errors[] = "Row $row_num: database update failed.";
                                $failed++;
                            }
                        } else {
                            // Insert
                            $ins = $db->prepare("INSERT INTO results (reg_no, semester, course_code, course_name, grade, marks) VALUES (:reg_no, :semester, :course_code, :course_name, :grade, :marks)");
                            $result = $ins->execute([
                                'reg_no' => $reg_no,
                                'semester' => $semester,
                                'course_code' => $course_code,
                                'course_name' => $course_name,
                                'grade' => $grade,
                                'marks' => $marks
                            ]);
                            if ($result) {
                                $inserted++;
                            } else {
                                $errors[] = "Row $row_num: database insert failed.";
                                $failed++;
                            }
                        }
                    }
                    fclose($handle);
                    
                    if ($inserted > 0 || $updated > 0) {
                        $success = "Import completed: $inserted new, $updated updated, $failed failed.";
                    } else {
                        $error = "No records were imported. Check errors below.";
                    }
                    $import_log = $errors;
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto;">
    <h3>📤 Bulk Upload Results (CSV)</h3>
    <p><a href="download_template.php" style="background: #17a2b8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;">📥 Download CSV Template</a></p>
    
    <div style="background: #e9ecef; padding: 10px; border-radius: 4px; margin: 15px 0;">
        <strong>CSV Format Requirements:</strong>
        <ul style="margin: 5px 0 0 20px;">
            <li>Columns (exactly 6): <code>reg_no, semester, course_code, course_name, grade, marks</code></li>
            <li><code>course_name</code> is optional but recommended</li>
            <li><code>reg_no</code> must exist in the <strong>students</strong> table</li>
            <li><code>marks</code> must be between 0 and 100</li>
            <li>If a result already exists (same reg_no + semester + course_code), it will be <strong>updated</strong> instead of duplicated</li>
        </ul>
    </div>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($import_log)): ?>
        <div style="background: #fff3cd; color: #856404; padding: 10px; margin-bottom: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
            <strong>Detailed Errors (<?php echo count($import_log); ?>):</strong><br>
            <?php foreach ($import_log as $log): ?>
                <?php echo htmlspecialchars($log); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label>Select CSV File:</label>
            <input type="file" name="csv_file" accept=".csv" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Upload & Import</button>
        <a href="index.php" style="margin-left: 10px;">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>