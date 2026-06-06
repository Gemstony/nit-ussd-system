<?php
// admin/announcements/edit.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM announcements WHERE announcement_id = :id");
$stmt->execute(['id' => $id]);
$ann = $stmt->fetch();
if (!$ann) {
    header('Location: index.php');
    exit;
}

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
        $update = $db->prepare("UPDATE announcements SET title = :title, content = :content, target_audience = :audience, is_active = :active, expires_at = :expires WHERE announcement_id = :id");
        $result = $update->execute([
            ':title' => $title,
            ':content' => $content,
            ':audience' => $target_audience,
            ':active' => $is_active,
            ':expires' => $expires_at,
            ':id' => $id
        ]);
        if ($result) {
            header('Location: index.php?msg=updated');
            exit;
        } else {
            $error = "Update failed.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Announcement</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($ann['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" rows="5" class="form-control" required><?php echo htmlspecialchars($ann['content']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" class="form-select">
                                <option value="students" <?php echo $ann['target_audience'] === 'students' ? 'selected' : ''; ?>>Students</option>
                                <option value="staff" <?php echo $ann['target_audience'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="all" <?php echo $ann['target_audience'] === 'all' ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?php echo $ann['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expires_at" class="form-control" value="<?php echo $ann['expires_at']; ?>">
                            <div class="form-text">Leave empty for no expiry.</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-warning">Update</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>