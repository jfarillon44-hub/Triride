<?php
$page_title = 'My Profile';
$page_header_title = 'Profile';
$active_tab = 'profile';
$show_back_btn = true;
$back_url = 'dashboard.php';

require_once 'header.php';

$update_error = '';
$update_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullname) || empty($phone) || empty($email)) {
        $update_error = "All profile fields are required.";
    } else {
        // Update user
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET fullname = ?, phone = ?, email = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssi", $fullname, $phone, $email, $hashed, $user_id);
        } else {
            $update_sql = "UPDATE users SET fullname = ?, phone = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssi", $fullname, $phone, $email, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $update_success = "Profile updated successfully!";
            
            // Reload user
            $p_sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($p_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $passenger = $stmt->get_result()->fetch_assoc();
        } else {
            $update_error = "Could not update profile. Email might be in use.";
        }
    }
}
?>

<div class="app-content">
    
    <!-- Balance Summary Card -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; text-align: center;">
        <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Wallet Balance</span>
        <h2 style="font-size: 1.85rem; color: var(--primary-color); font-weight: 700; margin-top: 0.25rem;" id="profile-wallet-bal">
            P<?php echo number_format($passenger['wallet_balance'], 2); ?>
        </h2>
        <button class="btn btn-secondary" style="margin-top: 0.75rem; font-size: 0.8rem; padding: 0.4rem 1rem; width: auto;" onclick="openTopUpModal()">
            <i class="fa-solid fa-plus-circle"></i> Add Funds
        </button>
    </div>

    <!-- Edit Profile Card -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; box-shadow: var(--shadow-sm);">
        <h4 style="font-size: 0.95rem; color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <i class="fa-solid fa-user-edit"></i> Edit Information
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
                <input type="text" name="fullname" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($passenger['fullname']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Email Address</label>
                <input type="email" name="email" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($passenger['email']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Phone Number</label>
                <input type="tel" name="phone" class="form-control" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;" required value="<?php echo htmlspecialchars($passenger['phone']); ?>">
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

<!-- Listen to sync events to update wallet balance in profile -->
<script>
    function onSyncUpdate(data) {
        if (data.status === 'success') {
            document.getElementById('profile-wallet-bal').innerText = 'P' + data.wallet_balance;
        }
    }
</script>

<?php require_once 'footer.php'; ?>
