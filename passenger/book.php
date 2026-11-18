<?php
$page_title = 'Book a Ride';
$page_header_title = 'Book a Ride';
$active_tab = 'home';
$show_back_btn = true;
$back_url = 'dashboard.php';

require_once 'header.php';

// Predefined locations
$locations = [
    ['name' => 'Agusan del Sur State University', 'lat' => 8.60420, 'lng' => 125.91420],
    ['name' => 'Patin-ay Provincial Capitol', 'lat' => 8.61800, 'lng' => 125.90100],
    ['name' => 'Prosperidad Public Market', 'lat' => 8.60100, 'lng' => 125.91100],
    ['name' => 'Neco Mall, Prosperidad', 'lat' => 8.60850, 'lng' => 125.92200],
    ['name' => 'San Francisco Terminal', 'lat' => 8.50850, 'lng' => 125.97500],
];

// Get selected indexes
$p_idx = isset($_GET['pickup_index']) ? intval($_GET['pickup_index']) : 0;
$d_idx = isset($_GET['dest_index']) ? intval($_GET['dest_index']) : 3;

// Safeguard out of bounds
if ($p_idx < 0 || $p_idx >= count($locations)) $p_idx = 0;
if ($d_idx < 0 || $d_idx >= count($locations)) $d_idx = 3;
if ($p_idx === $d_idx) $d_idx = ($p_idx + 1) % count($locations);

$pickup = $locations[$p_idx];
$dest = $locations[$d_idx];

// Haversine Distance helper
function get_haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// Compute distance and add road multiplier (detour)
$straight_dist = get_haversine_distance($pickup['lat'], $pickup['lng'], $dest['lat'], $dest['lng']);
$road_multiplier = 1.6;
$distance = round($straight_dist * $road_multiplier, 1);
if ($distance < 0.8) $distance = 0.8; // minimum distance

// Load rates
$settings_data = get_settings();
$base_fare = floatval($settings_data['base_fare']);
$per_km_rate = floatval($settings_data['per_km_rate']);

// Fare calculation: base_fare + (distance * per_km_rate)
$fare = $base_fare + ($distance * $per_km_rate);
// Round to whole numbers
$fare = ceil($fare);

// Est time (e.g. 7 mins per km + 3 mins buffer)
$est_time = ceil($distance * 6) + 2;
?>

<div class="app-content">
    <div style="background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
        
        <h4 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-map-marked-alt"></i> Trip Details
        </h4>

        <!-- Locations Display -->
        <div class="ride-location-info" style="margin-bottom: 1.5rem;">
            <span class="loc-pin pickup"><i class="fa-solid fa-circle-dot"></i></span>
            <div class="loc-text">
                <span class="loc-label">Pickup Location</span>
                <span class="loc-val" style="font-size: 0.85rem;"><?php echo htmlspecialchars($pickup['name']); ?></span>
            </div>
            
            <span class="loc-pin dest"><i class="fa-solid fa-location-dot"></i></span>
            <div class="loc-text" style="margin-top: 1rem;">
                <span class="loc-label">Destination</span>
                <span class="loc-val" style="font-size: 0.85rem;"><?php echo htmlspecialchars($dest['name']); ?></span>
            </div>
        </div>

        <!-- Vehicle Type choice -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 0.75rem 0; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 45px; height: 45px; background: var(--primary-light); color: var(--primary-color); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    🛺
                </div>
                <div>
                    <strong style="font-size: 0.9rem; display: block;">Tricycle (Regular)</strong>
                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $distance; ?> km · <?php echo $est_time; ?> mins</span>
                </div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 1.15rem; font-weight: 700; color: var(--primary-color);">P<?php echo number_format($fare, 2); ?></span>
            </div>
        </div>

        <!-- Confirm Form -->
        <form id="booking-confirm-form" onsubmit="submitBooking(event)">
            <input type="hidden" name="pickup_location" id="pickup_location" value="<?php echo htmlspecialchars($pickup['name']); ?>">
            <input type="hidden" name="pickup_lat" id="pickup_lat" value="<?php echo $pickup['lat']; ?>">
            <input type="hidden" name="pickup_lng" id="pickup_lng" value="<?php echo $pickup['lng']; ?>">
            
            <input type="hidden" name="destination" id="destination" value="<?php echo htmlspecialchars($dest['name']); ?>">
            <input type="hidden" name="dest_lat" id="dest_lat" value="<?php echo $dest['lat']; ?>">
            <input type="hidden" name="dest_lng" id="dest_lng" value="<?php echo $dest['lng']; ?>">
            
            <input type="hidden" name="fare" id="fare" value="<?php echo $fare; ?>">
            <input type="hidden" name="distance" id="distance" value="<?php echo $distance; ?>">
            <input type="hidden" name="duration" id="duration" value="<?php echo $est_time; ?>">

            <button type="submit" class="btn btn-primary" style="font-size: 0.95rem;" id="btn-booking-submit">
                Confirm Booking
            </button>
        </form>
    </div>
</div>

<script>
    function submitBooking(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-booking-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Finding a driver...';

        const payload = {
            pickup_location: document.getElementById('pickup_location').value,
            pickup_lat: document.getElementById('pickup_lat').value,
            pickup_lng: document.getElementById('pickup_lng').value,
            destination: document.getElementById('destination').value,
            dest_lat: document.getElementById('dest_lat').value,
            dest_lng: document.getElementById('dest_lng').value,
            fare: document.getElementById('fare').value,
            distance: document.getElementById('distance').value,
            duration: document.getElementById('duration').value
        };

        fetch('../api/ride_action.php?action=request_ride', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'track.php';
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = 'Confirm Booking';
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = 'Confirm Booking';
        });
    }
</script>

<?php require_once 'footer.php'; ?>
