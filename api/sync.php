<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Unauthenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$response = ['status' => 'success'];

if ($role === 'Passenger') {
    // 1. Get Passenger details
    $user_sql = "SELECT wallet_balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_res = $stmt->get_result()->fetch_assoc();
    $response['wallet_balance'] = number_format($user_res['wallet_balance'], 2);

    // 2. Get active ride
    $ride_sql = "SELECT r.*, d.fullname AS driver_name, d.phone AS driver_phone, d.vehicle_info, d.rating AS driver_rating, 
                 d.latitude AS driver_lat, d.longitude AS driver_lng 
                 FROM rides r 
                 LEFT JOIN drivers d ON r.driver_id = d.id 
                 WHERE r.user_id = ? 
                   AND r.status != 'Cancelled' 
                   AND (r.status != 'Completed' OR EXISTS (
                       SELECT 1 FROM payments p WHERE p.ride_id = r.id AND p.status = 'Pending'
                   ))
                 ORDER BY r.id DESC LIMIT 1";
    $stmt = $conn->prepare($ride_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $ride_res = $stmt->get_result();

    if ($ride_res && $ride_res->num_rows > 0) {
        $ride = $ride_res->fetch_assoc();
        $response['active_ride'] = [
            'id' => $ride['id'],
            'pickup_location' => $ride['pickup_location'],
            'destination' => $ride['destination'],
            'pickup_lat' => $ride['pickup_lat'],
            'pickup_lng' => $ride['pickup_lng'],
            'dest_lat' => $ride['dest_lat'],
            'dest_lng' => $ride['dest_lng'],
            'status' => $ride['status'],
            'fare' => number_format($ride['fare'], 2),
            'distance' => $ride['distance'],
            'driver_id' => $ride['driver_id'],
            'driver_name' => $ride['driver_name'],
            'driver_phone' => $ride['driver_phone'],
            'driver_rating' => $ride['driver_rating'],
            'driver_vehicle' => $ride['vehicle_info'],
            'driver_lat' => $ride['driver_lat'],
            'driver_lng' => $ride['driver_lng']
        ];

        // 3. Get Chat Logs
        $chat_sql = "SELECT * FROM chats WHERE ride_id = ? ORDER BY id ASC";
        $stmt = $conn->prepare($chat_sql);
        $stmt->bind_param("i", $ride['id']);
        $stmt->execute();
        $chats = $stmt->get_result();
        
        $chat_logs = [];
        while($c = $chats->fetch_assoc()) {
            $chat_logs[] = [
                'sender' => $c['sender'],
                'message' => $c['message'],
                'timestamp' => date('h:i A', strtotime($c['timestamp']))
            ];
        }
        $response['active_ride']['chats'] = $chat_logs;
        
        // 4. Get Payment if Completed but not paid yet
        $payment_sql = "SELECT * FROM payments WHERE ride_id = ? LIMIT 1";
        $stmt = $conn->prepare($payment_sql);
        $stmt->bind_param("i", $ride['id']);
        $stmt->execute();
        $pmt = $stmt->get_result();
        if ($pmt && $pmt->num_rows > 0) {
            $payment = $pmt->fetch_assoc();
            $response['active_ride']['payment_status'] = $payment['status'];
            $response['active_ride']['payment_method'] = $payment['method'];
        } else {
            $response['active_ride']['payment_status'] = 'Pending';
        }
    } else {
        $response['active_ride'] = null;
    }

} elseif ($role === 'Driver') {
    // 1. Get Driver status and ratings
    $driver_sql = "SELECT status, rating, latitude, longitude FROM drivers WHERE id = ?";
    $stmt = $conn->prepare($driver_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $driver_res = $stmt->get_result()->fetch_assoc();
    $response['driver_status'] = $driver_res['status'];
    $response['driver_rating'] = $driver_res['rating'];

    // Calculate today's earnings
    $earnings_sql = "SELECT SUM(amount) AS total FROM payments p 
                     JOIN rides r ON p.ride_id = r.id 
                     WHERE r.driver_id = ? AND r.status = 'Completed' 
                     AND DATE(r.end_time) = CURDATE() AND p.status = 'Paid'";
    $stmt = $conn->prepare($earnings_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $earn_res = $stmt->get_result()->fetch_assoc();
    $response['today_earnings'] = number_format($earn_res['total'] ?? 0.00, 2);

    $completed_sql = "SELECT COUNT(*) AS count FROM rides WHERE driver_id = ? AND status = 'Completed'";
    $stmt = $conn->prepare($completed_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $comp_res = $stmt->get_result()->fetch_assoc();
    $response['completed_rides'] = $comp_res['count'];

    // 2. Get active assigned ride
    $ride_sql = "SELECT r.*, u.fullname AS passenger_name, u.phone AS passenger_phone 
                 FROM rides r 
                 JOIN users u ON r.user_id = u.id 
                 WHERE r.driver_id = ? AND r.status NOT IN ('Completed', 'Cancelled') 
                 LIMIT 1";
    $stmt = $conn->prepare($ride_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $ride_res = $stmt->get_result();

    if ($ride_res && $ride_res->num_rows > 0) {
        $ride = $ride_res->fetch_assoc();
        $response['active_ride'] = [
            'id' => $ride['id'],
            'pickup_location' => $ride['pickup_location'],
            'destination' => $ride['destination'],
            'pickup_lat' => $ride['pickup_lat'],
            'pickup_lng' => $ride['pickup_lng'],
            'dest_lat' => $ride['dest_lat'],
            'dest_lng' => $ride['dest_lng'],
            'status' => $ride['status'],
            'fare' => number_format($ride['fare'], 2),
            'distance' => $ride['distance'],
            'passenger_name' => $ride['passenger_name'],
            'passenger_phone' => $ride['passenger_phone']
        ];

        // Get Chat Logs
        $chat_sql = "SELECT * FROM chats WHERE ride_id = ? ORDER BY id ASC";
        $stmt = $conn->prepare($chat_sql);
        $stmt->bind_param("i", $ride['id']);
        $stmt->execute();
        $chats = $stmt->get_result();
        
        $chat_logs = [];
        while($c = $chats->fetch_assoc()) {
            $chat_logs[] = [
                'sender' => $c['sender'],
                'message' => $c['message'],
                'timestamp' => date('h:i A', strtotime($c['timestamp']))
            ];
        }
        $response['active_ride']['chats'] = $chat_logs;
    } else {
        $response['active_ride'] = null;

        // 3. If driver is ONLINE and has NO active ride, look for PENDING requests
        if ($driver_res['status'] === 'Online') {
            $ignored_rides = isset($_SESSION['ignored_rides']) ? $_SESSION['ignored_rides'] : [];
            $placeholders = '';
            
            if (!empty($ignored_rides)) {
                $placeholders = "AND r.id NOT IN (" . implode(',', array_map('intval', $ignored_rides)) . ")";
            }

            $pending_sql = "SELECT r.*, u.fullname AS passenger_name, u.phone AS passenger_phone, u.id AS passenger_id
                            FROM rides r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE r.status = 'Pending' $placeholders 
                            ORDER BY r.id ASC LIMIT 1";
            $pending_result = mysqli_query($conn, $pending_sql);

            if ($pending_result && mysqli_num_rows($pending_result) > 0) {
                $pending = mysqli_fetch_assoc($pending_result);
                $response['incoming_request'] = [
                    'id' => $pending['id'],
                    'pickup_location' => $pending['pickup_location'],
                    'destination' => $pending['destination'],
                    'pickup_lat' => $pending['pickup_lat'],
                    'pickup_lng' => $pending['pickup_lng'],
                    'dest_lat' => $pending['dest_lat'],
                    'dest_lng' => $pending['dest_lng'],
                    'fare' => number_format($pending['fare'], 2),
                    'distance' => $pending['distance'],
                    'passenger_name' => $pending['passenger_name'],
                    'passenger_phone' => $pending['passenger_phone']
                ];
            }
        }
    }
}

echo json_encode($response);
exit();
?>
