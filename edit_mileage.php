<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mileageId = $_GET['mileage_id'] ?? null;
$vehicleId = $_GET['vehicle_id'] ?? null;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

if (!$mileageId || !$vehicleId) {
    die("Invalid request");
}

// Get the existing mileage record
$query = "SELECT * FROM mileage WHERE mileage_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mileageId);
$stmt->execute();
$result = $stmt->get_result();
$mileage = $result->fetch_assoc();

if (!$mileage) {
    die("Mileage record not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $date = $_POST['date'] ?? '';
    $odometerBegin = $_POST['odometer_begin'] ?? 0;
    $odometerEnd = $_POST['odometer_end'] ?? 0;
    $distance = $_POST['distance'] ?? 0;
    $liters = $_POST['liters'] ?? 0;
    $invoice = $_POST['invoice'] ?? '';
    $station = $_POST['station'] ?? '';
    $driver = $_POST['driver'] ?? '';
    
    // Validate data
    if (empty($date)) {
        die("Date is required");
    }

    if ($odometerEnd < $odometerBegin) {
        die("Odometer ending must be greater than or equal to odometer beginning");
    }

    // Update the record
    $query = "UPDATE mileage SET 
              date = ?, 
              odometer_begin = ?, 
              odometer_end = ?, 
              distance = ?, 
              liters = ?, 
              invoice = ?, 
              station = ?, 
              driver = ?
              WHERE mileage_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiidsssi", $date, $odometerBegin, $odometerEnd, $distance, $liters, $invoice, $station, $driver, $mileageId);
    
    if ($stmt->execute()) {
        header("Location: mileageMonthly.php?vehicle_id=$vehicleId&year=$year&month=$month");
        exit();
    } else {
        die("Error updating mileage data: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mileage Record</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Same styles as in mileageMonthly.php */
    </style>
</head>
<body>
    <nav class="navbar">
        <!-- Navbar content here -->
    </nav>
    
    <h2>Edit Mileage Record</h2>
    
    <form method="POST" action="edit_mileage.php?mileage_id=<?= $mileageId ?>&vehicle_id=<?= $vehicleId ?>&year=<?= $year ?>&month=<?= $month ?>">
        <div style="width: 400px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label><span class="input-label">Date:</span> 
                    <input type="date" name="date" value="<?= htmlspecialchars($mileage['date']) ?>" class="input-field small" required>
                </label>

                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Odometer Beginning:</span> 
                        <input type="number" name="odometer_begin" value="<?= htmlspecialchars($mileage['odometer_begin']) ?>" class="input-field small" required>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Odometer Ending:</span> 
                        <input type="number" name="odometer_end" value="<?= htmlspecialchars($mileage['odometer_end']) ?>" class="input-field small" required>
                    </label>
                </div>

                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Distance:</span> 
                        <input type="number" name="distance" value="<?= htmlspecialchars($mileage['distance']) ?>" class="input-field small" readonly>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Liters:</span> 
                        <input type="number" step="0.01" name="liters" value="<?= htmlspecialchars($mileage['liters']) ?>" class="input-field small">
                    </label>
                </div>

                <label><span class="input-label">Invoice Number:</span> 
                    <input type="text" name="invoice" value="<?= htmlspecialchars($mileage['invoice']) ?>" class="input-field small">
                </label>
                <label><span class="input-label">Gas Station:</span> 
                    <textarea name="station" rows="2" class="input-field small"><?= htmlspecialchars($mileage['station']) ?></textarea>
                </label>
                <label><span class="input-label">Driver:</span> 
                    <textarea name="driver" rows="2" class="input-field small"><?= htmlspecialchars($mileage['driver']) ?></textarea>
                </label>
            </div>

            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                <button type="submit" class="submit-btn">Save Changes</button>
                <a href="mileageMonthly.php?vehicle_id=<?= $vehicleId ?>&year=<?= $year ?>&month=<?= $month ?>" class="cancel-btn" style="text-decoration: none; text-align: center;">Cancel</a>
            </div>
        </div>
    </form>

    <script>
        // Calculate distance when odometer values change
        document.querySelector('input[name="odometer_begin"]').addEventListener("input", calculateDistance);
        document.querySelector('input[name="odometer_end"]').addEventListener("input", calculateDistance);

        function calculateDistance() {
            const begin = parseFloat(document.querySelector('input[name="odometer_begin"]').value) || 0;
            const end = parseFloat(document.querySelector('input[name="odometer_end"]').value) || 0;
            const distanceField = document.querySelector('input[name="distance"]');
            
            if (end >= begin) {
                distanceField.value = (end - begin).toFixed(2);
            } else {
                distanceField.value = "";
            }
        }

        // Initial calculation
        calculateDistance();
    </script>
</body>
</html>