<?php
// admin/registrations/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$reg_no = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : '';
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = [];
$params = [];
if ($reg_no) {
    $where[] = "cr.reg_no LIKE :reg_no";
    $params[':reg_no'] = "%$reg_no%";
}
if ($semester) {
    $where[] = "cr.semester = :semester";
    $params[':semester'] = $semester;
}
if ($status && in_array($status, ['registered', 'dropped', 'completed'])) {
    $where[] = "cr.status = :status";
    $params[':status'] = $status;
}
$whereSQL = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM course_registrations cr $whereSQL");
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

$sql = "SELECT cr.reg_id, cr.reg_no, cr.semester, cr.course_code, cr.registration_date, cr.status, s.full_name
        FROM course_registrations cr
        JOIN students s ON cr.reg_no = s.reg_no
        $whereSQL
        ORDER BY cr.semester DESC, s.full_name, cr.course_code
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Course Registrations Management</h5>
            <a href="add.php" class="btn btn-success btn-sm">➕ Register Student for Course</a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="reg_no" class="form-control" placeholder="Reg No" value="<?php echo htmlspecialchars($reg_no); ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="semester" class="form-control" placeholder="Semester" value="<?php echo htmlspecialchars($semester); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="registered" <?php echo $status === 'registered' ? 'selected' : ''; ?>>Registered</option>
                        <option value="dropped" <?php echo $status === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>

            <!-- Responsive table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr><th>Reg No</th><th>Student Name</th><th>Semester</th><th>Course Code</th><th>Reg Date</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registrations)): ?>
                            <tr><td colspan="7" class="text-center">No course registrations found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['reg_no']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['course_code']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reg['registration_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $reg['status'] === 'registered' ? 'success' : ($reg['status'] === 'dropped' ? 'danger' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($reg['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $reg['reg_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $reg['reg_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Drop this registration?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Bootstrap) -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&reg_no=<?php echo urlencode($reg_no); ?>&semester=<?php echo urlencode($semester); ?>&status=<?php echo urlencode($status); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>