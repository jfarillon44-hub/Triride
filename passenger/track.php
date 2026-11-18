<?php
$page_title = 'Tracking Ride';
$page_header_title = 'Tracking Ride';
$active_tab = 'home';
$show_back_btn = false;

require_once 'header.php';
?>

<!-- Map View container (collapses if searching) -->
<div id="searching-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 2rem; text-align: center;">
    <div style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--primary-light); border-top-color: var(--primary-color); animation: spin 1s infinite linear; margin-bottom: 2rem;"></div>
    <h3 style="color: var(--primary-color);">Finding Nearby Drivers...</h3>
    <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.5rem; max-width: 280px;">Matching you with the closest tricycle operator in Prosperidad.</p>
    
    <div style="margin-top: 3rem; width: 100%;">
        <button class="btn btn-danger" onclick="cancelRide()">Cancel Booking</button>
    </div>
</div>

<div id="tracking-container" style="display: none; flex-direction: column; flex: 1; position: relative;">
    <!-- Trip Badge Indicator -->
    <div id="trip-badge-bar" style="background: var(--primary-color); color: white; text-align: center; padding: 0.5rem; font-size: 0.8rem; font-weight: 700; z-index: 600;">
        Driver is on the way!
    </div>

    <!-- Map Area -->
    <div id="map-tracking" style="flex: 1; width: 100%; min-height: 350px; z-index: 1;"></div>

    <!-- Driver Card details -->
    <div class="floating-trip-info" style="position: relative; margin: 1rem; border: 1px solid var(--border-color); bottom: 0; left: 0; right: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    👨🏻‍✈️
                </div>
                <div>
                    <strong style="font-size: 0.95rem; display: block;" id="driver-name">Juan Dela Cruz</strong>
                    <span style="font-size: 0.75rem; color: var(--accent-yellow); font-weight: 700;">
                        <i class="fa-solid fa-star"></i> <span id="driver-rating">4.8</span>
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;" id="driver-vehicle">Green Tricycle</span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <!-- Chat & Phone actions -->
                <a href="chat.php" class="btn btn-secondary" style="width: 40px; height: 40px; border-radius: 50%; padding:0; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fa-solid fa-comments"></i>
                </a>
                <a href="#" id="driver-phone-link" class="btn btn-outline" style="width: 40px; height: 40px; border-radius: 50%; padding:0; display:flex; align-items:center; justify-content:center; font-size:1.1rem;" onclick="alert('Calling driver: ' + this.getAttribute('data-phone'))">
                    <i class="fa-solid fa-phone"></i>
                </a>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
            <div>
                <span class="text-muted" style="display:block;">Estimated Fare</span>
                <strong id="trip-fare" style="font-size: 1rem; color: var(--primary-color);">P0.00</strong>
            </div>
            <div>
                <span class="text-muted" style="display:block;">Distance</span>
                <strong id="trip-dist" style="font-size: 1rem;">0.0 km</strong>
            </div>
        </div>

        <button class="btn btn-danger" style="margin-top: 0.75rem; padding: 0.5rem; font-size: 0.85rem;" onclick="cancelRide()">Cancel Ride</button>
    </div>
</div>

<style>
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
</style>

<!-- Leaflet Map Integration and Polling actions -->
<script>
    let map = null;
    let passengerMarker = null;
    let destinationMarker = null;
    let driverMarker = null;

    function initMap(plat, plng, dlat, dlng) {
        if (map) return;

        // Initialize Leaflet Map centered at passenger pickup
        map = L.map('map-tracking').setView([plat, plng], 15);

        // Add OSM tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Green Circle marker for Pickup
        passengerMarker = L.circleMarker([plat, plng], {
            radius: 8,
            fillColor: "#28a745",
            color: "white",
            weight: 3,
            fillOpacity: 1
        }).addTo(map).bindPopup("Pickup Location");

        // Red Circle marker for Destination
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

        // Driver Marker (placed initially at pickup or placeholder)
        driverMarker = L.marker([plat, plng], { icon: tricycleIcon }).addTo(map).bindPopup("Your Tricycle Driver");

        // Fit boundaries
        const bounds = L.latLngBounds([plat, plng], [dlat, dlng]);
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    function onSyncUpdate(data) {
        const ride = data.active_ride;
        if (!ride) return;

        if (ride.status === 'Pending') {
            document.getElementById('searching-container').style.display = 'flex';
            document.getElementById('tracking-container').style.display = 'none';
        } else {
            document.getElementById('searching-container').style.display = 'none';
            document.getElementById('tracking-container').style.display = 'flex';

            // Set text details
            document.getElementById('driver-name').innerText = ride.driver_name || 'Juan Dela Cruz';
            document.getElementById('driver-rating').innerText = ride.driver_rating || '4.8';
            document.getElementById('driver-vehicle').innerText = ride.driver_vehicle || 'Green Tricycle';
            document.getElementById('trip-fare').innerText = 'P' + ride.fare;
            document.getElementById('trip-dist').innerText = ride.distance + ' km';
            
            const phoneBtn = document.getElementById('driver-phone-link');
            phoneBtn.setAttribute('data-phone', ride.driver_phone);

            // Update badge bar
            const badge = document.getElementById('trip-badge-bar');
            if (ride.status === 'Accepted') {
                badge.innerText = 'Driver accepted booking, on the way!';
                badge.style.background = 'var(--primary-color)';
            } else if (ride.status === 'Arrived') {
                badge.innerText = 'Driver has arrived at your location!';
                badge.style.background = 'var(--accent-green)';
            } else if (ride.status === 'Picked Up') {
                badge.innerText = 'On trip to your destination...';
                badge.style.background = 'var(--accent-orange)';
            }

            // Init Map and Markers
            initMap(ride.pickup_lat, ride.pickup_lng, ride.dest_lat, ride.dest_lng);

            // Dynamically update driver position
            if (driverMarker && ride.driver_lat && ride.driver_lng) {
                const driverLatLng = new L.LatLng(ride.driver_lat, ride.driver_lng);
                driverMarker.setLatLng(driverLatLng);
            }
        }
    }

    function cancelRide() {
        if (!confirm('Are you sure you want to cancel this booking?')) return;
        if (!activeRideId) return;

        fetch('../api/ride_action.php?action=cancel_ride', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ride_id: activeRideId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'dashboard.php';
            } else {
                alert(data.message);
            }
        });
    }
</script>

<?php require_once 'footer.php'; ?>
