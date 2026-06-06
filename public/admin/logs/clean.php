<?php
// admin/logs/clean.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

$stmt = $db->prepare("DELETE FROM ussd_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
$stmt->execute([':days' => $days]);
$deleted = $stmt->rowCount();

// Redirect back with message
header("Location: index.php?msg=Cleaned $deleted sessions older than $days days");
exit;