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

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h3>Outstanding Fee Balances</h3>
    <div style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <label>Filter by Semester:</label>
            <input type="text" name="semester" placeholder="e.g., 2024/2025 Sem I" value="<?php echo htmlspecialchars($semester); ?>" style="padding: 6px;">
            <button type="submit" style="background: #1a73e8; color: white; border: none; padding: 6px 12px;">Filter</button>
            <a href="balances.php" style="background: #6c757d; color: white; padding: 6px 12px; text-decoration: none;">Reset</a>
        </form>
    </div>
    
    <?php if (empty($balances)): ?>
        <p>No outstanding balances found.</p>
    <?php else: ?>
        <p><strong>Total Outstanding Amount:</strong> TZS <?php echo number_format($total_outstanding, 2); ?></p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr><th>Reg No</th><th>Student Name</th><th>Semester</th><th>Total Fees</th><th>Paid</th><th>Balance (Due)</th></tr>
            </thead>
            <tbody>
            <?php foreach ($balances as $b): ?>
                <tr>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($b['reg_no']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($b['full_name']); ?></td>
                    <td style="padding: 8px;"><?php echo htmlspecialchars($b['semester']); ?></td>
                    <td style="padding: 8px;">TZS <?php echo number_format($b['total_fees'], 2); ?></td>
                    <td style="padding: 8px;">TZS <?php echo number_format($b['paid_amount'], 2); ?></td>
                    <td style="padding: 8px; color: red; font-weight: bold;">TZS <?php echo number_format($b['balance'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <div style="margin-top: 20px;">
        <a href="index.php" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none;">Back to Fee List</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>