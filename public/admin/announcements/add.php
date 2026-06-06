<?php
// admin/announcements/add.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $target_audience = $_POST['target_audience'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (!in_array($target_audience, ['students', 'staff', 'all'])) {
        $error = 'Invalid audience.';
    } else {
        $stmt = $db->prepare("INSERT INTO announcements (title, content, target_audience, is_active, expires_at) VALUES (:title, :content, :audience, :active, :expires)");
        $result = $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':audience' => $target_audience,
            ':active' => $is_active,
            ':expires' => $expires_at
        ]);
        if ($result) {
            $success = "Announcement created successfully.";
        } else {
            $error = "Database error.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">New Announcement</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" rows="5" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" class="form-select">
                                <option value="students">Students</option>
                                <option value="staff">Staff</option>
                                <option value="all">All (Students + Staff)</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Active (visible to users)</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date (optional)</label>
                            <input type="date" name="expires_at" class="form-control">
                            <div class="form-text">Leave empty for no expiry.</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">Create Announcement</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>