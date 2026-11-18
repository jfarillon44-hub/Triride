<?php
$page_title = 'Driver Earnings';
$page_header_title = 'Earnings';
$active_tab = 'history';
$show_back_btn = true;
$back_url = 'dashboard.php';

require_once '../config.php';
require_login(['Driver']);

$user_id = $_SESSION['user_id'];

// Get Today's Earnings
$today_sql = "SELECT SUM(amount) AS total FROM payments p 
             JOIN rides r ON p.ride_id = r.id 
             WHERE r.driver_id = ? AND r.status = 'Completed' 
             AND DATE(r.end_time) = CURDATE() AND p.status = 'Paid'";
$stmt = $conn->prepare($today_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_res = $stmt->get_result()->fetch_assoc();
$today_earnings = $today_res['total'] ?? 0.00;

// Get Weekly Earnings (past 7 days)
$week_sql = "SELECT SUM(amount) AS total FROM payments p 
             JOIN rides r ON p.ride_id = r.id 
             WHERE r.driver_id = ? AND r.status = 'Completed' 
             AND r.end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND p.status = 'Paid'";
$stmt = $conn->prepare($week_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$week_res = $stmt->get_result()->fetch_assoc();
$week_earnings = $week_res['total'] ?? 0.00;

// Get Ride History logs
$history_sql = "SELECT r.*, u.fullname AS passenger_name, p.status AS payment_status, p.method AS payment_method 
                FROM rides r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN payments p ON p.ride_id = r.id
                WHERE r.driver_id = ? 
                ORDER BY r.id DESC";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();

require_once 'header.php';
?>

<div class="app-content">
    
    <!-- Earnings Overview widgets -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
        <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; text-align: center; box-shadow: var(--shadow-sm);">
            <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Today</span>
            <h3 style="font-size: 1.35rem; color: var(--primary-color); font-weight: 700; margin-top: 0.25rem;">
                P<?php echo number_format($today_earnings, 2); ?>
            </h3>
        </div>
        <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; text-align: center; box-shadow: var(--shadow-sm);">
            <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">This Week</span>
            <h3 style="font-size: 1.35rem; color: var(--primary-color); font-weight: 700; margin-top: 0.25rem;">
                P<?php echo number_format($week_earnings, 2); ?>
            </h3>
        </div>
    </div>

    <!-- Ride Logs List -->
    <h4 style="font-size: 0.95rem; color: var(--text-dark); margin-bottom: 0.75rem;">Ride History</h4>
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        
        <?php if ($history_result->num_rows === 0): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">📭</span>
                <p class="text-muted" style="font-size: 0.85rem;">No rides completed yet.</p>
            </div>
        <?php else: ?>
            
            <?php while($row = $history_result->fetch_assoc()): ?>
                <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                            <?php echo date('M d, Y', strtotime($row['request_time'])); ?>
                        </span>
                        
                        <?php if ($row['status'] === 'Completed'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php elseif ($row['status'] === 'Cancelled'): ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?php echo $row['status']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Journey Locations -->
                    <div style="font-size: 0.8rem; margin-bottom: 0.75rem; line-height: 1.4;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <i class="fa-solid fa-circle" style="color: var(--accent-green); font-size: 0.45rem;"></i>
                            <span><?php echo htmlspecialchars($row['pickup_location']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            <i class="fa-solid fa-circle" style="color: var(--accent-red); font-size: 0.45rem;"></i>
                            <span><?php echo htmlspecialchars($row['destination']); ?></span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.5rem; font-size: 0.75rem;">
                        <div style="color: var(--text-muted);">
                            Pax: <strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-color);">P<?php echo number_format($row['fare'], 2); ?></span>
                            <?php if ($row['payment_status'] === 'Paid'): ?>
                                <span style="color: var(--accent-green); font-weight:700; font-size:0.65rem; margin-left:0.25rem;">(Paid)</span>
                            <?php else: ?>
                                <span style="color: var(--accent-red); font-weight:700; font-size:0.65rem; margin-left:0.25rem;">(Pending)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>

    </div>
</div>

<?php require_once 'footer.php'; ?>
