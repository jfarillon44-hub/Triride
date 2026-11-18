<?php
$page_title = 'Ride History';
$page_header_title = 'Ride History';
$active_tab = 'history';
$show_back_btn = true;
$back_url = 'dashboard.php';

require_once 'header.php';

// Fetch ride history
$history_sql = "SELECT r.*, d.fullname AS driver_name, p.status AS payment_status, p.method AS payment_method 
                FROM rides r 
                LEFT JOIN drivers d ON r.driver_id = d.id 
                LEFT JOIN payments p ON p.ride_id = r.id
                WHERE r.user_id = ? 
                ORDER BY r.id DESC";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();
?>

<div class="app-content">
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        
        <?php if ($history_result->num_rows === 0): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 1rem;">📭</span>
                <p class="text-muted" style="font-size: 0.85rem;">No rides booked yet.</p>
                <a href="book.php" class="btn btn-primary" style="margin-top: 1rem; font-size: 0.85rem;">Book a Ride Now</a>
            </div>
        <?php else: ?>
            
            <?php while($row = $history_result->fetch_assoc()): ?>
                <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                            <?php echo date('M d, Y · h:i A', strtotime($row['request_time'])); ?>
                        </span>
                        
                        <?php if ($row['status'] === 'Completed'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php elseif ($row['status'] === 'Cancelled'): ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo $row['status']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Location info summary -->
                    <div style="font-size: 0.8rem; margin-bottom: 0.75rem; line-height: 1.5;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-circle" style="color: var(--accent-green); font-size: 0.5rem;"></i>
                            <span style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($row['pickup_location']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-circle" style="color: var(--accent-red); font-size: 0.5rem;"></i>
                            <span style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($row['destination']); ?></span>
                        </div>
                    </div>

                    <!-- Driver and Payment summary -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.5rem; font-size: 0.75rem;">
                        <div style="color: var(--text-muted);">
                            Driver: <strong><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-color);">P<?php echo number_format($row['fare'], 2); ?></span>
                            <?php if ($row['payment_status'] === 'Paid'): ?>
                                <span style="color: var(--accent-green); font-size: 0.65rem; font-weight: 700; margin-left: 0.25rem;">(Paid via <?php echo $row['payment_method']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>

    </div>
</div>

<?php require_once 'footer.php'; ?>
