<?php
// admin/registrations/by_student.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/Database.php';

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

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h3>Course Registrations for <?php echo htmlspecialchars($reg_no); ?></h3>
    <p><a href="index.php">&laquo; Back to all registrations</a></p>
    
    <?php if (empty($registrations)): ?>
        <p>No registrations found for this student.</p>
    <?php else: ?>
        <?php
        // Group by semester
        $grouped = [];
        foreach ($registrations as $reg) {
            $grouped[$reg['semester']][] = $reg;
        }
        ?>
        <?php foreach ($grouped as $semester => $courses): ?>
            <h4><?php echo htmlspecialchars($semester); ?></h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead><tr><th>Course Code</th><th>Reg Date</th><th>Status</th></tr></thead>
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
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>