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

    if ($action === 'request_ride') {
        if ($role !== 'Passenger') {
            echo json_encode(['status' => 'error', 'message' => 'Only passengers can book rides.']);
            exit();
        }

        $pickup = sanitize($data['pickup_location']);
        $destination = sanitize($data['destination']);
        $pickup_lat = floatval($data['pickup_lat'] ?? 8.6042);
        $pickup_lng = floatval($data['pickup_lng'] ?? 125.9142);
        $dest_lat = floatval($data['dest_lat'] ?? 8.6050);
        $dest_lng = floatval($data['dest_lng'] ?? 125.9150);
        $fare = floatval($data['fare'] ?? 25.00);
        $distance = floatval($data['distance'] ?? 1.0);
        $duration = intval($data['duration'] ?? 5);

        // Verify passenger doesn't already have an active ride
        $check_sql = "SELECT id FROM rides WHERE user_id = ? AND status NOT IN ('Completed', 'Cancelled')";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $check_res = $stmt->get_result();

        if ($check_res->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'You already have an active booking.'];
        } else {
            $insert_sql = "INSERT INTO rides (user_id, pickup_location, destination, pickup_lat, pickup_lng, dest_lat, dest_lng, status, fare, distance, duration) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("issddddddi", $user_id, $pickup, $destination, $pickup_lat, $pickup_lng, $dest_lat, $dest_lng, $fare, $distance, $duration);
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Ride requested successfully', 'ride_id' => $stmt->insert_id];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not save ride request.'];
            }
        }

    } elseif ($action === 'create_demo_ride') {
        $pickup = 'Agusan del Sur State University';
        $destination = 'Neco Mall, Prosperidad';
        $pickup_lat = 8.60420;
        $pickup_lng = 125.91420;
        $dest_lat = 8.60850;
        $dest_lng = 125.92200;
        $fare = 35.00;
        $distance = 1.7;
        $duration = 12;

        // Verify passenger 1 doesn't already have an active ride
        $check_sql = "SELECT id FROM rides WHERE user_id = 1 AND status NOT IN ('Completed', 'Cancelled')";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute();
        $check_res = $stmt->get_result();

        if ($check_res->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'Demo passenger Juliana already has an active booking. Please complete/cancel it first.'];
        } else {
            $insert_sql = "INSERT INTO rides (user_id, pickup_location, destination, pickup_lat, pickup_lng, dest_lat, dest_lng, status, fare, distance, duration) 
                           VALUES (1, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssddddddi", $pickup, $destination, $pickup_lat, $pickup_lng, $dest_lat, $dest_lng, $fare, $distance, $duration);
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Demo booking created successfully!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not create demo booking.'];
            }
        }

    } elseif ($action === 'accept_ride') {
        if ($role !== 'Driver') {
            echo json_encode(['status' => 'error', 'message' => 'Only drivers can accept rides.']);
            exit();
        }

        $ride_id = intval($data['ride_id']);

        // Check if ride is still pending
        $check_sql = "SELECT status FROM rides WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $ride_id);
        $stmt->execute();
        $ride = $stmt->get_result()->fetch_assoc();

        if (!$ride || $ride['status'] !== 'Pending') {
            $response = ['status' => 'error', 'message' => 'This ride request is no longer available.'];
        } else {
            $update_sql = "UPDATE rides SET driver_id = ?, status = 'Accepted' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $user_id, $ride_id);
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Ride accepted successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not accept ride.'];
            }
        }

    } elseif ($action === 'decline_ride') {
        if ($role !== 'Driver') {
            echo json_encode(['status' => 'error', 'message' => 'Only drivers can decline rides.']);
            exit();
        }

        $ride_id = intval($data['ride_id']);
        
        // Add to ignored list in session
        if (!isset($_SESSION['ignored_rides'])) {
            $_SESSION['ignored_rides'] = [];
        }
        $_SESSION['ignored_rides'][] = $ride_id;
        
        $response = ['status' => 'success', 'message' => 'Ride request declined (hidden)'];

    } elseif ($action === 'cancel_ride') {
        $ride_id = intval($data['ride_id']);

        $update_sql = "UPDATE rides SET status = 'Cancelled', end_time = CURRENT_TIMESTAMP WHERE id = ? AND (user_id = ? OR driver_id = ?)";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $ride_id, $user_id, $user_id);
        
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Ride cancelled successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Could not cancel ride.'];
        }

    } elseif ($action === 'update_status') {
        if ($role !== 'Driver') {
            echo json_encode(['status' => 'error', 'message' => 'Only drivers can update trip status.']);
            exit();
        }

        $ride_id = intval($data['ride_id']);
        $new_status = sanitize($data['status']); // 'Arrived', 'Picked Up', 'Completed'

        $update_sql = "UPDATE rides SET status = ? " . ($new_status === 'Completed' ? ", end_time = CURRENT_TIMESTAMP " : "") . " WHERE id = ? AND driver_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sii", $new_status, $ride_id, $user_id);
        
        if ($stmt->execute()) {
            if ($new_status === 'Completed') {
                // Get fare amount
                $fare_sql = "SELECT fare, user_id FROM rides WHERE id = ?";
                $stmt2 = $conn->prepare($fare_sql);
                $stmt2->bind_param("i", $ride_id);
                $stmt2->execute();
                $ride_info = $stmt2->get_result()->fetch_assoc();
                
                // Create payment entry
                $pmt_sql = "INSERT INTO payments (ride_id, amount, method, status) VALUES (?, ?, 'Cash', 'Pending')";
                $stmt3 = $conn->prepare($pmt_sql);
                $stmt3->bind_param("id", $ride_id, $ride_info['fare']);
                $stmt3->execute();
            }
            $response = ['status' => 'success', 'message' => "Status updated to $new_status"];
        } else {
            $response = ['status' => 'error', 'message' => 'Could not update status.'];
        }

    } elseif ($action === 'update_location') {
        if ($role !== 'Driver') {
            echo json_encode(['status' => 'error', 'message' => 'Only drivers can update locations.']);
            exit();
        }

        $lat = floatval($data['latitude']);
        $lng = floatval($data['longitude']);
        $ride_id = isset($data['ride_id']) ? intval($data['ride_id']) : 0;

        // Update driver's global coordinates
        $update_sql = "UPDATE drivers SET latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ddi", $lat, $lng, $user_id);
        
        if ($stmt->execute()) {
            if ($ride_id > 0) {
                // Record telemetry path
                $hist_sql = "INSERT INTO locations (ride_id, latitude, longitude) VALUES (?, ?, ?)";
                $stmt2 = $conn->prepare($hist_sql);
                $stmt2->bind_param("idd", $ride_id, $lat, $lng);
                $stmt2->execute();
            }
            $response = ['status' => 'success', 'message' => 'Location updated'];
        } else {
            $response = ['status' => 'error', 'message' => 'Could not update location.'];
        }
    }
}

echo json_encode($response);
exit();
?>
