<?php
$page_title = 'System Settings';
$page_header = 'TriRide Configurations';
$active_menu = 'settings';

require_once '../config.php';
require_login(['Admin']);

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_name = sanitize($_POST['app_name']);
    $base_fare = floatval($_POST['base_fare']);
    $per_km_rate = floatval($_POST['per_km_rate']);

    if (empty($app_name) || $base_fare <= 0 || $per_km_rate <= 0) {
        $error_msg = "All settings parameters must be valid values.";
    } else {
        $update = "UPDATE settings SET app_name=?, base_fare=?, per_km_rate=? WHERE id=1";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("sdd", $app_name, $base_fare, $per_km_rate);
        
        if ($stmt->execute()) {
            $success_msg = "System configurations updated successfully!";
            // Refresh settings in script
            $settings = get_settings();
        } else {
            $error_msg = "Failed to update configurations in database.";
        }
    }
}

require_once 'header.php';
?>

<div class="admin-panel-card" style="max-width: 600px;">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-gears" style="color:var(--primary-color);"></i> General System Settings</h3>
    </div>
    
    <div class="admin-panel-body">
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="settings.php" method="POST">
            <!-- App Name -->
            <div class="form-group">
                <label class="form-label" for="app_name">Application Name</label>
                <input type="text" name="app_name" id="app_name" class="form-control" placeholder="TriRide" required value="<?php echo htmlspecialchars($settings['app_name']); ?>">
            </div>

            <!-- Base Fare -->
            <div class="form-group" style="margin-top: 1.25rem;">
                <label class="form-label" for="base_fare">Base Fare (PHP)</label>
                <input type="number" name="base_fare" id="base_fare" step="0.01" min="1" class="form-control" required value="<?php echo number_format($settings['base_fare'], 2, '.', ''); ?>">
            </div>

            <!-- Per Km Rate -->
            <div class="form-group" style="margin-top: 1.25rem;">
                <label class="form-label" for="per_km_rate">Per Kilometer Rate (PHP)</label>
                <input type="number" name="per_km_rate" id="per_km_rate" step="0.01" min="0.1" class="form-control" required value="<?php echo number_format($settings['per_km_rate'], 2, '.', ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 2rem; width: auto; padding: 0.65rem 2rem;">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
