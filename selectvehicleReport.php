<?php
session_start();
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

require_once "db_connect.php";

$reportType = $_GET['report'] ?? '';
if (!$reportType) {
    echo "<script>alert('No report type selected.'); history.back();</script>";
    exit();
}

$vehicles = [];
$query = "SELECT vehicle_id, brand, model, image_url FROM vehicles ORDER BY brand, model";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Select Vehicle - NPC MVFM System</title>
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
        .main-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            padding: 20px;
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
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .vehicle-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px;
            justify-items: center;
        }
        .vehicle {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            width: 80%;
            height: 100%;
            max-width: 500px;
            max-height: 400px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .vehicle:hover {
            transform: scale(1.03);
        }
        .vehicle img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #ccc;
        }
        .vehicle-name {
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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

    <div class="main-container">
        <div style="text-align: left;">
            <a class="button" href="dashboard.php">Back</a>
        </div>
    
        <?php
            $reportTitles = [
                'maintenance' => 'Maintenance Reports',
                'mileage' => 'Fuel & Mileage Reports',
                'history' => 'Vehicle History Reports'
            ];
            $title = $reportTitles[$reportType] ?? 'Vehicle Reports';
        ?>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p>Select a vehicle</p>
        
        <div id="vehicleList" class="vehicle-list">
            <?php foreach ($vehicles as $vehicle): ?>
                <?php
                    $image = (!empty($vehicle['image_url']) && file_exists("uploads/{$vehicle['image_url']}")) 
                        ? "uploads/{$vehicle['image_url']}" 
                        : "uploads/404.png";
                ?>
                <div class="vehicle" data-id="<?= $vehicle['vehicle_id'] ?>">
                    <img src="<?= $image ?>" alt="Vehicle Image" onerror="this.src='uploads/404.png';" />
                    <div class="vehicle-name"><?= strtoupper($vehicle['brand'] . ' ' . $vehicle['model']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const reportType = <?= json_encode($reportType) ?>;

        document.querySelectorAll(".vehicle").forEach(vehicle => {
            vehicle.addEventListener("click", () => {
            const vehicleId = vehicle.getAttribute("data-id");
            localStorage.setItem("selectedVehicle", vehicleId);

            switch (reportType) {
                case "maintenance":
                window.location.href = "maintenanceSelection.php?vehicle_id=" + vehicleId;
                break;
                case "mileage":
                window.location.href = "mileageSelection.php?vehicle_id=" + vehicleId;
                break;
                case "history":
                window.location.href = "history.php?vehicle_id=" + vehicleId;
                break;
                default:
                alert("Invalid report type.");
            }
            });
        });
    </script>
</body>
</html>
