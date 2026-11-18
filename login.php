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
$settings = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize($_POST['email_phone']);
    $password = $_POST['password'];

    if (empty($login_input) || empty($password)) {
        $error_msg = "Please fill in all fields.";
    } else {
        // 1. Check Admin Table first (email matches)
        $admin_sql = "SELECT * FROM admins WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($admin_sql);
        $stmt->bind_param("s", $login_input);
        $stmt->execute();
        $admin_result = $stmt->get_result();

        if ($admin_result && $admin_result->num_rows > 0) {
            $admin = $admin_result->fetch_assoc();
            if (verify_password($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['fullname'] = $admin['fullname'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = 'Admin';
                header("Location: admin/dashboard.php");
                exit();
            }
        }

        // 2. Check Drivers Table (email or phone matches)
        $driver_sql = "SELECT * FROM drivers WHERE email = ? OR phone = ? LIMIT 1";
        $stmt = $conn->prepare($driver_sql);
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $driver_result = $stmt->get_result();

        if ($driver_result && $driver_result->num_rows > 0) {
            $driver = $driver_result->fetch_assoc();
            // Check suspension status
            if ($driver['status'] === 'Suspended') {
                $error_msg = "Your driver account is suspended. Please contact administrator.";
            } elseif (verify_password($password, $driver['password'])) {
                $_SESSION['user_id'] = $driver['id'];
                $_SESSION['fullname'] = $driver['fullname'];
                $_SESSION['email'] = $driver['email'];
                $_SESSION['role'] = 'Driver';
                $_SESSION['vehicle_info'] = $driver['vehicle_info'];
                header("Location: driver/dashboard.php");
                exit();
            }
        }

        // 3. Check Users Table (Passenger: email or phone matches)
        $user_sql = "SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1";
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $user_result = $stmt->get_result();

        if ($user_result && $user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            if (verify_password($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'Passenger';
                header("Location: passenger/dashboard.php");
                exit();
            }
        }

        $error_msg = "Invalid email/phone number or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($settings['app_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="auth-logo">
                    <img src="assets/img/logo.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'48\' height=\'48\'><rect width=\'100\' height=\'100\' fill=\'%23006430\' rx=\'20\'/><text x=\'50\' y=\'65\' font-size=\'45\' text-anchor=\'middle\' fill=\'white\'>🛺</text></svg>';" alt="TriRide Logo">
                    <h2 class="auth-title"><?php echo htmlspecialchars($settings['app_name']); ?></h2>
                </a>
                <h3 class="auth-subtitle">Welcome Back!</h3>
                <p class="text-muted" style="font-size: 0.85rem;">Login to your account</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email_phone">Email or Phone number</label>
                    <input type="text" id="email_phone" name="email_phone" class="form-control" placeholder="Enter your email or phone number" required value="<?php echo isset($_POST['email_phone']) ? htmlspecialchars($_POST['email_phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 0.25rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; display: flex; align-items: center; justify-content: center; height: 100%; width: 2.5rem; outline: none;">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <label class="form-checkbox">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                    <a href="#" class="auth-link" style="font-size: 0.8rem;" onclick="alert('Demo Feature: Please contact admin if password is lost.');">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="auth-links">
                <span class="text-muted">Don't have an account?</span>
                <a href="register.php" class="auth-link">Register here</a>
            </div>
        </div>
    </div>

    <script>
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
