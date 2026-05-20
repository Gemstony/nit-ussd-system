<?php
// admin/index.php
require_once 'includes/auth.php'; // ensures login
require_once __DIR__ . '/../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Get total students
$stmt = $db->query("SELECT COUNT(*) as total FROM students");
$totalStudents = $stmt->fetch()['total'];

// Get total fees collected (sum of paid_amount)
$stmt = $db->query("SELECT SUM(paid_amount) as total FROM fee_balances");
$totalFees = $stmt->fetch()['total'] ?? 0;

// Get total active announcements
$stmt = $db->query("SELECT COUNT(*) as total FROM announcements WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
$activeAnnouncements = $stmt->fetch()['total'];

// Get USSD sessions today
$stmt = $db->prepare("SELECT COUNT(*) as total FROM ussd_sessions WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$sessionsToday = $stmt->fetch()['total'];

include 'includes/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3>👨‍🎓 Students</h3>
        <p style="font-size: 2em;"><?php echo $totalStudents; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3>💰 Fees Collected</h3>
        <p style="font-size: 2em;">TZS <?php echo number_format($totalFees, 0); ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3>📢 Active Announcements</h3>
        <p style="font-size: 2em;"><?php echo $activeAnnouncements; ?></p>
    </div>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h3>📞 USSD Sessions Today</h3>
        <p style="font-size: 2em;"><?php echo $sessionsToday; ?></p>
    </div>
</div>

<div style="margin-top: 30px; background: white; padding: 20px; border-radius: 8px;">
    <h3>Quick Actions</h3>
    <p>
        <a href="students/add.php" style="background: #1a73e8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">➕ Add Student</a>
        <a href="results/upload.php" style="background: #1a73e8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">📤 Upload Results (CSV)</a>
        <a href="announcements/add.php" style="background: #1a73e8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">📢 New Announcement</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>