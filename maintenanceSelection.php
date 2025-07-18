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
if (!$vehicleId) {
    echo "<script>alert('No vehicle selected.'); window.location.href='selectvehicleReport.php?report=maintenance';</script>";
    exit();
}

$vehicleQuery = "SELECT * FROM vehicles WHERE vehicle_id = ?";
$vehicleStmt = $conn->prepare($vehicleQuery);
$vehicleStmt->bind_param("i", $vehicleId);
$vehicleStmt->execute();
$vehicleResult = $vehicleStmt->get_result();
$vehicle = $vehicleResult->fetch_assoc();

if (!$vehicle) {
    echo "<script>alert('Vehicle not found.'); window.location.href='selectvehicleReport.php?report=mileage';</script>";
    exit();
}

// Get the vehicle's acquisition year as the minimum selectable year
$acquisitionYear = date("Y", strtotime($vehicle['acquisition']));
$currentYear = date("Y");
$currentQuarter = ceil(date("n") / 3); // Current quarter (1-4)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Maintenance - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
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
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }
        h3 {
            font-size: 20px;
            font-weight: 400;
            text-decoration: none;
        }
        .year-container {
            margin: 20px 0;
        }
        label {
            font-size: 20px;
        }
        select {
            padding: 12px 15px;
            font-size: 18px;
            border: 2px solid #1e293b;
            border-radius: 8px;
            cursor: pointer;
        }
        .quarters {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 600px;
            max-height: 600px;
            margin: auto;
        }
        .quarter {
            padding: 20px;
            background: #1e293b;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }
        .quarter:hover {
            background: #334155;
            transform: scale(1.05);
        }
        .back-container {
            margin-top: 20px;
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
        .annual-container {
            margin: 0px auto;
            border-radius: 12px;
            max-width: 500px;
            text-align: center;
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
            <a class="button" href="selectvehicleReport.php?report=maintenance">Back</a>
        </div>
        <h1>Maintenance Report</h1>
        <p style="font-size: 13pt;"><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?> | 
            <strong>Plate No:</strong> <?= htmlspecialchars($vehicle['plate']) ?> | <strong>Fuel:</strong> <?= htmlspecialchars($vehicle['fuel']) ?>
        </p>
            <?php
            $image = (!empty($vehicle['image_url']) && file_exists("uploads/{$vehicle['image_url']}")) 
                ? "uploads/{$vehicle['image_url']}" 
                : "uploads/404.png";
            ?>
            <img src="<?= $image ?>" alt="Vehicle Image" style="width: 200px; height: auto; border-radius: 8px;" onerror="this.src='uploads/404.png';">
    </div>
    
    <hr style="margin: 30px 0; border: none; border-top: 2px solid #ccc;">

    <div class="annual-container">
        <h2><strong>Annual Report</strong></h2>
        <label for="annualYear" style="margin-right: 15px;">Select Year: </label>
        <select id="annualYear" onchange="goToAnnualReport()">
            <option value="" selected disabled>Select Year</option>
            <?php for ($y = $acquisitionYear; $y <= $currentYear; $y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    
    <hr style="margin: 30px 0; border: none; border-top: 2px solid #ccc;">

    <script>
        function goToAnnualReport() {
            const year = document.getElementById("annualYear").value;
            if (year) {
                window.location.href = `maintenanceAnnual.php?vehicle_id=${vehicleId}&year=${year}`;
            }
        }
    </script>
    <div class="container">
        <h2><strong>Quarterly Report</strong></h2>
        <div class="year-container">
            <label for="year" style="margin-right: 15px;">Select Year:</label>
            <select id="year" onchange="updateQuarters()"></select>
        </div>

        <div class="quarter-container">
            <h3>Select Quarter</h3>
            <div class="quarters" id="quarters"></div>
        </div>
    </div>

    <script>
        const vehicleId = <?= json_encode($vehicleId) ?>;
        const now = new Date();
        const currentYear = <?= $currentYear ?>;
        const currentQuarter = <?= $currentQuarter ?>;
        const startYear = <?= $acquisitionYear ?>;
        
        const quarters = [
            "Quarter 1 (Jan-Mar)",
            "Quarter 2 (Apr-Jun)",
            "Quarter 3 (Jul-Sep)",
            "Quarter 4 (Oct-Dec)"
        ];

        // Populate year dropdown dynamically
        const yearSelect = document.getElementById("year");
        for (let y = startYear; y <= currentYear; y++) {
            const opt = document.createElement("option");
            opt.value = y;
            opt.textContent = y;
            yearSelect.appendChild(opt);
        }
        yearSelect.value = currentYear; // Default to current year

        function updateQuarters() {
            const selectedYear = parseInt(yearSelect.value);
            const quartersDiv = document.getElementById("quarters");
            quartersDiv.innerHTML = "";

            quarters.forEach((quarter, index) => {
                const quarterNumber = index + 1;
                const quarterDiv = document.createElement("div");
                quarterDiv.className = "quarter";
                quarterDiv.innerText = quarter;

                // Check if this is a future quarter
                const isFuture = selectedYear === currentYear && quarterNumber > currentQuarter;

                if (isFuture) {
                    quarterDiv.style.backgroundColor = "#ccc";
                    quarterDiv.style.cursor = "not-allowed";
                    quarterDiv.title = "Future quarter not available";
                } else {
                    quarterDiv.onclick = () => {
                        window.location.href = `maintenanceQuarter.php?vehicle_id=${vehicleId}&year=${selectedYear}&quarter=${quarterNumber}`;
                    };
                }

                quartersDiv.appendChild(quarterDiv);
            });
        }

        // Initialize quarters
        updateQuarters();
    </script>

    <!-- <br><br><br>
    <a href="history.php?vehicle_id=<?= $vehicleId ?>" class="button" style="font-weight: bold;">View Vehicle Maintenance History</a> -->
</body>
</html>