<?php
require_once 'config.php';

// Check if already logged in, redirect to respective dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'Driver') {
        header("Location: driver/dashboard.php");
    } else {
        header("Location: passenger/dashboard.php");
    }
    exit();
}

$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['app_name']); ?> - Ride-Hailing for Tricycles</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="landing-container">
        <!-- Header -->
        <header class="landing-header">
            <a href="index.php" class="logo-container">
                <img src="assets/img/logo.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'48\' height=\'48\'><rect width=\'100\' height=\'100\' fill=\'%23006430\' rx=\'20\'/><text x=\'50\' y=\'65\' font-size=\'45\' text-anchor=\'middle\' fill=\'white\'>🛺</text></svg>';" alt="TriRide Logo" class="logo-img">
                <div class="logo-text">
                    <h1><?php echo htmlspecialchars($settings['app_name']); ?></h1>
                    <span>TRICYCLE RIDE-HAILING</span>
                </div>
            </a>
            <div>
                <a href="login.php" class="btn btn-secondary" style="width: auto; padding: 0.5rem 1.5rem; display: inline-flex;">Sign In</a>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="landing-hero">
            <div class="landing-hero-content">
                <h2 class="hero-title">Rides · Safe · Convenient</h2>
                <p class="hero-desc">
                    Welcome to <strong><?php echo htmlspecialchars($settings['app_name']); ?></strong>, the premier tricycle booking platform. 
                    Connecting local passengers with verified drivers for fast, transparent, and tracked transportation.
                </p>

                <h3>Select a Portal to Access the System:</h3>
                
                <div class="portal-grid">
                    <!-- Passenger Portal -->
                    <a href="passenger/dashboard.php" class="portal-card">
                        <span class="portal-icon">📱</span>
                        <h4 class="portal-title">Passenger Portal</h4>
                        <p class="portal-desc">Book tricycle rides, check fare estimates, top up wallet, and track your driver.</p>
                    </a>

                    <!-- Driver Portal -->
                    <a href="driver/dashboard.php" class="portal-card">
                        <span class="portal-icon">🛺</span>
                        <h4 class="portal-title">Driver Portal</h4>
                        <p class="portal-desc">Go online, receive instant booking requests, track earnings, and navigate trips.</p>
                    </a>

                    <!-- Admin Portal -->
                    <a href="admin/dashboard.php" class="portal-card">
                        <span class="portal-icon">🖥️</span>
                        <h4 class="portal-title">Admin Dashboard</h4>
                        <p class="portal-desc">Manage accounts, monitor active trips, view transactions, and generate reports.</p>
                    </a>
                </div>
            </div>

            <!-- Visual representation / image mockup -->
            <div class="landing-hero-image" style="text-align: center;">
                <div style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); display: inline-block;">
                    <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200' width='180' height='180'><circle cx='100' cy='100' r='80' fill='%23e6f0eb'/><text x='100' y='125' font-size='80' text-anchor='middle'>🛺</text></svg>" alt="TriRide Mockup" style="max-width: 100%; height: auto; margin-bottom: 1rem; border-radius: var(--radius-md);">
                    <h4 style="color: var(--primary-color);">Enhanced Tricycle Usability</h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Digitizing traditional local transit.</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer style="text-align: center; padding: 2rem 0; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['app_name']); ?>. All Rights Reserved.</p>
        </footer>
    </div>

</body>
</html>
