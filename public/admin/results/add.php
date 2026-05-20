<?php
// admin/results/add.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no']);
    $semester = trim($_POST['semester']);
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $grade = trim($_POST['grade']);
    $marks = floatval($_POST['marks']);
    
    // Validate
    if (empty($reg_no) || empty($semester) || empty($course_code) || empty($grade)) {
        $error = 'All fields except course_name are required.';
    } elseif ($marks < 0 || $marks > 100) {
        $error = 'Marks must be between 0 and 100.';
    } else {
        // Check if student exists
        $stmt = $db->prepare("SELECT reg_no FROM students WHERE reg_no = :reg_no");
        $stmt->execute(['reg_no' => $reg_no]);
        if (!$stmt->fetch()) {
            $error = "Student with reg_no '$reg_no' does not exist.";
        } else {
            // Check for duplicate (same student, semester, course)
            $dup = $db->prepare("SELECT result_id FROM results WHERE reg_no = :reg_no AND semester = :semester AND course_code = :course_code");
            $dup->execute(['reg_no' => $reg_no, 'semester' => $semester, 'course_code' => $course_code]);
            if ($dup->fetch()) {
                $error = "Result already exists for this student, semester, and course. Use Edit instead.";
            } else {
                $insert = $db->prepare("INSERT INTO results (reg_no, semester, course_code, course_name, grade, marks) VALUES (:reg_no, :semester, :course_code, :course_name, :grade, :marks)");
                if ($insert->execute(['reg_no' => $reg_no, 'semester' => $semester, 'course_code' => $course_code, 'course_name' => $course_name, 'grade' => $grade, 'marks' => $marks])) {
                    $success = "Result added successfully.";
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card p-4 mx-auto" style="max-width: 600px;">
    <h3>Add Single Result</h3>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Registration Number:</label>
            <input type="text" name="reg_no" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Semester:</label>
            <input type="text" name="semester" required placeholder="e.g., 2024/2025 Sem I" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Course Code:</label>
            <input type="text" name="course_code" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Course Name (optional):</label>
            <input type="text" name="course_name" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Grade:</label>
            <input type="text" name="grade" required placeholder="e.g., A, B+, C" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Marks (0-100):</label>
            <input type="number" name="marks" step="0.01" min="0" max="100" required class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save Result</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>