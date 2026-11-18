<?php
// Determine active tab
$active_tab = $active_tab ?? 'home';
?>
                <!-- App Bottom Navigation Bar -->
                <nav class="app-nav">
                    <a href="dashboard.php" class="nav-item <?php echo $active_tab === 'home' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house nav-icon"></i>
                        <span>Home</span>
                    </a>
                    <a href="history.php" class="nav-item <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock-rotate-left nav-icon"></i>
                        <span>History</span>
                    </a>
                    <a href="chat.php" class="nav-item <?php echo $active_tab === 'chat' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-comment-dots nav-icon"></i>
                        <span>Chat</span>
                    </a>
                    <a href="profile.php" class="nav-item <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user nav-icon"></i>
                        <span>Profile</span>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- WALLET TOP UP MODAL (Demo) -->
    <div class="admin-modal" id="topup-modal">
        <div class="admin-modal-content" style="max-width: 320px; border-radius: var(--radius-md);">
            <div class="admin-modal-header" style="padding: 0.75rem 1rem;">
                <h4 class="admin-panel-title">Tap Up Wallet</h4>
                <button type="button" style="border:none; background:transparent; font-size:1.2rem; cursor:pointer;" onclick="closeTopUpModal()">&times;</button>
            </div>
            <div class="admin-modal-body" style="padding: 1rem;">
                <form id="topup-form" onsubmit="submitTopUp(event)">
                    <div class="form-group">
                        <label class="form-label">Amount (PHP)</label>
                        <select name="amount" id="topup-amount" class="form-control">
                            <option value="50">P50.00</option>
                            <option value="100" selected>P100.00</option>
                            <option value="200">P200.00</option>
                            <option value="500">P500.00</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Funds</button>
                </form>
            </div>
        </div>
    </div>

    <!-- GLOBAL STATE SYNC & REDIRECT ENGINE -->
    <script>
        const PAGE_NAME = window.location.pathname.split('/').pop();
        let activeRideId = 0;
        let activeRideStatus = '';

        function syncState() {
            fetch('../api/sync.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update wallet display if exists
                        if (document.getElementById('sim-wallet-bal')) {
                            document.getElementById('sim-wallet-bal').innerText = data.wallet_balance;
                        }
                        if (document.getElementById('dash-wallet-bal')) {
                            document.getElementById('dash-wallet-bal').innerText = 'P' + data.wallet_balance;
                        }

                        // State redirection machine
                        const ride = data.active_ride;
                        if (ride) {
                            activeRideId = ride.id;
                            activeRideStatus = ride.status;

                            // Enable simulation controls for debugging
                            toggleSimControls(ride.status);

                            // Redirect conditions based on database state
                            if (ride.status === 'Pending' || ride.status === 'Accepted' || ride.status === 'Arrived' || ride.status === 'Picked Up') {
                                if (PAGE_NAME !== 'track.php' && PAGE_NAME !== 'chat.php' && PAGE_NAME !== 'book.php') {
                                    window.location.href = 'track.php';
                                }
                            } else if (ride.status === 'Completed' && ride.payment_status === 'Pending') {
                                if (PAGE_NAME !== 'payment.php') {
                                    window.location.href = 'payment.php';
                                }
                            }
                        } else {
                            activeRideId = 0;
                            activeRideStatus = '';
                            toggleSimControls(null);

                            // If no active ride, passengers shouldn't be on tracking or payment pages
                            if (PAGE_NAME === 'track.php' || PAGE_NAME === 'payment.php') {
                                window.location.href = 'dashboard.php';
                            }
                        }

                        // Dynamic updates for specific pages
                        if (typeof onSyncUpdate === 'function') {
                            onSyncUpdate(data);
                        }
                    }
                })
                .catch(err => console.error('Sync Error:', err));
        }

        // Run sync every 2.5 seconds
        setInterval(syncState, 2500);
        window.addEventListener('DOMContentLoaded', syncState);

        // Simulation Control Functions
        function toggleSimControls(status) {
            const btnAccept = document.getElementById('btn-sim-accept');
            const btnArrived = document.getElementById('btn-sim-arrived');
            const btnPicked = document.getElementById('btn-sim-pickedup');
            const btnComplete = document.getElementById('btn-sim-complete');

            if (!btnAccept) return; // Not on desktop view / panels

            // Reset all
            btnAccept.disabled = true;
            btnArrived.disabled = true;
            btnPicked.disabled = true;
            btnComplete.disabled = true;

            if (status === 'Pending') {
                btnAccept.disabled = false;
            } else if (status === 'Accepted') {
                btnArrived.disabled = false;
            } else if (status === 'Arrived') {
                btnPicked.disabled = false;
            } else if (status === 'Picked Up') {
                btnComplete.disabled = false;
            }
        }

        function simulateDriverAccept() {
            if (!activeRideId) return;
            // Fetch any online driver, for simulation let's assign driver 1 (Juan Dela Cruz)
            fetch('../api/ride_action.php?action=accept_ride', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ride_id: activeRideId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Force update driver coordinates online
                    updateSimDriverLoc(8.6042, 125.9142);
                    syncState();
                } else {
                    alert(data.message);
                }
            });
        }

        function updateSimDriverLoc(lat, lng) {
            fetch('../api/ride_action.php?action=update_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ride_id: activeRideId, latitude: lat, longitude: lng })
            });
        }

        function simulateDriverArrived() {
            updateRideStatus('Arrived');
        }

        function simulateDriverPickedUp() {
            updateRideStatus('Picked Up');
        }

        function simulateDriverCompleted() {
            updateRideStatus('Completed');
        }

        function updateRideStatus(status) {
            if (!activeRideId) return;
            fetch('../api/ride_action.php?action=update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ride_id: activeRideId, status: status })
            })
            .then(res => res.json())
            .then(() => syncState());
        }

        // Wallet Modals
        function openTopUpModal() {
            document.getElementById('topup-modal').classList.add('show');
        }

        function closeTopUpModal() {
            document.getElementById('topup-modal').classList.remove('show');
        }

        function submitTopUp(e) {
            e.preventDefault();
            const amt = document.getElementById('topup-amount').value;
            fetch('../api/wallet_action.php?action=topup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amt })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeTopUpModal();
                    syncState();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
