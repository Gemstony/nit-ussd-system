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

<div class="card p-4">
    <h3>Results Management</h3>
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <a href="add.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add Single Result</a>
            <a href="upload.php" class="btn btn-info"><i class="bi bi-upload me-1"></i>Upload CSV</a>
        </div>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" name="reg_no" class="form-control form-control-sm" placeholder="Reg No" value="<?php echo htmlspecialchars($reg_no); ?>">
            <input type="text" name="semester" class="form-control form-control-sm" placeholder="Semester" value="<?php echo htmlspecialchars($semester); ?>">
            <input type="text" name="course_code" class="form-control form-control-sm" placeholder="Course Code" value="<?php echo htmlspecialchars($course_code); ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr><th>Reg No</th><th>Student</th><th>Semester</th><th>Course Code</th><th>Course Name</th><th>Grade</th><th>Marks</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['reg_no']); ?></td>
                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['semester']); ?></td>
                    <td><?php echo htmlspecialchars($r['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($r['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['grade']); ?></td>
                    <td><?php echo htmlspecialchars($r['marks']); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $r['result_id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <a href="delete.php?id=<?php echo $r['result_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this result?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($results)): ?>
                <tr><td colspan="8" class="text-center py-4">No results found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>