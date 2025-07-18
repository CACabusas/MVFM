<?php
session_start();
require 'db_connect.php';

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$vehicleId = $_GET['vehicle_id'] ?? null;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

if (!$vehicleId) {
    echo "<script>alert('No vehicle selected.'); window.location.href='selectvehicleReport.php?report=mileage';</script>";
    exit();
}

$vehicleQuery = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicleResult = $stmt->get_result();
$vehicle = $vehicleResult->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?report=mileage';</script>";
    exit();
}

$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate));

$mileageQuery = "SELECT * FROM mileage 
                WHERE vehicle_id = ? 
                AND date BETWEEN ? AND ?
                ORDER BY date";
$stmt = $conn->prepare($mileageQuery);
$stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
$stmt->execute();
$mileageResult = $stmt->get_result();
$data = $mileageResult->fetch_all(MYSQLI_ASSOC);

$initialFuelBalance = 20; // You'll need to implement proper calculation for this

$totalDistance = 0;
$totalFuel = 0;
$totalPurchased = 0;
$totalCost = 0;

foreach ($data as $entry) {
    $totalDistance += $entry['distance'] ?? 0;
    $totalFuel += $entry['liters'] ?? 0;
    $totalPurchased += $entry['liters'] ?? 0;
    $totalCost += $entry['cost'] ?? 0;
}

$finalBalance = $initialFuelBalance + $totalPurchased - $totalFuel;
$fuelEfficiency = $totalFuel > 0 ? ($totalDistance / $totalFuel) : 0;

$monthName = date("F", mktime(0, 0, 0, $month, 10));
$periodText = "$monthName $year";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Monthly Mileage - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
            text-align: center;
        }
        html, body {
            overflow: visible;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1e293b;
            padding: 10px 20px;
        }
        .logo {
            display: flex;
            align-items: center;
            height: 35px;
        }
        .logo img {
            width: 100%;
        }
        .nav-links {
            list-style: none;
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
        }
        .nav-links li {
            position: relative;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px;
        }
        .nav-links a:hover {
            cursor: pointer;
        }
        .nav-links li a {
            display: flex;
            align-items: center;
            height: 50%;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(30, 41, 59, 0.95);
            border-radius: 5px;
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 200px;
            z-index: 10;
        }
        .dropdown-menu li a {
            padding: 10px;
            display: block;
            color: white;
            text-decoration: none;
        }
        .dropdown-menu li a:hover {
            background-color: #FFC107;
            color: black;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(30, 41, 59, 0.95);
            border-radius: 5px;
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 200px;
            z-index: 10;
        }
        .dropdown-menu li a {
            padding: 10px;
            display: block;
            color: white;
            text-decoration: none;
        }
        .dropdown-menu li a:hover {
            background-color: #FFC107;
            color: black;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .nav-links .dropdown {
            position: relative;
            }
        .nav-links .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(30, 41, 59, 0.9);
            list-style: none;
            padding: 0;
            margin: 0;
            display: none;
            min-width: 160px;
            border-radius: 5px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        .nav-links .dropdown-menu li {
            padding: 10px;
        }
        .nav-links .dropdown-menu a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .nav-links .dropdown-menu a:hover {
            background: #FFC107;
            color: black;
        }
        .nav-links .dropdown:hover .dropdown-menu {
            display: block;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        h2 {
            font-size: 28px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 30px;
        }
        h3 {
            color: #1e293b;
            margin-top: 0px;
            font-size: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        h4 {
            color: #FFC107;
            margin-top: -10px;
            font-size: 25px;
            margin-bottom: 10px;
            text-align: center;
            margin-bottom: 25px;
        }
        /* p {
            font-size: 18px;
            font-weight: 500;
            color: #555;
            margin-top: -10px;
            margin-bottom: 30px;
            text-align: center;
        } */
        table {
            width: 100%;
            max-width: 1280px;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            white-space: normal;
            padding: 6px;
            white-space: normal; /* Allow wrapping */
            word-wrap: break-word;
            border-bottom: 1px solid #ddd;
            text-align: center;
            font-size: 15px;
        }
        th {
            background-color: #1E293B;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }
        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }
        tr:hover td {
            background-color: #d5e4f5;
        }
        button {
            padding: 12px 15px;
            font-size: 8px;
            border-radius: 4px;
        }
        button {
            background: none;
            /* border: none; */
            padding: 0;
            cursor: pointer;
        }
        button i {
            font-size: 18px;
            margin: 5px;
        }
        button:hover i {
            opacity: 0.7;
        }
        .action-btn {
            margin: 5px;
            padding: 2px 3px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            transition: 0.3s ease-in-out;
        }
        .action-btn:hover {
            opacity: 0.8;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .edit-btn:hover {
            background-color: #388E3C;
        }
        .delete-btn:hover {
            background-color: #D32F2F;
        }
        .edit-btn, .delete-btn, .edit-btn:hover, .delete-btn:hover {
            border: none;
        }
        .editing {
            background-color: #ffffcc;
            outline: none;
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
            width: 100%;
        }
        .button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .button:hover {
            background-color: #334155;
        }
        .add-row-btn {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #1E293B;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .add-row-btn:hover {
            background-color: #334155;
            color: white;
        }
        .summary-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }
        .summary {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            width: 50%;
            min-width: 360px;
            max-width: 600px;
            text-align: center;
            border-left: 5px solid #1B3B82;
            margin-left: auto;
            margin-right: auto;
        }
        .summary-title {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #1B3B82;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            font-family: 'Arial', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary .value {
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            font-weight: 700;
            color: #0288D1;
        }
        .summary:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
            box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.2);
        }
        .input-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .input-field {
            width: 100%;
            padding: 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }
        .small {
            width: 80%;
        }
        .submit-btn, .cancel-btn {
            padding: 10px 16px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        .submit-btn {
            background-color: #27ae60;
            color: white;
            font-weight: 500;
        }
        .submit-btn:hover {
            background-color: #219150;
        }
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
            font-weight: 500;
        }
        .cancel-btn:hover {
            background-color: #c0392b;
        }
        .generate-report-btn {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            border-color: #334155;
            background-color: white;
            color: black;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .generate-report-btn:hover {
            background-color: #334155;
            color: white;
        }
        .show-summary-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .show-summary-btn:hover {
            background-color: #45a049;
        }
        
        .summary-content p {
            margin: 10px 0;
            line-height: 1.5;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            max-width: 1280px;
            margin: 0 auto 20px auto;
        }
        @media print {
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
            }
            nav, .add-row-btn, .submit-btn, .cancel-btn, .editing, .delete-btn, #overlay,
            #addContainer, #editContainer, .generate-report-btn, button, .button, .summary,
            .summary-container, .show-summary-btn {
                display: none !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                page-break-inside: auto;
            }
            table, th, td {
                border: 1px solid black;
                padding: 8px;
                color: black;
            }
            h2, h3, h4, p {
                text-align: center;
                color: black;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            th:last-child, td:last-child {
                display: none;
            }
            .summary {
                margin-top: 20px;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php">
            <img class="logo" src="logo simple.png" alt="Logo">
        </a>
        <ul class="nav-links">
            <li><a href="vehicles.php">Vehicles</a></li>
            <li class="dropdown">
                <a>Reports &#9662;</a>
                <ul class="dropdown-menu">
                    <li><a href="selectvehicleReport.php?report=maintenance">Maintenance Reports</a></li>
                    <li><a href="selectvehicleReport.php?report=mileage">Fuel & Mileage Reports</a></li>
                    <li><a href="selectvehicleReport.php?report=history">Vehicle History Reports</a></li>
                </ul>
            </li>
            <li><a href="misc.php">Misc</a></li>
            <li>
                <a href="?logout=true">
                    <img src="icons/logout.png" alt="Log out" style="width:35px; height:35px;">
                </a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <div style="text-align: left;">
            <a class="button" href="mileageSelection.php?vehicle_id=<?= $vehicleId ?>">Back</a>
        </div>

        <h2>Monthly Report of Mileage and Fuel Consumption</h2>
        <h3 id="periodSubtitle">For the Period: <span id="periodText"><?= $periodText ?></span></h3>
        <p><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['type'] . ')') ?> | 
        Plate No: <?= htmlspecialchars($vehicle['plate']) ?> | 
        Fuel Type: <?= htmlspecialchars($vehicle['fuel']) ?></p>
        
        <div class="button-group">
            <button class="add-row-btn" onclick="showAddContainer()">Add</button>
            <button class="add-row-btn" onclick="showSummaryModal()">Show Summary</button>
        </div>
        
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Odometer Beginning</th>
                    <th>Odometer Ending</th>
                    <th>Distance Travelled (KM)</th>
                    <th>Liters Purchased/Received</th>
                    <th>Cost (₱)</th>
                    <th>Invoice No.</th>
                    <th>Gas Station</th>
                    <th>Driver</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($data as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['date']) ?></td>
                        <td><?= htmlspecialchars($entry['odometer_begin']) ?></td>
                        <td><?= htmlspecialchars($entry['odometer_end']) ?></td>
                        <td><?= htmlspecialchars($entry['distance']) ?></td>
                        <td><?= htmlspecialchars($entry['liters']) ?></td>
                        <td><?= htmlspecialchars($entry['cost']) ?></td>
                        <td><?= htmlspecialchars($entry['invoice']) ?></td>
                        <td><?= htmlspecialchars($entry['station']) ?></td>
                        <td><?= htmlspecialchars($entry['driver']) ?></td>
                        <td>
                            <button class="edit-btn action-btn" onclick="editRow(<?= $entry['mileage_id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-btn action-btn" onclick="deleteRow(<?= $entry['mileage_id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="generate-report-btn" onclick="window.print()" style="margin: 10px 0;">Generate Report</button>
    </div>

    <div id="overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.3); z-index: 10; backdrop-filter: blur(4px);"></div>

    <div id="addContainer" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
    background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); 
    width: 400px; z-index: 11;">
        <h2>Enter New Data</h2>
        <form id="addForm" method="POST" action="save_mileage.php">
            <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label><span class="input-label">Date:</span> 
                    <input type="date" name="date" id="newDate" class="input-field small" required>
                </label>

                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Odometer Beginning:</span> 
                        <input type="number" name="odometer_begin" id="newBegin" class="input-field small" required>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Odometer Ending:</span> 
                        <input type="number" name="odometer_end" id="newEnd" class="input-field small" required>
                    </label>
                </div>

                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Distance:</span> 
                        <input type="number" name="distance" id="newDistance" class="input-field small" readonly>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Liters:</span> 
                        <input type="number" step="0.01" name="liters" id="newFuel" class="input-field small">
                    </label>
                </div>

                <label style="flex: 1;"><span class="input-label">Cost:</span> 
                    <input type="number" step="0.01" name="cost" class="input-field small">
                </label>
                <label><span class="input-label">Invoice Number:</span> 
                    <input type="text" name="invoice" id="newInvoice" class="input-field small">
                </label>
                <label><span class="input-label">Gas Station:</span> 
                    <textarea name="station" id="newStation" rows="2" class="input-field small"></textarea>
                </label>
                <label><span class="input-label">Driver:</span> 
                    <textarea name="driver" id="newDriver" rows="2" class="input-field small"></textarea>
                </label>
            </div>

            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                <button type="submit" class="submit-btn">Save</button>
                <button type="button" onclick="hideAddContainer()" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>

    <div id="editContainer" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
    background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    width: 400px; font-family: 'Poppins', sans-serif; z-index: 11;">
        <h2>Edit Entry</h2>
        <form id="editForm" method="POST" action="update_mileage.php">
            <input type="hidden" name="mileage_id" id="editId">
            <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label><span class="input-label">Date:</span>
                    <input type="date" name="date" id="editDate" class="input-field small" required>
                </label>
                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Odometer Beginning:</span>
                        <input type="number" name="odometer_begin" id="editBegin" class="input-field small" required>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Odometer Ending:</span>
                        <input type="number" name="odometer_end" id="editEnd" class="input-field small" required>
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <label style="flex: 1;"><span class="input-label">Distance:</span>
                        <input type="number" name="distance" id="editDistance" class="input-field small" readonly>
                    </label>
                    <label style="flex: 1;"><span class="input-label">Liters:</span>
                        <input type="number" step="0.01" name="liters" id="editFuel" class="input-field small">
                    </label>
                </div>

                <label style="flex: 1;"><span class="input-label">Cost:</span> 
                    <input type="number" step="0.01" name="cost" id="editCost" class="input-field small">
                </label>
                <label><span class="input-label">Invoice Number:</span>
                    <input type="text" name="invoice" id="editInvoice" class="input-field small">
                </label>
                <label><span class="input-label">Gas Station:</span>
                    <textarea name="station" id="editStation" rows="2" class="input-field small"></textarea>
                </label>
                <label><span class="input-label">Driver:</span>
                    <textarea name="driver" id="editDriver" rows="2" class="input-field small"></textarea>
                </label>
            </div>

            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                <button type="submit" class="submit-btn">Update</button>
                <button type="button" onclick="hideEditContainer()" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- <div class="summary">
        <h4>Summary of Fuel Consumption</h4>
        <p><strong>Total Distance Travelled:</strong> <span id="totalDistance"><?= round($totalDistance) ?></span> KM</p>
        <p><strong>Total Fuel Used:</strong> <span id="totalFuel"><?= round($totalFuel) ?></span> Liters</p>
        <p><strong>Fuel Efficiency:</strong> <span id="fuelEfficiency"><?= number_format($fuelEfficiency, 2) ?></span> KM/L</p>
        <p><strong>Purchase This Period:</strong> <span id="purchasePeriod"><?= number_format($totalPurchased, 2) ?></span> Liters</p>
        <p><strong>Total Cost:</strong> ₱<span id="totalCost"><?= number_format($totalCost, 2) ?></span></p>
    </div> -->

    <div id="summaryModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
    background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); 
    width: 400px; z-index: 11;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Summary of Fuel Consumption</h2>
        </div>
        <div class="summary-content" style="margin-top: 15px;">
            <p><strong>Total Distance Travelled:</strong> <span id="totalDistance"><?= round($totalDistance) ?></span> KM</p>
            <p><strong>Total Fuel Used:</strong> <span id="totalFuel"><?= round($totalFuel) ?></span> Liters</p>
            <p><strong>Fuel Efficiency:</strong> <span id="fuelEfficiency"><?= number_format($fuelEfficiency, 2) ?></span> KM/L</p>
            <p><strong>Purchase This Period:</strong> <span id="purchasePeriod"><?= number_format($totalPurchased, 2) ?></span> Liters</p>
            <p><strong>Total Cost:</strong> ₱<span id="totalCost"><?= number_format($totalCost, 2) ?></span></p>
        </div>
        <div style="margin-top: 15px; display: flex; justify-content: center;">
            <button type="button" onclick="hideSummaryModal()" class="cancel-btn">Close</button>
        </div>
    </div>

    <script>
        function fetchPreviousOdometer(date) {
            fetch(`get_last_odometer.php?vehicle_id=<?= $vehicleId ?>&before_date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.odometer_end !== null) {
                        document.getElementById("newBegin").value = data.odometer_end;
                        calculateNewDistance(); // Recalculate if odometer_end is already filled
                    } else {
                        // If no previous entries, set to 0 or keep empty
                        document.getElementById("newBegin").value = "";
                    }
                })
                .catch(error => console.error('Error fetching previous odometer:', error));
        }

        function showSummaryModal() {
            document.getElementById("summaryModal").style.display = "block";
            document.getElementById("overlay").style.display = "block";
        }

        function hideSummaryModal() {
            document.getElementById("summaryModal").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }

        function showAddContainer() {
            document.getElementById("addContainer").style.display = "block";
            document.getElementById("overlay").style.display = "block";

            const year = <?= $year ?>;
            const month = <?= $month ?>;
            const formattedMonth = month < 10 ? `0${month}` : month;
            const defaultDate = `${year}-${formattedMonth}-01`;
            const dateInput = document.getElementById("newDate");

            dateInput.value = defaultDate;
            fetchPreviousOdometer(dateInput.value);
            setupDateWatcher(); // Attach onchange event
        }

        function setupDateWatcher() {
            document.getElementById("newDate").addEventListener("change", function () {
                const selectedDate = this.value;
                fetchPreviousOdometer(selectedDate);
            });
        }

        function fetchPreviousOdometer(date) {
            fetch(`get_last_odometer.php?vehicle_id=<?= $vehicleId ?>&before_date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.odometer_end !== null) {
                        document.getElementById("newBegin").value = data.odometer_end;
                        calculateNewDistance(); // Recalculate if odometer_end is already filled
                    } else {
                        document.getElementById("newBegin").value = "";
                    }
                });
        }
        function hideAddContainer() {
            document.getElementById("addContainer").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }

        // Calculate distance when odometer values change
        document.getElementById("newBegin").addEventListener("input", calculateNewDistance);
        document.getElementById("newEnd").addEventListener("input", calculateNewDistance);

        function calculateNewDistance() {
            const begin = parseFloat(document.getElementById("newBegin").value) || 0;
            const end = parseFloat(document.getElementById("newEnd").value) || 0;
            const distanceField = document.getElementById("newDistance");
            
            if (end >= begin) {
                distanceField.value = (end - begin).toFixed(2);
            } else {
                distanceField.value = "";
            }
        }

        function editRow(mileageId) {
            fetch(`get_mileage.php?mileage_id=${mileageId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("editId").value = data.mileage_id;
                    document.getElementById("editDate").value = data.date;
                    document.getElementById("editBegin").value = data.odometer_begin;
                    document.getElementById("editEnd").value = data.odometer_end;
                    document.getElementById("editDistance").value = data.distance;
                    document.getElementById("editFuel").value = data.liters;
                    document.getElementById("editCost").value = data.cost;
                    document.getElementById("editInvoice").value = data.invoice;
                    document.getElementById("editStation").value = data.station;
                    document.getElementById("editDriver").value = data.driver;

                    document.getElementById("editContainer").style.display = "block";
                    document.getElementById("overlay").style.display = "block";
                });
        }

        function hideEditContainer() {
            document.getElementById("editContainer").style.display = "none";
            document.getElementById("overlay").style.display = "none";
        }

        // Auto calculate distance in edit form
        document.getElementById("editBegin").addEventListener("input", calculateEditDistance);
        document.getElementById("editEnd").addEventListener("input", calculateEditDistance);

        function calculateEditDistance() {
            const begin = parseFloat(document.getElementById("editBegin").value) || 0;
            const end = parseFloat(document.getElementById("editEnd").value) || 0;
            const distanceField = document.getElementById("editDistance");
            distanceField.value = end >= begin ? (end - begin).toFixed(2) : "";
        }

        function deleteRow(mileageId) {
            if (confirm("Are you sure you want to delete this entry?")) {
                window.location.href = `delete_mileage.php?mileage_id=${mileageId}&vehicle_id=<?= $vehicleId ?>&year=<?= $year ?>&month=<?= $month ?>`;
            }
        }
    </script>
</body>
</html>