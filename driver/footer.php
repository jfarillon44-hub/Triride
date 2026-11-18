<?php
$active_tab = $active_tab ?? 'home';
?>
                <!-- App Bottom Navigation Bar -->
                <nav class="app-nav">
                    <a href="dashboard.php" class="nav-item <?php echo $active_tab === 'home' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house nav-icon"></i>
                        <span>Home</span>
                    </a>
                    <a href="history.php" class="nav-item <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-line nav-icon"></i>
                        <span>Earnings</span>
                    </a>
                    <a href="chat.php" class="nav-item <?php echo $active_tab === 'chat' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-comment-dots nav-icon"></i>
                        <span>Chat</span>
                    </a>
                    <a href="profile.php" class="nav-item <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-circle nav-icon"></i>
                        <span>Profile</span>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- INCOMING RIDE REQUEST OVERLAY DIALOG -->
    <div class="modal-overlay" id="incoming-modal" style="display: none;">
        <div class="request-dialog">
            <div class="passenger-profile">
                <!-- Passenger Photo placeholder -->
                <div class="passenger-img">👩🏻</div>
                <h3 class="passenger-name" id="req-passenger-name">Juliana V. Farillon</h3>
                <span class="passenger-rating">
                    <i class="fa-solid fa-star"></i> 4.9 Rating
                </span>
            </div>
            
            <div class="request-details">
                <div style="margin-bottom: 0.5rem;">
                    <span style="color:var(--accent-green); font-weight:700;">From:</span>
                    <span id="req-pickup-val" style="font-weight:600;">Agusan del Sur State University</span>
                </div>
                <div>
                    <span style="color:var(--accent-red); font-weight:700;">To:</span>
                    <span id="req-dest-val" style="font-weight:600;">Neco Mall, Prosperidad</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 0.5rem; margin-top: 0.5rem; font-size: 0.8rem;">
                    <span>Distance: <strong id="req-dist-val">5.2 km</strong></span>
                    <span>Fare: <strong style="color: var(--primary-color);" id="req-fare-val">P35.00</strong></span>
                </div>
            </div>

            <div class="request-actions">
                <button type="button" class="btn btn-outline" style="border-color: var(--accent-red); color: var(--accent-red); padding:0.5rem;" onclick="declineIncomingRequest()">Decline</button>
                <button type="button" class="btn btn-primary" style="padding:0.5rem;" onclick="acceptIncomingRequest()">Accept</button>
            </div>
        </div>
    </div>

    <!-- GLOBAL STATE SYNC & REDIRECT ENGINE -->
    <script>
        const PAGE_NAME = window.location.pathname.split('/').pop();
        let activeRideId = 0;
        let incomingRideId = 0;

        function syncState() {
            fetch('../api/sync.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update status switch if exists
                        if (document.getElementById('driver-status-check')) {
                            document.getElementById('driver-status-check').checked = (data.driver_status === 'Online');
                            document.getElementById('status-label-text').innerText = data.driver_status;
                            document.getElementById('status-label-text').style.color = (data.driver_status === 'Online') ? 'var(--accent-green)' : 'var(--text-muted)';
                        }
                        
                        // Update earnings display if exists
                        if (document.getElementById('dash-earnings')) {
                            document.getElementById('dash-earnings').innerText = 'P' + data.today_earnings;
                            document.getElementById('dash-completed').innerText = data.completed_rides;
                        }

                        // State redirection & incoming popups
                        const ride = data.active_ride;
                        const incoming = data.incoming_request;

                        if (ride) {
                            activeRideId = ride.id;
                            hideIncomingModal();

                            // Redirect to active status page if not on status or chat page
                            if (PAGE_NAME !== 'status.php' && PAGE_NAME !== 'chat.php') {
                                window.location.href = 'status.php';
                            }
                        } else {
                            activeRideId = 0;
                            // If no active ride, driver shouldn't be on status page
                            if (PAGE_NAME === 'status.php') {
                                window.location.href = 'dashboard.php';
                            }

                            // Show incoming request if online and available
                            if (incoming && data.driver_status === 'Online') {
                                showIncomingModal(incoming);
                            } else {
                                hideIncomingModal();
                            }
                        }

                        // Sync specific updates for pages
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

        // Incoming Request Modal Toggles
        function showIncomingModal(req) {
            incomingRideId = req.id;
            document.getElementById('req-passenger-name').innerText = req.passenger_name;
            document.getElementById('req-pickup-val').innerText = req.pickup_location;
            document.getElementById('req-dest-val').innerText = req.destination;
            document.getElementById('req-dist-val').innerText = req.distance + ' km';
            document.getElementById('req-fare-val').innerText = 'P' + req.fare;
            document.getElementById('incoming-modal').style.display = 'flex';
        }

        function hideIncomingModal() {
            incomingRideId = 0;
            document.getElementById('incoming-modal').style.display = 'none';
        }

        function acceptIncomingRequest() {
            if (!incomingRideId) return;
            fetch('../api/ride_action.php?action=accept_ride', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ride_id: incomingRideId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    hideIncomingModal();
                    window.location.href = 'status.php';
                } else {
                    alert(data.message);
                }
            });
        }

        function declineIncomingRequest() {
            if (!incomingRideId) return;
            fetch('../api/ride_action.php?action=decline_ride', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ride_id: incomingRideId })
            })
            .then(res => res.json())
            .then(() => {
                hideIncomingModal();
                syncState();
            });
        }

        // Trigger simulator booking from passenger
        function triggerSimBooking() {
            const btn = document.getElementById('btn-sim-booking');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';

            fetch('../api/ride_action.php?action=create_demo_ride', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Alert or auto-sync
                    syncState();
                } else {
                    alert(data.message);
                }
                btn.disabled = false;
                btn.innerText = 'Create Demo Ride Request';
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerText = 'Create Demo Ride Request';
            });
        }
    </script>
</body>
</html>
