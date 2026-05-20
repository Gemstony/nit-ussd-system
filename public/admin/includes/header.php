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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f4f6f9;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            transform: translateX(-250px);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.show {
                transform: translateX(0);
            }
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background: #1a252f;
            border-left-color: #1a73e8;
            color: white;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        @media (max-width: 991.98px) {
            .content {
                margin-left: 0;
            }
        }

        .content.expanded {
            margin-left: 0;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-toggle {
            background: #2c3e50;
            border: none;
            color: white;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }

        .sidebar-toggle:hover {
            background: #1a252f;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        @media (min-width: 992px) {
            .overlay {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <h3 class="text-white px-3 pb-3">NIT USSD Admin</h3>
        <a href="/nit-ussd-system/public/admin/index.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
        <a href="/nit-ussd-system/public/admin/students/index.php"><i class="bi bi-person me-2"></i>Students</a>
        <a href="/nit-ussd-system/public/admin/fees/index.php"><i class="bi bi-currency-dollar me-2"></i>Fees</a>
        <a href="/nit-ussd-system/public/admin/results/index.php"><i class="bi bi-graph-up me-2"></i>Results</a>
        <a href="/nit-ussd-system/public/admin/registrations/index.php"><i class="bi bi-file-earmark-text me-2"></i>Registrations</a>
        <a href="/nit-ussd-system/public/admin/announcements/index.php"><i class="bi bi-megaphone me-2"></i>Announcements</a>
        <a href="/nit-ussd-system/public/admin/logs/index.php"><i class="bi bi-journal-text me-2"></i>Session Logs</a>
    </div>
    <div class="content" id="content">
        <div class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="m-0">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h2>
            </div>
            <a href="/nit-ussd-system/public/admin/logout.php" class="btn btn-danger">Logout</a>
        </div>