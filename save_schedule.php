<?php
// Start output buffering at the very beginning
ob_start();

session_start();
require_once "db_connect.php";

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$method = $_SERVER['REQUEST_METHOD'];

// Simplified driver handling function
function handleDriver($conn, $driver_name) {
    $driver_name = trim($driver_name);
    
    // If empty name, use "Unknown Driver"
    if (empty($driver_name)) {
        $driver_name = "Unknown Driver";
    }
    
    // Simple split - first word as first_name, rest as last_name
    $name_parts = explode(' ', $driver_name, 2);
    $first_name = $name_parts[0];
    $last_name = count($name_parts) > 1 ? $name_parts[1] : ' '; // Space as fallback
    
    // Find or create driver
    $stmt = $conn->prepare("SELECT driver_id FROM drivers WHERE first_name = ? AND last_name = ?");
    $stmt->bind_param("ss", $first_name, $last_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['driver_id'];
    }
    
    // Create driver if not exists
    $stmt = $conn->prepare("INSERT INTO drivers (first_name, last_name) VALUES (?, ?)");
    $stmt->bind_param("ss", $first_name, $last_name);
    if (!$stmt->execute()) {
        throw new Exception("Failed to create driver record");
    }
    return $stmt->insert_id;
}

try {
    // Get input data
    if ($method === 'DELETE') {
        $input = ['schedule_id' => $_GET['schedule_id'] ?? null];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input) && $method === 'POST') {
            $input = $_POST;
        }
    }

    // Validate required fields
    if ($method === 'POST' || $method === 'PUT') {
        $required = ['driver_name', 'vehicle_id', 'start_datetime', 'end_datetime', 'purpose'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
    }

    // Process request
    switch ($method) {
        case 'POST':
            $driver_id = handleDriver($conn, $input['driver_name']);
            
            $stmt = $conn->prepare("INSERT INTO driver_schedules 
                (driver_id, vehicle_id, start_datetime, end_datetime, purpose, status) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $status = $input['status'] ?? 'scheduled';
            $stmt->bind_param("iissss", 
                $driver_id,
                $input['vehicle_id'],
                $input['start_datetime'],
                $input['end_datetime'],
                $input['purpose'],
                $status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create schedule: " . $stmt->error);
            }
            
            // Clean output and send response
            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'id' => $stmt->insert_id,
                'message' => 'Schedule created successfully'
            ]);
            break;

        case 'PUT':
            if (empty($input['schedule_id'])) {
                throw new Exception("Schedule ID is required for update");
            }
            
            $driver_id = handleDriver($conn, $input['driver_name']);
            
            $stmt = $conn->prepare("UPDATE driver_schedules SET 
                driver_id = ?,
                vehicle_id = ?,
                start_datetime = ?,
                end_datetime = ?,
                purpose = ?,
                status = ?
                WHERE schedule_id = ?");
            
            $status = $input['status'] ?? 'scheduled';
            $stmt->bind_param("iissssi", 
                $driver_id,
                $input['vehicle_id'],
                $input['start_datetime'],
                $input['end_datetime'],
                $input['purpose'],
                $status,
                $input['schedule_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update schedule: " . $stmt->error);
            }
            
            // Clean output and send response
            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Schedule updated successfully'
            ]);
            break;

        case 'DELETE':
            $schedule_id = $_GET['schedule_id'] ?? null;
            
            if (empty($schedule_id) || !is_numeric($schedule_id)) {
                throw new Exception('Invalid schedule ID', 400);
            }
            
            $schedule_id = (int)$schedule_id;
            $stmt = $conn->prepare("DELETE FROM driver_schedules WHERE schedule_id = ?");
            $stmt->bind_param("i", $schedule_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error, 500);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Schedule not found', 404);
            }
            
            // Clean output and send response
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Deleted successfully',
                'id' => $schedule_id
            ]);
            break;
    }

} catch (Exception $e) {
    // Clean output and send error response
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>