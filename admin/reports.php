<?php
$page_title = 'Reports Panel';
$page_header = 'System Operational Reports';
$active_menu = 'reports';

require_once '../config.php';
require_login(['Admin']);

$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? sanitize($_GET['report_type']) : 'Daily Rides Report';

// Handle CSV / Excel Export request
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=triride_report_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['TriRide Operational Report', '', '', '', '']);
    fputcsv($output, ['Report Type:', $report_type, '', '', '']);
    fputcsv($output, ['Period:', $date_from . ' to ' . $date_to, '', '', '']);
    fputcsv($output, []);
    fputcsv($output, ['Date', 'Total Rides', 'Total Earnings (PHP)', 'Completed Rides', 'Cancelled Rides']);
    
    // Fetch query
    $where = "WHERE DATE(request_time) BETWEEN '$date_from' AND '$date_to'";
    $sql = "SELECT DATE(request_time) AS date_label, 
                   COUNT(*) AS total_rides, 
                   SUM(CASE WHEN status='Completed' THEN fare ELSE 0 END) AS total_earnings, 
                   SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed_count, 
                   SUM(CASE WHEN status='Cancelled' THEN 1 ELSE 0 END) AS cancelled_count 
            FROM rides 
            $where 
            GROUP BY DATE(request_time) 
            ORDER BY DATE(request_time) DESC";
    
    $res = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($res)) {
        fputcsv($output, [
            $row['date_label'],
            $row['total_rides'],
            number_format($row['total_earnings'], 2),
            $row['completed_count'],
            $row['cancelled_count']
        ]);
    }
    fclose($output);
    exit();
}

// Fetch report data
$where = "WHERE DATE(request_time) BETWEEN '$date_from' AND '$date_to'";
$sql = "SELECT DATE(request_time) AS date_label, 
               COUNT(*) AS total_rides, 
               SUM(CASE WHEN status='Completed' THEN fare ELSE 0 END) AS total_earnings, 
               SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed_count, 
               SUM(CASE WHEN status='Cancelled' THEN 1 ELSE 0 END) AS cancelled_count 
        FROM rides 
        $where 
        GROUP BY DATE(request_time) 
        ORDER BY DATE(request_time) DESC";

$report_res = mysqli_query($conn, $sql);

// Calculate aggregates
$total_rides_sum = 0;
$total_earnings_sum = 0;
$total_completed_sum = 0;
$total_cancelled_sum = 0;

$table_rows = [];
while ($row = mysqli_fetch_assoc($report_res)) {
    $total_rides_sum += $row['total_rides'];
    $total_earnings_sum += $row['total_earnings'];
    $total_completed_sum += $row['completed_count'];
    $total_cancelled_sum += $row['cancelled_count'];
    $table_rows[] = $row;
}

require_once 'header.php';
?>

<!-- Filter Bar -->
<div class="admin-panel-card no-print">
    <div class="admin-panel-body">
        <form action="reports.php" method="GET" class="filter-bar">
            <div class="form-group" style="margin-bottom:0; flex: 1; min-width: 200px;">
                <label class="form-label" style="font-size:0.75rem;">Report Type</label>
                <select name="report_type" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                    <option value="Daily Rides Report" <?php echo $report_type === 'Daily Rides Report' ? 'selected' : ''; ?>>Daily Rides Report</option>
                    <option value="Weekly Rides Report" <?php echo $report_type === 'Weekly Rides Report' ? 'selected' : ''; ?>>Weekly Rides Report</option>
                    <option value="Earnings Summary" <?php echo $report_type === 'Earnings Summary' ? 'selected' : ''; ?>>Earnings Summary</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:0; width: 150px;">
                <label class="form-label" style="font-size:0.75rem;">Date From</label>
                <input type="date" name="date_from" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" value="<?php echo $date_from; ?>">
            </div>

            <div class="form-group" style="margin-bottom:0; width: 150px;">
                <label class="form-label" style="font-size:0.75rem;">Date To</label>
                <input type="date" name="date_to" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" value="<?php echo $date_to; ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width:auto; padding: 0.6rem 1.25rem; margin-top: 1.25rem;">
                <i class="fa-solid fa-sync"></i> Generate Report
            </button>
        </form>
    </div>
</div>

<!-- Print invoice layout -->
<div id="print-area">
    
    <div class="print-only" style="display:none; text-align:center; margin-bottom: 2rem;">
        <h2><?php echo htmlspecialchars($settings['app_name']); ?> Operational Report</h2>
        <p>Report Type: <?php echo htmlspecialchars($report_type); ?></p>
        <p>Period: <?php echo htmlspecialchars($date_from); ?> to <?php echo htmlspecialchars($date_to); ?></p>
    </div>

    <!-- Summary metrics card -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; display:flex; justify-content:space-around; text-align:center;">
        <div>
            <span class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Total Rides</span>
            <h3 style="font-size:1.5rem; color:var(--text-dark); margin-top:0.25rem;"><?php echo $total_rides_sum; ?></h3>
        </div>
        <div style="border-left: 1px solid var(--border-color); padding-left: 2rem;">
            <span class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Total Earnings</span>
            <h3 style="font-size:1.5rem; color:var(--primary-color); margin-top:0.25rem;">P<?php echo number_format($total_earnings_sum, 2); ?></h3>
        </div>
        <div style="border-left: 1px solid var(--border-color); padding-left: 2rem;">
            <span class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Completed</span>
            <h3 style="font-size:1.5rem; color:var(--accent-green); margin-top:0.25rem;"><?php echo $total_completed_sum; ?></h3>
        </div>
        <div style="border-left: 1px solid var(--border-color); padding-left: 2rem;">
            <span class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Cancelled</span>
            <h3 style="font-size:1.5rem; color:var(--accent-red); margin-top:0.25rem;"><?php echo $total_cancelled_sum; ?></h3>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="admin-panel-card">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title"><i class="fa-solid fa-table" style="color:var(--primary-color);"></i> Report Details</h3>
            
            <div class="no-print" style="display:flex; gap:0.5rem;">
                <button type="button" class="btn btn-secondary" style="width:auto; padding:0.4rem 1rem; font-size:0.8rem; background-color: var(--accent-red); color: white;" onclick="window.print()">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
                <a href="reports.php?export=excel&report_type=<?php echo urlencode($report_type); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-secondary" style="width:auto; padding:0.4rem 1rem; font-size:0.8rem; background-color: var(--accent-green); color: white;">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="admin-panel-body" style="padding:0;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Rides</th>
                            <th>Total Earnings</th>
                            <th>Completed Rides</th>
                            <th>Cancelled Rides</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($table_rows)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:2rem;">No data matches within date range.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($table_rows as $row): ?>
                                <tr>
                                    <td><strong><?php echo date('M d, Y', strtotime($row['date_label'])); ?></strong></td>
                                    <td><?php echo $row['total_rides']; ?></td>
                                    <td><strong>P<?php echo number_format($row['total_earnings'], 2); ?></strong></td>
                                    <td><span style="color:var(--accent-green); font-weight:600;"><?php echo $row['completed_count']; ?></span></td>
                                    <td><span style="color:var(--accent-red); font-weight:600;"><?php echo $row['cancelled_count']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Styles for Print (PDF) formatting -->
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #print-area, #print-area * {
            visibility: visible;
        }
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
    }
</style>

<?php require_once 'footer.php'; ?>
