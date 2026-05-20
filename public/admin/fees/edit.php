<?php
// admin/fees/edit.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Fetch existing record
$stmt = $db->prepare("SELECT * FROM fee_balances WHERE fee_id = :id");
$stmt->execute(['id' => $id]);
$fee = $stmt->fetch();
if (!$fee) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_fees = floatval($_POST['total_fees']);
    $paid_amount = floatval($_POST['paid_amount']);
    
    if ($total_fees < 0 || $paid_amount < 0) {
        $error = 'Amounts cannot be negative.';
    } elseif ($paid_amount > $total_fees) {
        $error = 'Paid amount cannot exceed total fees.';
    } else {
        $update = $db->prepare("UPDATE fee_balances SET total_fees = :total, paid_amount = :paid WHERE fee_id = :id");
        if ($update->execute(['total' => $total_fees, 'paid' => $paid_amount, 'id' => $id])) {
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
    <h3>Edit Fee Record</h3>
    <p><strong>Student:</strong> <?php echo htmlspecialchars($fee['reg_no']); ?></p>
    <p><strong>Semester:</strong> <?php echo htmlspecialchars($fee['semester']); ?></p>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Total Fees (TZS):</label>
            <input type="number" name="total_fees" step="0.01" value="<?php echo $fee['total_fees']; ?>" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Paid Amount (TZS):</label>
            <input type="number" name="paid_amount" step="0.01" value="<?php echo $fee['paid_amount']; ?>" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Balance (auto-calculated):</label>
            <input type="text" value="TZS <?php echo number_format($fee['total_fees'] - $fee['paid_amount'], 2); ?>" disabled class="form-control bg-light">
        </div>
        <button type="submit" class="btn btn-warning">Update</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>