<?php
// admin/fees/balances.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Optional filter by semester
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';

$sql = "SELECT f.reg_no, s.full_name, f.semester, f.total_fees, f.paid_amount, f.balance
        FROM fee_balances f
        JOIN students s ON f.reg_no = s.reg_no
        WHERE f.balance > 0";
$params = [];
if ($semester) {
    $sql .= " AND f.semester = :semester";
    $params[':semester'] = $semester;
}
$sql .= " ORDER BY f.balance DESC, s.full_name";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$balances = $stmt->fetchAll();

// Calculate total outstanding
$total_outstanding = array_sum(array_column($balances, 'balance'));

include __DIR__ . '/../includes/header.php';
?>

<div class="card p-4">
    <h3>Outstanding Fee Balances</h3>
    <div class="mb-3">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <label class="form-label mb-0">Filter by Semester:</label>
            <input type="text" name="semester" class="form-control form-control-sm" placeholder="e.g., 2024/2025 Sem I" value="<?php echo htmlspecialchars($semester); ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="balances.php" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
    
    <?php if (empty($balances)): ?>
        <p>No outstanding balances found.</p>
    <?php else: ?>
        <p><strong>Total Outstanding Amount:</strong> TZS <?php echo number_format($total_outstanding, 2); ?></p>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr><th>Reg No</th><th>Student Name</th><th>Semester</th><th>Total Fees</th><th>Paid</th><th>Balance (Due)</th></tr>
                </thead>
                <tbody>
                <?php foreach ($balances as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['reg_no']); ?></td>
                        <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['semester']); ?></td>
                        <td>TZS <?php echo number_format($b['total_fees'], 2); ?></td>
                        <td>TZS <?php echo number_format($b['paid_amount'], 2); ?></td>
                        <td class="text-danger fw-bold">TZS <?php echo number_format($b['balance'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">Back to Fee List</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>