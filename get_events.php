<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$query = "SELECT 
    ds.schedule_id as id,
    CONCAT(d.first_name, ' ', d.last_name, ' - ', v.brand, ' ', v.model) AS title,
    ds.start_datetime as start,
    ds.end_datetime as end,
    ds.purpose,
    ds.status,
    d.first_name,
    d.last_name,
    ds.vehicle_id,
    v.brand,
    v.model,
    CASE 
        WHEN ds.status = 'completed' THEN '#28a745'
        WHEN ds.status = 'in-progress' THEN '#ffc107'
        WHEN ds.status = 'cancelled' THEN '#dc3545'
        ELSE '#007bff'
    END as color
FROM driver_schedules ds
LEFT JOIN drivers d ON ds.driver_id = d.driver_id
LEFT JOIN vehicles v ON ds.vehicle_id = v.vehicle_id
WHERE ds.end_datetime >= NOW() - INTERVAL 1 MONTH";

$result = $conn->query($query);
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['start'],
        'end' => $row['end'],
        'color' => $row['color'],
        'extendedProps' => [
            'driver_name' => $row['first_name'] . ' ' . $row['last_name'],
            'vehicle_id' => $row['vehicle_id'],
            'purpose' => $row['purpose'],
            'status' => $row['status']
        ]
    ];
}

echo json_encode($events);
?>