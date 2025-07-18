<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mileageId = $_POST['mileage_id'];
$vehicleId = $_POST['vehicle_id'];
$year = $_POST['year'];
$month = $_POST['month'];
$date = $_POST['date'];
$begin = $_POST['odometer_begin'];
$end = $_POST['odometer_end'];
$distance = $end - $begin;
$fuel = $_POST['liters'];
$cost = $_POST['cost'];
$invoice = $_POST['invoice'];
$station = $_POST['station'];
$driver = $_POST['driver'];

// Start transaction
$conn->begin_transaction();

try {
    // Get the original entry data
    $stmt = $conn->prepare("SELECT date FROM mileage WHERE mileage_id = ?");
    $stmt->bind_param("i", $mileageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $originalEntry = $result->fetch_assoc();

    // Update the current entry
    $query = "UPDATE mileage SET 
                date = ?, odometer_begin = ?, odometer_end = ?, distance = ?, liters = ?, 
                cost = ?, invoice = ?, station = ?, driver = ?
              WHERE mileage_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdddsdsssi", $date, $begin, $end, $distance, $fuel, $cost, $invoice, $station, $driver, $mileageId);
    $stmt->execute();

    // Get all entries after the current one (including those that might have moved due to date change)
    $stmt = $conn->prepare("SELECT mileage_id, date, odometer_begin, odometer_end 
                           FROM mileage 
                           WHERE vehicle_id = ? AND (date > ? OR (date = ? AND mileage_id > ?))
                           ORDER BY date, mileage_id");
    $stmt->bind_param("issi", $vehicleId, $date, $date, $mileageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $laterEntries = $result->fetch_all(MYSQLI_ASSOC);

    $previousEnd = $end;
    
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
    die("Error updating mileage data: " . $e->getMessage());
}
?>