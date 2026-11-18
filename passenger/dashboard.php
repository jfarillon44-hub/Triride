<?php
$page_title = 'TriRide Dashboard';
$page_header_title = 'TriRide';
$active_tab = 'home';
$show_back_btn = false;

require_once 'header.php';
?>

<div class="app-content">
    
    <!-- User Greeting Banner -->
    <div class="user-greeting">
        <div class="user-info">
            <p>Welcome Back!</p>
            <h3>Hello, <?php echo htmlspecialchars(explode(' ', $passenger['fullname'])[0]); ?>!</h3>
        </div>
        <div class="user-avatar">
            <?php echo substr($passenger['fullname'], 0, 1); ?>
        </div>
    </div>

    <!-- Wallet Card -->
    <div class="wallet-card">
        <div class="wallet-details">
            <span>Wallet Balance</span>
            <div class="wallet-balance" id="dash-wallet-bal">P<?php echo number_format($passenger['wallet_balance'], 2); ?></div>
        </div>
        <button type="button" class="btn-tapup" onclick="openTopUpModal()">Tap Up</button>
    </div>

    <!-- Quick Action Grid -->
    <div class="action-grid">
        <a href="book.php" class="action-item">
            <div class="action-icon-circle" style="background-color: #e6f7ed; color: #00873d;">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <span>Book Ride</span>
        </a>
        <a href="history.php" class="action-item">
            <div class="action-icon-circle" style="background-color: #eff6ff; color: #3b82f6;">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <span>History</span>
        </a>
        <a href="profile.php" class="action-item">
            <div class="action-icon-circle" style="background-color: #fef3c7; color: #d97706;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <span>Payments</span>
        </a>
        <a href="profile.php" class="action-item">
            <div class="action-icon-circle" style="background-color: #f5f3ff; color: #7c3aed;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <span>Profile</span>
        </a>
    </div>

    <!-- "Where to" Booking Query Card -->
    <div class="destination-search-card">
        <div class="destination-title">
            <i class="fa-solid fa-location-crosshairs" style="color: var(--primary-color);"></i> Where to?
        </div>
        <form action="book.php" method="GET">
            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Pickup Location</label>
                <select name="pickup_index" id="pickup_select" class="form-control" style="font-size: 0.85rem;" onchange="estimateRoute()">
                    <option value="0" selected>Agusan del Sur State University</option>
                    <option value="1">Patin-ay Provincial Capitol</option>
                    <option value="2">Prosperidad Public Market</option>
                    <option value="3">Neco Mall, Prosperidad</option>
                    <option value="4">San Francisco Terminal</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Destination</label>
                <select name="dest_index" id="dest_select" class="form-control" style="font-size: 0.85rem;" onchange="estimateRoute()">
                    <option value="0">Agusan del Sur State University</option>
                    <option value="1">Patin-ay Provincial Capitol</option>
                    <option value="2">Prosperidad Public Market</option>
                    <option value="3" selected>Neco Mall, Prosperidad</option>
                    <option value="4">San Francisco Terminal</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem; font-size: 0.9rem;">
                <i class="fa-solid fa-magnifying-glass"></i> Find Tricycle
            </button>
        </form>
    </div>

</div>

<!-- Auto-estimate coordinates on change for preview -->
<script>
    function estimateRoute() {
        const pSel = document.getElementById('pickup_select');
        const dSel = document.getElementById('dest_select');
        if (pSel.value === dSel.value) {
            // Prevent same locations
            dSel.selectedIndex = (dSel.selectedIndex + 1) % dSel.options.length;
        }
    }
</script>

<?php require_once 'footer.php'; ?>
