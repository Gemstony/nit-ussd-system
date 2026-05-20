<?php
// admin/results/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$reg_no = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : '';
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';
$course_code = isset($_GET['course_code']) ? trim($_GET['course_code']) : '';

$where = [];
$params = [];
if ($reg_no) {
    $where[] = "r.reg_no LIKE :reg_no";
    $params[':reg_no'] = "%$reg_no%";
}
if ($semester) {
    $where[] = "r.semester = :semester";
    $params[':semester'] = $semester;
}
if ($course_code) {
    $where[] = "r.course_code LIKE :course_code";
    $params[':course_code'] = "%$course_code%";
}
$whereSQL = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM results r $whereSQL");
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Fetch data with student name
$sql = "SELECT r.result_id, r.reg_no, r.semester, r.course_code, r.course_name, r.grade, r.marks, r.uploaded_at, s.full_name
        FROM results r
        JOIN students s ON r.reg_no = s.reg_no
        $whereSQL
        ORDER BY r.semester DESC, r.reg_no, r.course_code
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h3>Results Management</h3>
    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <a href="add.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">➕ Add Single Result</a>
            <!-- <a href="upload.php" style="background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">📤 Upload CSV</a> -->
        </div>
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="reg_no" placeholder="Reg No" value="<?php echo htmlspecialchars($reg_no); ?>" style="padding: 6px;">
            <input type="text" name="semester" placeholder="Semester" value="<?php echo htmlspecialchars($semester); ?>" style="padding: 6px;">
            <input type="text" name="course_code" placeholder="Course Code" value="<?php echo htmlspecialchars($course_code); ?>" style="padding: 6px;">
            <button type="submit" style="background: #1a73e8; color: white; border: none; padding: 6px 12px;">Filter</button>
            <a href="index.php" style="background: #6c757d; color: white; padding: 6px 12px; text-decoration: none;">Reset</a>
        </form>
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr><th>Reg No</th><th>Student</th><th>Semester</th><th>Course Code</th><th>Course Name</th><th>Grade</th><th>Marks</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['reg_no']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['semester']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['course_code']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['course_name']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['grade']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($r['marks']); ?></td>
                <td style="padding: 8px;">
                    <a href="edit.php?id=<?php echo $r['result_id']; ?>">Edit</a> |
                    <a href="delete.php?id=<?php echo $r['result_id']; ?>" onclick="return confirm('Delete this result?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($results)): ?>
            <tr><td colspan="8" style="padding: 20px; text-align: center;">No results found.</td></tr>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>