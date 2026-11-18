<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

$response = ['status' => 'error', 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    if ($action === 'topup') {
        if ($role !== 'Passenger') {
            echo json_encode(['status' => 'error', 'message' => 'Only passengers can top up.']);
            exit();
        }

        $amount = floatval($data['amount'] ?? 0);
        if ($amount <= 0) {
            $response = ['status' => 'error', 'message' => 'Invalid amount.'];
        } else {
            $update_sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("di", $amount, $user_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Wallet topped up successfully!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not top up.'];
            }
        }

    } elseif ($action === 'pay_ride') {
        if ($role !== 'Passenger') {
            echo json_encode(['status' => 'error', 'message' => 'Only passengers can authorize payments.']);
            exit();
        }

        $ride_id = intval($data['ride_id']);
        $method = sanitize($data['method']); // 'Cash', 'GCash', 'Maya', 'PayPal', 'Wallet'

        // Get ride details
        $ride_sql = "SELECT fare FROM rides WHERE id = ? AND user_id = ? LIMIT 1";
        $stmt = $conn->prepare($ride_sql);
        $stmt->bind_param("ii", $ride_id, $user_id);
        $stmt->execute();
        $ride_res = $stmt->get_result();

        if ($ride_res->num_rows === 0) {
            $response = ['status' => 'error', 'message' => 'Ride not found.'];
        } else {
            $ride = $ride_res->fetch_assoc();
            $amount = $ride['fare'];

            // Process payment method (GCash, Maya, PayPal, and Wallet deduct from the user's wallet balance)
            if (in_array($method, ['GCash', 'Maya', 'PayPal', 'Wallet'])) {
                // Check if user has enough balance
                $user_sql = "SELECT wallet_balance FROM users WHERE id = ? LIMIT 1";
                $stmt = $conn->prepare($user_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if ($user['wallet_balance'] < $amount) {
                    echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance. Please top up first.']);
                    exit();
                }

                // Deduct wallet balance
                $deduct_sql = "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?";
                $stmt = $conn->prepare($deduct_sql);
                $stmt->bind_param("di", $amount, $user_id);
                $stmt->execute();
            }

            // Update payment record in database
            // Note: payments might already exist (created by driver's Completed status) or we create it.
            $check_pmt = "SELECT id FROM payments WHERE ride_id = ? LIMIT 1";
            $stmt = $conn->prepare($check_pmt);
            $stmt->bind_param("i", $ride_id);
            $stmt->execute();
            $pmt_res = $stmt->get_result();

            $pay_status = 'Paid';

            if ($pmt_res->num_rows > 0) {
                $pmt = $pmt_res->fetch_assoc();
                $update_pmt = "UPDATE payments SET method = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($update_pmt);
                $stmt->bind_param("ssi", $method, $pay_status, $pmt['id']);
                $stmt->execute();
            } else {
                $insert_pmt = "INSERT INTO payments (ride_id, amount, method, status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_pmt);
                $stmt->bind_param("idss", $ride_id, $amount, $method, $pay_status);
                $stmt->execute();
            }

            // Set ride status to Completed just in case, but usually driver does it
            $update_ride = "UPDATE rides SET status = 'Completed', end_time = CURRENT_TIMESTAMP WHERE id = ? AND status != 'Completed'";
            $stmt = $conn->prepare($update_ride);
            $stmt->bind_param("i", $ride_id);
            $stmt->execute();

            $response = ['status' => 'success', 'message' => 'Payment processed successfully!'];
        }
    }
}

echo json_encode($response);
exit();
?>
