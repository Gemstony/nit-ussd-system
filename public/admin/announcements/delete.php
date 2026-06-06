<?php
// admin/announcements/delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $stmt = $db->prepare("DELETE FROM announcements WHERE announcement_id = :id");
    $stmt->execute(['id' => $id]);
}
header('Location: index.php');
exit;