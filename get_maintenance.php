<?php
include 'db_connect.php';

$vehicle_id = $_GET['vehicle_id'];

$sql = "SELECT maintenance_id, vehicle_id, date, frequency, type, repair_shop, description, cost 
        FROM maintenance 
        WHERE vehicle_id = ? 
        ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>