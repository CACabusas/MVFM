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
$quarter = $_GET['quarter'] ?? ceil(date('n') / 3); // Default to current quarter

if (!$vehicleId) {
    echo "<script>alert('No vehicle selected.'); window.location.href='selectvehicleReport.php?report=maintenance';</script>";
    exit();
}

// Determine date range based on quarter
$startMonth = (($quarter - 1) * 3) + 1;
$endMonth = $startMonth + 2;

// Handle December (month 12) case
if ($endMonth > 12) {
    $endMonth = 12;
}

$startDate = "$year-$startMonth-01";
$endDate = date("Y-m-t", strtotime("$year-$endMonth-01"));

$vehicleQuery = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicleResult = $stmt->get_result();
$vehicle = $vehicleResult->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?report=maintenance';</script>";
    exit();
}

// Get maintenance records for the selected quarter and year
$maintenanceQuery = "SELECT * FROM maintenance 
                    WHERE vehicle_id = ? 
                    AND date BETWEEN ? AND ?
                    ORDER BY date";
$stmt = $conn->prepare($maintenanceQuery);
$stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
$stmt->execute();
$maintenanceResult = $stmt->get_result();
$data = $maintenanceResult->fetch_all(MYSQLI_ASSOC);

// Calculate total cost
$totalCost = array_sum(array_column($data, 'cost'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Quarterly Maintenance Report - NPC MVFM System</title>
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
        .container {
            display: none;
            margin-top: 50px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1280px;
        }
        .visible {
            display: block;
        }
        .search-container,
        .add-container {
            border: 2px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            background: white;
            box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .add-container {
            display: none;
            flex-direction: column;
            width: 50%;
            min-width: 300px;
            margin: 50px auto;
        }
        label {
            font-weight: 600;
            margin-top: 5px;
            color: #1e293b;
            text-align: left;
        }
        select, input {
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
        }
        .open-modal-btn {
            padding: 10px 15px;
            background-color: #1e293b;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s ease-in-out;
        }
        .open-modal-btn:hover {
            background-color: #FFC107;
            color: #333;
        }
        table {
            width: 100%;
            max-width: 1280px;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            table-layout: fixed;
        }
        th, td {
            padding: 8px 10px;
            white-space: normal !important;
            word-wrap: break-word;
            border-bottom: 1px solid #ddd;
            text-align: center;
            font-size: 15px;
            overflow-wrap: break-word;
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
        th:nth-child(1), td:nth-child(1) { width: 5%; } /* Count */
        th:nth-child(2), td:nth-child(2) { width: 8%; } /* Date */
        th:nth-child(3), td:nth-child(3) { width: 10%; } /* Repair Shop */
        th:nth-child(4), td:nth-child(4) { width: 8%; } /* Reference Type */
        th:nth-child(5), td:nth-child(5) { width: 10%; } /* Issue Slip Number */
        th:nth-child(6), td:nth-child(6) { width: 30%; } /* Description - give more space */
        th:nth-child(7), td:nth-child(7) { width: 12%; } /* Maintenance Type */
        th:nth-child(8), td:nth-child(8) { width: 10%; } /* Cost */
        th:nth-child(9), td:nth-child(9) { width: 7%; } /* Actions */
        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
        }
        .back-button i {
            font-size: 18px;
        }
        .back-button:hover {
            background-color: #FFC107;
            color: #333;
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
        .total {
            font-weight: bold;
            font-size: 18px;
        }
        .action-btn {
            margin: 5px;
            padding: 8px 12px;
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
        .error-message {
            color: red;
            display: none;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .add-container {
                width: 80%;
            }
            h2 {
                font-size: 32px;
            }
            table, th, td {
                font-size: 14px;
            }
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            text-align: center;
            box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 600;
        }
        .input-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: left;
        }
        .input-group {
            flex: 1;
            min-width: 0;
        }
        .small-input input {
            width: 160px !important;
        }
        .row {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            align-items: center;
        }
        .row div {
            flex: 1;
        }
        .input-container label {
            font-size: 14px;
            font-weight: 600;
        }
        .input-field {
            width: 90%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 15px;
        }
        .save-btn, .cancel-btn {
            padding: 8px 14px;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
        }
        .save-btn {
            background: #28a745;
            color: white;
        }
        .cancel-btn {
            background: #dc3545;
            color: white;
        }
        .save-btn:hover, .cancel-btn:hover {
            opacity: 0.9;
        }
        .modal.show {
            display: flex;
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
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
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
            border: none;
        }
        @media print {
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
            }
            nav, .add-row-btn, .submit-btn, .cancel-btn, .editing, .delete-btn, #overlay,
            #addContainer, #editContainer, .generate-report-btn, button, .button, .summary,
            .summary-container {
                display: none !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                page-break-inside: auto;
            }
            table, th {
                border: 1px solid black;
                padding: 8px;
                color: black;
                font-size: 8pt;
            }
            td {
                border: 1px solid black;
                padding: 8px;
                color: black;
                font-size: 8pt;
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
        .file-type-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .file-type-select {
            flex: 1;
            min-width: 120px;
        }
        .file-type-input {
            flex: 2;
        }
        .description-container {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .description-input {
            flex: 2;
        }
        .description-type {
            flex: 1;
            min-width: 150px;
        }
        .add-description-btn {
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
        }
        .add-description-btn:hover {
            background-color: #334155;
        }
        .remove-description-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
        }
        .remove-description-btn:hover {
            background-color: #bb2d3b;
        }
        .descriptions-wrapper {
            margin-bottom: 15px;
        }
        .maintenance-type {
            width: 120px;
        }
        /* table th:nth-child(4), table td:nth-child(4) {
            width: 150px;
        }
        table th:nth-child(5), table td:nth-child(5) {
            width: 120px;
        } */
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

    <div class="main-container">
        <div style="text-align: left;">
            <a class="button" href="maintenanceSelection.php?vehicle_id=<?= $vehicleId ?>">Back</a>
        </div>
        <h2>Quarterly Maintenance Report</h2>
        <h3 id="periodSubtitle">For the Period: 
            <?php 
            // Convert quarter number to text
            $quarterNames = [
                1 => 'Quarter 1 (Jan-Mar)',
                2 => 'Quarter 2 (Apr-Jun)',
                3 => 'Quarter 3 (Jul-Sep)',
                4 => 'Quarter 4 (Oct-Dec)'
            ];
            echo htmlspecialchars($quarterNames[$quarter] . ' ' . $year);
            ?>
        </h3>
        <p><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['type'] . ')') ?> | 
        <strong>Plate No:</strong> <?= htmlspecialchars($vehicle['plate']) ?> | 
        <strong>Fuel Type:</strong> <?= htmlspecialchars($vehicle['fuel']) ?></p>
        <button class="add-row-btn" onclick="toggleAddContainer()">Add</button>

        <div id="addContainer" class="modal">
            <div class="modal-content">
                <h3>Add Vehicle Maintenance Record</h3>
                <div class="input-container">
                    <!-- Date & Frequency in One Row -->
                    <div class="row">
                        <div class="input-group small-input">
                            <label for="date">Date:</label>
                            <input type="date" id="date" class="input-field">
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label>Repair Shop:</label>
                            <input type="text" id="repairShop" class="input-field">
                        </div>
                    </div>

                    <!-- File Type Row -->
                    <div class="row">
                        <div class="input-group">
                            <label>Reference Type:</label>
                            <div class="file-type-container">
                                <select id="fileType" class="input-field file-type-select">
                                    <option value="">Select</option>
                                    <option value="Purchase Order Number">Purchase Order Number</option>
                                    <option value="Contract Number">Contract Number</option>
                                    <option value="Admin">Admin</option>
                                </select>
                                <input type="text" id="fileTypeValue" class="input-field file-type-input" placeholder="Enter value">
                            </div>
                        </div>
                    </div>

                    <!-- Description and Maintenance Type -->
                    <div class="descriptions-wrapper">
                        <label>Descriptions:</label>
                        <div id="descriptionsContainer">
                            <div class="description-container">
                                <input type="text" class="input-field description-input" placeholder="Description">
                                <select class="input-field maintenance-type">
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                </select>
                                <button type="button" class="add-description-btn" onclick="addDescriptionField()">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <label>Cost:</label>
                    <input type="number" id="cost" step="0.01" min="0" class="input-field">
                </div>
                <div class="button-container">
                    <button class="save-btn" onclick="saveRecord()">Save</button>
                    <button class="cancel-btn" onclick="toggleAddContainer()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Edit Modal (initially hidden) -->
        <div id="editContainer" class="modal">
            <div class="modal-content">
                <h3>Edit Vehicle Maintenance Record</h3>
                <div class="input-container">
                    <input type="hidden" id="editMaintenanceId">
                    
                    <!-- Date & Frequency in One Row -->
                    <div class="row">
                        <div class="input-group small-input">
                            <label for="editDate">Date:</label>
                            <input type="date" id="editDate" class="input-field">
                        </div>
                    </div>

                    <div class="row">
                        <div>
                            <label>Repair Shop:</label>
                            <input type="text" id="editRepairShop" class="input-field">
                        </div>
                    </div>

                    <!-- File Type Row -->
                    <div class="row">
                        <div class="input-group">
                            <label>Reference Type:</label>
                            <div class="file-type-container">
                                <select id="editFileType" class="input-field file-type-select">
                                    <option value="">Select</option>
                                    <option value="Purchase Order Number">Purchase Order Number</option>
                                    <option value="Contract Number">Contract Number</option>
                                    <option value="Admin">Admin</option>
                                </select>
                                <input type="text" id="editFileTypeValue" class="input-field file-type-input" placeholder="Enter value">
                            </div>
                        </div>
                    </div>

                    <!-- Description and Maintenance Type -->
                    <div class="descriptions-wrapper">
                        <label>Descriptions:</label>
                        <div id="editDescriptionsContainer">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                    
                    <label>Cost:</label>
                    <input type="number" id="editCost" step="0.01" min="0" class="input-field">
                </div>
                <div class="button-container">
                    <button class="save-btn" onclick="saveEdit()">Save Changes</button>
                    <button class="cancel-btn" onclick="toggleEditContainer()">Cancel</button>
                </div>
            </div>
        </div>

        <table id="reportTable">
            <thead>
                <tr>
                    <th>Count</th>
                    <th>Date</th>
                    <th>Repair Shop</th>
                    <th>Reference Type</th> 
                    <th>Issue Slip Number</th> 
                    <th>Description</th>
                    <th>Maintenance Type</th>
                    <th>Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="reportBody">
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $index => $record): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($record['date']) ?></td>
                        <td><?= htmlspecialchars($record['repair_shop']) ?></td>
                        <td><?= htmlspecialchars($record['file_type']) ?></td>
                        <td><?= htmlspecialchars($record['file_type_value']) ?></td>
                        <td><?= htmlspecialchars($record['description']) ?></td>
                        <td class="maintenance-type"><?= htmlspecialchars($record['type']) ?></td>
                        <td>₱<?= number_format($record['cost'], 2) ?></td>
                        <td>
                            <button class="edit-btn action-btn" onclick="editRow(<?= $record['maintenance_id'] ?>)">
                                <i class="fas fa-edit"></i> 
                            </button>
                            <button class="delete-btn action-btn" onclick="deleteRow(this, <?= $record['maintenance_id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="6">Total</td>
                    <td id="maintenanceCounts"></td>
                    <td id="totalCost">₱<?= number_format($totalCost, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <button class="generate-report-btn" onclick="window.print()" style="margin: 10px 0;">Generate Report</button>
    </div>

    <script>
        // Get the quarter parameters from URL
        const urlParams = new URLSearchParams(window.location.search);
        const year = urlParams.get('year') || new Date().getFullYear();
        const quarter = urlParams.get('quarter') || Math.ceil((new Date().getMonth() + 1) / 3);
        const vehicleId = urlParams.get('vehicle_id');

        // Calculate date range for the quarter
        const startMonth = ((quarter - 1) * 3) + 1;
        const endMonth = startMonth + 2;
        
        // Set minimum and maximum dates for the date picker
        document.addEventListener("DOMContentLoaded", function() {
            const dateInput = document.getElementById('date');
            const minDate = `${year}-${String(startMonth).padStart(2, '0')}-01`;
            const maxDate = new Date(year, endMonth, 0).toISOString().split('T')[0];
            
            dateInput.min = minDate;
            dateInput.max = maxDate;
            dateInput.value = minDate; // Default to start of quarter
            
            // Initialize the table with existing data
            updateTotalCost();
        });

        function toggleAddContainer() {
            const modal = document.getElementById("addContainer");
            if (!modal.classList.contains("show")) {
                modal.style.display = "flex";
                setTimeout(() => modal.classList.add("show"), 10);
            } else {
                modal.classList.remove("show");  
                setTimeout(() => modal.style.display = "none", 300);
            }
        }

        function toggleEditContainer() {
            const modal = document.getElementById("editContainer");
            if (!modal.classList.contains("show")) {
                modal.style.display = "flex";
                setTimeout(() => modal.classList.add("show"), 10);
            } else {
                modal.classList.remove("show");  
                setTimeout(() => modal.style.display = "none", 300);
            }
        }

        function addDescriptionField() {
            const container = document.getElementById('descriptionsContainer');
            const newDescriptionDiv = document.createElement('div');
            newDescriptionDiv.className = 'description-container';
            newDescriptionDiv.innerHTML = `
                <input type="text" class="input-field description-input" placeholder="Description">
                <select class="input-field maintenance-type">
                    <option value="preventive">Preventive</option>
                    <option value="corrective">Corrective</option>
                </select>
                <button type="button" class="remove-description-btn" onclick="removeDescriptionField(this)">-</button>
            `;
            container.appendChild(newDescriptionDiv);
        }

        function removeDescriptionField(button) {
            const container = document.getElementById('descriptionsContainer');
            if (container.children.length > 1) {
                button.closest('.description-container').remove();
            }
        }

        function saveRecord() {
            const date = document.getElementById("date").value;
            const shop = document.getElementById("repairShop").value;
            const fileType = document.getElementById("fileType").value;
            const fileTypeValue = document.getElementById("fileTypeValue").value;
            const cost = parseFloat(document.getElementById("cost").value) || 0;

            // Collect all descriptions and their types
            const descriptionContainers = document.querySelectorAll('#descriptionsContainer .description-container');
            const descriptions = [];
            let types = [];
            
            descriptionContainers.forEach(container => {
                const input = container.querySelector('.description-input');
                const select = container.querySelector('.maintenance-type');
                if (input.value.trim()) {
                    // Store description with its type in format "description:type"
                    descriptions.push(`${input.value.trim()}:${select.value}`);
                    types.push(select.value);
                }
            });

            if (!date || !shop || descriptions.length === 0 || isNaN(cost)) {
                alert("Please fill all required fields correctly.");
                return;
            }

            // Validate file type if one is selected
            if (fileType && !fileTypeValue) {
                alert("Please enter a value for the selected reference type.");
                return;
            }

            // Validate date is within quarter
            const recordDate = new Date(date);
            const recordYear = recordDate.getFullYear();
            const recordMonth = recordDate.getMonth() + 1;
            
            if (recordYear != year || recordMonth < startMonth || recordMonth > endMonth) {
                alert(`Date must be within Q${quarter} ${year} (${getMonthName(startMonth)} to ${getMonthName(endMonth)})`);
                return;
            }

            // Combine descriptions into one string for the database
            const descriptionText = descriptions.join(',');
            
            // Determine maintenance type (if all same, use that, otherwise "Mixed")
            const uniqueTypes = [...new Set(types)];
            const maintenanceType = uniqueTypes.length === 1 ? uniqueTypes[0] : 'Mixed';

            const payload = {
                vehicle_id: parseInt(vehicleId),
                date: date,
                type: maintenanceType,
                repair_shop: shop,
                file_type: fileType,
                file_type_value: fileTypeValue,
                description: descriptionText,
                cost: cost
            };

            fetch("add_maintenance.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toggleAddContainer();
                    const tbody = document.getElementById("reportBody");
                    
                    // Check if table is empty
                    if (tbody.children.length === 0 || 
                        (tbody.children.length === 1 && tbody.children[0].textContent.includes("No maintenance records"))) {
                        tbody.innerHTML = '';
                    }
                    
                    // Add the new record to the table
                    const newRow = document.createElement("tr");
                    const rowCount = tbody.children.length + 1;
                    
                    newRow.innerHTML = `
                        <td>${rowCount}</td>
                        <td>${date}</td>
                        <td>${shop}</td>
                        <td>${fileType || ''}</td>
                        <td>${fileTypeValue || ''}</td>
                        <td>${descriptionText}</td>
                        <td class="maintenance-type">${maintenanceType}</td>
                        <td>₱${cost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>
                            <button class="edit-btn action-btn" onclick="editRow(${data.maintenance_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="delete-btn action-btn" onclick="deleteRow(this, ${data.maintenance_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    tbody.appendChild(newRow);
                    updateTotalCost();
                    
                    // Clear form
                    document.getElementById("date").value = date;
                    document.getElementById("repairShop").value = "";
                    document.getElementById("fileType").value = "";
                    document.getElementById("fileTypeValue").value = "";
                    document.getElementById("cost").value = "";
                    
                    // Reset descriptions
                    const descContainer = document.getElementById('descriptionsContainer');
                    descContainer.innerHTML = `
                        <div class="description-container">
                            <input type="text" class="input-field description-input" placeholder="Description">
                            <select class="input-field maintenance-type">
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                            </select>
                            <button type="button" class="add-description-btn" onclick="addDescriptionField()">+</button>
                        </div>
                    `;
                } else {
                    alert("Error: " + data.error);
                }
            });
        }

        function editRow(maintenanceId) {
            // Fetch the maintenance record details
            fetch(`get_maintenance_details.php?maintenance_id=${maintenanceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        // Populate the edit modal with the record data
                        document.getElementById('editMaintenanceId').value = data.maintenance_id;
                        document.getElementById('editDate').value = data.date;
                        document.getElementById('editRepairShop').value = data.repair_shop;
                        document.getElementById('editFileType').value = data.file_type || '';
                        document.getElementById('editFileTypeValue').value = data.file_type_value || '';
                        document.getElementById('editCost').value = data.cost;

                        // Set date constraints
                        document.getElementById('editDate').min = `${year}-${String(startMonth).padStart(2, '0')}-01`;
                        document.getElementById('editDate').max = new Date(year, endMonth, 0).toISOString().split('T')[0];

                        // Handle descriptions
                        const descriptionsContainer = document.getElementById('editDescriptionsContainer');
                        descriptionsContainer.innerHTML = '';

                        // Split the description string into individual description:type pairs
                        const descriptionPairs = data.description.split(',');
                        
                        descriptionPairs.forEach((pair, index) => {
                            // Split each pair into description and type
                            const [desc, type] = pair.split(':');
                            
                            const descDiv = document.createElement('div');
                            descDiv.className = 'description-container';
                            descDiv.innerHTML = `
                                <input type="text" class="input-field description-input" value="${desc}" placeholder="Description">
                                <select class="input-field maintenance-type">
                                    <option value="preventive" ${type === 'preventive' ? 'selected' : ''}>Preventive</option>
                                    <option value="corrective" ${type === 'corrective' ? 'selected' : ''}>Corrective</option>
                                </select>
                                ${index === 0 ? 
                                    '<button type="button" class="add-description-btn" onclick="addDescriptionFieldInEdit(this)">+</button>' : 
                                    '<button type="button" class="remove-description-btn" onclick="removeDescriptionFieldInEdit(this)">-</button>'}
                            `;
                            descriptionsContainer.appendChild(descDiv);
                        });

                        // Show the edit modal
                        toggleEditContainer();
                    } else {
                        alert('Failed to load maintenance record details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching maintenance record details.');
                });
        }

        function saveEdit() {
            const maintenanceId = document.getElementById('editMaintenanceId').value;
            const date = document.getElementById('editDate').value;
            const shop = document.getElementById('editRepairShop').value;
            const fileType = document.getElementById('editFileType').value;
            const fileTypeValue = document.getElementById('editFileTypeValue').value;
            const cost = parseFloat(document.getElementById('editCost').value) || 0;

            // Collect all descriptions and their types
            const descriptionContainers = document.querySelectorAll('#editDescriptionsContainer .description-container');
            const descriptions = [];
            let types = [];
            
            descriptionContainers.forEach(container => {
                const input = container.querySelector('.description-input');
                const select = container.querySelector('.maintenance-type');
                if (input.value.trim()) {
                    // Store description with its type in format "description:type"
                    descriptions.push(`${input.value.trim()}:${select.value}`);
                    types.push(select.value);
                }
            });

            if (!date || !shop || descriptions.length === 0 || isNaN(cost)) {
                alert("Please fill all required fields correctly.");
                return;
            }

            // Validate file type if one is selected
            if (fileType && !fileTypeValue) {
                alert("Please enter a value for the selected reference type.");
                return;
            }

            // Validate date is within quarter
            const recordDate = new Date(date);
            const recordYear = recordDate.getFullYear();
            const recordMonth = recordDate.getMonth() + 1;
            
            if (recordYear != year || recordMonth < startMonth || recordMonth > endMonth) {
                alert(`Date must be within Q${quarter} ${year} (${getMonthName(startMonth)} to ${getMonthName(endMonth)})`);
                return;
            }

            // Combine descriptions into one string for the database
            const descriptionText = descriptions.join(',');
            
            // Determine maintenance type (if all same, use that, otherwise "Mixed")
            const uniqueTypes = [...new Set(types)];
            const maintenanceType = uniqueTypes.length === 1 ? uniqueTypes[0] : 'Mixed';

            const payload = {
                maintenance_id: maintenanceId,
                date: date,
                type: maintenanceType,
                repair_shop: shop,
                file_type: fileType,
                file_type_value: fileTypeValue,
                description: descriptionText,
                cost: cost
            };

            fetch("update_maintenance.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Refresh the table to show updated data
                    location.reload();
                } else {
                    alert("Error: " + (data.error || "Failed to update record"));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred while updating the record.");
            });
        }

        function deleteRow(button, maintenance_id) {
            if (!confirm("Are you sure you want to delete this record?")) return;

            fetch("delete_maintenance.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({ maintenance_id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from the table
                    button.closest('tr').remove();
                    updateTotalCost();
                } else {
                    alert("Error deleting record");
                }
            });
        }

        function getMonthName(monthNumber) {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[monthNumber - 1];
        }

        function updateTotalCost() {
            const rows = document.querySelectorAll("#reportBody tr");
            let totalCost = 0;
            let preventiveCount = 0;
            let correctiveCount = 0;
            
            rows.forEach(row => {
                // Get cost
                const costText = row.cells[7].textContent.replace("₱", "").replace(/,/g, ''); 
                const cost = parseFloat(costText) || 0;
                totalCost += cost;
                
                // Get maintenance type counts
                const maintenanceType = row.cells[6].textContent.trim();
                if (maintenanceType === "Mixed") {
                    // For mixed types, we need to parse the descriptions
                    const descriptionText = row.cells[5].textContent;
                    const descriptions = descriptionText.split(',');
                    
                    descriptions.forEach(desc => {
                        const parts = desc.split(':');
                        if (parts.length === 2) {
                            const type = parts[1].trim().toLowerCase();
                            if (type === 'preventive') {
                                preventiveCount++;
                            } else if (type === 'corrective') {
                                correctiveCount++;
                            }
                        } else {
                            // Fallback for old entries without type info
                            if (maintenanceType === "Preventive") {
                                preventiveCount++;
                            } else if (maintenanceType === "Corrective") {
                                correctiveCount++;
                            }
                        }
                    });
                } else {
                    // For single type entries
                    if (maintenanceType === "Preventive") {
                        preventiveCount++;
                    } else if (maintenanceType === "Corrective") {
                        correctiveCount++;
                    }
                }
            });
            
            // Update the display
            document.getElementById("totalCost").textContent = `₱${totalCost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById("maintenanceCounts").innerHTML = `
                <div style="display: flex; flex-direction: column; font-size: 0.9em;">
                    <span>Preventive: ${preventiveCount}</span>
                    <span>Corrective: ${correctiveCount}</span>
                </div>
            `;
        }

        function removeDescriptionFieldInEdit(button) {
            const container = button.closest('#editDescriptionsContainer');
            if (container.children.length > 1) {
                button.closest('.description-container').remove();
                
                // If we removed a description and now only one remains, make sure it has an add button
                if (container.children.length === 1) {
                    const firstContainer = container.querySelector('.description-container');
                    const existingButton = firstContainer.querySelector('button');
                    if (existingButton && existingButton.classList.contains('remove-description-btn')) {
                        existingButton.outerHTML = '<button type="button" class="add-description-btn" onclick="addDescriptionFieldInEdit(this)">+</button>';
                    }
                }
            }
        }
    </script>
</body>