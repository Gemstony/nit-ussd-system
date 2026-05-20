<?php
// admin/students/add.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_no = trim($_POST['reg_no']);
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $pin = $_POST['pin'];
    
    // Validation
    if (empty($reg_no) || empty($full_name) || empty($phone_number) || empty($pin)) {
        $error = 'All fields are required.';
    } elseif (strlen($pin) < 4 || strlen($pin) > 6) {
        $error = 'PIN must be 4-6 digits.';
    } else {
        // Check if reg_no already exists
        $stmt = $db->prepare("SELECT reg_no FROM students WHERE reg_no = :reg_no");
        $stmt->execute(['reg_no' => $reg_no]);
        if ($stmt->fetch()) {
            $error = 'Registration number already exists.';
        } else {
            $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO students (reg_no, full_name, phone_number, pin_hash) VALUES (:reg_no, :full_name, :phone_number, :pin_hash)");
            if ($stmt->execute(['reg_no' => $reg_no, 'full_name' => $full_name, 'phone_number' => $phone_number, 'pin_hash' => $pin_hash])) {
                $success = 'Student added successfully.';
                // Clear form
                $reg_no = $full_name = $phone_number = '';
            } else {
                $error = 'Database error.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;">
    <h3>Add New Student</h3>
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label>Registration Number:</label>
            <input type="text" name="reg_no" value="<?php echo htmlspecialchars($reg_no ?? ''); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Full Name:</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Phone Number:</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number ?? ''); ?>" required placeholder="e.g., 255712345678" style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>PIN (4-6 digits):</label>
            <input type="password" name="pin" required style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>
        <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px;">Save Student</button>
        <a href="index.php" style="margin-left: 10px;">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>