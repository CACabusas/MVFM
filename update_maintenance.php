<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Read raw input
$input = json_decode(file_get_contents("php://input"), true);

$maintenance_id = $input['maintenance_id'];
$date = $input['date'];
$type = $input['type'];
$repair_shop = $input['repair_shop'];
$file_type = $input['file_type'];
$file_type_value = $input['file_type_value'];
$description = $input['description'];
$cost = $input['cost'];

$query = "UPDATE maintenance SET 
            date = ?, type = ?, repair_shop = ?, file_type = ?, file_type_value = ?, description = ?, cost = ?
          WHERE maintenance_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssdi", $date, $type, $repair_shop, $file_type, $file_type_value, $description, $cost, $maintenance_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
?>