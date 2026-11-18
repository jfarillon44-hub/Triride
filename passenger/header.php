<?php
require_once '../config.php';
require_login(['Passenger']);

$settings = get_settings();
$user_id = $_SESSION['user_id'];

// Get passenger info
$p_sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($p_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$passenger = $stmt->get_result()->fetch_assoc();

// Determine page title
if (!isset($page_title)) {
    $page_title = 'TriRide Passenger';
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
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <!-- Leaflet Map JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>

    <div class="emulator-layout">
        <!-- Simulator Side Panel (Visible on Desktop) -->
        <div class="simulator-panel">
            <h3 class="sim-title">
                <i class="fa-solid fa-gamepad"></i> TriRide Simulator
            </h3>
            <p class="sim-desc">Use this control panel to simulate real-time tricycle drivers and GPS updates for demo purposes.</p>

            <div class="sim-group">
                <div class="sim-group-title">Passenger Details</div>
                <div style="font-size: 0.85rem; line-height: 1.6;">
                    <strong>Name:</strong> <?php echo htmlspecialchars($passenger['fullname']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($passenger['phone']); ?><br>
                    <strong>Wallet Balance:</strong> P<span id="sim-wallet-bal"><?php echo number_format($passenger['wallet_balance'], 2); ?></span>
                </div>
            </div>

            <div class="sim-group">
                <div class="sim-group-title">Quick Actions</div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem;" onclick="openTopUpModal()">
                        <i class="fa-solid fa-wallet"></i> Top Up Wallet (+P100)
                    </button>
                    <a href="../logout.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.5rem;">
                        <i class="fa-solid fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="sim-group">
                <div class="sim-group-title">Driver Simulation Controls</div>
                <p style="font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.5rem;">If you book a ride, open another tab as Driver (driver01@gmail.com) OR use these buttons to auto-simulate driver steps:</p>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;" id="sim-driver-controls">
                    <button class="btn btn-outline" style="font-size: 0.75rem; padding: 0.4rem;" onclick="simulateDriverAccept()" disabled id="btn-sim-accept">
                        1. Driver Accept Booking
                    </button>
                    <button class="btn btn-outline" style="font-size: 0.75rem; padding: 0.4rem;" onclick="simulateDriverArrived()" disabled id="btn-sim-arrived">
                        2. Driver Arrived at Pickup
                    </button>
                    <button class="btn btn-outline" style="font-size: 0.75rem; padding: 0.4rem;" onclick="simulateDriverPickedUp()" disabled id="btn-sim-pickedup">
                        3. Passenger Picked Up
                    </button>
                    <button class="btn btn-outline" style="font-size: 0.75rem; padding: 0.4rem;" onclick="simulateDriverCompleted()" disabled id="btn-sim-complete">
                        4. Driver Complete Ride
                    </button>
                </div>
            </div>
            
            <div style="text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 1rem;">
                Running on XAMPP (Native PHP)
            </div>
        </div>

        <!-- Phone Mockup Frame -->
        <div class="phone-emulator">
            <div class="app-container">
                <!-- Status Bar -->
                <div class="status-bar">
                    <span>9:41</span>
                    <div class="notch"></div>
                    <div style="display: flex; gap: 4px; align-items: center;">
                        <i class="fa-solid fa-signal"></i>
                        <i class="fa-solid fa-wifi"></i>
                        <i class="fa-solid fa-battery-three-quarters"></i>
                    </div>
                </div>

                <!-- App Inner Header -->
                <header class="app-header">
                    <?php if (isset($show_back_btn) && $show_back_btn): ?>
                        <a href="<?php echo $back_url; ?>" class="back-btn"><i class="fa-solid fa-chevron-left"></i></a>
                    <?php else: ?>
                        <div style="width: 20px;"></div>
                    <?php endif; ?>
                    <h2><?php echo htmlspecialchars($page_header_title ?? $settings['app_name']); ?></h2>
                    <div style="position: relative;">
                        <span id="notif-dot" style="display:none; position: absolute; top: -2px; right: -2px; width: 8px; height: 8px; background: red; border-radius: 50%;"></span>
                        <a href="chat.php" style="color: white; font-size: 1.15rem;"><i class="fa-regular fa-bell"></i></a>
                    </div>
                </header>
