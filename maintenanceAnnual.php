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

function getMaintenanceCountsByQuarter($conn, $vehicleId, $year) {
    $quarterlyData = [];
    
    for ($quarter = 1; $quarter <= 4; $quarter++) {
        // Calculate start and end dates for the quarter
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;
        
        // Handle December (month 12) case
        if ($endMonth > 12) {
            $endMonth = 12;
        }
        
        $startDate = "$year-$startMonth-01";
        $endDate = date("Y-m-t", strtotime("$year-$endMonth-01"));
        
        // Query to get maintenance counts for the quarter
        $query = "SELECT 
                    SUM(CASE WHEN type = 'Corrective' THEN 1 ELSE 0 END) as corrective_count,
                    SUM(CASE WHEN type = 'Preventive' THEN 1 ELSE 0 END) as preventive_count,
                    SUM(CASE WHEN type = 'Mixed' THEN 1 ELSE 0 END) as mixed_count,
                    SUM(cost) as total_cost
                  FROM maintenance 
                  WHERE vehicle_id = ? 
                  AND date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $vehicleId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // For mixed records, we need to count the individual descriptions
        if ($result['mixed_count'] > 0) {
            $mixedQuery = "SELECT description FROM maintenance 
                          WHERE vehicle_id = ? 
                          AND type = 'Mixed'
                          AND date BETWEEN ? AND ?";
            $mixedStmt = $conn->prepare($mixedQuery);
            $mixedStmt->bind_param("iss", $vehicleId, $startDate, $endDate);
            $mixedStmt->execute();
            $mixedResults = $mixedStmt->get_result();
            
            $mixedCorrective = 0;
            $mixedPreventive = 0;
            
            while ($row = $mixedResults->fetch_assoc()) {
                $descriptions = explode(',', $row['description']);
                foreach ($descriptions as $desc) {
                    $parts = explode(':', $desc);
                    if (count($parts) === 2) {
                        $type = trim($parts[1]);
                        if ($type === 'corrective') $mixedCorrective++;
                        if ($type === 'preventive') $mixedPreventive++;
                    }
                }
            }
            
            $result['corrective_count'] += $mixedCorrective;
            $result['preventive_count'] += $mixedPreventive;
        }
        
        $quarterlyData[$quarter] = [
            'corrective' => $result['corrective_count'] ?? 0,
            'preventive' => $result['preventive_count'] ?? 0,
            'cost' => $result['total_cost'] ?? 0
        ];
    }
    
    return $quarterlyData;
}

$quarterlyCounts = getMaintenanceCountsByQuarter($conn, $vehicleId, $year);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Annual Maintenance - NPC MVFM System</title>
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
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            border-color: 334155;
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
                /* margin: 20mm; */
            }
            nav, .back-btn, .add-row-btn, .submit-btn, .cancel-btn, .edit-btn, .delete-btn, #overlay,
            #addContainer, #editContainer, .generate-report-btn, #costComparisonChart {
                display: none !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
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
            .summary {
                margin-top: 20px;
            }
            .button {
                display: none;
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
            <a class="button" href="maintenanceSelection.php?vehicle_id=<?= $vehicleId ?>">Back</a>
        </div>
        <h2>Annual Report of Maintenance</h2>
        <h3>For the Year: <?= $year ?></h3>
        <p><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> | 
            <strong>Plate No:</strong> <?= htmlspecialchars($vehicle['plate']) ?> | <strong>Cost:</strong> ₱<?= number_format($vehicle['cost'], 2) ?>
        </p>

        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Quarter</th>
                    <th>Total Corrective Maintenance Count</th>
                    <th>Total Preventive Maintenance Count</th>
                    <th>Cost (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quarterlyCounts as $quarter => $data): ?>
                <tr>
                    <td>Q<?= $quarter ?></td>
                    <td><?= $data['corrective'] ?></td>
                    <td><?= $data['preventive'] ?></td>
                    <td>₱<?= number_format($data['cost'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td><strong>Total</strong></td>
                    <td><strong><?= array_sum(array_column($quarterlyCounts, 'corrective')) ?></strong></td>
                    <td><strong><?= array_sum(array_column($quarterlyCounts, 'preventive')) ?></strong></td>
                    <td><strong>₱<?= number_format(array_sum(array_column($quarterlyCounts, 'cost')), 2) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <br>
        <br>
        <div class="summary">
            <h3>Cost Summary</h3>
            <p>Total Maintenance Cost for <?= $year ?>: ₱<?= number_format(array_sum(array_column($quarterlyCounts, 'cost')), 2) ?></p>
            <?php 
                $vehicleCost = $vehicle['cost'];
                $totalMaintenanceCost = array_sum(array_column($quarterlyCounts, 'cost'));
                $percentage = ($vehicleCost > 0) ? round(($totalMaintenanceCost / $vehicleCost) * 100, 2) : 0;
            ?>
            <p>Maintenance costs represent <strong><?= $percentage ?>%</strong> of the vehicle's total cost (₱<?= number_format($vehicleCost, 2) ?>).</p>
        </div>

        <!-- <div id="costComparisonChart" style="width: 100%; max-width: 600px; margin: 20px auto;">
            <canvas id="myPieChart" width="400" height="400"></canvas>
            <div id="printPercentage" style="display: none;">
                <?php 
                    $vehicleCost = $vehicle['cost'];
                    $totalMaintenanceCost = array_sum(array_column($quarterlyCounts, 'cost'));
                    $percentage = ($vehicleCost > 0) ? round(($totalMaintenanceCost / $vehicleCost) * 100, 2) : 0;
                ?>
                <p>Maintenance costs represent <?= $percentage ?>% of the vehicle's total cost.</p>
            </div>
        </div> -->

        <button class="generate-report-btn" onclick="window.print()" style="margin: 10px 0;">Generate Report</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Get the data from PHP
        const vehicleCost = <?= $vehicle['cost'] ?>;
        const totalMaintenanceCost = <?= array_sum(array_column($quarterlyCounts, 'cost')) ?>;
        
        // Calculate percentage
        const percentage = vehicleCost > 0 ? Math.round((totalMaintenanceCost / vehicleCost) * 100 * 100) / 100 : 0;
        
        // Create pie chart
        // const ctx = document.getElementById('myPieChart').getContext('2d');
        // const myPieChart = new Chart(ctx, {
        //     type: 'pie',
        //     data: {
        //         labels: ['Maintenance Cost', 'Vehicle Cost'],
        //         datasets: [{
        //             data: [totalMaintenanceCost, Math.max(0, vehicleCost - totalMaintenanceCost)],
        //             backgroundColor: [
        //                 'rgba(255, 99, 132, 0.7)',
        //                 'rgba(54, 162, 235, 0.7)'
        //             ],
        //             borderColor: [
        //                 'rgba(255, 99, 132, 1)',
        //                 'rgba(54, 162, 235, 1)'
        //             ],
        //             borderWidth: 1
        //         }]
        //     },
        //     options: {
        //         responsive: true,
        //         plugins: {
        //             title: {
        //                 display: true,
        //                 text: `Maintenance Cost (${percentage}% of Vehicle Cost)`,
        //                 font: {
        //                     size: 16
        //                 }
        //             },
        //             legend: {
        //                 position: 'bottom'
        //             },
        //             tooltip: {
        //                 callbacks: {
        //                     label: function(context) {
        //                         let label = context.label || '';
        //                         if (label) {
        //                             label += ': ';
        //                         }
        //                         label += '₱' + context.raw.toLocaleString('en-PH', 
        //                             { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        //                         return label;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // });
    </script>
</body>
</html>