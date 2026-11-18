<?php
require_once 'config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'Driver') {
        header("Location: driver/dashboard.php");
    } else {
        header("Location: passenger/dashboard.php");
    }
    exit();
}

$error_msg = '';
$success_msg = '';
$settings = get_settings();

// Determine default active tab
$active_tab = isset($_GET['type']) && $_GET['type'] === 'driver' ? 'driver' : 'passenger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_type = sanitize($_POST['role_type']);
    $fullname = sanitize($_POST['fullname']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Ensure all fields are filled
    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
        $active_tab = $role_type;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email address.";
        $active_tab = $role_type;
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
        $active_tab = $role_type;
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($role_type === 'driver') {
            $license_no = sanitize($_POST['license_no']);
            $vehicle_info = sanitize($_POST['vehicle_info']);
            
            if (empty($license_no)) {
                $error_msg = "License number is required for drivers.";
                $active_tab = 'driver';
            } else {
                // Check if license, email or phone is already taken
                $check_sql = "SELECT id FROM drivers WHERE email = ? OR phone = ? OR license_no = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("sss", $email, $phone, $license_no);
                $stmt->execute();
                $check_res = $stmt->get_result();
                
                if ($check_res->num_rows > 0) {
                    $error_msg = "Email, phone number, or driver license is already registered.";
                    $active_tab = 'driver';
                } else {
                    $insert_sql = "INSERT INTO drivers (fullname, phone, license_no, email, password, status, rating, vehicle_info) VALUES (?, ?, ?, ?, ?, 'Offline', 5.00, ?)";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("ssssss", $fullname, $phone, $license_no, $email, $hashed_password, $vehicle_info);
                    
                    if ($stmt->execute()) {
                        $success_msg = "Driver registration successful! You can now log in.";
                        $active_tab = 'driver';
                    } else {
                        $error_msg = "Registration failed. Please try again.";
                        $active_tab = 'driver';
                    }
                }
            }
        } else {
            // Passenger Registration
            // Check if email or phone is already taken in users
            $check_sql = "SELECT id FROM users WHERE email = ? OR phone = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $check_res = $stmt->get_result();
            
            if ($check_res->num_rows > 0) {
                $error_msg = "Email or phone number is already registered.";
                $active_tab = 'passenger';
            } else {
                $insert_sql = "INSERT INTO users (fullname, phone, email, password, wallet_balance) VALUES (?, ?, ?, ?, 150.00)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssss", $fullname, $phone, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $success_msg = "Passenger registration successful! You can now log in.";
                    $active_tab = 'passenger';
                } else {
                    $error_msg = "Registration failed. Please try again.";
                    $active_tab = 'passenger';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo htmlspecialchars($settings['app_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            background: transparent;
            font-size: 0.9rem;
            color: var(--text-muted);
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 480px;">
            <div class="auth-header">
                <a href="index.php" class="auth-logo">
                    <img src="assets/img/logo.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'48\' height=\'48\'><rect width=\'100\' height=\'100\' fill=\'%23006430\' rx=\'20\'/><text x=\'50\' y=\'65\' font-size=\'45\' text-anchor=\'middle\' fill=\'white\'>🛺</text></svg>';" alt="TriRide Logo">
                    <h2 class="auth-title"><?php echo htmlspecialchars($settings['app_name']); ?></h2>
                </a>
                <h3 id="reg-title" class="auth-subtitle">Create Passenger Account</h3>
                <p id="reg-desc" class="text-muted" style="font-size: 0.85rem;">Join TriRide today!</p>
            </div>

            <!-- Role Selector Tabs -->
            <div class="tabs">
                <button type="button" class="tab-btn <?php echo $active_tab === 'passenger' ? 'active' : ''; ?>" onclick="switchTab('passenger')">Passenger</button>
                <button type="button" class="tab-btn <?php echo $active_tab === 'driver' ? 'active' : ''; ?>" onclick="switchTab('driver')">Tricycle Driver</button>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="reg-form">
                <!-- Hidden inputs -->
                <input type="hidden" name="role_type" id="role_type" value="<?php echo htmlspecialchars($active_tab); ?>">

                <div class="form-group">
                    <label class="form-label" for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) && !$success_msg ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) && !$success_msg ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="09123456789" required value="<?php echo isset($_POST['phone']) && !$success_msg ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <!-- Driver Specific Fields -->
                <div id="driver-fields" style="<?php echo $active_tab === 'driver' ? '' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label class="form-label" for="license_no">Driver License No.</label>
                        <input type="text" id="license_no" name="license_no" class="form-control" placeholder="L-12345-67890" <?php echo $active_tab === 'driver' ? 'required' : ''; ?> value="<?php echo isset($_POST['license_no']) && !$success_msg ? htmlspecialchars($_POST['license_no']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="vehicle_info">Tricycle Description</label>
                        <input type="text" id="vehicle_info" name="vehicle_info" class="form-control" placeholder="Green Tricycle, Sidecar #42" value="<?php echo isset($_POST['vehicle_info']) && !$success_msg ? htmlspecialchars($_POST['vehicle_info']) : 'Green Tricycle'; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 0.25rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; display: flex; align-items: center; justify-content: center; height: 100%; width: 2.5rem; outline: none;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; right: 0.25rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; display: flex; align-items: center; justify-content: center; height: 100%; width: 2.5rem; outline: none;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="btn-register" style="margin-top: 1rem;">Register</button>
            </form>

            <div class="auth-links">
                <span class="text-muted">Already have an account?</span>
                <a href="login.php" class="auth-link">Login here</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(role) {
            document.getElementById('role_type').value = role;
            
            // Buttons styling
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Activate clicked
            if (role === 'passenger') {
                event.target.classList.add('active');
                document.getElementById('reg-title').innerText = 'Create Passenger Account';
                document.getElementById('reg-desc').innerText = 'Join TriRide today!';
                document.getElementById('driver-fields').style.display = 'none';
                document.getElementById('license_no').removeAttribute('required');
            } else {
                event.target.classList.add('active');
                document.getElementById('reg-title').innerText = 'Create Driver Account';
                document.getElementById('reg-desc').innerText = 'Become a TriRide Tricycle Partner!';
                document.getElementById('driver-fields').style.display = 'block';
                document.getElementById('license_no').setAttribute('required', 'required');
            }
        }

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
