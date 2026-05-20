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

<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="bi bi-people me-2 text-primary"></i>Students</h5>
                <p class="card-text display-4 fw-bold text-primary"><?php echo $totalStudents; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="bi bi-cash-coin me-2 text-success"></i>Fees Collected</h5>
                <p class="card-text display-6 fw-bold text-success">TZS <?php echo number_format($totalFees, 0); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="bi bi-megaphone me-2 text-info"></i>Active Announcements</h5>
                <p class="card-text display-4 fw-bold text-info"><?php echo $activeAnnouncements; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="bi bi-telephone me-2 text-warning"></i>USSD Sessions Today</h5>
                <p class="card-text display-4 fw-bold text-warning"><?php echo $sessionsToday; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 shadow-sm">
    <div class="card-body">
        <h3 class="card-title"><i class="bi bi-lightning me-2"></i>Quick Actions</h3>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="students/add.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Student</a>
            <a href="results/upload.php" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Upload Results (CSV)</a>
            <a href="announcements/add.php" class="btn btn-primary"><i class="bi bi-megaphone me-1"></i>New Announcement</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>