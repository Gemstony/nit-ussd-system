<?php
// admin/fees/record_payment.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Get list of students for dropdown (optional) – but we'll use manual entry for simplicity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no']);
    $semester = trim($_POST['semester']);
    $amount = floatval($_POST['amount']);
    
    if (empty($reg_no) || empty($semester) || $amount <= 0) {
        $error = 'All fields are required and amount must be positive.';
    } else {
        // Check if fee record exists
        $stmt = $db->prepare("SELECT fee_id, paid_amount, total_fees FROM fee_balances WHERE reg_no = :reg_no AND semester = :semester");
        $stmt->execute(['reg_no' => $reg_no, 'semester' => $semester]);
        $fee = $stmt->fetch();
        
        if (!$fee) {
            $error = "No fee record found for reg_no '$reg_no' and semester '$semester'. Please create a fee record first via Edit page.";
        } else {
            $new_paid = $fee['paid_amount'] + $amount;
            if ($new_paid > $fee['total_fees']) {
                $error = "Payment exceeds total fees. Total: " . number_format($fee['total_fees'], 2) . ", Already paid: " . number_format($fee['paid_amount'], 2) . ", Attempted: $amount";
            } else {
                $update = $db->prepare("UPDATE fee_balances SET paid_amount = :paid WHERE fee_id = :id");
                if ($update->execute(['paid' => $new_paid, 'id' => $fee['fee_id']])) {
                    $success = "Payment of TZS " . number_format($amount, 2) . " recorded successfully. New paid amount: " . number_format($new_paid, 2);
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card p-4 mx-auto" style="max-width: 600px;">
    <h3>Record Fee Payment</h3>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Registration Number:</label>
            <input type="text" name="reg_no" required placeholder="e.g., NIT/2022/1234" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Semester:</label>
            <input type="text" name="semester" required placeholder="e.g., 2024/2025 Sem I" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Amount Paid (TZS):</label>
            <input type="number" name="amount" step="0.01" required class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Record Payment</button>
        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>