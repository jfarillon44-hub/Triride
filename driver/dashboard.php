<?php
$page_title = 'Driver Dashboard';
$page_header_title = 'TriRide Driver';
$active_tab = 'home';
$show_back_btn = false;

require_once '../config.php';
require_login(['Driver']);

$user_id = $_SESSION['user_id'];

// Handle AJAX or POST status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $new_status = sanitize($_POST['status']);
    if ($new_status === 'Online' || $new_status === 'Offline') {
        $up_sql = "UPDATE drivers SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($up_sql);
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'driver_status' => $new_status]);
        exit();
    }
}

require_once 'header.php';
?>

<div class="app-content">

    <!-- Driver Greeting banner -->
    <div class="user-greeting" style="background-color: var(--primary-color);">
        <div class="user-info">
            <p>Good Morning,</p>
            <h3><?php echo htmlspecialchars(explode(' ', $driver['fullname'])[0]); ?>!</h3>
        </div>
        <div class="user-avatar" style="background: white; color: var(--primary-color);">
            🛺
        </div>
    </div>

    <!-- Status Toggle Switch -->
    <div class="status-toggle-container">
        <div>
            <span class="status-label">Duty Status: </span>
            <span id="status-label-text" style="font-weight: 700; color: var(--text-muted);">Offline</span>
        </div>
        <label class="switch">
            <input type="checkbox" id="driver-status-check" onchange="toggleDutyStatus(this)">
            <span class="slider"></span>
        </label>
    </div>

    <!-- Statistics Strip -->
    <div class="driver-stats-strip">
        <div class="driver-stat-card">
            <p>Today's Earnings</p>
            <h4 id="dash-earnings">P0.00</h4>
        </div>
        <div class="driver-stat-card">
            <p>Trips Completed</p>
            <h4 id="dash-completed">0</h4>
        </div>
        <div class="driver-stat-card">
            <p>Operator Rating</p>
            <h4 style="color: var(--accent-yellow);"><i class="fa-solid fa-star"></i> <?php echo number_format($driver['rating'], 1); ?></h4>
        </div>
    </div>

    <!-- Current Ride Overview (Empty State by default, as state sync auto-redirects active rides to status.php) -->
    <div class="ride-card" id="no-ride-card">
        <div class="ride-card-header">
            <span class="ride-card-title">Current Status</span>
        </div>
        <div style="text-align: center; padding: 1.5rem 0; color: var(--text-muted); font-size: 0.85rem;">
            <i class="fa-solid fa-hourglass-start" style="font-size: 1.75rem; margin-bottom: 0.5rem; display: block; color: var(--text-muted);"></i>
            <span id="duty-hint-text">Go Online to start receiving ride requests.</span>
        </div>
    </div>

</div>

<script>
    function toggleDutyStatus(chk) {
        const status = chk.checked ? 'Online' : 'Offline';
        const label = document.getElementById('status-label-text');
        
        label.innerText = status;
        label.style.color = chk.checked ? 'var(--accent-green)' : 'var(--text-muted)';
        
        const hint = document.getElementById('duty-hint-text');
        if (chk.checked) {
            hint.innerHTML = 'Online and ready. Waiting for passengers...';
        } else {
            hint.innerHTML = 'Go Online to start receiving ride requests.';
        }

        // Post status update
        const fd = new FormData();
        fd.append('action', 'toggle_status');
        fd.append('status', status);

        fetch('dashboard.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                syncState(); // Force-sync
            }
        });
    }

    // Capture sync state updates on load
    function onSyncUpdate(data) {
        const hint = document.getElementById('duty-hint-text');
        if (data.driver_status === 'Online') {
            hint.innerHTML = 'Online and ready. Waiting for passengers...';
        } else {
            hint.innerHTML = 'Go Online to start receiving ride requests.';
        }
    }
</script>

<?php require_once 'footer.php'; ?>
