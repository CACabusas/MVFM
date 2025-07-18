<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$vehicleId = $_GET['vehicle_id'] ?? null;

if (!$vehicleId) {
    // Redirect to vehicle selection if no vehicle is chosen
    header("Location: selectvehicleReport.php?history=true");
    exit();
}

// Get vehicle information including acquisition cost
$vehicleQuery = "SELECT v.*, 
                COALESCE(ac.acquisition_cost, v.cost) as acquisition_cost 
                FROM vehicles v
                LEFT JOIN acquisition_costs ac ON v.vehicle_id = ac.vehicle_id
                WHERE v.vehicle_id = ?";
$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicleResult = $stmt->get_result();
$vehicle = $vehicleResult->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?history=true';</script>";
    exit();
}

$acquisitionCost = $vehicle['acquisition_cost'] ?? 0;

// Get ALL maintenance records for the vehicle (removed the year filter)
$maintenanceQuery = "SELECT * FROM maintenance 
                    WHERE vehicle_id = ? 
                    ORDER BY date DESC";  // Changed to DESC to show most recent first
$stmt = $conn->prepare($maintenanceQuery);
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$maintenanceResult = $stmt->get_result();
$data = $maintenanceResult->fetch_all(MYSQLI_ASSOC);

// Group records by PO Number (file_type_value) and date
$groupedData = [];
foreach ($data as $record) {
    $key = $record['file_type_value'] . '|' . $record['date'];
    if (!isset($groupedData[$key])) {
        $groupedData[$key] = [
            'date' => $record['date'],
            'reference' => $record['file_type'],
            'issue_slip' => $record['file_type_value'],
            'nature_of_repair' => [],
            'cost' => 0
        ];
    }
    
    // Split descriptions by comma and add to nature_of_repair
    $descriptions = explode(',', $record['description']);
    foreach ($descriptions as $desc) {
        $trimmedDesc = trim($desc);
        if (!empty($trimmedDesc)) {
            $groupedData[$key]['nature_of_repair'][] = $trimmedDesc;
        }
    }
    
    $groupedData[$key]['cost'] += $record['cost'];
}

// Calculate total cost and percentages
$totalCost = 0;
foreach ($groupedData as &$group) {
    $totalCost += $group['cost'];
    $group['percentage'] = $acquisitionCost > 0 ? ($group['cost'] / $acquisitionCost) * 100 : 0;
}
unset($group); // Break the reference

// Get fuel and mileage statistics
$mileageQuery = "SELECT 
                SUM(distance) as total_distance,
                SUM(liters) as total_fuel,
                SUM(cost) as total_fuel_cost
                FROM mileage 
                WHERE vehicle_id = ?";
$stmt = $conn->prepare($mileageQuery);
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$mileageResult = $stmt->get_result()->fetch_assoc();

$totalDistance = $mileageResult['total_distance'] ?? 0;
$totalFuel = $mileageResult['total_fuel'] ?? 0;
$totalFuelCost = $mileageResult['total_fuel_cost'] ?? 0;
$fuelEfficiency = ($totalFuel > 0) ? round($totalDistance / $totalFuel, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair History - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .vehicle-info {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            margin-left: auto;
            margin-right: auto;
            width: 100%;
            max-width: 1280px;
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
        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }
        /* th:nth-child(1), td:nth-child(1) { width: 8%; }
        th:nth-child(2), td:nth-child(2) { width: 8%; }
        th:nth-child(5), td:nth-child(5) { width: 10%; }
        th:nth-child(6), td:nth-child(6) { width: 8%; } */
        .button {
            margin-bottom: 20px;
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
        .back-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #1e293b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background-color: #334155;
        }
        .print-button {
            display: block;
            width: 100px;
            margin: 20px auto;
            padding: 10px;
            background-color: #1e293b;
            color: white;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
        }
        .print-button:hover {
            background-color: #334155;
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px;
            min-width: 200px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .generate-report-btn {
            padding: 10px 20px;
            font-size: 16px;
            border-color: #1e293b;
            background-color: white;
            color: black;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .generate-report-btn:hover {
            background-color: #1e293b;
            color: white;
            border-color: #1e293b;
        }
        @media print {
            .navbar, .back-button, .generate-report-btn {
                display: none !important;
            }
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12px;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            .stats-container, .stat-value {
                color: black;
                font-size: 12pt;
            }
            .stats-container {
                justify-content: center;
                gap: 5px;
                margin-bottom: 5px;
            }
            .stat-box {
                box-shadow: none !important;
                margin: 5px !important;
                padding: 5px 10px !important;
                min-width: auto !important;
                flex: 1;
            }
            .stat-label {
                font-size: 8pt;
                color: black;
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
            h1, h2, h3, h4, p {
                text-align: center;
                color: black;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
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
            <a class="button" href="selectvehicleReport.php?report=history">Back</a>
        </div>
        
        <h2>Complete Vehicle History</h2>
        
        <div class="vehicle-info">
            <h3><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['type'] . ')'); ?></h3>

            <?php
            $image = (!empty($vehicle['image_url']) && file_exists("uploads/{$vehicle['image_url']}")) 
                ? "uploads/{$vehicle['image_url']}" 
                : "uploads/404.png";
            ?>
            <img src="<?= $image ?>" alt="Vehicle Image" style="width: 200px; height: auto; border-radius: 8px;" onerror="this.src='uploads/404.png';">

            <p><strong>Plate No:</strong> <?php echo htmlspecialchars($vehicle['plate']); ?> | <strong>Fuel:</strong> <?= htmlspecialchars($vehicle['fuel']) ?> | <strong>Cost:</strong> ₱<?php echo number_format($acquisitionCost, 2); ?></p>
        </div>

   <div style="margin-left: calc(-50vw + 50%); margin-right: calc(-50vw + 50%);"><hr style="margin: 30px 0; border: none; border-top: 2px solid #ccc;"></div>

        <button class="generate-report-btn" onclick="window.print()" style="margin: 10px 0;">Generate Report</button>
        
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($groupedData); ?></div>
                <div class="stat-label">Total Maintenance Activities</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₱<?php echo number_format($totalCost, 2); ?></div>
                <div class="stat-label">Total Maintenance Cost</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo number_format(($acquisitionCost > 0 ? ($totalCost / $acquisitionCost) * 100 : 0), 2); ?>%</div>
                <div class="stat-label">of Acquisition Cost</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo number_format($totalDistance); ?> km</div>
                <div class="stat-label">Total Distance</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo number_format($totalFuel); ?> L</div>
                <div class="stat-label">Total Fuel</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">₱<?php echo number_format($totalFuelCost, 2); ?></div>
                <div class="stat-label">Total Fuel Cost</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $fuelEfficiency; ?> km/L</div>
                <div class="stat-label">Fuel Efficiency</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference Type</th>
                    <th>Issue Slip Number</th>
                    <th>Nature of Repair</th>
                    <th>Amount</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groupedData)): ?>
                    <?php foreach ($groupedData as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['reference']); ?></td>
                            <td><?php echo htmlspecialchars($record['issue_slip']); ?></td>
                            <td>
                                <?php foreach ($record['nature_of_repair'] as $item): ?>
                                    <?php echo htmlspecialchars($item); ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td>₱<?php echo number_format($record['cost'], 2); ?></td>
                            <td><?php echo number_format($record['percentage'], 2); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4">TOTAL</td>
                        <td>₱<?php echo number_format($totalCost, 2); ?></td>
                        <td><?php echo number_format(($acquisitionCost > 0 ? ($totalCost / $acquisitionCost) * 100 : 0), 2); ?>%</td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No maintenance records found for this vehicle</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>