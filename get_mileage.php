<?php
require 'db_connect.php';

$mileageId = $_GET['mileage_id'] ?? null;

if (!$mileageId) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid ID"]);
    exit;
}

$query = "SELECT * FROM mileage WHERE mileage_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mileageId);
$stmt->execute();
$result = $stmt->get_result();

if ($entry = $result->fetch_assoc()) {
    echo json_encode($entry);
} else {
    echo json_encode(["error" => "Entry not found"]);
}
?>