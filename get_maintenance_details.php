<?php
include 'db_connect.php';

$maintenance_id = $_GET['maintenance_id'];

$sql = "SELECT * FROM maintenance WHERE maintenance_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $maintenance_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(null);
}
?>