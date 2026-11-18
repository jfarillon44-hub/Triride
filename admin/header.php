<?php
require_once '../config.php';
require_login(['Admin']);

$settings = get_settings();
$user_id = $_SESSION['user_id'];

if (!isset($page_title)) {
    $page_title = 'Admin Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($settings['app_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../assets/img/logo.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'38\' height=\'38\'><rect width=\'100\' height=\'100\' fill=\'%23006430\' rx=\'20\'/><text x=\'50\' y=\'65\' font-size=\'45\' text-anchor=\'middle\' fill=\'white\'>🛺</text></svg>';" alt="TriRide Logo" style="border-radius: 8px;">
                <h2><?php echo htmlspecialchars($settings['app_name']); ?></h2>
            </div>
            
            <ul class="admin-menu">
                <li class="admin-menu-item <?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>">
                    <a href="dashboard.php" class="admin-menu-link">
                        <i class="fa-solid fa-gauge"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'users' ? 'active' : ''; ?>">
                    <a href="users.php" class="admin-menu-link">
                        <i class="fa-solid fa-users"></i>
                        <span>Users (Passengers)</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'drivers' ? 'active' : ''; ?>">
                    <a href="drivers.php" class="admin-menu-link">
                        <i class="fa-solid fa-id-card"></i>
                        <span>Drivers</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'rides' ? 'active' : ''; ?>">
                    <a href="rides.php" class="admin-menu-link">
                        <i class="fa-solid fa-route"></i>
                        <span>Rides Monitor</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'payments' ? 'active' : ''; ?>">
                    <a href="payments.php" class="admin-menu-link">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>Payments Logs</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'reports' ? 'active' : ''; ?>">
                    <a href="reports.php" class="admin-menu-link">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Reports Panel</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo $active_menu === 'settings' ? 'active' : ''; ?>">
                    <a href="settings.php" class="admin-menu-link">
                        <i class="fa-solid fa-gears"></i>
                        <span>System Settings</span>
                    </a>
                </li>
                <li class="admin-menu-item" style="margin-top: 3rem;">
                    <a href="../logout.php" class="admin-menu-link" style="color: #fca5a5;">
                        <i class="fa-solid fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>

            <div class="admin-user-footer">
                <div>
                    <div style="font-weight:700; color:white;"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                    <div style="font-size:0.75rem; color:#64748b;">System Admin</div>
                </div>
            </div>
        </aside>

        <!-- Main Content Panel wrapper -->
        <main class="admin-main">
            <!-- Navbar -->
            <header class="admin-navbar">
                <h2 class="admin-navbar-title"><?php echo htmlspecialchars($page_header ?? 'Admin Panel'); ?></h2>
                <div style="font-size:0.9rem; font-weight:600; color:var(--text-muted);">
                    Server Time: <span id="admin-time"><?php echo date('h:i A'); ?></span>
                </div>
            </header>

            <div class="admin-content-container">
