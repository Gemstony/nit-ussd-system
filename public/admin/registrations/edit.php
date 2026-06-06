<?php
// admin/registrations/edit.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM course_registrations WHERE reg_id = :id");
$stmt->execute(['id' => $id]);
$reg = $stmt->fetch();
if (!$reg) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = trim($_POST['course_code']);
    $registration_date = trim($_POST['registration_date']);
    $status = trim($_POST['status']);

    if (empty($course_code) || empty($registration_date) || empty($status)) {
        $error = 'All fields are required.';
    } elseif (!in_array($status, ['registered', 'dropped', 'completed'])) {
        $error = 'Invalid status.';
    } else {
        if ($course_code !== $reg['course_code']) {
            $dupCheck = $db->prepare("SELECT reg_id FROM course_registrations WHERE reg_no = :reg_no AND semester = :semester AND course_code = :course_code AND reg_id != :id");
            $dupCheck->execute(['reg_no' => $reg['reg_no'], 'semester' => $reg['semester'], 'course_code' => $course_code, 'id' => $id]);
            if ($dupCheck->fetch()) {
                $error = "This student is already registered for the same course in the same semester.";
            }
        }
        if (!$error) {
            $update = $db->prepare("UPDATE course_registrations SET course_code = :course_code, registration_date = :registration_date, status = :status WHERE reg_id = :id");
            if ($update->execute(['course_code' => $course_code, 'registration_date' => $registration_date, 'status' => $status, 'id' => $id])) {
                header('Location: index.php?msg=updated');
                exit;
            } else {
                $error = "Update failed.";
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
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Course Registration</h5>
                </div>
                <div class="card-body">
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($reg['reg_no']); ?></p>
                    <p><strong>Semester:</strong> <?php echo htmlspecialchars($reg['semester']); ?></p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Course Code</label>
                            <input type="text" name="course_code" class="form-control" value="<?php echo htmlspecialchars($reg['course_code']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Registration Date</label>
                            <input type="date" name="registration_date" class="form-control" value="<?php echo $reg['registration_date']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="registered" <?php echo $reg['status'] === 'registered' ? 'selected' : ''; ?>>Registered</option>
                                <option value="dropped" <?php echo $reg['status'] === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                                <option value="completed" <?php echo $reg['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-warning">Update</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>