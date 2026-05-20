<?php
// admin/students/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Pagination & search
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with search
if (!empty($search)) {
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM students WHERE reg_no LIKE :search OR full_name LIKE :search");
    $countStmt->execute(['search' => "%$search%"]);
    $total = $countStmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT student_id, reg_no, full_name, phone_number, created_at FROM students 
                          WHERE reg_no LIKE :search OR full_name LIKE :search 
                          ORDER BY reg_no LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', "%$search%");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $countStmt = $db->query("SELECT COUNT(*) as total FROM students");
    $total = $countStmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT student_id, reg_no, full_name, phone_number, created_at FROM students ORDER BY reg_no LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$students = $stmt->fetchAll();

$totalPages = ceil($total / $limit);

include __DIR__ . '/../includes/header.php';
?>

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h3>Student Management</h3>
    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <a href="add.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">➕ Add New Student</a>
        <form method="GET" style="display: flex;">
            <input type="text" name="search" placeholder="Search by reg_no or name" value="<?php echo htmlspecialchars($search); ?>" style="padding: 6px; width: 250px;">
            <button type="submit" style="background: #1a73e8; color: white; border: none; padding: 6px 12px; margin-left: 5px;">Search</button>
        </form>
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr><th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">Reg No</th>
                <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">Full Name</th>
                <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">Phone</th>
                <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">Registered</th>
                <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($s['reg_no']); ?></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($s['full_name']); ?></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($s['phone_number']); ?></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                    <a href="view.php?id=<?php echo $s['student_id']; ?>" style="color: #1a73e8;">View</a> |
                    <a href="edit.php?id=<?php echo $s['student_id']; ?>" style="color: #ffc107;">Edit</a> |
                    <a href="delete.php?id=<?php echo $s['student_id']; ?>" onclick="return confirm('Are you sure? This will also delete all linked fees, results, registrations.');" style="color: #e74c3c;">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
            <tr><td colspan="5" style="padding: 20px; text-align: center;">No students found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top: 20px; text-align: center;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" style="display: inline-block; padding: 5px 10px; margin: 0 2px; background: <?php echo $i == $page ? '#1a73e8' : '#eee'; ?>; color: <?php echo $i == $page ? 'white' : 'black'; ?>; text-decoration: none; border-radius: 3px;"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>