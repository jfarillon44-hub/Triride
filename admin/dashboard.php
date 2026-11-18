<?php
$page_title = 'Admin Dashboard';
$page_header = 'Dashboard Overview';
$active_menu = 'dashboard';

require_once '../config.php';
require_login(['Admin']);

// 1. Fetch KPI Counts
$u_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users");
$users_count = mysqli_fetch_assoc($u_res)['count'];

$d_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers");
$drivers_count = mysqli_fetch_assoc($d_res)['count'];

$r_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM rides WHERE DATE(request_time) = CURDATE()");
$rides_today = mysqli_fetch_assoc($r_res)['count'];

$e_res = mysqli_query($conn, "SELECT SUM(amount) AS total FROM payments WHERE status = 'Paid'");
$total_earnings = mysqli_fetch_assoc($e_res)['total'] ?? 0.00;

// 2. Fetch Chart Points (Past 7 Days of Ride counts)
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    
    $c_res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM rides WHERE DATE(request_time) = '$date'");
    $count = mysqli_fetch_assoc($c_res)['count'];
    $chart_data[$day_name] = $count;
}

// Prepare SVG Line points
// Grid size: width = 600, height = 200
$points = "";
$max_val = max(array_values($chart_data));
if ($max_val < 5) $max_val = 5; // buffer

$x_step = 600 / 6;
$x = 0;
$chart_points_arr = [];

foreach ($chart_data as $day => $count) {
    // Invert y because SVG 0 is top
    $y = 200 - ($count / $max_val * 150) - 25; 
    $chart_points_arr[] = ['x' => $x, 'y' => $y, 'day' => $day, 'val' => $count];
    $points .= "$x,$y ";
    $x += $x_step;
}

// 3. Fetch Recent Rides list
$recent_rides = mysqli_query($conn, "SELECT r.*, u.fullname AS passenger_name, d.fullname AS driver_name 
                                     FROM rides r 
                                     JOIN users u ON r.user_id = u.id 
                                     LEFT JOIN drivers d ON r.driver_id = d.id 
                                     ORDER BY r.id DESC LIMIT 5");

require_once 'header.php';
?>

<!-- KPI Stats Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-details">
            <p>Total Passengers</p>
            <h2><?php echo $users_count; ?></h2>
        </div>
        <div class="admin-stat-icon-circle users">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-details">
            <p>Active Drivers</p>
            <h2><?php echo $drivers_count; ?></h2>
        </div>
        <div class="admin-stat-icon-circle drivers">
            <i class="fa-solid fa-id-card"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-details">
            <p>Rides Today</p>
            <h2><?php echo $rides_today; ?></h2>
        </div>
        <div class="admin-stat-icon-circle rides">
            <i class="fa-solid fa-route"></i>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-details">
            <p>Total Earnings</p>
            <h2>P<?php echo number_format($total_earnings, 2); ?></h2>
        </div>
        <div class="admin-stat-icon-circle earnings">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>
</div>

<!-- Chart Panel -->
<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-chart-line" style="color:var(--primary-color);"></i> Rides Overview (This Week)</h3>
    </div>
    <div class="admin-panel-body">
        <div class="chart-container">
            <svg viewBox="0 0 600 200" style="width: 100%; height: 100%;">
                <!-- Grid Lines -->
                <line x1="0" y1="25" x2="600" y2="25" class="svg-chart-grid" />
                <line x1="0" y1="75" x2="600" y2="75" class="svg-chart-grid" />
                <line x1="0" y1="125" x2="600" y2="125" class="svg-chart-grid" />
                <line x1="0" y1="175" x2="600" y2="175" class="svg-chart-grid" />

                <!-- Chart Line -->
                <polyline points="<?php echo trim($points); ?>" class="svg-chart-line" />

                <!-- Chart Dots and Text -->
                <?php foreach ($chart_points_arr as $pt): ?>
                    <circle cx="<?php echo $pt['x']; ?>" cy="<?php echo $pt['y']; ?>" r="5" class="svg-chart-dot" />
                    <!-- Count label above dot -->
                    <text x="<?php echo $pt['x']; ?>" y="<?php echo $pt['y'] - 12; ?>" font-size="10" text-anchor="middle" font-weight="700" fill="var(--primary-color)">
                        <?php echo $pt['val']; ?>
                    </text>
                    <!-- Day label below baseline -->
                    <text x="<?php echo $pt['x']; ?>" y="195" font-size="10" text-anchor="middle" font-weight="600" fill="var(--text-muted)">
                        <?php echo $pt['day']; ?>
                    </text>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>
</div>

<!-- Recent Rides list -->
<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-clock" style="color:var(--primary-color);"></i> Recent Rides</h3>
        <a href="rides.php" class="btn btn-secondary" style="width:auto; padding: 0.35rem 1rem; font-size: 0.8rem;">Monitor All Rides</a>
    </div>
    <div class="admin-panel-body" style="padding:0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ride ID</th>
                        <th>Passenger Name</th>
                        <th>Driver Name</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Fare</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_rides) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:2rem;">No bookings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($recent_rides)): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                                <td style="max-width: 150px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($row['pickup_location']); ?></td>
                                <td style="max-width: 150px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($row['destination']); ?></td>
                                <td>P<?php echo number_format($row['fare'], 2); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Completed'): ?>
                                        <span class="badge badge-success">Completed</span>
                                    <?php elseif ($row['status'] === 'Cancelled'): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php elseif ($row['status'] === 'Pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><?php echo $row['status']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
