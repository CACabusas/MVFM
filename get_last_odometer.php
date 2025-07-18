<?php
require 'db_connect.php';

$vehicleId = $_GET['vehicle_id'] ?? null;
$beforeDate = $_GET['before_date'] ?? null;

if (!$vehicleId || !$beforeDate) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Get the most recent odometer reading before the specified date
$stmt = $conn->prepare("SELECT odometer_end FROM mileage 
                       WHERE vehicle_id = ? AND date < ? 
                       ORDER BY date DESC, mileage_id DESC 
                       LIMIT 1");
$stmt->bind_param("is", $vehicleId, $beforeDate);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['odometer_end' => $row['odometer_end'] ?? null]);
?>