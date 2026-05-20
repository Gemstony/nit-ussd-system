<?php
// admin/results/edit.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM results WHERE result_id = :id");
$stmt->execute(['id' => $id]);
$result = $stmt->fetch();
if (!$result) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = trim($_POST['course_name']);
    $grade = trim($_POST['grade']);
    $marks = floatval($_POST['marks']);
    
    if (empty($grade)) {
        $error = 'Grade is required.';
    } elseif ($marks < 0 || $marks > 100) {
        $error = 'Marks must be between 0 and 100.';
    } else {
        $update = $db->prepare("UPDATE results SET course_name = :course_name, grade = :grade, marks = :marks WHERE result_id = :id");
        if ($update->execute(['course_name' => $course_name, 'grade' => $grade, 'marks' => $marks, 'id' => $id])) {
            header('Location: index.php?msg=updated');
            exit;
        } else {
            $error = 'Update failed.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card p-4 mx-auto" style="max-width: 600px;">
    <h3>Edit Result</h3>
    <p><strong>Student:</strong> <?php echo htmlspecialchars($result['reg_no']); ?></p>
    <p><strong>Semester:</strong> <?php echo htmlspecialchars($result['semester']); ?></p>
    <p><strong>Course Code:</strong> <?php echo htmlspecialchars($result['course_code']); ?></p>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Course Name:</label>
            <input type="text" name="course_name" value="<?php echo htmlspecialchars($result['course_name']); ?>" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Grade:</label>
            <input type="text" name="grade" value="<?php echo htmlspecialchars($result['grade']); ?>" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Marks:</label>
            <input type="number" name="marks" step="0.01" value="<?php echo $result['marks']; ?>" required class="form-control">
        </div>
        <button type="submit" class="btn btn-warning">Update</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>