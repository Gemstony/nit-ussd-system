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

<div style="background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;">
    <h3>Edit Result</h3>
    <p><strong>Student:</strong> <?php echo htmlspecialchars($result['reg_no']); ?></p>
    <p><strong>Semester:</strong> <?php echo htmlspecialchars($result['semester']); ?></p>
    <p><strong>Course Code:</strong> <?php echo htmlspecialchars($result['course_code']); ?></p>
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label>Course Name:</label>
            <input type="text" name="course_name" value="<?php echo htmlspecialchars($result['course_name']); ?>" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Grade:</label>
            <input type="text" name="grade" value="<?php echo htmlspecialchars($result['grade']); ?>" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Marks:</label>
            <input type="number" name="marks" step="0.01" value="<?php echo $result['marks']; ?>" required style="width: 100%; padding: 8px;">
        </div>
        <button type="submit" style="background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 4px;">Update</button>
        <a href="index.php" style="margin-left: 10px;">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>