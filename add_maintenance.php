<?php
include 'db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $sql = "INSERT INTO maintenance 
            (vehicle_id, date, type, repair_shop, file_type, file_type_value, description, cost)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssd",
        $data['vehicle_id'],
        $data['date'],
        $data['type'],
        $data['repair_shop'],
        $data['file_type'],
        $data['file_type_value'],
        $data['description'],
        $data['cost']
    );

    if ($stmt->execute()) {
        $maintenance_id = $conn->insert_id;
        echo json_encode([
            "success" => true,
            "maintenance_id" => $maintenance_id
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>