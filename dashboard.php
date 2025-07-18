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

// Fetch vehicles
$vehicles = [];
$query = "SELECT vehicle_id, brand, model, image_url, cost FROM vehicles ORDER BY brand, model";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}

// Get current year
$current_year = date('Y');

// Prepare data for comparison charts
$vehicle_labels = [];
$fuel_efficiency_data = [];
$mileage_data = [];
$maintenance_cost_data = [];
$vehicle_cost_data = [];
$maintenance_percentage_data = [];

foreach ($vehicles as $vehicle) {
    $vehicle_id = $vehicle['vehicle_id'];
    $vehicle_labels[] = $vehicle['brand'] . ' ' . $vehicle['model'];
    $vehicle_cost = $vehicle['cost'];
    $vehicle_cost_data[] = $vehicle_cost;
    
    // Initialize with zeros
    $fuel_efficiency_data[$vehicle_id] = 0;
    $mileage_data[$vehicle_id] = 0;
    $maintenance_cost_data[$vehicle_id] = 0;
    
    // Get fuel efficiency (km/liter) for current year only
    $query = "SELECT 
                SUM(distance) as total_distance,
                SUM(liters) as total_liters
              FROM mileage 
              WHERE vehicle_id = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $vehicle_id, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $total_distance = $row['total_distance'] ?? 0;
        $total_liters = $row['total_liters'] ?? 1; // Avoid division by zero
        $mileage_data[$vehicle_id] = $total_distance;
        $fuel_efficiency_data[$vehicle_id] = round($total_distance / $total_liters, 2);
    }
    
    // Get maintenance cost for ALL YEARS
    $query = "SELECT SUM(cost) as total_cost
              FROM maintenance 
              WHERE vehicle_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $total_maintenance_cost = $row['total_cost'] ?? 0;
        $maintenance_cost_data[$vehicle_id] = $total_maintenance_cost;
        
        // Calculate maintenance cost as percentage of vehicle cost
        $maintenance_percentage = ($vehicle_cost > 0) ? ($total_maintenance_cost / $vehicle_cost) * 100 : 0;
        $maintenance_percentage_data[$vehicle_id] = round($maintenance_percentage, 2);
    }
}

// Prepare data for Chart.js
$vehicle_labels_js = json_encode($vehicle_labels);
$fuel_efficiency_js = json_encode(array_values($fuel_efficiency_data));
$mileage_js = json_encode(array_values($mileage_data));
$maintenance_percentage_js = json_encode(array_values($maintenance_percentage_data));

// Fetch vehicles for dropdown
$vehicles_for_dropdown = [];
$query = "SELECT vehicle_id, CONCAT(brand, ' ', model) as vehicle_name FROM vehicles ORDER BY brand, model";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles_for_dropdown[] = $row;
    }
}

// Fetch scheduled drives (all future events)
$scheduled_drives = [];
$query = "SELECT 
            ds.schedule_id, 
            ds.driver_id, 
            CONCAT(d.first_name, ' ', d.last_name) AS driver_name,
            ds.vehicle_id, 
            ds.start_datetime, 
            ds.end_datetime, 
            ds.purpose, 
            ds.status,
            v.brand, 
            v.model
          FROM driver_schedules ds
          LEFT JOIN drivers d ON ds.driver_id = d.driver_id
          LEFT JOIN vehicles v ON ds.vehicle_id = v.vehicle_id
          WHERE ds.end_datetime >= NOW()
          ORDER BY ds.start_datetime";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $scheduled_drives[] = $row;
    }
}

// Prepare data for calendar
$calendar_events = [];
foreach ($scheduled_drives as $drive) {
    $calendar_events[] = [
        'id' => $drive['schedule_id'],
        'title' => $drive['driver_name'] . ' - ' . $drive['brand'] . ' ' . $drive['model'],
        'start' => $drive['start_datetime'],
        'end' => $drive['end_datetime'],
        'driver_id' => $drive['driver_id'],
        'driver_name' => $drive['driver_name'],
        'vehicle_id' => $drive['vehicle_id'],
        'vehicle' => $drive['brand'] . ' ' . $drive['model'],
        'purpose' => $drive['purpose'],
        'status' => $drive['status'],
        'color' => $drive['status'] == 'completed' ? '#28a745' : 
                 ($drive['status'] == 'in-progress' ? '#ffc107' : 
                 ($drive['status'] == 'cancelled' ? '#dc3545' : '#007bff'))
    ];
}
$calendar_events_js = json_encode($calendar_events);
$vehicles_js = json_encode($vehicles_for_dropdown);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - NPC MVFM System</title>
    <link rel="icon" type="image/png" href="company-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- Bootstrap CSS for modal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            width: 100%;
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
            height: 240px;
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
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1e293b;
            text-align: center;
        }
        .chart-box {
            height: 250px;
            position: relative;
        }
        .current-year-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            font-style: italic;
        }
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            min-height: 600px;
        }

        #calendar {
            margin: 0 auto;
            max-width: 1100px;
            height: 550px;
        }

        .fc-event {
            cursor: pointer;
            font-size: 0.9em;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .fc-event-title {
            white-space: normal;
        }

        .fc-daygrid-event-dot {
            display: none;
        }

        .fc-toolbar-title {
            font-size: 1.25em;
        }

        .fc-button {
            background-color: #1e293b !important;
            border: none !important;
        }

        .fc-button:hover {
            background-color: #334155 !important;
        }

        .fc-button-active {
            background-color: #64748b !important;
        }
        
        /* Calendar Modal Styles */
        .modal-header {
            background-color: #1e293b;
            color: white;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .btn-save {
            background-color: #1e293b;
            color: white;
        }
        
        .btn-save:hover {
            background-color: #334155;
        }
        
        .btn-close {
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php">
            <img class="logo" src="logo simple.png" alt="Logo">
        </a>
        <ul class="nav-links">
            <li style="margin-top: 12px;"><a href="vehicles.php">Vehicles</a></li>
            <li class="dropdown" style="margin-top: 12px;">
                <a style="color: white;">Reports &#9662;</a>
                <ul class="dropdown-menu">
                    <li><a style="text-align: center;" href="selectvehicleReport.php?report=maintenance">Maintenance Reports</a></li>
                    <li><a style="text-align: center;" href="selectvehicleReport.php?report=mileage">Fuel & Mileage Reports</a></li>
                    <li><a style="text-align: center;" href="selectvehicleReport.php?report=history">Vehicle History Reports</a></li>
                </ul>
            </li>
            <li style="margin-top: 12px;"><a href="misc.php">Misc</a></li>
            <li style="margin-top: 12px; margin-bottom: -12px;">
                <a href="?logout=true">
                    <img src="icons/logout.png" alt="Log out" style="width:35px; height:35px;">
                </a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <h2 style="font-size: 2em; margin: 20px 20px;">Dashboard</h2>
        
        <div class="chart-container">
            <div class="chart-wrapper">
                <div class="chart-title">Fuel Efficiency Comparison</div>
                <div class="chart-box">
                    <canvas id="fuelEfficiencyChart"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper">
                <div class="chart-title">Mileage Comparison</div>
                <div class="chart-box">
                    <canvas id="mileageChart"></canvas>
                </div>
            </div>
            
            <div class="chart-wrapper" style="grid-column: span 2;">
                <div class="chart-title">Maintenance Cost as Percentage of Vehicle Cost</div>
                <div class="chart-box">
                    <canvas id="costPercentageChart"></canvas>
                </div>
            </div>
        </div>

        <div class="calendar-container">
            <h2>Driver Availability Calendar</h2>
            <div id="calendar"></div>
        </div>

        <!-- Event Modal -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel">Schedule Drive</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="eventForm">
                            <input type="hidden" id="schedule_id" name="schedule_id">
                            <div class="mb-3">
                                <label for="driver_name" class="form-label">Driver Name</label>
                                <input type="text" class="form-control" id="driver_name" name="driver_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="vehicle_id" class="form-label">Vehicle</label>
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <option value="">Select Vehicle</option>
                                    <?php foreach ($vehicles_for_dropdown as $vehicle): ?>
                                        <option value="<?= $vehicle['vehicle_id'] ?>"><?= $vehicle['vehicle_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="start_datetime" class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" required>
                            </div>
                            <div class="mb-3">
                                <label for="end_datetime" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" required>
                            </div>
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <input type="text" class="form-control" id="purpose" name="purpose" required>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;">Delete</button>
                        <button type="button" class="btn btn-save" id="saveBtn">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <h2>Vehicle Assessment</h2>
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

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <!-- Bootstrap JS for modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    

<script>
    // Define consistent colors for charts
    const vehicleColors = [
        'rgba(0, 51, 102, 0.8)',
        'rgba(0, 76, 153, 0.8)',
        'rgba(25, 25, 112, 0.8)',
        'rgba(0, 105, 148, 0.8)',
        'rgba(0, 53, 128, 0.8)',
        'rgba(0, 83, 135, 0.8)',
        'rgba(0, 49, 83, 0.8)',
        'rgba(0, 56, 147, 0.8)'
    ];
    const borderColors = vehicleColors.map(color => color.replace('0.8)', '1)'));

    // Initialize charts with persistent legend that hides both bar and label
    document.addEventListener('DOMContentLoaded', function() {
        const vehicleNames = <?= $vehicle_labels_js ?>;
        
        // Common function to update chart visibility
        function updateChartVisibility(chart, index) {
            const meta = chart.getDatasetMeta(0);
            meta.data[index].hidden = !meta.data[index].hidden;
            chart.update();
        }

        // Fuel Efficiency Chart
        const fuelEfficiencyCtx = document.getElementById('fuelEfficiencyChart').getContext('2d');
        const fuelEfficiencyChart = new Chart(fuelEfficiencyCtx, {
            type: 'bar',
            data: {
                labels: vehicleNames,
                datasets: [{
                    label: 'Fuel Efficiency (km/liter)',
                    data: <?= $fuel_efficiency_js ?>,
                    backgroundColor: vehicleColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const meta = chart.getDatasetMeta(0);
                                        return {
                                            text: label,
                                            fillStyle: vehicleColors[i % vehicleColors.length],
                                            hidden: meta.data[i] ? meta.data[i].hidden : false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            updateChartVisibility(legend.chart, legendItem.index);
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x.toFixed(2) + ' km/l';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilometers per Liter',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Vehicles',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333',
                            callback: function(value, index) {
                                const meta = this.chart.getDatasetMeta(0);
                                if (meta.data[index] && meta.data[index].hidden) {
                                    return null;
                                }
                                return value;
                            }
                        }
                    }
                },
                onClick: (e, elements, chart) => {
                    if (elements.length > 0) {
                        updateChartVisibility(chart, elements[0].index);
                    }
                }
            }
        });

        // Mileage Chart
        const mileageCtx = document.getElementById('mileageChart').getContext('2d');
        const mileageChart = new Chart(mileageCtx, {
            type: 'bar',
            data: {
                labels: vehicleNames,
                datasets: [{
                    label: 'Distance Travelled (km)',
                    data: <?= $mileage_js ?>,
                    backgroundColor: vehicleColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const meta = chart.getDatasetMeta(0);
                                        return {
                                            text: label,
                                            fillStyle: vehicleColors[i % vehicleColors.length],
                                            hidden: meta.data[i] ? meta.data[i].hidden : false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            updateChartVisibility(legend.chart, legendItem.index);
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x.toLocaleString() + ' km';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kilometers',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333',
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Vehicles',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333',
                            callback: function(value, index) {
                                const meta = this.chart.getDatasetMeta(0);
                                if (meta.data[index] && meta.data[index].hidden) {
                                    return null;
                                }
                                return value;
                            }
                        }
                    }
                },
                onClick: (e, elements, chart) => {
                    if (elements.length > 0) {
                        updateChartVisibility(chart, elements[0].index);
                    }
                }
            }
        });

        // Maintenance Cost Percentage Chart
        const costPercentageCtx = document.getElementById('costPercentageChart').getContext('2d');
        const costPercentageChart = new Chart(costPercentageCtx, {
            type: 'bar',
            data: {
                labels: vehicleNames,
                datasets: [{
                    label: 'Maintenance Cost as % of Vehicle Cost',
                    data: <?= $maintenance_percentage_js ?>,
                    backgroundColor: function(context) {
                        const index = context.dataIndex;
                        const value = context.raw;
                        if (value >= 75) return 'rgba(239, 68, 68, 0.7)';
                        if (value >= 50) return 'rgba(255, 159, 64, 0.7)';
                        return vehicleColors[index % vehicleColors.length];
                    },
                    borderColor: function(context) {
                        const index = context.dataIndex;
                        const value = context.raw;
                        if (value >= 75) return 'rgba(239, 68, 68, 1)';
                        if (value >= 50) return 'rgba(255, 159, 64, 1)';
                        return borderColors[index % borderColors.length];
                    },
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const meta = chart.getDatasetMeta(0);
                                        return {
                                            text: label,
                                            fillStyle: vehicleColors[i % vehicleColors.length],
                                            hidden: meta.data[i] ? meta.data[i].hidden : false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            updateChartVisibility(legend.chart, legendItem.index);
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x.toFixed(2) + '%';
                            },
                            afterLabel: function(context) {
                                const vehicleCost = <?= json_encode($vehicle_cost_data) ?>[context.dataIndex];
                                const maintenanceCost = <?= json_encode(array_values($maintenance_cost_data)) ?>[context.dataIndex];
                                return [
                                    `Vehicle Cost: ₱${vehicleCost.toLocaleString()}`,
                                    `Maintenance Cost: ₱${maintenanceCost.toLocaleString()}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333',
                            callback: function(value) {
                                return value + '%';
                            },
                            stepSize: 20
                        },
                        grid: {
                            color: function(context) {
                                if (context.tick.value === 50) {
                                    return 'rgba(255, 159, 64, 0.5)';
                                } else if (context.tick.value === 75) {
                                    return 'rgba(239, 68, 68, 0.5)';
                                }
                                return 'rgba(0, 0, 0, 0.05)';
                            },
                            lineWidth: function(context) {
                                if (context.tick.value === 50 || context.tick.value === 75) {
                                    return 2;
                                }
                                return 1;
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Vehicles',
                            color: '#333333',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            color: '#333333',
                            callback: function(value, index) {
                                const meta = this.chart.getDatasetMeta(0);
                                if (meta.data[index] && meta.data[index].hidden) {
                                    return null;
                                }
                                return value;
                            }
                        }
                    }
                },
                onClick: (e, elements, chart) => {
                    if (elements.length > 0) {
                        updateChartVisibility(chart, elements[0].index);
                    }
                }
            }
        });

        // Vehicle click handlers
        document.querySelectorAll(".vehicle").forEach(vehicle => {
            vehicle.addEventListener("click", () => {
                const vehicleId = vehicle.getAttribute("data-id");
                window.location.href = "assessment.php?vehicle_id=" + vehicleId;
            });
        });

        // CALENDAR INITIALIZATION
        const calendarEl = document.getElementById('calendar');
        const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true,
            selectable: true,
            events: {
                url: 'get_events.php',
                method: 'GET',
                failure: function() {
                    alert('Error loading events!');
                }
            },
            eventClick: function(info) {
                openModal(info.event);
            },
            dateClick: function(info) {
                openModal(null, info.dateStr);
            },
            select: function(info) {
                openModal(null, info.startStr, info.endStr);
            },
            eventDrop: function(info) {
                updateEvent(info.event);
            },
            eventResize: function(info) {
                updateEvent(info.event);
            },
            eventDidMount: function(info) {
                console.log("Event rendered:", info.event);
            }
        });

        calendar.render();

        // MODAL FUNCTIONS
        function openModal(event, dateStr = null, endStr = null) {
            const form = document.getElementById('eventForm');
            form.reset();
            
            if (event) {
                // Editing existing event
                document.getElementById('schedule_id').value = event.id;
                document.getElementById('driver_name').value = event.extendedProps.driver_name;
                document.getElementById('vehicle_id').value = event.extendedProps.vehicle_id;
                document.getElementById('purpose').value = event.extendedProps.purpose;
                document.getElementById('status').value = event.extendedProps.status;
                
                // Format dates for datetime-local input
                const start = new Date(event.start);
                start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                document.getElementById('start_datetime').value = start.toISOString().slice(0, 16);
                
                if (event.end) {
                    const end = new Date(event.end);
                    end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
                    document.getElementById('end_datetime').value = end.toISOString().slice(0, 16);
                }
                
                document.getElementById('deleteBtn').style.display = 'inline-block';
            } else {
                // Adding new event
                document.getElementById('schedule_id').value = '';
                document.getElementById('deleteBtn').style.display = 'none';
                
                // Set default dates if provided
                if (dateStr) {
                    const start = new Date(dateStr);
                    start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                    document.getElementById('start_datetime').value = start.toISOString().slice(0, 16);
                    
                    const end = new Date(endStr || dateStr);
                    end.setHours(end.getHours() + 1);
                    end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
                    document.getElementById('end_datetime').value = end.toISOString().slice(0, 16);
                }
            }
            
            eventModal.show();
        }

        // SAVE EVENT HANDLER
        document.getElementById('saveBtn').addEventListener('click', function() {
            const driverName = document.getElementById('driver_name').value.trim();
            const vehicleId = document.getElementById('vehicle_id').value;
            const startDatetime = document.getElementById('start_datetime').value;
            const endDatetime = document.getElementById('end_datetime').value;
            const purpose = document.getElementById('purpose').value.trim();
            
            if (!driverName || !vehicleId || !startDatetime || !endDatetime || !purpose) {
                alert('Please fill in all required fields');
                return;
            }
            
            const formData = new FormData(document.getElementById('eventForm'));
            const data = Object.fromEntries(formData.entries());
            
            // Convert datetime strings to MySQL format
            data.start_datetime = new Date(data.start_datetime).toISOString().slice(0, 19).replace('T', ' ');
            data.end_datetime = new Date(data.end_datetime).toISOString().slice(0, 19).replace('T', ' ');
            
            const isNew = !data.schedule_id;
            const vehicleName = $('#vehicle_id option:selected').text();
            
            $.ajax({
                url: 'save_schedule.php',
                method: isNew ? 'POST' : 'PUT',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (isNew) {
                            // Add new event to calendar
                            calendar.addEvent({
                                id: response.id,
                                title: `${data.driver_name} - ${vehicleName}`,
                                start: data.start_datetime,
                                end: data.end_datetime,
                                allDay: false,
                                extendedProps: {
                                    driver_name: data.driver_name,
                                    vehicle_id: data.vehicle_id,
                                    purpose: data.purpose,
                                    status: data.status || 'scheduled'
                                },
                                color: getStatusColor(data.status || 'scheduled')
                            });
                        } else {
                            // Update existing event
                            const event = calendar.getEventById(data.schedule_id);
                            if (event) {
                                event.setProp('title', `${data.driver_name} - ${vehicleName}`);
                                event.setDates(data.start_datetime, data.end_datetime);
                                event.setExtendedProp('purpose', data.purpose);
                                event.setExtendedProp('status', data.status || 'scheduled');
                                event.setProp('color', getStatusColor(data.status || 'scheduled'));
                            }
                        }
                        eventModal.hide();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error saving schedule: ' + (response.message || 'Unknown error'));
                    } catch (e) {
                        alert('Error saving schedule. Please check console for details.');
                    }
                    console.error('Error details:', xhr.responseText);
                }
            });
        });

        // DELETE EVENT HANDLER
        document.getElementById('deleteBtn').addEventListener('click', async function() {
            if (!confirm('Permanently delete this schedule?')) return;
            
            const scheduleId = document.getElementById('schedule_id').value;
            if (!scheduleId) {
                alert('Error: Missing schedule ID');
                return;
            }

            try {
                const response = await fetch(`save_schedule.php?schedule_id=${scheduleId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                // First get the response as text to see what we're dealing with
                const responseText = await response.text();
                
                try {
                    // Then try to parse it as JSON
                    const result = JSON.parse(responseText);
                    
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Deletion failed');
                    }
                    
                    // Success case
                    calendar.getEventById(scheduleId)?.remove();
                    eventModal.hide();
                    alert(result.message || 'Deleted successfully');
                    
                } catch (e) {
                    console.error('Invalid JSON received:', responseText);
                    throw new Error('Server returned invalid data: ' + responseText.substring(0, 50));
                }
                
            } catch (error) {
                console.error('Delete error:', error);
                alert('Deletion failed: ' + error.message);
            }
        });

        // UPDATE EVENT ON DRAG/RESIZE
        function updateEvent(event) {
            const data = {
                schedule_id: event.id,
                start_datetime: event.start.toISOString().slice(0, 19).replace('T', ' '),
                end_datetime: event.end ? event.end.toISOString().slice(0, 19).replace('T', ' ') : null
            };
            
            $.ajax({
                url: 'save_schedule.php',
                method: 'PUT',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        calendar.refetchEvents();
                    }
                },
                error: function() {
                    calendar.refetchEvents();
                }
            });
        }

        // HELPER FUNCTION: Get color based on status
        function getStatusColor(status) {
            switch(status) {
                case 'completed': return '#28a745';
                case 'in-progress': return '#ffc107';
                case 'cancelled': return '#dc3545';
                default: return '#007bff';
            }
        }
    });
</script>

</body>
</html>
