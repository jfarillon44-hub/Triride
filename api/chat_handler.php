<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $ride_id = intval($data['ride_id']);
    $message = sanitize($data['message']);
    
    // Check if sender role is correct
    $sender = ($role === 'Driver') ? 'Driver' : 'Passenger';

    if (empty($message) || $ride_id <= 0) {
        $response = ['status' => 'error', 'message' => 'Message and Ride ID are required.'];
    } else {
        // Verify active ride matches
        $verify_sql = "SELECT id FROM rides WHERE id = ? AND (user_id = ? OR driver_id = ?)";
        $stmt = $conn->prepare($verify_sql);
        $stmt->bind_param("iii", $ride_id, $user_id, $user_id);
        $stmt->execute();
        $verify_res = $stmt->get_result();

        if ($verify_res->num_rows === 0) {
            $response = ['status' => 'error', 'message' => 'Unauthorized chat request.'];
        } else {
            $insert_sql = "INSERT INTO chats (ride_id, sender, message) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iss", $ride_id, $sender, $message);
            
            if ($stmt->execute()) {
                $response = [
                    'status' => 'success', 
                    'message' => 'Message sent successfully',
                    'chat' => [
                        'sender' => $sender,
                        'message' => $message,
                        'timestamp' => date('h:i A')
                    ]
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not save message.'];
            }
        }
    }
}

echo json_encode($response);
exit();
?>
