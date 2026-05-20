<?php
// admin/includes/header.php
if (!isset($_SESSION))
    session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - NIT USSD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100%;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        .sidebar a:hover {
            background: #1a252f;
            border-left-color: #1a73e8;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .top-bar h2 {
            margin: 0;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            text-decoration: none;
            border-radius: 4px;
        }

        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3 style="padding: 0 20px 20px;">NIT USSD Admin</h3>
        <a href="/nit-ussd-system/public/admin/index.php">🏠 Dashboard</a>
        <a href="/nit-ussd-system/public/admin/students/index.php">👨‍🎓 Students</a>
        <a href="/nit-ussd-system/public/admin/fees/index.php">💰 Fees</a>
        <a href="/nit-ussd-system/public/admin/results/index.php">📊 Results</a>
        <a href="/nit-ussd-system/public/admin/registrations/index.php">📝 Registrations</a>
        <a href="/nit-ussd-system/public/admin/announcements/index.php">📢 Announcements</a>
        <a href="/nit-ussd-system/public/admin/logs/index.php">📜 Session Logs</a>
    </div>
    <div class="content">
        <div class="top-bar">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h2>
            <a href="/admin/logout.php" class="logout-btn">Logout</a>
        </div>