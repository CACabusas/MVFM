<?php
require_once "db_connect.php";

header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!in_array($type, ['forms', 'policies'])) {
    die(json_encode([]));
}

$resource_type = $type === 'forms' ? 'form' : 'policy';
$result = $conn->query("SELECT form_name, form_url FROM forms WHERE form_type = '$resource_type' ORDER BY form_name");

$resources = [];
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

echo json_encode($resources);
?>