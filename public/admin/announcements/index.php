<?php
// admin/announcements/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Filters
$audience = isset($_GET['audience']) ? $_GET['audience'] : '';
$active = isset($_GET['active']) ? $_GET['active'] : '';

$where = [];
$params = [];
if ($audience && in_array($audience, ['students', 'staff', 'all'])) {
    $where[] = "target_audience = :audience";
    $params[':audience'] = $audience;
}
if ($active !== '') {
    $where[] = "is_active = :active";
    $params[':active'] = (int)$active;
}
$whereSQL = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Fetch all announcements (no pagination needed unless many)
$sql = "SELECT * FROM announcements $whereSQL ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$announcements = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Announcements Management</h5>
            <a href="add.php" class="btn btn-success btn-sm">➕ New Announcement</a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="audience" class="form-select">
                        <option value="">All Audiences</option>
                        <option value="students" <?php echo $audience === 'students' ? 'selected' : ''; ?>>Students</option>
                        <option value="staff" <?php echo $audience === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="all" <?php echo $audience === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $active === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $active === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>

            <!-- Responsive table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Title</th><th>Audience</th><th>Status</th><th>Expiry Date</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr><td colspan="7" class="text-center">No announcements found.</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($announcements as $ann): ?>
                                <tr>
                                    <td><?php echo $ann['announcement_id']; ?></td>
                                    <td><?php echo htmlspecialchars($ann['title']); ?></td>
                                    <td>
                                        <?php 
                                            $badge = $ann['target_audience'] === 'students' ? 'primary' : ($ann['target_audience'] === 'staff' ? 'info' : 'secondary');
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($ann['target_audience']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($ann['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $ann['expires_at'] ? date('d/m/Y', strtotime($ann['expires_at'])) : 'Never'; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($ann['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $ann['announcement_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="toggle_status.php?id=<?php echo $ann['announcement_id']; ?>" class="btn btn-sm <?php echo $ann['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>">
                                            <?php echo $ann['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="delete.php?id=<?php echo $ann['announcement_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this announcement?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>