<?php
// admin/registrations/add.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no']);
    $semester = trim($_POST['semester']);
    $course_code = trim($_POST['course_code']);
    $registration_date = trim($_POST['registration_date']);
    $status = trim($_POST['status']);

    if (empty($reg_no) || empty($semester) || empty($course_code) || empty($registration_date) || empty($status)) {
        $error = 'All fields are required.';
    } elseif (!in_array($status, ['registered', 'dropped', 'completed'])) {
        $error = 'Invalid status.';
    } else {
        $studentCheck = $db->prepare("SELECT reg_no FROM students WHERE reg_no = :reg_no");
        $studentCheck->execute(['reg_no' => $reg_no]);
        if (!$studentCheck->fetch()) {
            $error = "Student with reg_no '$reg_no' does not exist.";
        } else {
            $dupCheck = $db->prepare("SELECT reg_id FROM course_registrations WHERE reg_no = :reg_no AND semester = :semester AND course_code = :course_code");
            $dupCheck->execute(['reg_no' => $reg_no, 'semester' => $semester, 'course_code' => $course_code]);
            if ($dupCheck->fetch()) {
                $error = "This student is already registered for the same course in the same semester.";
            } else {
                $insert = $db->prepare("INSERT INTO course_registrations (reg_no, semester, course_code, registration_date, status) VALUES (:reg_no, :semester, :course_code, :registration_date, :status)");
                if ($insert->execute(['reg_no' => $reg_no, 'semester' => $semester, 'course_code' => $course_code, 'registration_date' => $registration_date, 'status' => $status])) {
                    $success = "Student registered for course successfully.";
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Register Student for Course</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Registration Number</label>
                            <input type="text" name="reg_no" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <input type="text" name="semester" class="form-control" placeholder="e.g., 2024/2025 Sem I" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Code</label>
                            <input type="text" name="course_code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Registration Date</label>
                            <input type="date" name="registration_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="registered">Registered</option>
                                <option value="dropped">Dropped</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">Register</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>