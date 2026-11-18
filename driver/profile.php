<?php
$page_title = 'Driver Profile';
$page_header_title = 'Profile';
$active_tab = 'profile';
$show_back_btn = true;
$back_url = 'dashboard.php';

require_once '../config.php';
require_login(['Driver']);

$user_id = $_SESSION['user_id'];
$update_error = '';
$update_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $license_no = sanitize($_POST['license_no']);
    $vehicle_info = sanitize($_POST['vehicle_info']);
    $password = $_POST['password'];

    if (empty($fullname) || empty($phone) || empty($email) || empty($license_no)) {
        $update_error = "All fields except password are required.";
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE drivers SET fullname = ?, phone = ?, email = ?, license_no = ?, vehicle_info = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssi", $fullname, $phone, $email, $license_no, $vehicle_info, $hashed, $user_id);
        } else {
            $update_sql = "UPDATE drivers SET fullname = ?, phone = ?, email = ?, license_no = ?, vehicle_info = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssi", $fullname, $phone, $email, $license_no, $vehicle_info, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['vehicle_info'] = $vehicle_info;
            $update_success = "Profile updated successfully!";
            
            // Reload driver details
            $d_sql = "SELECT * FROM drivers WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($d_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $driver = $stmt->get_result()->fetch_assoc();
        } else {
            $update_error = "Could not update profile. Email or license number may be in use.";
        }
    }
}

require_once 'header.php';
?>

<div class="app-content">

    <!-- Profile Details Card -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm);">
        <h4 style="font-size: 0.95rem; color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <i class="fa-solid fa-user-edit"></i> Edit Driver Details
        </h4>

        <?php if (!empty($update_error)): ?>
            <div class="alert alert-danger" style="padding: 0.5rem; font-size: 0.75rem; margin-bottom: 0.75rem;">
                <?php echo $update_error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($update_success)): ?>
            <div class="alert alert-success" style="padding: 0.5rem; font-size: 0.75rem; margin-bottom: 0.75rem;">
                <?php echo $update_success; ?>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST">
            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Full Name</label>
                <input type="text" name="fullname" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($driver['fullname']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Email Address</label>
                <input type="email" name="email" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($driver['email']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Phone Number</label>
                <input type="tel" name="phone" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($driver['phone']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Driver License No.</label>
                <input type="text" name="license_no" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($driver['license_no']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Tricycle Description</label>
                <input type="text" name="vehicle_info" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($driver['vehicle_info']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Password (Leave blank to keep same)</label>
                <input type="password" name="password" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" placeholder="New password">
            </div>

            <button type="submit" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.6rem; margin-top: 0.5rem;">Save Changes</button>
        </form>

        <a href="../logout.php" class="btn btn-outline" style="font-size: 0.85rem; padding: 0.6rem; margin-top: 0.75rem; border-color: var(--accent-red); color: var(--accent-red);">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </div>

</div>

<?php require_once 'footer.php'; ?>
