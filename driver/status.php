<?php
$page_title = 'Trip Navigation';
$page_header_title = 'Trip Status Update';
$active_tab = 'home';
$show_back_btn = false;

require_once '../config.php';
require_login(['Driver']);

require_once 'header.php';
?>

<div id="status-trip-container" style="display: flex; flex-direction: column; flex: 1; position: relative;">
    <!-- Directions Indicator -->
    <div class="navigation-bar-step">
        <i class="fa-solid fa-turn-up nav-direction-icon" id="nav-direction-icon"></i>
        <div>
            <strong id="nav-direction-text" style="display: block;">On the way to pickup</strong>
            <span style="font-size: 0.75rem; opacity: 0.8;" id="nav-telemetry-text">1.7 km · 12 mins</span>
        </div>
    </div>

    <!-- Map View -->
    <div id="map-navigation" style="flex: 1; width: 100%; min-height: 300px; z-index: 1;"></div>

    <!-- Trip Action Control Card -->
    <div class="floating-trip-info" style="position: relative; margin: 1rem; border: 1px solid var(--border-color); bottom: 0; left: 0; right: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Passenger Details</span>
                <strong style="font-size: 0.95rem; display: block;" id="passenger-name-text">Juliana V. Farillon</strong>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="chat.php" class="btn btn-secondary" style="width: 35px; height: 35px; border-radius: 50%; padding:0; display:flex; align-items:center; justify-content:center; font-size:1rem;">
                    <i class="fa-solid fa-comments"></i>
                </a>
                <a href="#" id="passenger-phone-link" class="btn btn-outline" style="width: 35px; height: 35px; border-radius: 50%; padding:0; display:flex; align-items:center; justify-content:center; font-size:1rem;" onclick="alert('Calling passenger: ' + this.getAttribute('data-phone'))">
                    <i class="fa-solid fa-phone"></i>
                </a>
            </div>
        </div>

        <div style="margin-bottom: 0.75rem; font-size: 0.8rem;" id="target-loc-card">
            <span class="text-muted" id="target-loc-label" style="font-weight: 600;">Pickup Location:</span>
            <div id="target-loc-val" style="font-weight: 700; margin-top: 0.25rem;">Agusan del Sur State University</div>
        </div>

        <!-- Big Trip Action Button -->
        <button class="btn btn-primary" id="btn-trip-action" onclick="advanceTripStatus()">
            Arrived at Pickup
        </button>
        
        <!-- Checklist overview -->
        <div class="progress-list" style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
            <div class="progress-step" id="step-accepted">
                <span class="step-circle">1</span>
                <span class="step-label">Booking Accepted</span>
            </div>
            <div class="progress-step" id="step-arrived">
                <span class="step-circle">2</span>
                <span class="step-label">Arrived at Pickup</span>
            </div>
            <div class="progress-step" id="step-boarded">
                <span class="step-circle">3</span>
                <span class="step-label">Passenger Picked Up</span>
            </div>
            <div class="progress-step" id="step-completed">
                <span class="step-circle">4</span>
                <span class="step-label">Trip Completed</span>
            </div>
        </div>
    </div>
</div>

<script>
    let map = null;
    let passengerMarker = null;
    let destinationMarker = null;
    let driverMarker = null;
    
    let currentLat = 8.6042;
    let currentLng = 125.9142;
    let startLat = 8.6042;
    let startLng = 125.9142;
    let targetLat = 8.6042;
    let targetLng = 125.9142;
    
    let interpStep = 0;
    const maxInterpSteps = 8; // Steps for animation

    function initMap(plat, plng, dlat, dlng, drvlat, drvlng) {
        if (map) return;

        map = L.map('map-navigation').setView([drvlat, drvlng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Pickup point (Green Marker)
        passengerMarker = L.circleMarker([plat, plng], {
            radius: 8,
            fillColor: "#28a745",
            color: "white",
            weight: 3,
            fillOpacity: 1
        }).addTo(map).bindPopup("Pickup");

        // Destination point (Red Marker)
        destinationMarker = L.circleMarker([dlat, dlng], {
            radius: 8,
            fillColor: "#dc3545",
            color: "white",
            weight: 3,
            fillOpacity: 1
        }).addTo(map).bindPopup("Destination");

        // Tricycle emoji icon for Driver
        const tricycleIcon = L.divIcon({
            html: '<div style="font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">🛺</div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Driver marker
        driverMarker = L.marker([drvlat, drvlng], { icon: tricycleIcon }).addTo(map).bindPopup("Your Location");

        // Fit
        const bounds = L.latLngBounds([plat, plng], [dlat, dlng], [drvlat, drvlng]);
        map.fitBounds(bounds, { padding: [40, 40] });
    }

    function onSyncUpdate(data) {
        const ride = data.active_ride;
        if (!ride) return;

        document.getElementById('passenger-name-text').innerText = ride.passenger_name;
        document.getElementById('passenger-phone-link').setAttribute('data-phone', ride.passenger_phone);
        document.getElementById('nav-telemetry-text').innerText = `${ride.distance} km · ${ride.duration} mins`;

        // Update list status highlights
        updateChecklistUI(ride.status);

        // Define starts and targets for simulated movement
        if (ride.status === 'Accepted') {
            document.getElementById('nav-direction-icon').className = 'fa-solid fa-turn-up nav-direction-icon';
            document.getElementById('nav-direction-text').innerText = 'Drive to Pickup Location';
            document.getElementById('target-loc-label').innerText = 'Pickup Location:';
            document.getElementById('target-loc-val').innerText = ride.pickup_location;
            document.getElementById('btn-trip-action').innerText = 'Arrived at Pickup';
            
            // Sim target is Pickup
            startLat = 8.6042; // default start
            startLng = 125.9142;
            targetLat = parseFloat(ride.pickup_lat);
            targetLng = parseFloat(ride.pickup_lng);
        } else if (ride.status === 'Arrived') {
            document.getElementById('nav-direction-icon').className = 'fa-solid fa-hourglass-half nav-direction-icon';
            document.getElementById('nav-direction-text').innerText = 'Waiting for Passenger...';
            document.getElementById('target-loc-label').innerText = 'Passenger Pickup point:';
            document.getElementById('target-loc-val').innerText = ride.pickup_location;
            document.getElementById('btn-trip-action').innerText = 'Passenger Boarded';
            
            // Stop movement during waiting
            interpStep = 0;
            startLat = parseFloat(ride.pickup_lat);
            startLng = parseFloat(ride.pickup_lng);
            targetLat = parseFloat(ride.pickup_lat);
            targetLng = parseFloat(ride.pickup_lng);
        } else if (ride.status === 'Picked Up') {
            document.getElementById('nav-direction-icon').className = 'fa-solid fa-location-arrow nav-direction-icon';
            document.getElementById('nav-direction-text').innerText = 'Driving to Destination';
            document.getElementById('target-loc-label').innerText = 'Destination:';
            document.getElementById('target-loc-val').innerText = ride.destination;
            document.getElementById('btn-trip-action').innerText = 'End Trip';
            
            // Sim target is Destination
            startLat = parseFloat(ride.pickup_lat);
            startLng = parseFloat(ride.pickup_lng);
            targetLat = parseFloat(ride.dest_lat);
            targetLng = parseFloat(ride.dest_lng);
        }

        // Initialize map
        initMap(ride.pickup_lat, ride.pickup_lng, ride.dest_lat, ride.dest_lng, currentLat, currentLng);

        // Run coordinate interpolation to move driver smoothly!
        if (ride.status === 'Accepted' || ride.status === 'Picked Up') {
            if (interpStep < maxInterpSteps) {
                interpStep++;
                currentLat = startLat + (targetLat - startLat) * (interpStep / maxInterpSteps);
                currentLng = startLng + (targetLng - startLng) * (interpStep / maxInterpSteps);
                
                // Update marker
                if (driverMarker) {
                    driverMarker.setLatLng([currentLat, currentLng]);
                }
                
                // Update side panel text
                if (document.getElementById('sim-gps-coords')) {
                    document.getElementById('sim-gps-coords').innerText = `${currentLat.toFixed(5)}, ${currentLng.toFixed(5)}`;
                }

                // POST coordinates back to database
                fetch('../api/ride_action.php?action=update_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ride_id: ride.id, latitude: currentLat, longitude: currentLng })
                });
            }
        } else {
            // When waiting or completed, keep at pickup
            currentLat = targetLat;
            currentLng = targetLng;
            if (driverMarker) {
                driverMarker.setLatLng([currentLat, currentLng]);
            }
        }
    }

    function updateChecklistUI(status) {
        // Reset checklist
        const steps = ['accepted', 'arrived', 'boarded', 'completed'];
        steps.forEach(st => document.getElementById(`step-${st}`).className = 'progress-step');

        document.getElementById('step-accepted').classList.add('completed');

        if (status === 'Accepted') {
            document.getElementById('step-arrived').classList.add('active');
        } else if (status === 'Arrived') {
            document.getElementById('step-arrived').classList.add('completed');
            document.getElementById('step-boarded').classList.add('active');
        } else if (status === 'Picked Up') {
            document.getElementById('step-arrived').classList.add('completed');
            document.getElementById('step-boarded').classList.add('completed');
            document.getElementById('step-completed').classList.add('active');
        }
    }

    function advanceTripStatus() {
        if (!activeRideId) return;

        let nextStatus = '';
        const btn = document.getElementById('btn-trip-action');
        
        if (btn.innerText === 'Arrived at Pickup') {
            nextStatus = 'Arrived';
        } else if (btn.innerText === 'Passenger Boarded') {
            nextStatus = 'Picked Up';
            interpStep = 0; // reset animation step for destination run
        } else if (btn.innerText === 'End Trip') {
            if (!confirm('Are you sure you want to end this trip and charge passenger?')) return;
            nextStatus = 'Completed';
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';

        fetch('../api/ride_action.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ride_id: activeRideId, status: nextStatus })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.status === 'success') {
                syncState();
            } else {
                alert(data.message);
            }
        });
    }
</script>

<?php require_once 'footer.php'; ?>
