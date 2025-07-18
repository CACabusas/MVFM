<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"));

$sql = "DELETE FROM maintenance WHERE maintenance_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $data->maintenance_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>
