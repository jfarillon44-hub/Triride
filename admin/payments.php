<?php
$page_title = 'Payments Audit';
$page_header = 'Transaction Payments Logs';
$active_menu = 'payments';

require_once '../config.php';
require_login(['Admin']);

$payments = mysqli_query($conn, "SELECT p.*, r.id AS ride_id, u.fullname AS passenger_name, d.fullname AS driver_name 
                                 FROM payments p 
                                 JOIN rides r ON p.ride_id = r.id 
                                 JOIN users u ON r.user_id = u.id 
                                 LEFT JOIN drivers d ON r.driver_id = d.id 
                                 ORDER BY p.id DESC");
require_once 'header.php';
?>

<div class="admin-panel-card">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title">Payment Transactions Log</h3>
    </div>
    <div class="admin-panel-body" style="padding:0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Ride ID</th>
                        <th>Passenger</th>
                        <th>Driver</th>
                        <th>Amount Paid</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($payments) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:2rem;">No payment transactions logged.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($payments)): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>#<?php echo $row['ride_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['passenger_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['driver_name'] ?? 'Unassigned'); ?></td>
                                <td>P<?php echo number_format($row['amount'], 2); ?></td>
                                <td>
                                    <?php if ($row['method'] === 'Cash'): ?>
                                        <i class="fa-solid fa-money-bill-wave" style="color:#28a745;"></i> Cash
                                    <?php elseif ($row['method'] === 'Wallet'): ?>
                                        <i class="fa-solid fa-wallet" style="color:var(--primary-color);"></i> Wallet
                                    <?php else: ?>
                                        <i class="fa-solid fa-mobile-screen-button" style="color:#2563eb;"></i> <?php echo $row['method']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
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
