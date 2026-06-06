<?php
// admin/announcements/toggle_status.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $db->prepare("UPDATE announcements SET is_active = NOT is_active WHERE announcement_id = :id");
    $stmt->execute(['id' => $id]);
}
header('Location: index.php');
exit;