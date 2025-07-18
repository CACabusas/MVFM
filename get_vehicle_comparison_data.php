<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch vehicles
$vehicles = [];
$query = "SELECT vehicle_id, brand, model, cost FROM vehicles ORDER BY brand, model";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}

// Prepare data for comparison charts
$vehicle_labels = [];
$fuel_efficiency_data = [];
$mileage_data = [];
$maintenance_cost_data = [];
$vehicle_cost_data = [];

foreach ($vehicles as $vehicle) {
    $vehicle_id = $vehicle['vehicle_id'];
    $vehicle_labels[] = $vehicle['brand'] . ' ' . $vehicle['model'];
    $vehicle_cost_data[] = $vehicle['cost'];
    
    // Initialize with zeros
    $fuel_efficiency_data[$vehicle_id] = 0;
    $mileage_data[$vehicle_id] = 0;
    $maintenance_cost_data[$vehicle_id] = 0;
    
    // Get fuel efficiency (km/liter) for selected year
    $query = "SELECT 
                SUM(distance) as total_distance,
                SUM(liters) as total_liters
              FROM mileage 
              WHERE vehicle_id = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $vehicle_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $total_distance = $row['total_distance'] ?? 0;
        $total_liters = $row['total_liters'] ?? 1; // Avoid division by zero
        $mileage_data[$vehicle_id] = $total_distance;
        $fuel_efficiency_data[$vehicle_id] = round($total_distance / $total_liters, 2);
    }
    
    // Get maintenance cost for selected year
    $query = "SELECT SUM(cost) as total_cost
              FROM maintenance 
              WHERE vehicle_id = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $vehicle_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $maintenance_cost_data[$vehicle_id] = $row['total_cost'] ?? 0;
    }
}

// Prepare response
$response = [
    'fuel_efficiency' => array_values($fuel_efficiency_data),
    'mileage' => array_values($mileage_data),
    'maintenance_cost' => array_values($maintenance_cost_data),
    'vehicle_cost' => $vehicle_cost_data
];

header('Content-Type: application/json');
echo json_encode($response);
?>