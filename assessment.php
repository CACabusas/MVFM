<?php
session_start();
require_once "db_connect.php";

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

// Get vehicle ID from URL
$vehicle_id = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;
if ($vehicle_id <= 0) {
    echo "<script>alert('Invalid vehicle selected.'); window.location.href='selectvehicleReport.php?report=assessment';</script>";
    exit();
}

// Get vehicle details
$vehicle = [];
$query = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $vehicle = $result->fetch_assoc();
} else {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?report=assessment';</script>";
    exit();
}

// Get available years for this vehicle
$years = [];
$query = "SELECT DISTINCT YEAR(date) as year FROM mileage WHERE vehicle_id = ? 
          UNION 
          SELECT DISTINCT YEAR(date) as year FROM maintenance WHERE vehicle_id = ? 
          ORDER BY year DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $years[] = $row['year'];
}

// Default to current year if no year selected
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : (count($years) > 0 ? $years[0] : date('Y'));

// Get assessment data for selected year
$assessment_data = [
    'fuel_consumption' => 0,
    'fuel_cost' => 0,
    'distance_travelled' => 0,
    'maintenance_cost' => 0,
    'maintenance_count' => 0,
    'corrective_cost' => 0,
    'preventive_cost' => 0,
    'corrective_count' => 0,
    'preventive_count' => 0,
    'accident_count' => 0
];

// Get fuel and mileage data
$query = "SELECT SUM(liters) as total_liters, SUM(cost) as total_cost, SUM(distance) as total_distance 
          FROM mileage 
          WHERE vehicle_id = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $assessment_data['fuel_consumption'] = $row['total_liters'] ?? 0;
    $assessment_data['fuel_cost'] = $row['total_cost'] ?? 0;
    $assessment_data['distance_travelled'] = $row['total_distance'] ?? 0;
}

// Get maintenance data
$query = "SELECT 
            SUM(CASE WHEN type = 'Corrective' THEN 1 ELSE 0 END) as corrective_count,
            SUM(CASE WHEN type = 'Preventive' THEN 1 ELSE 0 END) as preventive_count,
            SUM(CASE WHEN type = 'Mixed' THEN 1 ELSE 0 END) as mixed_count,
            SUM(cost) as total_cost,
            SUM(CASE WHEN type = 'Corrective' THEN cost ELSE 0 END) as corrective_cost,
            SUM(CASE WHEN type = 'Preventive' THEN cost ELSE 0 END) as preventive_cost
          FROM maintenance 
          WHERE vehicle_id = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $assessment_data['maintenance_cost'] = $row['total_cost'] ?? 0;
    $assessment_data['corrective_cost'] = $row['corrective_cost'] ?? 0;
    $assessment_data['preventive_cost'] = $row['preventive_cost'] ?? 0;
    
    // For mixed records, we need to count the individual descriptions
    if ($row['mixed_count'] > 0) {
        $mixedQuery = "SELECT description FROM maintenance 
                      WHERE vehicle_id = ? AND YEAR(date) = ? AND type = 'Mixed'";
        $mixedStmt = $conn->prepare($mixedQuery);
        $mixedStmt->bind_param("ii", $vehicle_id, $selected_year);
        $mixedStmt->execute();
        $mixedResults = $mixedStmt->get_result();
        
        $mixedCorrective = 0;
        $mixedPreventive = 0;
        
        while ($row_mixed = $mixedResults->fetch_assoc()) {
            $descriptions = explode(',', $row_mixed['description']);
            foreach ($descriptions as $desc) {
                $parts = explode(':', $desc);
                if (count($parts) === 2) {
                    $type = trim($parts[1]);
                    if ($type === 'corrective') $mixedCorrective++;
                    if ($type === 'preventive') $mixedPreventive++;
                }
            }
        }
        
        $assessment_data['corrective_count'] = ($row['corrective_count'] ?? 0) + $mixedCorrective;
        $assessment_data['preventive_count'] = ($row['preventive_count'] ?? 0) + $mixedPreventive;
        $assessment_data['maintenance_count'] = $assessment_data['corrective_count'] + $assessment_data['preventive_count'];
    } else {
        $assessment_data['corrective_count'] = $row['corrective_count'] ?? 0;
        $assessment_data['preventive_count'] = $row['preventive_count'] ?? 0;
        $assessment_data['maintenance_count'] = $assessment_data['corrective_count'] + $assessment_data['preventive_count'];
    }
}

// Get accident count (assuming accidents are maintenance with type='corrective' and description contains 'accident')
$query = "SELECT COUNT(*) as accident_count 
          FROM maintenance 
          WHERE vehicle_id = ? AND YEAR(date) = ? AND type = 'corrective' 
          AND LOWER(description) LIKE '%accident%'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $assessment_data['accident_count'] = $row['accident_count'] ?? 0;
}

// Calculate average km per liter
$avg_km_per_liter = ($assessment_data['fuel_consumption'] > 0) 
    ? round($assessment_data['distance_travelled'] / $assessment_data['fuel_consumption'], 2) 
    : 0;

// Get monthly data for charts (for fuel and distance)
$monthly_data = [];
for ($month = 1; $month <= 12; $month++) {
    $monthly_data[$month] = [
        'fuel' => 0,
        'distance' => 0,
        'cost' => 0,
        'efficiency' => 0
    ];
}

// Get quarterly maintenance data
$quarterly_data = [];
for ($quarter = 1; $quarter <= 4; $quarter++) {
    $quarterly_data[$quarter] = [
        'corrective' => 0,
        'preventive' => 0
    ];
}

// Get quarterly maintenance counts
$query = "SELECT 
            QUARTER(date) as quarter, 
            type,
            description
          FROM maintenance 
          WHERE vehicle_id = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();

// Reset quarterly data
$quarterly_data = [];
for ($quarter = 1; $quarter <= 4; $quarter++) {
    $quarterly_data[$quarter] = [
        'corrective' => 0,
        'preventive' => 0
    ];
}

while ($row = $result->fetch_assoc()) {
    $quarter = $row['quarter'];
    $type = strtolower($row['type']);
    
    if ($type === 'mixed') {
        // Handle mixed records by counting individual descriptions
        $descriptions = explode(',', $row['description']);
        foreach ($descriptions as $desc) {
            $parts = explode(':', $desc);
            if (count($parts) === 2) {
                $descType = trim($parts[1]);
                if ($descType === 'corrective') {
                    $quarterly_data[$quarter]['corrective']++;
                } elseif ($descType === 'preventive') {
                    $quarterly_data[$quarter]['preventive']++;
                }
            }
        }
    } else {
        // Handle direct corrective/preventive records
        if ($type === 'corrective') {
            $quarterly_data[$quarter]['corrective']++;
        } elseif ($type === 'preventive') {
            $quarterly_data[$quarter]['preventive']++;
        }
    }
}

// Get monthly fuel, distance, and cost
$query = "SELECT MONTH(date) as month, 
                 SUM(liters) as fuel, 
                 SUM(distance) as distance,
                 SUM(cost) as cost
          FROM mileage 
          WHERE vehicle_id = ? AND YEAR(date) = ? 
          GROUP BY MONTH(date)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $vehicle_id, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $month = $row['month'];
    $monthly_data[$month]['fuel'] = $row['fuel'] ?? 0;
    $monthly_data[$month]['distance'] = $row['distance'] ?? 0;
    $monthly_data[$month]['cost'] = $row['cost'] ?? 0;
    // Calculate efficiency (km/liter) for each month
    $monthly_data[$month]['efficiency'] = ($row['fuel'] > 0) 
        ? round($row['distance'] / $row['fuel'], 2) 
        : 0;
}

// Prepare data for charts
$monthly_labels = json_encode(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
$quarterly_labels = json_encode(['Q1', 'Q2', 'Q3', 'Q4']);
$chart_corrective = json_encode(array_column($quarterly_data, 'corrective'));
$chart_preventive = json_encode(array_column($quarterly_data, 'preventive'));
$chart_fuel = json_encode(array_column($monthly_data, 'fuel'));
$chart_distance = json_encode(array_column($monthly_data, 'distance'));
$chart_cost = json_encode(array_column($monthly_data, 'cost'));
$chart_efficiency = json_encode(array_column($monthly_data, 'efficiency'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Annual Assessment - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
        }
        .vehicle-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .vehicle-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        .vehicle-info {
            flex: 1;
        }
        .vehicle-title {
            font-size: 28px;
            margin: 0 0 10px 0;
            color: #1e293b;
        }
        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .detail-item {
            margin-bottom: 5px;
        }
        .detail-label {
            font-weight: bold;
            color: #64748b;
        }
        .year-selector {
            margin: 20px 0;
            display: flex;
            align-items: center;
        }
        .year-selector label {
            margin-right: 10px;
            font-weight: bold;
        }
        .year-selector select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .assessment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .assessment-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card-title {
            font-size: 16px;
            margin: 0 0 10px 0;
            color: #475569;
        }
        .card-value {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #1e293b;
        }
        .card-unit {
            font-size: 14px;
            color: #64748b;
        }
        .chart-container {
            margin: 30px 0;
        }
        .chart-title {
            font-size: 18px;
            margin: 0 0 15px 0;
            color: #1e293b;
        }
        .chart-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .back-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1e293b;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background-color: #334155;
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
                    <!-- <li><a href="selectvehicleReport.php?report=assessment">Annual Vehicle Assessment Reports</a></li> -->
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
            <a class="back-button" href="dashboard.php">Back</a>
        </div>
        
        <div class="vehicle-header">
            <?php
            $image = (!empty($vehicle['image_url']) && file_exists("uploads/{$vehicle['image_url']}")) 
                ? "uploads/{$vehicle['image_url']}" 
                : "uploads/404.png";
            ?>
            <img src="<?= $image ?>" alt="Vehicle Image" class="vehicle-image" onerror="this.src='uploads/404.png';">
            <div class="vehicle-info">
                <h1 class="vehicle-title"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h1>
                <div class="vehicle-details">
                    <div class="detail-item"><span class="detail-label">Plate No.:</span> <?= htmlspecialchars($vehicle['plate']) ?></div>
                    <div class="detail-item"><span class="detail-label">Year:</span> <?= htmlspecialchars($vehicle['year']) ?></div>
                    <div class="detail-item"><span class="detail-label">Type:</span> <?= htmlspecialchars($vehicle['type']) ?></div>
                    <div class="detail-item"><span class="detail-label">Fuel:</span> <?= htmlspecialchars($vehicle['fuel']) ?></div>
                    <div class="detail-item"><span class="detail-label">Cost:</span> ₱<?= number_format($vehicle['cost'], 2) ?></div>
                    <div class="detail-item"><span class="detail-label">Assignment:</span> <?= htmlspecialchars($vehicle['assignment']) ?></div>
                    <div class="detail-item"><span class="detail-label">PAR To:</span> <?= htmlspecialchars($vehicle['par_to']) ?></div>
                </div>
            </div>
        </div>

        <div class="year-selector">
            <label for="year">Assessment Year:</label>
            <select id="year" onchange="window.location.href='assessment.php?vehicle_id=<?= $vehicle_id ?>&year=' + this.value">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <h2>Annual Assessment Summary for <?= $selected_year ?></h2>
        
        <h3>Maintenance</h3>
        <div class="assessment-grid">
            <div class="assessment-card">
                <h3 class="card-title">Maintenance Cost</h3>
                <p class="card-value">₱<?= number_format($assessment_data['maintenance_cost'], 2) ?></p>
                <p class="card-unit">total cost</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Maintenance Count</h3>
                <p class="card-value"><?= $assessment_data['maintenance_count'] ?></p>
                <p class="card-unit">total services</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Corrective Maintenance</h3>
                <p class="card-value"><?= $assessment_data['corrective_count'] ?></p>
                <p class="card-unit">services (₱<?= number_format($assessment_data['corrective_cost'], 2) ?>)</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Preventive Maintenance</h3>
                <p class="card-value"><?= $assessment_data['preventive_count'] ?></p>
                <p class="card-unit">services (₱<?= number_format($assessment_data['preventive_cost'], 2) ?>)</p>
            </div>
            <!-- <div class="assessment-card">
                <h3 class="card-title">Accidents</h3>
                <p class="card-value"><?= $assessment_data['accident_count'] ?></p>
                <p class="card-unit">reported accidents</p>
            </div> -->
        </div>

        <div class="chart-container">
            <div class="chart-box">
                <h3 class="chart-title">Quarterly Maintenance Services</h3>
                <canvas id="maintenanceChart"></canvas>
            </div>
            <div class="chart-container">
                <div class="chart-box">
                    <h3 class="chart-title">Quarterly Maintenance Costs</h3>
                    <canvas id="maintenanceCostChart"></canvas>
                </div>
            </div>
        </div>
        
        <br>
        <br>

        <h3>Fuel & Mileage</h3>
        <div class="assessment-grid">
            <div class="assessment-card">
                <h3 class="card-title">Fuel Consumption</h3>
                <p class="card-value"><?= number_format($assessment_data['fuel_consumption']) ?></p>
                <p class="card-unit">liters</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Fuel Cost</h3>
                <p class="card-value">₱<?= number_format($assessment_data['fuel_cost'], 2) ?></p>
                <p class="card-unit">total cost</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Distance Travelled</h3>
                <p class="card-value"><?= number_format($assessment_data['distance_travelled']) ?></p>
                <p class="card-unit">kilometers</p>
            </div>
            <div class="assessment-card">
                <h3 class="card-title">Avg. Km/Liter</h3>
                <p class="card-value"><?= $avg_km_per_liter ?></p>
                <p class="card-unit">kilometers per liter</p>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-box">
                <h3 class="chart-title">Monthly Fuel Consumption</h3>
                <canvas id="fuelChart"></canvas>
            </div>
            
            <div class="chart-box">
                <h3 class="chart-title">Monthly Distance Travelled</h3>
                <canvas id="distanceChart"></canvas>
            </div>
            
            <div class="chart-box">
                <h3 class="chart-title">Monthly Fuel Efficiency</h3>
                <canvas id="efficiencyChart"></canvas>
            </div>
            
            <div class="chart-box">
                <h3 class="chart-title">Monthly Fuel Cost</h3>
                <canvas id="costChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Maintenance Chart (Quarterly)
        const maintenanceCtx = document.getElementById('maintenanceChart').getContext('2d');
        const maintenanceChart = new Chart(maintenanceCtx, {
            type: 'bar',
            data: {
                labels: <?= $quarterly_labels ?>,
                datasets: [
                    {
                        label: 'Corrective Maintenance',
                        data: <?= $chart_corrective ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Preventive Maintenance',
                        data: <?= $chart_preventive ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw;
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Services'
                        },
                        ticks: {
                            precision: 0 // Ensure whole numbers
                        }
                    }
                }
            }
        });

        <?php
            // Get quarterly maintenance costs
            $quarterly_cost_data = [];
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $quarterly_cost_data[$quarter] = [
                    'corrective' => 0,
                    'preventive' => 0,
                    'total' => 0
                ];
            }

            $query = "SELECT 
                        QUARTER(date) as quarter, 
                        type,
                        description,
                        cost
                    FROM maintenance 
                    WHERE vehicle_id = ? AND YEAR(date) = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $vehicle_id, $selected_year);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $quarter = $row['quarter'];
                $type = strtolower($row['type']);
                $cost = $row['cost'] ?? 0;
                
                if ($type === 'mixed') {
                    // For mixed records, we need to split the cost proportionally based on descriptions
                    $descriptions = explode(',', $row['description']);
                    $corrective_parts = 0;
                    $preventive_parts = 0;
                    
                    foreach ($descriptions as $desc) {
                        $parts = explode(':', $desc);
                        if (count($parts) === 2) {
                            $descType = trim($parts[1]);
                            if ($descType === 'corrective') $corrective_parts++;
                            if ($descType === 'preventive') $preventive_parts++;
                        }
                    }
                    
                    $total_parts = $corrective_parts + $preventive_parts;
                    if ($total_parts > 0) {
                        $quarterly_cost_data[$quarter]['corrective'] += ($cost * $corrective_parts / $total_parts);
                        $quarterly_cost_data[$quarter]['preventive'] += ($cost * $preventive_parts / $total_parts);
                    }
                } else {
                    // Handle direct corrective/preventive records
                    if ($type === 'corrective') {
                        $quarterly_cost_data[$quarter]['corrective'] += $cost;
                    } elseif ($type === 'preventive') {
                        $quarterly_cost_data[$quarter]['preventive'] += $cost;
                    }
                }
                
                $quarterly_cost_data[$quarter]['total'] += $cost;
            }

            // Prepare data for chart
            $chart_corrective_cost = json_encode(array_column($quarterly_cost_data, 'corrective'));
            $chart_preventive_cost = json_encode(array_column($quarterly_cost_data, 'preventive'));
            $chart_total_cost = json_encode(array_column($quarterly_cost_data, 'total'));
        ?>

        // Maintenance Cost Chart (Quarterly)
        const maintenanceCostCtx = document.getElementById('maintenanceCostChart').getContext('2d');
        const maintenanceCostChart = new Chart(maintenanceCostCtx, {
            type: 'bar',
            data: {
                labels: <?= $quarterly_labels ?>,
                datasets: [
                    {
                        label: 'Corrective Cost',
                        data: <?= $chart_corrective_cost ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Preventive Cost',
                        data: <?= $chart_preventive_cost ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Cost',
                        data: <?= $chart_total_cost ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        type: 'line',
                        fill: false,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '₱' + context.raw.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cost (₱)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Fuel Chart (Monthly)
        const fuelCtx = document.getElementById('fuelChart').getContext('2d');
        const fuelChart = new Chart(fuelCtx, {
            type: 'bar',
            data: {
                labels: <?= $monthly_labels ?>,
                datasets: [{
                    label: 'Fuel Consumption (liters)',
                    data: <?= $chart_fuel ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Liters'
                        }
                    }
                }
            }
        });

        // Distance Chart (Monthly)
        const distanceCtx = document.getElementById('distanceChart').getContext('2d');
        const distanceChart = new Chart(distanceCtx, {
            type: 'line',
            data: {
                labels: <?= $monthly_labels ?>,
                datasets: [{
                    label: 'Distance Travelled (km)',
                    data: <?= $chart_distance ?>,
                    borderColor: 'rgba(168, 85, 247, 1)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilometers'
                        }
                    }
                }
            }
        });

        // Efficiency Chart (Monthly)
        const efficiencyCtx = document.getElementById('efficiencyChart').getContext('2d');
        const efficiencyChart = new Chart(efficiencyCtx, {
            type: 'line',
            data: {
                labels: <?= $monthly_labels ?>,
                datasets: [{
                    label: 'Fuel Efficiency (km/liter)',
                    data: <?= $chart_efficiency ?>,
                    borderColor: 'rgba(234, 179, 8, 1)',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toFixed(2) + ' km/l';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilometers per Liter'
                        }
                    }
                }
            }
        });

        // Cost Chart (Monthly)
        const costCtx = document.getElementById('costChart').getContext('2d');
        const costChart = new Chart(costCtx, {
            type: 'bar',
            data: {
                labels: <?= $monthly_labels ?>,
                datasets: [{
                    label: 'Fuel Cost (₱)',
                    data: <?= $chart_cost ?>,
                    backgroundColor: 'rgba(22, 163, 74, 0.7)',
                    borderColor: 'rgba(22, 163, 74, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cost (₱)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>