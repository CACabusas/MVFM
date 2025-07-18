<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = $_POST['vehicle_id'] ?? null;
    $date = $_POST['date'] ?? '';
    $odometerBegin = $_POST['odometer_begin'] ?? 0;
    $odometerEnd = $_POST['odometer_end'] ?? 0;
    $distance = $_POST['distance'] ?? 0;
    $liters = $_POST['liters'] ?? 0;
    $cost = $_POST['cost'] ?? 0;
    $invoice = $_POST['invoice'] ?? '';
    $station = $_POST['station'] ?? '';
    $driver = $_POST['driver'] ?? '';
    $year = $_POST['year'] ?? date('Y');
    $month = $_POST['month'] ?? date('n');

    // Validate data
    if (empty($date)) {
        die("Date is required");
    }

    if ($odometerEnd < $odometerBegin) {
        die("Odometer ending must be greater than or equal to odometer beginning");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert the new entry
        $query = "INSERT INTO mileage (vehicle_id, date, odometer_begin, odometer_end, distance, liters, cost, invoice, station, driver)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isiiiddsss", $vehicleId, $date, $odometerBegin, $odometerEnd, $distance, $liters, $cost, $invoice, $station, $driver);
        $stmt->execute();
        
        // Get the ID of the newly inserted entry
        $newId = $stmt->insert_id;
        
        // Get all entries after the new one
        $stmt = $conn->prepare("SELECT mileage_id, date, odometer_begin, odometer_end 
                               FROM mileage 
                               WHERE vehicle_id = ? AND date > ? AND mileage_id != ?
                               ORDER BY date, mileage_id");
        $stmt->bind_param("isi", $vehicleId, $date, $newId);
        $stmt->execute();
        $result = $stmt->get_result();
        $laterEntries = $result->fetch_all(MYSQLI_ASSOC);

        $previousEnd = $odometerEnd;
        
        // Update subsequent entries
        foreach ($laterEntries as $laterEntry) {
            $newBegin = $previousEnd;
            $distance = $laterEntry['odometer_end'] - $laterEntry['odometer_begin'];
            $newEnd = $newBegin + $distance;
            
            $updateStmt = $conn->prepare("UPDATE mileage 
                                         SET odometer_begin = ?, odometer_end = ?, distance = ?
                                         WHERE mileage_id = ?");
            $updateStmt->bind_param("iiii", $newBegin, $newEnd, $distance, $laterEntry['mileage_id']);
            $updateStmt->execute();
            
            $previousEnd = $newEnd;
        }

        $conn->commit();
        header("Location: mileageMonthly.php?vehicle_id=$vehicleId&year=$year&month=$month");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error saving mileage data: " . $e->getMessage());
    }
} else {
    header("Location: mileageSelection.php");
    exit();
}
?>