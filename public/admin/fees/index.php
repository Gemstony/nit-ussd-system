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

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h3>Fee Balances Management</h3>
    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <a href="record_payment.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">💰 Record Payment</a>
        <a href="balances.php" style="background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">📊 Outstanding Balances</a>
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="reg_no" placeholder="Registration Number" value="<?php echo htmlspecialchars($search_reg); ?>" style="padding: 6px;">
            <input type="text" name="semester" placeholder="Semester (e.g., 2024/2025 Sem I)" value="<?php echo htmlspecialchars($semester); ?>" style="padding: 6px;">
            <button type="submit" style="background: #1a73e8; color: white; border: none; padding: 6px 12px;">Filter</button>
            <a href="index.php" style="background: #6c757d; color: white; padding: 6px 12px; text-decoration: none;">Reset</a>
        </form>
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr><th>Reg No</th><th>Student Name</th><th>Semester</th><th>Total Fees</th><th>Paid</th><th>Balance</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($fees as $f): ?>
            <tr>
                <td style="padding: 8px;"><?php echo htmlspecialchars($f['reg_no']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($f['full_name']); ?></td>
                <td style="padding: 8px;"><?php echo htmlspecialchars($f['semester']); ?></td>
                <td style="padding: 8px;">TZS <?php echo number_format($f['total_fees'], 2); ?></td>
                <td style="padding: 8px;">TZS <?php echo number_format($f['paid_amount'], 2); ?></td>
                <td style="padding: 8px; <?php echo $f['balance'] > 0 ? 'color:red; font-weight:bold;' : 'color:green;'; ?>">TZS <?php echo number_format($f['balance'], 2); ?></td>
                <td style="padding: 8px;">
                    <a href="edit.php?id=<?php echo $f['fee_id']; ?>">Edit</a> |
                    <a href="delete.php?id=<?php echo $f['fee_id']; ?>" onclick="return confirm('Delete this fee record?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($fees)): ?>
            <tr><td colspan="7" style="padding: 20px; text-align: center;">No fee records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top: 20px; text-align: center;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&reg_no=<?php echo urlencode($search_reg); ?>&semester=<?php echo urlencode($semester); ?>" style="padding: 5px 10px; margin: 2px; background: <?php echo $i == $page ? '#1a73e8' : '#eee'; ?>; color: <?php echo $i == $page ? 'white' : 'black'; ?>; text-decoration: none;"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>