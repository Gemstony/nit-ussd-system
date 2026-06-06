<?php
// admin/logs/export.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$db = Database::getInstance()->getConnection();

// Same filters as index.php
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

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

$sql = "SELECT session_id, phone_number, current_state, payload, created_at, expires_at 
        FROM ussd_sessions 
        $whereSQL 
        ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$sessions = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ussd_sessions_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

// Write header row
fputcsv($output, ['Session ID', 'Phone Number', 'State', 'Payload (JSON)', 'Created At', 'Expires At']);

// Write data rows
foreach ($sessions as $s) {
    fputcsv($output, [
        $s['session_id'],
        $s['phone_number'],
        $s['current_state'],
        $s['payload'], // raw JSON string
        $s['created_at'],
        $s['expires_at'] ?? ''
    ]);
}

fclose($output);
exit;