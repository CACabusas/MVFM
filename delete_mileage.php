<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mileageId = $_GET['mileage_id'] ?? null;
$vehicleId = $_GET['vehicle_id'] ?? null;
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;

if (!$mileageId || !$vehicleId || !$year || !$month) {
    die("Missing parameters");
}

// Get the entry to be deleted
$stmt = $conn->prepare("SELECT date, odometer_begin, odometer_end FROM mileage WHERE mileage_id = ?");
$stmt->bind_param("i", $mileageId);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();

if (!$entry) {
    die("Entry not found");
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete the entry
    $stmt = $conn->prepare("DELETE FROM mileage WHERE mileage_id = ?");
    $stmt->bind_param("i", $mileageId);
    $stmt->execute();

    // Get all entries after the deleted one
    $stmt = $conn->prepare("SELECT mileage_id, date, odometer_begin, odometer_end 
                           FROM mileage 
                           WHERE vehicle_id = ? AND date > ? 
                           ORDER BY date, mileage_id");
    $stmt->bind_param("is", $vehicleId, $entry['date']);
    $stmt->execute();
    $result = $stmt->get_result();
    $laterEntries = $result->fetch_all(MYSQLI_ASSOC);

    $previousEnd = $entry['odometer_begin'];
    
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
    die("Error deleting entry: " . $e->getMessage());
}
?>