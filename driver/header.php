<?php
require_once '../config.php';
require_login(['Driver']);

$settings = get_settings();
$user_id = $_SESSION['user_id'];

// Get driver info
$d_sql = "SELECT * FROM drivers WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($d_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();

if (!isset($page_title)) {
    $page_title = 'TriRide Driver';
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
        <div class="simulator-panel" style="border-color: #15803d;">
            <h3 class="sim-title" style="color: #4ade80;">
                <i class="fa-solid fa-gamepad"></i> Driver Simulator
            </h3>
            <p class="sim-desc">Use this control panel to simulate incoming bookings and vehicle telemetry updates without leaving this tab.</p>

            <div class="sim-group">
                <div class="sim-group-title" style="color: #4ade80;">Driver Profile</div>
                <div style="font-size: 0.85rem; line-height: 1.6;">
                    <strong>Name:</strong> <?php echo htmlspecialchars($driver['fullname']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($driver['phone']); ?><br>
                    <strong>Vehicle:</strong> <?php echo htmlspecialchars($driver['vehicle_info']); ?><br>
                    <strong>License No:</strong> <?php echo htmlspecialchars($driver['license_no']); ?><br>
                    <strong>GPS coords:</strong> <span id="sim-gps-coords"><?php echo $driver['latitude']; ?>, <?php echo $driver['longitude']; ?></span>
                </div>
            </div>

            <div class="sim-group">
                <div class="sim-group-title" style="color: #4ade80;">Passenger Simulation</div>
                <p style="font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.5rem;">Simulate a passenger requesting a ride from Agusan del Sur State University to Neco Mall (Fare P35.00):</p>
                <button class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem; background-color: #166534; color: white;" onclick="triggerSimBooking()" id="btn-sim-booking">
                    Create Demo Ride Request
                </button>
            </div>
            
            <div class="sim-group">
                <div class="sim-group-title" style="color: #4ade80;">Quick Actions</div>
                <a href="../logout.php" class="btn btn-outline" style="font-size: 0.85rem; padding: 0.5rem; border-color: rgba(255,255,255,0.2); color: white;">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div style="text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 1rem;">
                TriRide Tricycle Driver Dashboard
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
                <header class="app-header" style="background-color: var(--primary-color);">
                    <?php if (isset($show_back_btn) && $show_back_btn): ?>
                        <a href="<?php echo $back_url; ?>" class="back-btn"><i class="fa-solid fa-chevron-left"></i></a>
                    <?php else: ?>
                        <div style="width: 20px;"></div>
                    <?php endif; ?>
                    <h2><?php echo htmlspecialchars($page_header_title ?? $settings['app_name']); ?></h2>
                    <a href="chat.php" style="color: white; font-size: 1.15rem;"><i class="fa-regular fa-bell"></i></a>
                </header>
