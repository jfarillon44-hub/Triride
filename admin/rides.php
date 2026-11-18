<?php
$page_title = 'Rides Monitor';
$page_header = 'System Ride Transactions';
$active_menu = 'rides';

require_once '../config.php';
require_login(['Admin']);

$rides = mysqli_query($conn, "SELECT r.*, u.fullname AS passenger_name, d.fullname AS driver_name 
                             FROM rides r 
                             JOIN users u ON r.user_id = u.id 
                             LEFT JOIN drivers d ON r.driver_id = d.id 
                             ORDER BY r.id DESC");
require_once 'header.php';
?>

<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title">Active & Historical Rides</h3>
    </div>
    <div class="admin-panel-body" style="padding:0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ride ID</th>
                        <th>Passenger</th>
                        <th>Assigned Driver</th>
                        <th>Pickup Location</th>
                        <th>Destination</th>
                        <th>Fare</th>
                        <th>Distance</th>
                        <th>Request Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($rides) === 0): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:2rem;">No rides recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($rides)): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo htmlspecialchars($row['pickup_location']); ?></td>
                                <td><?php echo htmlspecialchars($row['destination']); ?></td>
                                <td>P<?php echo number_format($row['fare'], 2); ?></td>
                                <td><?php echo $row['distance'] ?? '0.0'; ?> km</td>
                                <td><?php echo date('M d, Y · h:i A', strtotime($row['request_time'])); ?></td>
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
