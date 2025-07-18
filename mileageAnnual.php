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
$reportType = $_GET['report_type'] ?? 'annual'; // New parameter for report type

if (!$vehicleId) {
    echo "<script>alert('No vehicle selected.'); window.location.href='selectvehicleReport.php?report=mileage';</script>";
    exit();
}

// Get vehicle info
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?report=mileage';</script>";
    exit();
}

$monthlyData = [];
$quarterlyData = [];
$totalDistance = 0;
$totalFuel = 0;
$totalCost = 0;

// Process monthly data
for ($month = 1; $month <= 12; $month++) {
    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    $stmt = $conn->prepare("SELECT SUM(distance) as total_distance, SUM(liters) as total_liters, SUM(cost) as total_cost FROM mileage 
                            WHERE vehicle_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $distance = $result['total_distance'] ?? 0;
    $liters = $result['total_liters'] ?? 0;
    $cost = $result['total_cost'] ?? 0;
    $efficiency = ($liters > 0) ? ($distance / $liters) : 0;

    $monthlyData[] = [
        'month' => date("F", mktime(0, 0, 0, $month, 1)),
        'distance' => $distance,
        'liters' => $liters,
        'cost' => $cost,
        'efficiency' => $efficiency,
    ];

    $totalDistance += $distance;
    $totalFuel += $liters;
    $totalCost += $cost;
}

// Process quarterly data
for ($quarter = 1; $quarter <= 4; $quarter++) {
    $startMonth = (($quarter - 1) * 3) + 1;
    $endMonth = $startMonth + 2;
    
    $startDate = "$year-$startMonth-01";
    $endDate = date("Y-m-t", strtotime("$year-$endMonth-01"));
    
    $stmt = $conn->prepare("SELECT SUM(distance) as total_distance, SUM(liters) as total_liters, SUM(cost) as total_cost FROM mileage 
                            WHERE vehicle_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $distance = $result['total_distance'] ?? 0;
    $liters = $result['total_liters'] ?? 0;
    $cost = $result['total_cost'] ?? 0;
    $efficiency = ($liters > 0) ? ($distance / $liters) : 0;
    
    $quarterlyData[] = [
        'quarter' => "Q$quarter",
        'start_month' => date("F", mktime(0, 0, 0, $startMonth, 1)),
        'end_month' => date("F", mktime(0, 0, 0, $endMonth, 1)),
        'distance' => $distance,
        'liters' => $liters,
        'cost' => $cost,
        'efficiency' => $efficiency,
    ];
}

$totalEfficiency = ($totalFuel > 0) ? ($totalDistance / $totalFuel) : 0;

// Function to get data for a specific quarter
function getQuarterData($quarter, $year, $vehicleId, $conn) {
    $startMonth = (($quarter - 1) * 3) + 1;
    $endMonth = $startMonth + 2;
    
    $startDate = "$year-$startMonth-01";
    $endDate = date("Y-m-t", strtotime("$year-$endMonth-01"));
    
    $stmt = $conn->prepare("SELECT SUM(distance) as total_distance, SUM(liters) as total_liters, SUM(cost) as total_cost FROM mileage 
                            WHERE vehicle_id = ? AND date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $distance = $result['total_distance'] ?? 0;
    $liters = $result['total_liters'] ?? 0;
    $cost = $result['total_cost'] ?? 0;
    $efficiency = ($liters > 0) ? ($distance / $liters) : 0;
    
    return [
        'quarter' => "Q$quarter",
        'start_month' => date("F", mktime(0, 0, 0, $startMonth, 1)),
        'end_month' => date("F", mktime(0, 0, 0, $endMonth, 1)),
        'distance' => $distance,
        'liters' => $liters,
        'cost' => $cost,
        'efficiency' => $efficiency,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Annual Mileage - NPC MVFM System</title>
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
            color: #1E293B;
            margin-top: 0px;
            font-size: 20px;
            margin-bottom: 10px;
            text-align: center;
        }
        h4 {
            color: #1E293B;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
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
        }
        th {
            background-color: #1E293B;
            color: white;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 15px;
            padding: 8px;
            white-space: nowrap;
        }
        td {
            font-size: 15px;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }
        tr:hover td {
            background-color: #d5e4f5;
        }
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
        .report-options {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            align-items: center;
        }
        .report-options select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        @media print {
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
            }
            nav, .back-btn, .add-row-btn, .submit-btn, .cancel-btn, .edit-btn, .delete-btn, #overlay,
            #addContainer, #editContainer, .generate-report-btn, .report-options {
                display: none !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table, th, td {
                border: 1px solid black;
                padding: 8px;
                color: black;
            }
            h2, h3, h4 {
                text-align: center;
                color: black;
            }
            .summary {
                margin-top: 20px;
            }
            .button {
                display: none;
            }
            @page {
                size: A4 portrait;
                margin: 20mm;
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
        <h2>Annual/Quarterly Report of Mileage and Fuel Consumption</h2>
        <h3>For the Year: <?= $year ?></h3>
        <p><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> | 
            <strong>Plate No: </strong><?= htmlspecialchars($vehicle['plate']) ?> | <strong>Fuel: </strong><?= htmlspecialchars($vehicle['fuel']) ?>
        </p>

        <!-- Report Options -->
        <div class="report-options">
            <select id="reportType" onchange="updateReportType()">
                <option value="annual" <?= $reportType === 'annual' ? 'selected' : '' ?>>Annual Report</option>
                <option value="q1" <?= $reportType === 'q1' ? 'selected' : '' ?>>Quarter 1 (Jan-Mar)</option>
                <option value="q2" <?= $reportType === 'q2' ? 'selected' : '' ?>>Quarter 2 (Apr-Jun)</option>
                <option value="q3" <?= $reportType === 'q3' ? 'selected' : '' ?>>Quarter 3 (Jul-Sep)</option>
                <option value="q4" <?= $reportType === 'q4' ? 'selected' : '' ?>>Quarter 4 (Oct-Dec)</option>
            </select>
            <button class="generate-report-btn" onclick="printReport()">Generate Report</button>
        </div>

        <?php if ($reportType === 'annual'): ?>
            <!-- Annual Report -->
            <h4>Monthly Breakdown</h4>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Distance (KM)</th>
                        <th>Total Fuel (L)</th>
                        <th>Fuel Efficiency (KM/L)</th>
                        <th>Cost (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $month): ?>
                        <tr>
                            <td><?= $month['month'] ?></td>
                            <td><?= number_format($month['distance'], 2) ?></td>
                            <td><?= number_format($month['liters'], 2) ?></td>
                            <td><?= number_format($month['efficiency'], 2) ?></td>
                            <td><?= number_format($month['cost'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td>Total</td>
                        <td><?= number_format($totalDistance, 2) ?> KM</td>
                        <td><?= number_format($totalFuel, 2) ?> L</td>
                        <td><?= number_format($totalEfficiency, 2) ?> KM/L</td>
                        <td>₱<?= number_format($totalCost, 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <h4>Quarterly Breakdown</h4>
            <table border="1" cellpadding="10" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Quarter</th>
                        <th>Period</th>
                        <th>Total Distance (KM)</th>
                        <th>Total Fuel (L)</th>
                        <th>Fuel Efficiency (KM/L)</th>
                        <th>Cost (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quarterlyData as $quarter): ?>
                        <tr>
                            <td><?= $quarter['quarter'] ?></td>
                            <td><?= $quarter['start_month'] ?> - <?= $quarter['end_month'] ?></td>
                            <td><?= number_format($quarter['distance'], 2) ?></td>
                            <td><?= number_format($quarter['liters'], 2) ?></td>
                            <td><?= number_format($quarter['efficiency'], 2) ?></td>
                            <td><?= number_format($quarter['cost'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: 
            // Handle quarterly reports
            $quarter = substr($reportType, 1);
            $quarterData = getQuarterData($quarter, $year, $vehicleId, $conn);
            $monthsInQuarter = [
                '1' => ['January', 'February', 'March'],
                '2' => ['April', 'May', 'June'],
                '3' => ['July', 'August', 'September'],
                '4' => ['October', 'November', 'December']
            ];
        ?>
            <!-- Quarterly Report -->
            <h4>Quarter <?= $quarter ?> Report (<?= $quarterData['start_month'] ?> - <?= $quarterData['end_month'] ?>)</h4>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Distance (KM)</th>
                        <th>Total Fuel (L)</th>
                        <th>Fuel Efficiency (KM/L)</th>
                        <th>Cost (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthsInQuarter[$quarter] as $monthName): 
                        $monthIndex = array_search($monthName, array_column($monthlyData, 'month'));
                        $monthData = $monthlyData[$monthIndex] ?? [
                            'month' => $monthName,
                            'distance' => 0,
                            'liters' => 0,
                            'cost' => 0,
                            'efficiency' => 0
                        ];
                    ?>
                        <tr>
                            <td><?= $monthData['month'] ?></td>
                            <td><?= number_format($monthData['distance'], 2) ?></td>
                            <td><?= number_format($monthData['liters'], 2) ?></td>
                            <td><?= number_format($monthData['efficiency'], 2) ?></td>
                            <td><?= number_format($monthData['cost'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td>Quarter Total</td>
                        <td><?= number_format($quarterData['distance'], 2) ?> KM</td>
                        <td><?= number_format($quarterData['liters'], 2) ?> L</td>
                        <td><?= number_format($quarterData['efficiency'], 2) ?> KM/L</td>
                        <td>₱<?= number_format($quarterData['cost'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function updateReportType() {
            const reportType = document.getElementById('reportType').value;
            const url = new URL(window.location.href);
            url.searchParams.set('report_type', reportType);
            window.location.href = url.toString();
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>