<?php
// admin/registrations/by_student.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$reg_no = isset($_GET['reg_no']) ? trim($_GET['reg_no']) : '';

if (!$reg_no) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM course_registrations WHERE reg_no = :reg_no ORDER BY semester DESC, course_code");
$stmt->execute(['reg_no' => $reg_no]);
$registrations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Course Registrations for <?php echo htmlspecialchars($reg_no); ?></h5>
        </div>
        <div class="card-body">
            <a href="index.php" class="btn btn-secondary btn-sm mb-3">&laquo; Back to all registrations</a>

            <?php if (empty($registrations)): ?>
                <div class="alert alert-info">No registrations found for this student.</div>
            <?php else: ?>
                <?php
                $grouped = [];
                foreach ($registrations as $reg) {
                    $grouped[$reg['semester']][] = $reg;
                }
                ?>
                <?php foreach ($grouped as $semester => $courses): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <strong><?php echo htmlspecialchars($semester); ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr><th>Course Code</th><th>Registration Date</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $c): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($c['registration_date'])); ?></td>
                                                <td><?php echo ucfirst($c['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>