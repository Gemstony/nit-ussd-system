<?php
// admin/fees/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Pagination & filters
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search_reg = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : '';
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';

// Build WHERE clause
$where = [];
$params = [];
if ($search_reg) {
    $where[] = "s.reg_no LIKE :reg_no";
    $params[':reg_no'] = "%$search_reg%";
}
if ($semester) {
    $where[] = "f.semester = :semester";
    $params[':semester'] = $semester;
}
$whereSQL = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Count total
$countSQL = "SELECT COUNT(*) as total FROM fee_balances f 
             JOIN students s ON f.reg_no = s.reg_no 
             $whereSQL";
$stmt = $db->prepare($countSQL);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Fetch records
$sql = "SELECT f.fee_id, f.reg_no, f.semester, f.total_fees, f.paid_amount, f.balance, s.full_name
        FROM fee_balances f
        JOIN students s ON f.reg_no = s.reg_no
        $whereSQL
        ORDER BY f.semester DESC, s.reg_no
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$fees = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card p-4">
    <h3>Fee Balances Management</h3>
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="record_payment.php" class="btn btn-success"><i class="bi bi-cash-coin me-1"></i>Record Payment</a>
            <a href="balances.php" class="btn btn-info"><i class="bi bi-graph-up me-1"></i>Outstanding Balances</a>
        </div>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" name="reg_no" class="form-control form-control-sm" placeholder="Registration Number" value="<?php echo htmlspecialchars($search_reg); ?>">
            <input type="text" name="semester" class="form-control form-control-sm" placeholder="Semester (e.g., 2024/2025 Sem I)" value="<?php echo htmlspecialchars($semester); ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr><th>Reg No</th><th>Student Name</th><th>Semester</th><th>Total Fees</th><th>Paid</th><th>Balance</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($fees as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['reg_no']); ?></td>
                    <td><?php echo htmlspecialchars($f['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($f['semester']); ?></td>
                    <td>TZS <?php echo number_format($f['total_fees'], 2); ?></td>
                    <td>TZS <?php echo number_format($f['paid_amount'], 2); ?></td>
                    <td class="<?php echo $f['balance'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">TZS <?php echo number_format($f['balance'], 2); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $f['fee_id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <a href="delete.php?id=<?php echo $f['fee_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this fee record?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($fees)): ?>
                <tr><td colspan="7" class="text-center py-4">No fee records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&reg_no=<?php echo urlencode($search_reg); ?>&semester=<?php echo urlencode($semester); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>