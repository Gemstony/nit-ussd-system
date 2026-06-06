<?php
// admin/logs/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Filters
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination
$limit = 30;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = [];
$params = [];
if ($phone) {
    $where[] = "phone_number LIKE :phone";
    $params[':phone'] = "%$phone%";
}
if ($state) {
    $where[] = "current_state = :state";
    $params[':state'] = $state;
}
if ($date_from) {
    $where[] = "created_at >= :date_from";
    $params[':date_from'] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $where[] = "created_at <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}
$whereSQL = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Count total rows for pagination
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM ussd_sessions $whereSQL");
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Fetch session data
$sql = "SELECT session_id, phone_number, current_state, payload, created_at, updated_at, expires_at 
        FROM ussd_sessions 
        $whereSQL 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$sessions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">USSD Session Logs</h5>
            <div>
                <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-info btn-sm">📥 Export to CSV</a>
                <a href="clean.php" class="btn btn-danger btn-sm" onclick="return confirm('Delete all sessions older than 30 days?');">🗑️ Clean Old Sessions</a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g., 255712345678" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" placeholder="e.g., main_menu" value="<?php echo htmlspecialchars($state); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
                    <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </form>

            <!-- Responsive table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Session ID</th>
                            <th>Phone</th>
                            <th>State</th>
                            <th>Payload</th>
                            <th>Created At</th>
                            <th>Expires At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr><td colspan="6" class="text-center">No session logs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars(substr($s['session_id'], 0, 20)); ?>...</code></td>
                                    <td><?php echo htmlspecialchars($s['phone_number']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s['current_state']); ?></span></td>
                                    <td>
                                        <?php 
                                            $payload = json_decode($s['payload'], true);
                                            if ($payload && !empty($payload)) {
                                                echo '<pre style="font-size:0.75rem; margin:0; max-height:80px; overflow:auto;">' . htmlspecialchars(print_r($payload, true)) . '</pre>';
                                            } else {
                                                echo '<span class="text-muted">—</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($s['created_at'])); ?></td>
                                    <td><?php echo $s['expires_at'] ? date('d/m/Y H:i:s', strtotime($s['expires_at'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&phone=<?php echo urlencode($phone); ?>&state=<?php echo urlencode($state); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>